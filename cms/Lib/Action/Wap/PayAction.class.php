<?php

class PayAction extends BaseAction{
    protected function _initialize() {
        parent::_initialize();
        if(defined('IS_INDEP_HOUSE')){
            $this->indep_house = C('INDEP_HOUSE_URL');
        }else{
            $this->indep_house = 'wap.php';
        }
        //二次验证时间
        if($this->user_session['uid']){
            if($times = D('Verify_limit')->field('end_time')->where(array('uid'=>$this->user_session['uid'],'times'=>array('lt',2)))->find()){
                $_SESSION['user']['verify_end_time']=$times['end_time'];
            }
        }
    }

    /**
     * 星空云支付
     */
    public function xky()
    {
        $notifyUrl = C('config.site_url') . '/source/xky_notice.php';
        $this->assign('notify_url', $notifyUrl);
        $this->display();
    }

    /**
     * 星空云支付异步通知
     */
    public function xkyNotify()
    {
        header("Content-type: application/json");
        fdump(empty($_POST) ? json_decode(file_get_contents("php://input"), true) : $_POST, 'xkyNotify', true);
        echo json_encode(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
        exit;
    }

    public function check(){
		
		fdump_api(['payCheck' => [$_GET, $this->is_wxapp_browser, $this->config]], 'pay/payCheck',1);
		//营销系统活动、景区订单、导游订单对接不跳转新版支付
		//快速买单在小程序里不跳转新版
        if (!in_array($_GET['type'], array('wxapp', 'ticket', 'guide')) && !($this->is_wxapp_browser && $_GET['type'] == 'store')) {
			redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $_GET['order_id'] . '&type=' . $_GET['type'] . '&order_from=' . $_GET['order_from']);
        }
        if(empty($this->user_session)){
            $this->error_tips(L_('请先进行登录！'),U('Login/index'));
        }

        $now_user = D('User')->get_user($this->user_session['uid']);

        // 餐饮用户点餐不能自主结账
        if(C('config.foodshop_cash_only_clerk')==1 && $_GET['type'] == 'plat'){
            $now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
            if($now_order['order_info']['business_type']=='foodshop'&&$now_order['order_info']['status']!=0){
                $this->error_tips(L_('尊敬的用户，您不能自主结账，请到收银台进行结账！'));
            }
        }

        if (empty($now_user['phone'])) {
            $need_bind_phone = true;

            //跑腿小程序独立支付，不校验手机号
            if($this->user_session['from_device_id'] == 'wxapp_paotui'){
                $need_bind_phone = false;
            }

            // 扫码点餐不需要绑定手机号
            if($_GET['type'] == 'plat'){
                $now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
                if($now_order['order_info']['business_type']=='foodshop' && $now_order['order_info']['order_info']['order_from'] =='1'){
                    $need_bind_phone = false;
                }
            }

            if($this->config['open_extra_price']){
                $need_bind_phone = false;
            }

            if(($_GET['type'] == 'store' || $_GET['type'] == 'shop') && !$this->config['cash_bind_phone']){
                $need_bind_phone = false;
            }

            if($need_bind_phone){
                $this->error_tips('尊敬的用户，为了提供更好的服务，请您在消费之前先绑定手机号码！',U('My/bind_user',array('referer'=>urlencode(U('Pay/check',$_GET)))));
            }

        }

        if(!in_array($_GET['type'],array('group','meal','weidian','takeout', 'food', 'foodPad','recharge','appoint','wxapp', 'store', 'shop', 'mall', 'plat','balance-appoint', 'integral_mall','yuedan','yuedan_service_release','ticket','guide'))){
            $this->error_tips('订单来源无法识别，请重试。');
        }

        $group_pay_offline = true;
        if($_GET['type'] == 'group'){
            $now_order = D('Group_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));

            if($now_order['order_info']['group_share_num']>0||$now_order['order_info']['pin_num']>0)$group_pay_offline=false;
        }else if($_GET['type'] == 'meal' || $_GET['type'] == 'takeout' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad'){
            $now_order = D('Meal_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']), false, $_GET['type']);
            if ($now_order['order_info']['paid'] == 2) $this->assign('notCard',true);
            $_GET['type']  = 'meal';
        }else if($_GET['type'] == 'weidian'){
            $now_order = D('Weidian_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
            $this->assign('notCard',true);
        }else if($_GET['type'] == 'recharge'){
            $now_order = D('User_recharge_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
            $this->assign('notCard',true);
        }else if($_GET['type'] == 'appoint'){
            $now_order = D('Appoint_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        }else if($_GET['type'] == 'yuedan'){//郭先森 //约单处理
            $now_order = D('Yuedan_service_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        }else if($_GET['type'] == 'yuedan_service_release'){//郭先森 //约单发布处理
            $now_order = D('Yuedan_service_release')->get_pay_order($this->user_session['uid'],intval($_GET['rid']));
        }else if($_GET['type'] == 'wxapp'){
            $_GET['notOffline'] = true;
            $now_order = D('Wxapp_order')->get_pay_order($_GET['uid'],intval($_GET['order_id']));
            $this->assign('notCard',true);
        }else if($_GET['type'] == 'store'){
            $_GET['notOffline'] = true;
            $now_order = D('Store_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
            //$this->assign('notCard',true);
        }else if($_GET['type'] == 'shop' || $_GET['type'] == 'mall'){
            $now_order = D('Shop_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
            if($this->config['open_extra_price']==0  && empty($now_user) && $now_order['order_info'] && $now_order['order_info']['order_from'] != 6){
                $this->error_tips('尊敬的用户，为了提供更好的服务，请您在消费之前先绑定手机号码！',U('My/bind_user',array('referer'=>urlencode(U('Pay/check',$_GET)))));
            }
        }else if($_GET['type'] == 'plat'){
            $now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
            $now_order['order_info']['extra_price'] =  $now_order['order_info']['order_info']['extra_price'];
            $now_village = M('House_village')->where(array('village_id'=>$now_order['order_info']['order_info']['village_id']))->find();
            $this->assign('now_village',$now_village);
            if($now_order['order_info']['status']==1 && $now_order['order_info']['business_type']!='foodshop'){
                $this->error_tips('订单已支付');
            }
        }else if($_GET['type'] == 'balance-appoint'){
            $now_order = D('Appoint_order')->get_pay_balace_order($this->user_session['uid'],intval($_GET['order_id']));


        }else if($_GET['type'] == 'integral_mall'){
            $now_order = D('Merchant_integral_mall_goods_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        }else if($_GET['type'] == 'ticket'){
            $now_order = D('Scenic_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        }else if($_GET['type'] == 'guide'){
            $now_order = D('Scenic_aguide')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        }else if ($_GET['type'] == 'mcard') {
            $now_order = D('Membership_card_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
        }else{
            $this->error_tips('非法的订单');
        }
        if($now_order['error'] == 1){
            if($now_order['url']){
                $this->success_tips($now_order['msg'],$now_order['url']);
            }else{
                $this->error_tips($now_order['msg']);
            }
        }

        $order_info = $now_order['order_info'];

        if(!isset($order_info['discount_status'])){
            $order_info['discount_status']=  true;
        }
        //来自商城的活动（拼团、砍价）不允许使用平台/商家优惠券抵扣
        if(($order_info['order_type']=='shop' ||$order_info['order_type']=='mall') && $order_info['order_from']==1 && ($order_info['group_id']>0 || $order_info['bar_id']>0)){
            $order_info['discount_status']=false;
        }
        // 商家积分兑换 不允许使用平台/商家优惠券抵扣
        if($order_info['order_type']=='integral_mall'){
            $order_info['discount_status']=false;
        }



        if($this->is_weixin_browser && !empty($_SESSION['openid']) && ($merchant_bind = D('Weixin_bind')->field(true)->where(array('mer_id' => $order_info['mer_id'], 'type' => 0))->find()) && $merchant_bind['service_type_info'] == '2' && $merchant_bind['verify_type_info'] != '-1'){
            $this->open_authorize_openid(array('appid' => $merchant_bind['authorizer_appid'], 'mer_id' => $merchant_bind['mer_id']));
        }

        if($this->config['open_extra_price']==1&&($order_info['order_type']!='appoint'||$order_info['discount_status'])){
            $user_score_use_percent=(float)$this->config['user_score_use_percent'];
            $order_info['order_extra_price'] = bcdiv($order_info['extra_price'],$user_score_use_percent,2);
        }else{
            $order_info['order_extra_price'] = 0;
            $order_info['extra_price'] = 0;
        }
        $this->assign('order_info',$order_info);

        if($this->is_app_browser){
            $this->display();die;
        }

        if($order_info['mer_id']){
            $now_merchant = D('Merchant')->get_info($order_info['mer_id']);
            if(empty($notOffline)){
                if ($now_merchant) {
                    $notOffline =($pay_offline && $now_merchant['is_offline'] == 1) ? 0 : 1;
                }
            }

            $use_discount = true;
            if($order_info['discount_status']===false){
                $use_discount = false;
            }

            //定制只能使用桌台或会员卡折扣
            if(1 == $this->config['is_open_merchant_foodshop_discount']) {
                if (($order_info['order_info']['mer_table_discount']>0&&$order_info['order_info']['mer_table_discount']<100) && $order_info['order_info']['table_or_vipcard_discount'] == 0) {
                    $use_discount = false;
                }
            }

            if($this->config['open_score_discount'] && $use_discount){
                switch($now_merchant['user_discount_type']){
                    case 1 :   //优惠
                        break;
                    case 2 :   //积分
                        $use_discount =false;
                        break;
                    case 3 :   //2选1
                        $use_discount =  $_GET['discount_type'] == 'score'?false:true;
                        break;
                }
            }
            $this->assign('now_merchant',$now_merchant);
        }


        //判断线下支付
        if($order_info['order_type'] == 'plat' || $order_info['order_type'] == 'integral_mall'){
            $this->assign('pay_offline',$order_info['pay_offline']);
        } else {
            $pay_offline = D('Percent_rate')->pay_offline($order_info['mer_id'],$_GET['type']);
            $this->assign('pay_offline',$pay_offline&&$group_pay_offline);
        }

        //判断商户是否开启了线上支付(包括控制能否使用积分和平台余额)
        $pay_online = 1;
        if($_GET['type'] =='group' || $_GET['type'] =='shop'|| $_GET['type'] =='mall'){
            $pay_online = D('Percent_rate')->pay_online($order_info['mer_id']);
        }
        
        //判断对接
        if(C('butt_open')){
            import('ORG.Net.Http');
            $http = new Http();
            $postArr = array(
                'butt_id' => $order_info['mer_id'],
                'order_id' => $order_info['order_id'],
                'order_type' => $order_info['order_type'],
                'order_name' => $order_info['order_name'],
                'order_num' => $order_info['order_num'],
                'order_price' => $order_info['order_price']*100,
                'order_total_money' => $order_info['order_total_money'],
                'redirct_url' => $this->config['site_url'].'/'.$this->indep_house.'?c=Pay&a=butt_pay',
            );
            $return = Http::curlPost(C('butt_pay_post_url'),get_butt_encrypt_key($postArr,C('butt_key')));
            if($return['err_code']){
                $this->error($return['err_msg']);
            }else if($return['result']){
                redirect($return['result']);
            }else{
                $this->error('调用支付时发生错误，请重试');
            }
        }

        //得到微信优惠金额,判断用户能否购买此团购
        $cheap_info = array('can_buy'=>true,'can_cheap'=>false,'wx_cheap'=>0);
        if($_GET['type'] == 'group' && $this->is_weixin_browser){
            $now_user = D('User')->get_user($this->user_session['uid']);
            if($this->config['weixin_buy_follow_wechat'] == 2 && !empty($_SESSION['openid']) && empty($now_user['is_follow'])){
                $cheap_info['can_buy'] = false;
            }
            $cheap_info['wx_cheap'] = D('Group')->get_group_cheap($order_info['group_id']);
            $cheap_info['wx_cheap'] = $cheap_info['wx_cheap']*$order_info['order_num'];
            if($cheap_info['wx_cheap']){
                $cheap_info['can_cheap'] = true;
                if($_SESSION['openid']){
                    if($this->config['weixin_buy_follow_wechat'] == 1 && empty($now_user['is_follow'])){
                        $cheap_info['can_cheap'] = false;
                    }
                }else{
                    $cheap_info['can_cheap'] = false;
                }
                $cheap_info['wx_cheap'] = round($cheap_info['wx_cheap'] * 100) /100;
                $_SESSION['wx_cheap'] = $cheap_info['wx_cheap'];
            }
        }
        $this->assign('cheap_info',$cheap_info);

        //用户信息
        $now_user = D('User')->get_user($this->user_session['uid']);

        if(empty($now_user)){
            $this->error_tips('未获取到您的帐号信息，请重试！');
        }
        $now_user['now_money'] = floatval($now_user['now_money']);
        //子商户设置不允许使用平台余额支付
        if(($this->config['cash_nobind_phone_use_balance']==0 && empty($now_user['phone'])) || $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id']>0 && $now_merchant['sub_mch_sys_pay']!=1){
            $now_user['now_money'] = 0;
        }
        //商户未开启线上支付，不能使用平台余额
        if(!$pay_online){
            $now_user['now_money'] = 0;
        }
        $this->assign('now_user',$now_user);

        //使用企业预存款支付
        if ($this->config['open_company_advance_deposit'] == 1 && $_GET['type'] != 'plat' &&$order_info['business_type'] != 'merchant_user_recharge') {
            $qiyeMerchant = M('Qiye_merchant')->where(['mer_id' => intval($now_order['order_info']['mer_id'])])->find();
            $qiyeStaff = M('Qiye_staff')->where(['job_number' => intval($this->user_session['uid'])])->find();
            if ($qiyeMerchant && $qiyeStaff && $qiyeMerchant['qiye_id'] == $qiyeStaff['qiye_id'] && $qiyeStaff['money'] > 0) {
                $qiye = M('Qiye')->where(['qiye_id' => $qiyeMerchant['qiye_id'], 'status' => 1])->find();
                if ($qiye) {
                    $this->assign('qiyeStaff', $qiyeStaff);
                }
            }
        }


        if(($this->config['cash_nobind_phone_use_balance'] || !empty($now_user['phone'])) && $_GET['type'] != 'recharge' && $order_info['business_type'] != 'merchant_user_recharge' && $_GET['type'] != 'weidian' && ($order_info['business_type']==''||$order_info['business_type']!='card_new_recharge') ) {

            //商家优惠券
            if ($this->is_wxapp_browser) { 
                $platform = 'wxapp';
            } else if ($this->is_app_browser) { 
                $platform = 'app';
            } else if ($this->is_weixin_browser) {
                $platform = 'weixin';
            } else {
                $platform = 'wap';
            }
            $order_info['total_money'] = $order_info['order_total_money'];
            $tmp_order = $order_info;

            if($order_info['order_type']=='store'){
                $tmp_order['total_money']-=$tmp_order['no_discount_money'];
            }else  if($order_info['business_type']=='foodshop'){
                //$tmp_order['total_money']=$order_info['order_info']['can_discount_money'];
                //$tmp_order['total_money']=$order_info['order_info']['can_discount_table_money'];
            }

//            if ($cheap_info['can_cheap']) {
//                $tmp_order['total_money']-=$cheap_info['wx_cheap'];
//            }

            // 获取联盟卡
            if ($now_order['order_info']['can_use_mer_union_card']) {
                // 联盟会员卡列表
                $card_list = D('Card_new')->get_union_card_by_uid_and_mer_id($this->user_session['uid'], $order_info['mer_id']);
                $card_url_param = $_GET;
                unset($card_url_param['union_mer_id'],$card_url_param['card_id'],$card_url_param['coupon_id']);
                $this->assign('card_url_param',$card_url_param);
                $this->assign('card_list',$card_list);

                // 使用联盟会员卡
                if ($_GET['union_mer_id']&&$_GET['union_mer_id']!=$order_info['mer_id']) {
                    // 获取联盟关系
                    $union_mer = D('Card_new')->get_union_merchant($_GET['union_mer_id'], $order_info['mer_id']);
                    if ($union_mer) {
                        $union_card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $_GET['union_mer_id']);
                    }
                }
            }
            $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $order_info['mer_id']);

            // 使用联盟会员卡(会员卡余额和折扣使用选择的会员卡，优惠券使用本商家的优惠券)
            if ($union_card_info) {
                $card_info['discount'] = $union_card_info['union_card_info'];
                $card_info['card_money'] = $union_card_info['card_money'];
                $card_info['card_money_give'] = $union_card_info['card_money_give'];
            }

            $card_info['discount'] == 0 && $card_info['discount']=10;
           // if($card_info['status']) {
                $merchant_balance = $card_info['card_money'] + $card_info['card_money_give'];
                if (!$use_discount || isset($order_info['discount_status']) && !$order_info['discount_status'] || empty($card_info)||empty($card_info['discount'])) {
                    $card_info['discount'] = 10;
                }

                $_SESSION['discount'] = $card_info['discount'];
                $tmp_order['uid'] = $this->user_session['uid'];

                if($tmp_order['freight_charge']>0){
                    $tmp_order['total_money'] = ($tmp_order['total_money']-$tmp_order['freight_charge']) * $card_info['discount'] / 10 - $cheap_info['wx_cheap'] +$tmp_order['freight_charge'];
                }else{
                    $tmp_order['total_money'] = ($tmp_order['total_money']) * $card_info['discount'] / 10 - $cheap_info['wx_cheap'];
                }

            // 获取联盟卡
            if ($now_order['order_info']['can_use_mer_union_card']) {
                // 联盟会员卡列表
                $card_list = D('Card_new')->get_union_card_by_uid_and_mer_id($this->user_session['uid'], $order_info['mer_id']);
                $card_url_param = $_GET;
                unset($card_url_param['union_mer_id'],$card_url_param['card_id'],$card_url_param['coupon_id']);
                $this->assign('card_url_param',$card_url_param);
                $this->assign('card_list',$card_list);

                // 使用联盟会员卡
                if ($_GET['union_mer_id']&&$_GET['union_mer_id']!=$order_info['mer_id']) {
                    // 获取联盟关系
                    $union_mer = D('Card_new')->get_union_merchant($_GET['union_mer_id'], $order_info['mer_id']);
                    if ($union_mer) {
                        $union_card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $_GET['union_mer_id']);
                    }
                }
            }
            $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $order_info['mer_id']);

            // 使用联盟会员卡(会员卡余额和折扣使用选择的会员卡，优惠券使用本商家的优惠券)
            if ($union_card_info) {
                $card_info['discount'] = $union_card_info['union_card_info'];
                $card_info['card_money'] = $union_card_info['card_money'];
                $card_info['card_money_give'] = $union_card_info['card_money_give'];
            }

            $card_info['discount'] == 0 && $card_info['discount']=10;
            // if($card_info['status']) {
            $merchant_balance = $card_info['card_money'] + $card_info['card_money_give'];
            if (!$use_discount || isset($order_info['discount_status']) && !$order_info['discount_status'] || empty($card_info)||empty($card_info['discount'])) {
                $card_info['discount'] = 10;
            }

            $_SESSION['discount'] = $card_info['discount'];
            $tmp_order['uid'] = $this->user_session['uid'];

            if($tmp_order['freight_charge']>0){
                $tmp_order['total_money'] = ($tmp_order['total_money']-$tmp_order['freight_charge']) * $card_info['discount'] / 10 - $cheap_info['wx_cheap'] +$tmp_order['freight_charge'];
            }else{
                $tmp_order['total_money'] = ($tmp_order['total_money']) * $card_info['discount'] / 10 - $cheap_info['wx_cheap'];
            }


            //快店部分商品不参加商家优惠券
            if( $order_info['order_type']=='shop' && $tmp_order['can_discount_money']>0){
                $tmp_order['can_discount_money'] = $tmp_order['can_discount_money'] * $card_info['discount'] / 10 ;
            }

            if ((!isset($order_info['discount_status']) || $order_info['discount_status'])&&$_GET['unmer_coupon']!=1 && $use_discount) {
                if($order_info['order_type']=='shop' && $_GET['type']!='mall'){
                    if($tmp_order['can_discount_money']>0){
                        if (!empty($_GET['card_id'])) {
                            $now_coupon = D('Member_card_coupon')->get_coupon_by_recordid($_GET['card_id'], $this->user_session['uid']);
                            $now_coupon['type'] = 'mer';
                            $this->assign('now_coupon', $now_coupon);
                        } else {
                            if (empty($_GET['merc_id'])) {
                                if (!empty($order_info['business_type'])) {
                                    $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $_GET['type'], $platform, $order_info['business_type']);
                                } else {
                                    $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $_GET['type'], $platform);
                                }

                                $mer_coupon = reset($card_coupon_list);

                            } else {
                                $mer_coupon = D('Card_new_coupon')->get_coupon_info($_GET['merc_id'],$tmp_order);
                            }

                        }
                    }
                }else{

                    if (!empty($_GET['card_id'])) {
                        $now_coupon = D('Member_card_coupon')->get_coupon_by_recordid($_GET['card_id'], $this->user_session['uid']);
                        $now_coupon['type'] = 'mer';
                        $this->assign('now_coupon', $now_coupon);
                    } else {

                        if (empty($_GET['merc_id'])) {
                            if (!empty($order_info['business_type'])) {
                                $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $_GET['type'], $platform, $order_info['business_type']);
                            } else {

                                $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $_GET['type'], $platform);
                            }


                            $mer_coupon = reset($card_coupon_list);

                        } else {
                            $mer_coupon = D('Card_new_coupon')->get_coupon_info($_GET['merc_id']);
                        }

                    }
                }

            }
            // }else{

            //   }

            if($_GET['type'] == 'yuedan'){
                $use_discount = true;
            }
            //平台优惠券

            if ($use_discount && ($tmp_order['total_money'] > $mer_coupon['discount'] || empty($mer_coupon))&&$_GET['unsys_coupon']!=1&&($order_info['discount_status']||!isset($order_info['discount_status']))) {
                $tmp_order['total_money'] -= empty($mer_coupon['discount']) ? 0 : $mer_coupon['discount'];

                if (empty($_GET['sysc_id'])) {
                    if (!empty($order_info['business_type'])) {
                        $now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $_GET['type'], $this->user_session['phone'], $this->user_session['uid'], $platform, $order_info['business_type']);
                    } else {
                        $now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $_GET['type'], $this->user_session['phone'], $this->user_session['uid'], $platform);
                    }
                    $system_coupon = reset($now_coupon);
//var_dump($system_coupon);
                } else {
                    $system_coupon = D('System_coupon')->get_coupon_info($_GET['sysc_id']);
                    if($system_coupon['is_discount']==1){
                        $system_coupon['discount_money'] =round( $tmp_order['total_money'] * (100-$system_coupon['discount_value']*10)/100,2);
                    }else{
                        $system_coupon['discount_money'] = $system_coupon['discount'];
                    }
                }
            }

            //子商户设置不允许使用平台优惠抵扣
            if( $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id']>0 && $now_merchant['sub_mch_discount']!=1){
                $system_coupon = array();
            }

            if (!empty($mer_coupon)) {
                $mer_coupon['coupon_url_param'] = array('merc_id' => $mer_coupon['id'], 'order_id' => $order_info['order_id'], 'type' => $_GET['type'],'discount_type'=>'coupon');
                if($now_merchant['user_discount_type'] == 2 ||$_GET['discount_type']=='coupon'){
                    $mer_coupon['coupon_url_param']['discount_type'] = 'coupon';
                }else{
                    unset($mer_coupon['coupon_url_param']['discount_type']);
                }
                if($system_coupon['discount']<$tmp_order['total_money']){
                    $this->assign('card_coupon', $mer_coupon);
                    $_SESSION['merc_id'] = $mer_coupon['id'];
                    $_SESSION['card_discount'] = $mer_coupon['discount'];
                }
                $this->assign('isUseMerCoupon',1);
            } else {
                $mer_coupon['coupon_url_param'] = array();
                if($_GET['unmer_coupon']!=1){
                    $this->assign('isUseMerCoupon',2);
                }
            }


            if (!empty($system_coupon)) {
                $system_coupon['coupon_url_param'] = array('sysc_id' => $system_coupon['id'], 'order_id' => $order_info['order_id'], 'type' => $_GET['type'],'discount_type'=>'coupon');

                if($now_merchant['user_discount_type'] == 2 ||$_GET['discount_type']=='coupon'){
                    $system_coupon['coupon_url_param']['discount_type'] = 'coupon';
                }else{
                    unset($system_coupon['coupon_url_param']['discount_type']);
                }

                if($system_coupon['is_discount']==0 && $system_coupon['discount']>=$tmp_order['total_money']){
                    $ban_mer_coupon=1;
                }else{
                    $ban_mer_coupon=0;
                }

                $this->assign('ban_mer_coupon',$ban_mer_coupon);
                //   if($system_coupon['discount']<$tmp_order['total_money']) {
                $_SESSION['sysc_id'] = $system_coupon['id'];

                $system_coupon['discount'] = $system_coupon['discount_money'];

                $this->assign('system_coupon', $system_coupon);

                $tmp_order['total_money'] -= empty($system_coupon['discount']) ? 0 : $system_coupon['discount'];

                // }
            } else {
                $system_coupon['coupon_url_param'] = array();
            }



            $coupon_url = array_merge($mer_coupon['coupon_url_param'], $system_coupon['coupon_url_param']);
            if($_GET['unsys_coupon']&&!empty($coupon_url)){
                unset($_SESSION['sysc_id']);
                $coupon_url['unsys_coupon']=1;
            }
            if($_GET['unmer_coupon']&&!empty($coupon_url)){
                unset($_SESSION['merc_id']);
                $coupon_url['unmer_coupon']=1;
            }
            if($_GET['card_password']){
                if(!empty($coupon_url)){
                    $coupon_url['card_password']=$_GET['card_password'];
                }
                $ecard_result=D('Ecard_coupon')->check_ecard_coupon($_GET['card_password'],0,0);
                if($ecard_result['error']==1){
                    $this->error_tips($ecard_result);
                }else{
                    $this->assign('ecard_info',$ecard_result['data']);
                }
            }
            $this->assign('coupon_url', $coupon_url);

            if (isset($order_info['discount_status']) && !$order_info['discount_status']) {
                $card_info['discount'] = 10;
            }
            $this->assign('card_info', $card_info);

            $this->assign('merchant_balance', $merchant_balance);

        }

        //使用积分
        $score_can_use_count=0;
        $score_deducte=0;
        $user_score_use_percent = (float)$this->config['user_score_use_percent'];
        //定制元宝判断条件
        if($this->config['open_extra_price']==1&&$order_info['extra_price']>0&&$pay_online) {
            if ($_GET['type'] == 'group' || $_GET['type'] == 'store' || $_GET['type'] == 'meal' ||$_GET['type'] == 'mall' || $_GET['type'] == 'takeout' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad' || $_GET['type'] == 'appoint' || $_GET['type'] == 'mall' || $_GET['type'] == 'shop' || $_GET['type'] == 'balance-appoint' || ($order_info['order_type'] == 'plat' && $order_info['pay_system_score'])) {           //business_type
                $type_ = $_GET['type'];
                if ($order_info['business_type'] == 'foodshop') {
                    $type_ = 'meal';
                }

                if ($_GET['type'] == 'balance-appoint') {
                    $type_ = 'appoint';
                }
                if ($_GET['type'] == 'mall') {
                    $type_ = 'shop';
                }



                $user_score_use_condition = $this->config['user_score_use_condition'];
                $user_score_max_use = D('Percent_rate')->get_max_core_use($order_info['mer_id'], $type_);//不同业务不同积分
                // $user_score_max_use=$score_config['user_score_max_use'];

                if ($_GET['type'] == 'group') {
                    $group_info = D('Group')->where(array('group_id' => $order_info['group_id']))->find();
                    if ($group_info['score_use']) {
                        if ($group_info['group_max_score_use'] != 0) {
                            $user_score_max_use = $group_info['group_max_score_use'];
                        }
                    } else {
                        $user_score_max_use = 0;
                    }
                }


                $user_score_use_percent = (float)$this->config['user_score_use_percent'];
                $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

                if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
                    $total_ = $order_info['extra_price'];
                    if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                        if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                            $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                            $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                            $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                        } else {
                            $score_can_use_count = $total_ * $user_score_use_percent > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : $total_ * $user_score_use_percent;
                            $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                            $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                        }
                    }
                }

            }
        }elseif($pay_online){
            if ($_GET['type'] == 'group' || $_GET['type'] == 'store' || $_GET['type'] == 'meal' || $_GET['type'] == 'takeout' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad' || $_GET['type'] == 'appoint' || $_GET['type'] == 'mall' || $_GET['type'] == 'shop' || $_GET['type'] == 'balance-appoint' || ($order_info['order_type'] == 'plat' && $order_info['pay_system_score'] && $order_info['business_type'] != 'village_group')) {           //business_type
                $type_ = $_GET['type'];
                if ($order_info['business_type'] == 'foodshop') {
                    $type_ = 'meal';
                }

                if ($_GET['type'] == 'balance-appoint') {
                    $type_ = 'appoint';
                }
                if ($_GET['type'] == 'mall') {
                    $type_ = 'mall';
                }
                if ($_GET['type'] == 'store') {
//                   $tmp_order['total_money'] = $order_info['order_price'];
                }

                if($_GET['type']=='plat'&&empty($tmp_order)){
                    $tmp_order['total_money']  =$order_info['order_total_money'];
                }

                $user_score_use_condition = $this->config['user_score_use_condition'];
                $user_score_max_use = D('Percent_rate')->get_max_core_use($order_info['mer_id'], $type_,$tmp_order['total_money'],$tmp_order);//不同业务不同积分

//                 $user_score_max_use=$score_config['user_score_max_use'];

                if ($_GET['type'] == 'group') {
                    $group_info = D('Group')->where(array('group_id' => $order_info['group_id']))->find();
                    if ($group_info['score_use']) {
                        if ($group_info['group_max_score_use'] != 0) {
                            $user_score_max_use = $group_info['group_max_score_use'];
                        }
                    } else {
                        $user_score_max_use = 0;
                    }
                }
                $user_score_use_percent = (float)$this->config['user_score_use_percent'];
                $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

                if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
                    $total_ = $tmp_order['total_money'];

                    if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                        if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                            $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                            $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                            $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                        } else {
                            $score_can_use_count = ceil($total_ * $user_score_use_percent) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : ceil($total_ * $user_score_use_percent);
                            $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                            $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                        }
                    }
                }

            }
        }


        //物业费积分判断 现在改成所有的缴费都可使用获得积分
        // if($_GET['type'] == 'plat'&& $order_info['order_info']['order_type']=='property'){
        if($_GET['type'] == 'plat'&& in_array($order_info['order_info']['order_type'], array('property','custom','park','gas','electric','water','custom_payment'))){
            $score_can_use_count = $order_info['order_info']['score_can_use'];
            $score_deducte = $order_info['order_info']['score_can_pay'];
            $score_percent = $order_info['order_info']['score_percent'];
            if($now_user['score_count']<$score_can_use_count){
                $score_can_use_count = $now_user['score_count'];
                $score_deducte = floor($score_can_use_count/$score_percent);
            }
        }

        // 社区收银台缴费
        if($_GET['type'] == 'plat'&& $order_info['business_type']=='house_village_pay_cashier'){
            $score_info = D(ucfirst($order_info['business_type']).'_order')->get_score($order_info['order_info']['cashier_id'],$now_user);
            $score_can_use_count = $score_info['score_can_use'];
            $score_deducte = $score_info['score_can_pay'];
            $score_percent = $score_info['score_percent'];
            if($now_user['score_count']<$score_can_use_count){
                $score_can_use_count = $now_user['score_count'];
                $score_deducte = floor($score_can_use_count/$score_percent);
            }
        }

        //子商户设置不允许使用平台优惠抵扣  开关 1 子商户 ，2 绑定手机 3 积分冻结
        if(  $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id']>0 && $now_merchant['sub_mch_discount']!=1||($this->config['cash_nobind_phone_use_balance']==0 && empty($now_user['phone'])) || $now_user['forzen_score'] == 1){
            $score_can_use_count = 0;
            $score_deducte = 0;
        }

        //可用积分
        $this->assign('score_can_use_count', $score_can_use_count);
        $this->assign('score_deducte', sprintf("%.2f",substr(sprintf("%.3f", $score_deducte), 0, -1)));
        $this->assign('score_count', $now_user['score_count']);

        //调出支付方式
        $notOnline = intval($_GET['notOnline']);
        if($_GET['type'] != 'recharge' && $_GET['type'] != 'appoint' && $order_info['business_type'] != 'merchant_user_recharge'){
            $notOffline = intval($_GET['notOffline']);
        }else{
            $notOffline = 1;
        }

        //********************预定金不允许线下支付*************************//
        if (intval($_GET['isdeposit'])) $notOffline = 1;
        if($_GET['type'] == 'meal' || $_GET['type'] == 'takeout' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad'){
            $t_order = D('Meal_order')->get_order_by_id($this->user_session['uid'], intval($_GET['order_id']));
            $true_price = $t_order['total_price'] - $t_order['minus_price'];
            if ($t_order['price'] < $true_price) $notOffline = 1;
        }
        //********************预定金不允许线下支付*************************//


        if($this->config['open_extra_price']==1){
            $now_mer = M('Merchant')->where(array('mer_id'=>$order_info['mer_id']))->find();

            $score_percent = $now_mer['score_get']>=0?$now_mer['score_get']:$this->config['user_score_get'];

            $this->assign('score_get',$score_percent);
        }

        if (isset($_GET['online']) && $_GET['type'] == 'foodPad') {
            $online = isset($_GET['online']) ? intval($_GET['online']) : 1;
            $notOnline = $online ? 0 : 1;
            $notOffline = $online ? 1 : $notOffline;
        }

        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        if(in_array($_GET['type'],array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','balance-appoint','merchant_user_recharge')) && $order_info['mer_id']){

        }else if($_GET['type'] == 'plat' && $order_info['pay_merchant_ownpay'] && $order_info['mer_id']){
            $this->config['merchant_ownpay'] = $this->config['merchant_ownpay'];
        }else{
            $this->config['merchant_ownpay'] = 0;
        }
        $now_merchant = D('Merchant')->get_info($order_info['mer_id']);
        if ($now_merchant) {
            $notOffline = ($pay_offline && ($now_merchant['is_offline'] == 1)) ?0 : 1;
        }
        $notOnline = $pay_online?$notOnline:1;
        switch($this->config['merchant_ownpay']){
            case 0:
                $pay_method = D('Config')->get_pay_method($notOnline,$notOffline,true);
                break;
            case 1:
                $pay_method = D('Config')->get_pay_method($notOnline,$notOffline,true);
                if($this->config['city_ownpay']){

                    $merchant_ownpay = D('City_ownpay')->field('city_id',true)->where(array('city_id'=>$now_merchant['city_id']))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            if(strpos($ownKey,'app')){
                                continue;
                            }
                            if($ownKey=='weixin'){
                                $ownValueArr['is_own'] = 6;
                            }else{
                                $ownValueArr['is_own'] = true;
                            }
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$now_merchant['mer_id']))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        if(strpos($ownKey,'app')){
                            continue;
                        }
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }

                break;
            case 2:
                $pay_method = array();
                if($this->config['city_ownpay']){

                    $merchant_ownpay = D('City_ownpay')->field('city_id',true)->where(array('city_id'=>$now_merchant['city_id']))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            if(strpos($ownKey,'app')){
                                continue;
                            }
                            if($ownKey=='weixin'){
                                $ownValueArr['is_own'] = 6;
                            }else{
                                $ownValueArr['is_own'] = true;
                            }
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }

                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$now_merchant['mer_id']))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method($notOnline,$notOffline,true);
                }

                break;
        }

        if($this->config['nmgzhcs_appid']){
            $now_merchant['shop_openid'] = $now_merchant['shop_openid'] ? : '-1';
            if($this->user_session['nmgzhcs_openid'] && $now_merchant['shop_openid']){
                $third_way_pay = true;
                $this->assign('third_way_pay',$third_way_pay);
                foreach($pay_method as $key=>$value){
                    if($key != 'nmgpay'){
                        unset($pay_method[$key]);
                    }
                }
            }else{
                unset($pay_method['nmgpay']);
            }
        }

        if(isset($pay_method['alipayh5'])){
            $pay_method['alipayh5']['name'] = '支付宝';
        }
        if(isset($pay_method['yzfpay'])){
            $pay_method['yzfpay']['name'] = '翼支付';
        }

        if(isset($pay_method['alipay_app'])){
            unset($pay_method['alipay_app']);
        }

        if(isset($pay_method['wftpay'])){
            $pay_method['wft_weixin']['name'] = '网付通微信';
            $pay_method['wft_alipay']['name'] = '网付通支付宝';
        }

        if (  $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
            $pay_method['weixin']['name'] = '微信支付';
            $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
            $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
            $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
            $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_client_cert'];
            $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
            //$pay_method['weixin']['config']['is_own'] = 1 ;
        }

        if($this->config['open_allinyun']==1){
            $can_pay_allinyun = true;
            $allinyun = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();

            if($order_info['mer_id'] && $_GET['type']!='recharge'){
                $allinyun_mer =  D('Deposit')->get_merchant_info($order_info['mer_id'],1);
                if( $allinyun_mer['status']==1){
                    $can_pay_allinyun = true;
                }else{
                    $can_pay_allinyun = false;
                }
            }
            if($allinyun['bizUserId'] && $can_pay_allinyun){
                if($pay_method['allinyun_gateway']){
                    $pay_method['allinyun_gateway']['name'] ='云商通网关';
                }
                $_GET['type']!='recharge' && $pay_method['allinyun_balance']['name'] ='云商通余额';
                $this->assign('balance_disable',1);
            }
        }


        if(empty($pay_method) && $this->is_app_browser==false){
            $this->error_tips('系统管理员没开启任意一种支付方式！');
        }

        if(empty($_SESSION['openid']) || $_GET['type'] == 'foodPad' || !$this->is_weixin_browser){
            unset($pay_method['weixin'],$pay_method['weifutong']);
        }
        unset($pay_method['weixinapp']);
        unset($pay_method['yzfpay_offline']);
		//商家存在，且是认证服务号，进行用户微信授权
		if($now_merchant && $this->is_weixin_browser){
			$merchant_bind = D('Weixin_bind')->field(true)->where(array('mer_id' => $now_merchant['mer_id'], 'type' => 0, 'service_type_info' => 2, 'verify_type_info' => array('neq' , -1)))->find();
			if(empty($merchant_bind)){
				if($pay_method['weixin']['config']['is_own']){
					unset($pay_method['weixin']);
				}
			}else{
				if(empty($_SESSION['open_authorize_openid']) || empty($_SESSION['open_authorize_mer_id']) || ($_SESSION['open_authorize_mer_id'] != $order_info['mer_id'])){
					$this->open_authorize_openid(array('appid'=>$merchant_bind['authorizer_appid'], 'mer_id'=>$merchant_bind['mer_id']));
				}
				if($_SESSION['open_authorize_openid'] == 'error'){
					if($pay_method['weixin']['config']['is_own']){
						unset($pay_method['weixin']);
					}
					unset($_SESSION['open_authorize_openid']);
				}
			}
        }

        $this->assign('pay_method',$pay_method);


        //定制桌台优惠
        if($now_order['order_info']['order_info']['table_or_vipcard_discount']==0){
            if($_GET['type']=='plat'&&$now_order['order_info']['business_type']=='foodshop'){
                $now_ordert = D('Foodshop_order')->get_order_detail(array('order_id'=>$order_info['order_info']['order_id']));
                $tablesDiscount = D('Merchant_store_foodshop')->getTableDiscount($order_info['mer_id'],$order_info['store_id'],$order_info['order_info']['order_id'],$now_ordert);
                if($tablesDiscount!=false && $tablesDiscount['mer_discount']!=0){
                    $this->assign('tablesDiscount',$tablesDiscount);
                }
            }
        }

        if($_GET['type'] == 'group'){
            $this->behavior(array('model'=>'Pay_group','mer_id'=>$order_info['mer_id'],'biz_id'=>$order_info['order_id']),true);
        }else if($_GET['type'] == 'meal'){
            $this->behavior(array('model'=>'Play_meal','mer_id'=>$order_info['mer_id'],'biz_id'=>$order_info['order_id']),true);
        }

        $this->assign('type',$_GET['type']);
        $this->assign('order_id',$_GET['order_id']);

        //支付宝是否采用JS调起
        $alipay_js_call = 'false';
        if($this->config['open_wftpay'] && $now_user['alipay_uid'] && $this->is_alipay_browser){
            $alipay_js_call = 'true';
        }
        $this->assign('alipay_js_call',$alipay_js_call);

        $this->display();
    }

    protected function getPayName($label){
        $payName = array(
            'weixin' => '微信支付',
            'tenpay' => '财付通支付',
			'alipay' => '支付宝',
            'alipayh5' => '支付宝',
            'yeepay' => '银行卡支付(易宝支付)',
            'allinpay' => '银行卡支付(通联支付)',
            'chinabank' => '银行卡支付(网银在线)',
            'weifutong' => $this->config['pay_weifutong_alias_name'],
            'yzfpay' => "翼支付",
            'wftpay' => "网付通",
            'allinpay_mer' => "通联支付",
        );
        return $payName[$label];
    }

    /**
     * @desc 根据订单id判断各个商品库存是否充足-jjc
     * @param $orderid
     * @return bool
     */
    protected function check_stock($orderid){
        if(!$orderid) return false;
        $order_detail = M('shop_order_detail')->field('goods_id,num')->where(array('order_id'=>$orderid))->select();
        if(!empty($order_detail)){
            $goods_id_arr = array();
            foreach($order_detail as $v){
                $goods_id_arr[] = $v['goods_id'];
            }
            $shop_goods = M('shop_goods')->field('goods_id,name,stock_num')->where(array('goods_id'=>array('in',$goods_id_arr)))->select();
            foreach($order_detail as $key =>$val){
                foreach($shop_goods as $k => $v){
                    if($val['goods_id']==$v['goods_id']){
                        if($v['stock_num']<$val['num'] && $v['stock_num']<>-1){
                            $tips = '抱歉，商品（'.$v['name'].'）库存不足！';
                            $this->error_tips($tips);exit;
                        }
                    }

                }
            }
        }
    }

    public function go_pay()
    {
        fdump_api([$_GET,$_POST],'go_pay_0221',1);
		//小程序非微信支付使用 GET 传参 跳转此页面
		if($_GET['pay_param']){
			$pay_param = base64_decode(urldecode($_GET['pay_param']));
            $lastPoint = strripos($pay_param,'}');
            $pay_param = substr($pay_param,0,$lastPoint+1);
			$_POST = json_decode($pay_param,true);
			fdump_api($_POST, 'pay/pay_params', 1);
			if($_POST['pay_type'] == 'alipay' && $this->config['pay_alipayh5_open']){
				$_POST['pay_type'] = 'alipayh5';
			}
            if($_POST['pay_type'] == 'weixin' && $this->config['pay_weixinh5_open']){
                $_POST['pay_type'] = 'weixinh5';   
            }
        }

        //换参数
        if ($_POST['ticket'] != '') {
            $_POST['use_merchant_balance'] = $_POST['use_merchant_money'];
            $_POST['use_balance'] = !isset($_POST['use_balance_money']) ? 1 : $_POST['use_balance_money'];
            $_POST['discount_type'] = !isset($_POST['discount_type']) ? '' : $_POST['discount_type'];
            $_POST['qiye_pay_checked'] = !isset($_POST['use_qiye_money']) ? 0 : intval($_POST['use_qiye_money']);
            $_POST['use_employee_balance'] = !isset($_POST['use_employee_balance']) ? 0 : intval($_POST['use_employee_balance']);
            $_POST['use_employee_score'] = !isset($_POST['use_employee_score']) ? 0 : intval($_POST['use_employee_score']);
        }
        if ($_POST['order_id'] && $this->user_session['uid'] && in_array($_POST['order_type'], ['shop', 'mall'])) {
            D('Shop_order')->where(['order_id' => intval($_POST['order_id'])])->save(['pay_uid' => $this->user_session['uid']]);
        }

        if (in_array($_POST['order_type'], ['shop'])) {
            //下单人判断避免重复支付
            $cachePayUser =  S('shop_order' . $_POST['order_id']);
            if ($cachePayUser !== false && $cachePayUser != $this->user_session['uid']) {
                $this->error_tips(L_('当前订单正在支付中，请勿重复支付！'));
            }
            S('shop_order' . $_POST['order_id'],  $this->user_session['uid'], 60 * 2);
        }


        $_POST['is_pay_for_others'] = isset($_POST['is_pay_for_others']) ? $_POST['is_pay_for_others'] : 0;
        if ($_POST['is_pay_for_others'] && in_array($_POST['order_type'], ['shop', 'mall'])) {
            $this->user_session['uid'] = D('Shop_order')->where(['order_id' => intval($_POST['order_id'])])->getField('uid');
        }

        $need_user = true;
        if ($_POST['order_type'] == 'store') {
            $need_user = false;
        }

        if ($need_user && empty($this->user_session)) {
            if ($this->is_app_browser) {
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！', U('Login/index', $location_param));
            } else {
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index', $location_param));
            }
        }

        //用户信息
        $now_user = D('User')->get_user($this->user_session['uid']);
        if ($need_user && empty($now_user)) {
            $this->error_tips('未获取到您的帐号信息，请重试！');
        }
        if ($_POST['pay_type'] == 'offline' && $_POST['use_balance']) {
            if ($allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid'])) {
                $this->error_tips('您是云商通账户,不能使用余额+线下的支付方式');
            }
        }

        if (!in_array($_POST['order_type'], array('group', 'meal', 'weidian', 'takeout', 'food', 'foodPad', 'recharge', 'appoint', 'waimai', 'wxapp', 'store', 'shop', 'mall', 'plat', 'balance-appoint', 'plat', 'integral_mall','yuedan','yuedan_service_release','spread','mcard', 'ticket', 'guide'))) {
            $this->error_tips('订单来源无法识别，请重试。');
        }
        //平台E卡
        $card_password = I('card_password', '', 'trim');
        $ecard_info = array();
        if (!empty($card_password)) {
            $ecard_result = D('Ecard_coupon')->check_ecard_coupon($card_password, 0, 0);
            if ($ecard_result['error'] == 1) {
                $this->error_tips($ecard_result['msg']);
            } else {
                $ecard_info = $ecard_result['data'];
            }
        }

//        $this->check_stock($_POST['order_id']);
        //如果没有使用低费率，直接跳转支付宝
        if (($this->config['open_juhepay'] == 0 || strtolower($_POST['order_type']) == 'recharge') && ($this->config['open_weikebaba'] == 0 || empty($now_user['alipay_uid']) || !$this->is_alipay_browser) && ($this->config['open_wftpay'] == 0 || empty($now_user['alipay_uid']) || !$this->is_alipay_browser) && (strtolower($_POST['pay_type']) == 'alipay' || strtolower($_POST['pay_type']) == 'alipayh5') && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'xkyapp') === false && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'miniprogram') === false) {
            $url = U('Pay/alipay', $_POST);
            $this->assign('url', $url);
            $this->display('alipay_pay');
            die;
        }

        switch ($_POST['order_type']) {
            case 'group':
                $now_order = D('Group_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                fdump($now_order, 'now_order_1111111111', 1);
                break;
            case 'meal':
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order = D('Meal_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']), false, $_POST['order_type']);
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'recharge':
                $now_order = D('User_recharge_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'appoint':
                $now_order = D('Appoint_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'waimai':
                $now_order = D('Waimai_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']), false, $_POST['order_type']);
                if ($now_order['order_info']['pay_type'] !== $_POST['pay_type']) {
                    $this->error_tips('非法的订单');
                }
                break;
            case 'wxapp':
                $now_order = D('Wxapp_order')->get_pay_order(0, intval($_POST['order_id']));
                break;
            case 'store':
                $now_order = D('Store_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'shop':
            case 'mall':
                $now_order = D('Shop_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'plat':
                $now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'balance-appoint':
                $now_order = D('Appoint_order')->get_pay_balace_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'yuedan':
                $now_order = D('Yuedan_service_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'yuedan_service_release':
                $now_order = D('Yuedan_service_release')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'integral_mall':
                $now_order = D('Merchant_integral_mall_goods_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'spread':
                $now_order = D('Merchant_spread_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'mcard':
                $now_order = D('Membership_card_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'ticket':
                //景区门票订单
                $now_order = D('Scenic_order')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            case 'guide':
                //导游
                $now_order = D('Scenic_aguide')->get_pay_order($this->user_session['uid'], intval($_POST['order_id']));
                break;
            default:
                $this->error_tips('非法的订单!');
        }
        if ($now_order['error'] == 1) {
            if (stripos($now_order['msg'], L_('已经支付')) !== false && $now_order['url']) {
                redirect($now_order['url']);
            }
            $this->error_tips($now_order['msg'], $now_order['url']);
        }

        $order_info = $now_order['order_info'];

        if ($this->is_wxapp_browser) {
            $order_info['is_mobile'] = 3;
        } else if ($this->is_app_browser) {
            $order_info['is_mobile'] = 2;
        } else {
            $order_info['is_mobile'] = 1;
        }
        $now_merchant = D('Merchant')->get_info($order_info['mer_id']);
        if ($now_merchant['status'] != 1&& $order_info['mer_id'] > 0) {
            $this->error_tips('该商家状态异常，无法支付');
        }
        $use_discount = true;

        if ($this->config['open_score_discount'] && $order_info['order_type'] != 'integral_mall' && $order_info['book_id'] == 0) {
            switch ($now_merchant['user_discount_type']) {
                case 1 :   //优惠
                    $order_info['score_discount_type'] = 2;
                    break;
                case 2 :   //积分
                    $use_discount = false;
                    $order_info['score_discount_type'] = 1;
                    break;
                case 3 :   //2选1
                    $use_discount = $_POST['discount_type'] == 'score' ? false : true;
                    break;
            }
            if ($_POST['discount_type'] == "score") {
                $order_info['score_discount_type'] = 1;
            } else if ($_POST['discount_type'] == "coupon") {
                $order_info['score_discount_type'] = 2;
            }

        }
        // 商家积分兑换 不允许使用平台/商家优惠券抵扣
        if ($order_info['order_type'] == 'integral_mall') {
            $order_info['discount_status'] = false;
        }
        //定制只能使用桌台或会员卡折扣
        if (1 == $this->config['is_open_merchant_foodshop_discount'] && $order_info['is_foodshop_new'] != 1) {
            if (($order_info['order_info']['mer_table_discount'] > 0 && $order_info['order_info']['mer_table_discount'] < 100) && $order_info['order_info']['table_or_vipcard_discount'] == 0) {
                $use_discount = false;
            }
        }
        //商家会员卡余额
        if ($_POST['use_merchant_balance']) {
            $order_info['use_merchant_balance'] = 1;
        } else {
            $order_info['use_merchant_balance'] = 0;
        }
        if ($_POST['order_type'] != 'recharge' && $_POST['order_type'] != 'weidian' && (empty($order_info['business_type']) || $order_info['business_type'] != 'card_new_recharge')) {
            //优惠券
            if ($use_discount && (!isset($order_info['discount_status']) || $order_info['discount_status'])) {
                if ((!empty($_POST['card_id']) && $_POST['card_id'] == $_SESSION['merc_id']) || ($_POST['ticket'] && $_POST['card_id'] && $_POST['use_mer_coupon'])) {
                    $card_coupon = D('Card_new_coupon')->get_coupon_by_id($_POST['card_id']);
                    $now_coupon['card_price'] = $card_coupon['price'];
                    $now_coupon['merc_id'] = $card_coupon['id'];
                    unset($_SESSION['merc_id']);
                }
                if ((!empty($_POST['coupon_id']) && $_POST['coupon_id'] == $_SESSION['sysc_id']) || ($_POST['ticket'] && $_POST['coupon_id'] && $_POST['use_sys_coupon'])) {
                    $system_coupon = D('System_coupon')->get_coupon_by_id($_POST['coupon_id']);
                    if ($system_coupon['discount_type'] == 1) {
                        $tmp_total_money = $order_info['freight_charge'];
                    } elseif ($system_coupon['discount_type'] == 2) {
                        $tmp_total_money = $order_info['goods_price'];
                    } else {
                        $tmp_total_money = $order_info['order_total_money'];
                    }
                    $system_coupon['discount'] = min($tmp_total_money, $system_coupon['discount']);
                    $now_coupon['coupon_price'] = $system_coupon['discount'] < 0 ? 0 : $system_coupon['discount'];
                    $now_coupon['sysc_id'] = $system_coupon['id'];
                    $now_coupon['discount_type'] = $system_coupon['discount_type'];//优惠类型：0不限，1减免运费，2减免餐费
                }
            }
            //子商户设置不允许使用平台优惠抵扣
            if ($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0 && $now_merchant['sub_mch_discount'] != 1) {
                $now_coupon['coupon_price'] = 0;
                $now_coupon['sysc_id'] = 0;
            }

            if ($order_info['mer_id']) {
                // 获取商家联盟会员卡卡
                if ($now_order['order_info']['can_use_mer_union_card'] && $_POST['union_mer_id'] && $_POST['union_mer_id'] != $order_info['mer_id']) {
                    // 获取联盟关系
                    $union_mer = D('Card_new')->get_union_merchant($_POST['union_mer_id'], $order_info['mer_id']);
                    if ($union_mer) {
                        $union_card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $_POST['union_mer_id']);
                    }
                }
                $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $order_info['mer_id']);

                // 使用联盟会员卡(会员卡余额和折扣使用选择的会员卡，优惠券使用本商家的优惠券)
                if ($union_card_info) {
                    $card_info['discount'] = $union_card_info['union_card_info'];
                    $card_info['card_money'] = $union_card_info['card_money'];
                    $card_info['card_money_give'] = $union_card_info['card_money_give'];
                    $merchant_balance['union_mer_id'] = $union_card_info['mer_id'];
                }

                if (empty($card_info)) {
                    $merchant_balance['card_money'] = 0;
                    $merchant_balance['card_give_money'] = 0;
                    $merchant_balance['card_discount'] = 10;
                } elseif ($order_info['order_from'] == 1 && ($order_info['group_id'] > 0 || $order_info['bar_id'] > 0)) {
                    $merchant_balance['card_money'] = 0;
                    $merchant_balance['card_give_money'] = $card_info['card_money_give'];
                    $merchant_balance['card_discount'] = 10;
                } else {
                    $merchant_balance['card_money'] = $card_info['card_money'];
                    $merchant_balance['card_give_money'] = $card_info['card_money_give'];
                    if (!$use_discount || empty($card_info['discount']) || (isset($order_info['discount_status']) && !$order_info['discount_status'])) {
                        $merchant_balance['card_discount'] = 10;
                    } else {
                        $merchant_balance['card_discount'] = $card_info['discount'];
                    }
                }
            }

            //判断优惠券折扣
            if ($system_coupon['is_discount'] && $system_coupon['discount_value'] > 0 && $system_coupon['discount_value'] < 10) {
                $tmp_wx_cheap = 0;

                $tmp_total_money = $order_info['order_total_money'];
                if ($order_info['order_type'] == 'store') {
                    $tmp_total_money -= $order_info['no_discount_money'];
                } else if ($order_info['business_type'] == 'foodshop') {
                    $tmp_total_money = $order_info['order_info']['can_discount_money'];
                }
                if ($merchant_balance['card_discount'] > 0) {
                    $tmp_total_money = sprintf("%.2f", $tmp_total_money * $merchant_balance['card_discount'] / 10);
                }
                if ($order_info['order_type'] == 'group') {
                    if ($order_info['order_type'] == 'group') {
                        //判断有没有使用微信，如果是微信，则检测此团购有没有微信优惠！
                        if ($this->is_app_browser) {
                            $now_group_ = M('Group')->field('`group_id`,`wx_cheap`')->where(array('group_id' => $order_info['group_id']))->find();
                            $tmp_total_money = $order_info['order_num'] * $now_group_['wx_cheap'];
                        } elseif ($this->config['weixin_buy_follow_wechat'] == 2 && empty($now_user['is_follow'])) {
                            $tmp_total_money = 0;
                        } elseif ($this->config['weixin_buy_follow_wechat'] == 1 && empty($now_user['is_follow'])) {
                            $tmp_total_money = 0;
                        } elseif ($_SESSION['openid']) {
                            $now_group_ = M('Group')->field('`group_id`,`wx_cheap`')->where(array('group_id' => $order_info['group_id']))->find();
                            $tmp_total_money -= $order_info['order_num'] * $now_group_['wx_cheap'];
                        }

                    }
                    $tmp_total_money = $tmp_total_money - $tmp_wx_cheap;
                    if ($now_coupon['card_price'] > 0) {
                        if ($now_coupon['card_price'] < $tmp_total_money) {
                            $tmp_total_money -= $now_coupon['card_price'];
                        } else {
                            $tmp_total_money -= 0;
                        }

                    }

                    if ($tmp_total_money > 0 && !empty($system_coupon) && $tmp_total_money >= $system_coupon['order_money']) {
                        $now_coupon['coupon_price'] = round($tmp_total_money * (100 - $system_coupon['discount_value'] * 10) / 100, 2);
                    }
                } else {
                    $now_coupon['coupon_price'] = round($tmp_total_money * (100 - $system_coupon['discount_value'] * 10) / 100, 2);
                }
            }
        }
//        dump($now_coupon);exit;

        //判断积分是否够用 防止支付同时积分被改动


        if ($_POST['order_type'] != 'integral_mall' && $_POST['use_score'] && (empty($order_info['business_type']) || $order_info['business_type'] != 'mobile_recharge')) {
            if ($now_user['score_count'] < $_POST['score_used_count']) {
                $this->error_tips('账户' . $this->config['score_name'] . '不够，请重试！');
            }
            if ($this->config['open_extra_price']) {

                if (isset($order_info['extra_price']) && $order_info['extra_price'] > 0 && $order_info['extra_price'] < $_POST['score_deducte']) {
                    $this->error_tips($this->config['score_name'] . '抵扣金额错误！');
                }
            } else {
                if ($order_info['order_total_money'] < $_POST['score_deducte']) {
                    $this->error_tips($this->config['score_name'] . '抵扣金额错误！');
                }
            }

            $order_info['score_used_count'] = $_POST['score_used_count'];
            $order_info['score_deducte'] = $this->config['user_score_use_percent'] > 0 ? bcdiv($_POST['score_used_count'], (float)$this->config['user_score_use_percent'], 2) : 0;
            $order_info['score_deducte'] = $order_info['score_deducte'] > $order_info['order_total_money'] ? $order_info['order_total_money'] : $order_info['score_deducte'];
        } else {
            $order_info['score_used_count'] = 0;
            $order_info['score_deducte'] = 0;
        }
        $order_info['use_score'] = $_POST['use_score'];
        //这里为了解决app支付
        if ($_POST['use_balance'] == 1) {
            $order_info['use_balance'] = 1;
        } else {
            $order_info['use_balance'] = 0;
        }

        //子商户设置不允许使用平台余额支付
        if ($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0 && $now_merchant['sub_mch_sys_pay'] != 1) {
            $order_info['use_balance'] = 0;
        }
        //子商户设置不允许使用平台优惠抵扣
        if ($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0 && $now_merchant['sub_mch_discount'] != 1 || ($this->config['cash_nobind_phone_use_balance'] == 0 && empty($now_user['phone'])) || $now_user['forzen_score'] == 1) {
            $order_info['score_used_count'] = 0;
            $order_info['score_deducte'] = 0;
            $order_info['use_score'] = $_POST['use_score'];
        }

        if ($_POST['order_type'] == 'plat' && $this->config['open_extra_price'] == 1 && $order_info['business_type'] == 'foodshop') {
            $now_business_order = M('Foodshop_order')->where(array('order_id' => $order_info['business_id']))->find();
            $order_info['order_total_money'] += $now_business_order['extra_price'];
        } else if ($_POST['order_type'] == 'balance-appoint' && $this->config['open_extra_price'] == 1) {
            $order_info['order_total_money'] += $order_info['extra_price'];
        }
        //如果用户存在余额或使用了优惠券，则保存至订单信息。如果金额满足订单总价，则实时扣除并返回支付成功！若不够则不实时扣除，防止用户在付款过程中取消支付
        $wx_cheap = 0;
        if ($order_info['order_type'] == 'group') {
            //判断有没有使用微信，如果是微信，则检测此团购有没有微信优惠！
            if ($this->is_app_browser) {
                $now_group = M('Group')->field('`group_id`,`wx_cheap`')->where(array('group_id' => $order_info['group_id']))->find();
                $wx_cheap = $order_info['order_num'] * $now_group['wx_cheap'];
            } elseif ($this->config['weixin_buy_follow_wechat'] == 2 && empty($now_user['is_follow'])) {
                $this->error_tips('您未关注公众号，不能购买！请先关注公众号。');
            } elseif ($this->config['weixin_buy_follow_wechat'] == 1 && empty($now_user['is_follow'])) {
                $wx_cheap = 0;
            } elseif ($_SESSION['openid']) {
                $now_group = M('Group')->field('`group_id`,`wx_cheap`')->where(array('group_id' => $order_info['group_id']))->find();
                $wx_cheap = $order_info['order_num'] * $now_group['wx_cheap'];
            }
            $save_result = D('Group_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $wx_cheap, $ecard_info);
            if (isset($now_order['order_info']['is_head']) && $now_order['order_info']['is_head'] > 0) {// 团购拼团订单支付成功跳转拼团详情页
                //$save_result['url'] = '/wap.php?g=Wap&c=Groupnew&a=invitePin&order_id='.intval($_POST['order_id']);
            }
        } else if ($order_info['order_type'] == 'meal' || $order_info['order_type'] == 'takeout' || $order_info['order_type'] == 'food' || $order_info['order_type'] == 'foodPad') {
            $save_result = D('Meal_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $order_info['order_type'], $ecard_info);
        } else if ($order_info['order_type'] == 'weidian') {
            $save_result = D('Weidian_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'recharge') {
            $save_result = D('User_recharge_order')->web_befor_pay($order_info, $now_user);
        } else if ($order_info['order_type'] == 'appoint') {
            $save_result = D('Appoint_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'yuedan') {
            $save_result = D('Yuedan_service_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'waimai') {
            $save_result = D('Waimai_order')->wap_befor_pay($order_info, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'wxapp') {
            $save_result = D('Wxapp_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'store') {
            if ($_POST['qiye_pay_checked'] == 1) {
                $order_info['qiye_balance'] = 1;
            }
            $save_result = D('Store_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'shop' || $order_info['order_type'] == 'mall') {
            if ($_POST['qiye_pay_checked'] == 1) {
                $order_info['qiye_balance'] = 1;
            }
            if($_POST['use_employee_balance'] == 1){
                $order_info['employee_balance'] = 1;
            }
            if($_POST['use_employee_score'] == 1){
                $order_info['employee_score'] = 1;
            }
            $save_result = D('Shop_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'plat') {
            //定制商家桌次折扣
            if ($order_info['order_info']['table_or_vipcard_discount'] == 0) {
                if ($order_info['order_info']['mer_table_discount'] > 0 && $order_info['order_info']['mer_table_discount'] < 100) {
                    $discount = $order_info['order_info']['mer_table_discount'] / 100;
                    $reduce_money = round($order_info['order_info']['can_discount_table_money'] - $order_info['order_info']['can_discount_table_money'] * $discount, 2);
                    $pay_money = $order_info['order_total_money'];
                    $pay_money = round($pay_money - $reduce_money, 2);
                    $order_info['order_total_money'] = $pay_money;
                }
            }

            //使用企业预付款支付
            if ($_POST['qiye_pay_checked'] == 1) {
                $order_info['qiye_balance'] = 1;
            }
            // 堂扫不参与会员卡折扣
            if ($order_info['order_info']['is_foodshop_new']) {
                $merchant_balance['card_discount'] = 0;
            }

            $save_result = D('Plat_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'balance-appoint') {
            $save_result = D('Appoint_order')->wap_balace_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'integral_mall') {
            $save_result = D('Merchant_integral_mall_goods_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user, $ecard_info);
        } else if ($order_info['order_type'] == 'spread') {
            $save_result = D('Merchant_spread_order')->wap_befor_pay($order_info, $now_user);
        }else if($order_info['order_type'] == 'mcard'){
            $save_result = D('Membership_card_order')->wap_befor_pay($order_info, $now_user);
        } else if ($order_info['order_type'] == 'ticket') {
            $save_result = D('Scenic_order')->wap_befor_pay($order_info, $now_user);
        } else if ($order_info['order_type'] == 'guide') {
            $save_result = D('Scenic_aguide')->wap_befor_pay($order_info, $now_user);
        }

        if (cookie('is_house')) {
            $save_result['url'] = str_replace('wap.php', C('INDEP_HOUSE_URL'), $save_result['url']);
        }
        if ($save_result['error_code']) {
            $this->error_tips($save_result['msg']);
        } else if ($save_result['url']) {
            $this->success_tips($save_result['msg'], $save_result['url']);
        }

        //需要支付的钱
        $pay_money = round($save_result['pay_money'] * 100) / 100;

        if (in_array($order_info['order_type'], array('mall', 'group', 'meal', 'weidian', 'takeout', 'food', 'foodPad', 'recharge', 'appoint', 'waimai', 'wxapp', 'store', 'shop', 'plat', 'balance-appoint')) && $order_info['mer_id']) {
            $mer_id = $order_info['mer_id'];
        } else {
            $this->config['merchant_ownpay'] = 0;
        }
        if ($order_info['pay_merchant_ownpay'] === false) {
            $this->config['merchant_ownpay'] = 0;
        }
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        switch ($this->config['merchant_ownpay']) {
            case '0':
                $pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
                if ($this->config['city_ownpay']) {

                    $merchant_ownpay = D('City_ownpay')->field('city_id', true)->where(array('city_id' => $now_merchant['city_id']))->find();
                    foreach ($merchant_ownpay as $ownKey => $ownValue) {
                        $ownValueArr = unserialize($ownValue);
                        if ($ownValueArr['open']) {
                            if (strpos($ownKey, 'app')) {
                                continue;
                            }
                            if ($ownKey == 'weixin') {
                                $ownValueArr['is_own'] = 6;
                            } else {
                                $ownValueArr['is_own'] = true;
                            }
                            $pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
                        }
                    }
                }
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
                foreach ($merchant_ownpay as $ownKey => $ownValue) {
                    $ownValueArr = unserialize($ownValue);
                    if ($ownValueArr['open']) {
                        if (strpos($ownKey, 'app')) {
                            continue;
                        }
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
                    }
                }

                break;
            case '2':
                $pay_method = array();
                if ($this->config['city_ownpay']) {
                    $merchant_ownpay = D('City_ownpay')->field('city_id', true)->where(array('city_id' => $now_merchant['city_id']))->find();
                    foreach ($merchant_ownpay as $ownKey => $ownValue) {
                        $ownValueArr = unserialize($ownValue);
                        if ($ownValueArr['open']) {
                            if (strpos($ownKey, 'app')) {
                                continue;
                            }
                            if ($ownKey == 'weixin') {
                                $ownValueArr['is_own'] = 0;
                            } else {
                                $ownValueArr['is_own'] = true;
                            }
                            $pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
                        }
                    }
                }
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
                foreach ($merchant_ownpay as $ownKey => $ownValue) {
                    $ownValueArr = unserialize($ownValue);
                    if ($ownValueArr['open']) {
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
                    }
                }

                if (empty($pay_method)) {
                    $pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
                }
                break;
        }

        if (strpos($_POST['pay_type'], 'wft') !== false) {
            $pay_method['wftpay']['config']['real_pay_type'] = $_POST['pay_type'];
            $_POST['pay_type'] = 'wftpay';
        }
        //配置服务商子商户支付
        if ($this->config['open_sub_mchid']) {
            $is_sub_mchid = false;
            if ($pay_method['weixin']['config']['is_own'] === 6) {
                $is_sub_mchid = true;
            } else {
                $now_store = D('Merchant_store')->get_store_by_storeId($order_info['store_id']);
                if ($order_info['store_id'] != 0 && $now_store['open_sub_mchid'] && $now_store['sub_mch_id'] > 0) {
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_store['sub_mch_id'];
                    $pay_method['weixin']['config']['is_own'] = 3;
                    $is_sub_mchid = true;
                } else if ($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                    $pay_method['weixin']['config']['is_own'] = 2;
                    $is_sub_mchid = true;
                } else if ($order_info['business_type'] == 'house_village_pay' || $order_info['business_type'] == 'house_village_express' || $order_info['business_type'] == 'house_village_pay_cashier') {
                    $now_village = D('House_village')->get_one($order_info['order_info']['village_id']);
                    if ($now_village['sub_mch_id'] > 0) {
                        $pay_method['weixin']['config']['is_own'] = 5;
                        $pay_method['weixin']['config']['sub_mch_id'] = $now_village['sub_mch_id'];
                        $is_sub_mchid = true;
                    }
                } else if ($pay_method['weixin']['config']['sub_mch_id'] && $pay_method['weixin']['config']['is_own'] == 0) {
                    $is_sub_mchid = true;
                }
            }

            if ($is_sub_mchid) {
                $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
                $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
                $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
                $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
            }
        }


        $order_id = $order_info['order_id'];
        if ($_POST['order_type'] == 'takeout' || $_POST['order_type'] == 'food' || $_POST['order_type'] == 'foodPad') {
            $order_table = 'Meal_order';
        } else if ($_POST['order_type'] == 'recharge') {
            $order_table = 'User_recharge_order';
            if (floatval($pay_money) != floatval($order_info['order_price'])) {
                $this->error_tips('充值订单有误');
            }
        } else if ($_POST['order_type'] == 'balance-appoint') {
            $order_table = 'Appoint_order';
            //$order_info['order_type']='appoint';
        } else if ($_POST['order_type'] == 'mall') {
            $order_table = 'Shop_order';
        } else if ($_POST['order_type'] == 'yuedan') {
            $order_table = 'Yuedan_service_order';

        }else if($_POST['order_type']=='spread'){
            $order_table = 'Merchant_spread_order';
        }else if ($_POST['order_type'] == 'mcard') {
            $order_table = 'Membership_card_order';
        } else if ($_POST['order_type'] == 'ticket') {
            $order_table = 'Scenic_order';
        } else if ($_POST['order_type'] == 'guide') {
            $order_table = 'Scenic_aguide_order';
        } else {
            $order_table = ucfirst($_POST['order_type']) . '_order';
        }

        //更新长id
        $nowtime = date("ymdHis");
        if ($_POST['order_type'] == 'balance-appoint') {
            $nowtime = date("mdHis");
            $orderid = $nowtime . sprintf("%04d", $this->user_session['uid']);
        } 
        elseif($_POST['pay_type'] == 'farmersbankpay'){
            $nowtime = date("dHis");
            $orderid = $nowtime . str_pad(substr(sprintf("%04d", $this->user_session['uid']),-4),4,'0',STR_PAD_LEFT);
        }
        else {
            /* if($_POST['pay_type'] == 'merchantwarrior'){
                $nowtime = str_shuffle(str_replace(array('4','5'),'0',$nowtime));
            } */
            $orderid = $nowtime . rand(10, 99) . sprintf("%08d", $this->user_session['uid']);
        }

        $data_tmp['pay_type'] = $_POST['pay_type'];
        $data_tmp['order_type'] = $_POST['order_type'];
        $data_tmp['order_id'] = $order_id;
        $data_tmp['orderid'] = $orderid;
        $data_tmp['addtime'] = $nowtime;
        if (!D('Tmp_orderid')->add($data_tmp)) {
            $this->error_tips('更新订单信息失败，请联系管理员');
        }

        if ($_POST['order_type'] == 'appoint') {
            $save_pay_id = D($order_table)->where(array("order_id" => $order_id))->setField('orderids', $orderid);
        } else {
            $save_pay_id = D($order_table)->where(array("order_id" => $order_id))->setField('orderid', $orderid);
        }

        if ($order_info['is_foodshop_new']) {
            // 新餐饮 支付手续费
            $pay_method = D('Merchant_store')->get_pay_method($order_info['store_id']);
        }

        // 堂扫税率
        if ($order_info['is_foodshop_new'] && $order_info['tax_rate_price'] > 0) {
            $pay_money = getFormatNumber($pay_money + $order_info['tax_rate_price']);
        }

        //支付前保存在线支付金额
        $_service = 0;//支付手续费
        if ($pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1') {
            $service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
            $_service = $pay_money * ($service_charge / 100);
        }
        $pay_rate = 0;
        if ($pay_method[$_POST['pay_type']]['config']['rates'] > 0 && !$order_info['is_foodshop_new']) {
            $pay_rate = $pay_method[$_POST['pay_type']]['config']['rates'];
        }
        $pay_money = round(($pay_money + $_service) * 100) / 100;//保留两位小数，但不四舍五入
        $payment_money_date['payment_money'] = $pay_money;
        $payment_money_date['pay_type'] = $_POST['pay_type'];
        $payment_money_date['order_type'] = $_POST['order_type'];
        $payment_money_date['order_id'] = $_POST['order_id'];
        $payment_money_date['orderid'] = $orderid;
        $payment_money_date['addtime'] = time();
        $payment_money_date['is_mobile_pay'] = $this->is_app_browser ? 2 : ($this->is_wxapp_browser ? 3 : 1);
        $payment_money_date['service_charge'] = round($_service * 100) / 100;//支付手续费
        $payment_money_date['pay_rate'] = round($pay_rate, 2);

        M('Payment_money_order')->add($payment_money_date);

        if ($_POST['pay_type'] == 'plat') {
            // 手续费记入plat_order表
            M('Plat_order')->where(array('order_id' => $_POST['order_id']))->save(array('service_charge' => $payment_money_date['service_charge']));
        }

        if ($save_pay_id === false) {
            $this->error_tips('更新订单信息失败，请联系管理员2');
        } else {
            $order_info['order_id'] = $orderid;
        }

        //支付前,加上费率处理
        if ($pay_method[$_POST['pay_type']]['config']['rates'] > 0 && !$order_info['is_foodshop_new']) {

            if ($order_info['book_id'] == 0 || empty($order_info['book_id'])) {
                $pay_rate = $pay_method[$_POST['pay_type']]['config']['rates'];
                $pay_money = $pay_money * $pay_rate;
            }
        }

        //xky新空云app中 调用新空云配置
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'xkyapp') !== false) {
            M('Payment_money_order')->where(array('orderid' => $order_info['order_id'], 'order_type' => $_POST['order_type']))->data(array('pay_method' => 'xkypay', 'orderNo' => $order_info['order_id'], 'is_own' => 0))->save();
            $this->assign("goods_name", $order_info['order_name']);
            $this->assign("total_fee", $pay_money);
            $this->assign("pay_type", ($_POST['pay_type'] == 'weixin' ? '0' : ($_POST['pay_type'] == 'alipayh5' ? '1' : '-1')));
            $this->assign("order_no", $_POST['order_type'] . '_' . $order_info['order_id']);
            $this->assign("callback_url", C('config.site_url') . '/source/xkypay_page_notice.php');
            $this->assign("redirect_url", C('config.site_url') . '/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=xkypay&order_type=' . $_POST['order_type'] . '&order_id=' . $order_info['order_id'] . '&source=1');
            $this->display("xkypay");
            exit;
        }

        //nmgpay
        if ($this->config['nmgzhcs_appid'] && $this->user_session['nmgzhcs_openid'] && $_POST['order_type'] != 'recharge') {
            if (!$now_merchant['shop_openid']) {
                $now_merchant['shop_openid'] = '-1';//虚拟商户
            }
            $order_info['uid'] = $this->user_session['uid'];
            $order_info['merchant'] = $now_merchant;
            import("@.ORG.pay.Nmgpay");
            $pay_moodel = new nmgpay($order_info, $pay_money, $_POST['pay_type'], $pay_method[$_POST['pay_type']]['config'], $this->user_session, 1);
            $is_nmgapp = strpos(strtolower($_SERVER['HTTP_USER_AGENT']), '_jianxin_') !== false ? 1 : 0;//是否在内蒙古app中
            $nmg_result = $pay_moodel->mobile_pay($pay_money, $is_nmgapp);
            if ($nmg_result['error']) {
                $this->error_tips($nmg_result['msg']);
            } else {
                $this->assign("order_code", $nmg_result['code']);
                $this->assign("shop_openid", $now_merchant['shop_openid']);
                $this->assign("redirect", $nmg_result['redirect']);
                $this->display("nmgpay");
                exit;
            }
        }

        //四川农信
        if ($order_info['business_type'] == 'house_village_pay' || $order_info['business_type'] == 'house_village_express' || $order_info['business_type'] == 'house_village_pay_cashier') {
            $scrcu_mer_no = D('Chinaums')->getVillageScrcu($order_info['order_info']['village_id']);

        } else {
            $now_merchant = M("Merchant")->where(['mer_id' => $order_info['mer_id']])->find();
            $scrcu_mer_no = $now_merchant['scrcu_mer_no'];
        }

        //利楚扫呗(目前仅支持外卖)
        if(C("config.lichu_open") == '1' && ($_POST['pay_type'] == 'weixin' || $_POST['pay_type'] == 'weixinh5') && in_array($_POST['order_type'],['shop','store']) && C("config.lichu_merchant") && in_array($order_info['mer_id'],explode(',',C("config.lichu_merchant")))){
            //获取利楚商户参数
            $merchant_info = M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->field('lichu_merchant_no,lichu_terminal_id,lichu_token')->find();
            $lichu_config = [
                'merchant_no'=>$merchant_info['lichu_merchant_no'],
                'terminal_id'=>$merchant_info['lichu_terminal_id'],
                'token'=>$merchant_info['lichu_token']
            ];
            $source = 'merchant';//订单入账对象，默认是商户
            if(!$merchant_info['lichu_merchant_no'] || !$merchant_info['lichu_terminal_id'] || !$merchant_info['lichu_token']){
                //商户未配置，直接获取平台利楚参数
                $lichu_config = [
                    'merchant_no'=>C('config.lichu_merchant_no'),
                    'terminal_id'=>C('config.lichu_terminal_id'),
                    'token'=>C('config.lichu_token')
                ];
                $source = 'platform';
            }
            if(!$lichu_config['merchant_no'] || !$lichu_config['terminal_id'] || !$lichu_config['token']){
                $this->error_tips('后台利楚参数未配置，无法支付');
            }
            $lichu_config['pay_money'] = $pay_money;
            $lichu_config['openid'] = $this->user_session['openid'];
            $lichu_config['wxapp_openid'] = $now_user['wxapp_openid']??'';
            $lichu_config['notify_url'] =C('config.site_url') . '/wap.php?c=Lichu&a=notify&order_type='.$_POST['order_type'].'&order_id='.$order_info['order_id'];
            require_once $_SERVER['DOCUMENT_ROOT'] . "/v20/extend/pay/LichuPay.php";
            $pay_moodel = new LichuPay();
            if($_POST['order_type']=='store'){
                $order_info['real_orderid'] = $order_info['order_id'];
                $order_info['pay_money'] = $order_info['order_total_money'];
            }
            $res = $pay_moodel->pay($lichu_config,$order_info);
            if ($res['error'] == '0' && $res['result'] != '') {
                $third_plat_data = [
                    'source'=>$source,
                    'token'=>$lichu_config['token'],
                    'merchant_no'=>$lichu_config['merchant_no'],
                    'terminal_id'=>$lichu_config['terminal_id'],
                    'total_fee'=>$pay_money*100,
                    'out_trade_no'=>$res['result']['out_trade_no']
                ];
                M('Payment_money_order')->where(array('orderid'=>$orderid,'order_type'=>$_POST['order_type']))->data(array('pay_method'=>'lichu','orderNo'=>$res['result']['out_trade_no'],'third_plat_data'=>json_encode($third_plat_data)))->save();//保存支付通道
                $this->assign('form',$res['result']);
                $this->display();
            } else {
                $this->error_tips($res['msg']);
            }
        }
        
        if(C("config.pay_scrcu_cash_open") == '1' && ($_POST['pay_type'] == 'weixin' || $_POST['pay_type'] == 'weixinh5')){
            fdump('weixin','scrcu_weixin');
            import("@.ORG.pay.Scrcu");
            $pay_moodel = new Scrcu();

            if ($this->is_weixin_browser && !$this->is_wxapp_browser) {
                $res = $pay_moodel->wechatPay($_POST['order_type'], $orderid, $pay_money, $order_info['order_name'], $this->user_session['openid'], $scrcu_mer_no);
            } elseif ($this->is_wxapp_browser) {
                $res = $pay_moodel->wechatminiPay($_POST['order_type'], $orderid, $pay_money, $order_info['order_name'], $this->user_session['wxapp_openid'], $scrcu_mer_no);

            }

            if ($res['error'] == '0') {
                redirect($res['redirectUrl']);
                exit;
            } else {
                $this->error_tips($res['msg']);
            }
        }

        if ($this->config['nmgzhcs_appid'] && $this->user_session['nmgzhcs_openid'] && $_POST['order_type'] == 'recharge') {
            redirect($this->config['nmgzhcs_base_url'] . '/wxmember/recharge');
        }
        fdump_api([
            'msg' => '支付信息',
            'order_id' => $orderid,
            'pay_money' => $pay_money,
            'extra' => array_merge($order_info, $_POST)
        ],'pay/pay_info', true);
        
        $orderTypeArr = ['shop','group','appoint', 'dining'];
        if($this->config['mallbook_pay_switch'] == 1 && $_POST['pay_type'] == 'weixin' && in_array($_POST['order_type'], $orderTypeArr)){//mallbook通道支付
            try{
                import('@.ORG.pay.Mallbookpay.MallbookPayService');
                $agent = $this->getPayAgent();
                $pay = new MallbookPayService($agent);
                $pay->config['pay_type'] = $_POST['pay_type'];
                if($this->DEVICE_ID == 'wxapp'){
                    $pay->config['openid'] = $_SESSION['wxapp_openid'] ?: ($now_user['wxapp_openid'] ?: $now_user['openid']);
                }else if($_POST['app_type'] == 'packapp'){
                    $pay->config['openid'] = $_SESSION['openid'] ?: $now_user['openid'];
                }
                $extra = array_merge($order_info, $_POST);
                $wxPayParams = $pay->pay($orderid, $pay_money, $extra);
                fdump_api([
                    'msg' => 'mallbook支付',
                    'wxPayParams' => $wxPayParams
                ],'mallbookOldPay/pay', true);
                
                $pay_arr['pay_money'] = $pay_money;
                $pay_arr['weixin_param'] = $wxPayParams;
                $pay_arr['error'] = 0;
                $pay_arr['orderid'] = $orderid;
                $this->returnCode(0,$pay_arr);
            } catch(\Exception $e){
                $this->returnCode(1222,'',$e->getMessage());
            }
        }
        if (strpos($_POST['pay_type'], 'allinyun') !== false || ($this->user_session['openid'] && $_POST['pay_type'] == 'weixin' && $this->config['open_allinyun'] == 1)) {

            import('@.ORG.AccountDeposit.AccountDeposit');
            $deposit = new AccountDeposit('Allinyun');
            $allyun = $deposit->getDeposit();
            //$allinyun_user = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();
            $allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid']);
            if ($order_info['mer_id']) {
                $allinyun_mer = D('Deposit')->get_merchant_info($order_info['mer_id'], 1);
                $order_info['recieverId'] = $allinyun_mer['bizUserId'];
            }


            if ($allinyun_user && ($allinyun_mer['status'] == 1 || $_POST['order_type'] == 'recharge')) {
                if (empty($allinyun_user['phone'])) {
                    $this->error_tips('您的云商通账号还未绑定手机无法完成支付，请先绑定手机号码', U('SetAccountDeposit/bindphone'));
                }
                $allyun->setUser($allinyun_user);
                $pay_type = explode('_', $_POST['pay_type']);
                $order_info['payerId'] = $allinyun_user['bizUserId'];
                $order_info['openid'] = $this->user_session['openid'];
                if ($_POST['pay_type'] == 'weixin') {
                    $order_info['pay_type'] = 'weixin';
                } else {
                    $order_info['pay_type'] = $pay_type[1];
                }
                $order_info['source'] = 1;
                $order_info['platform'] = $_POST['platform'] ? $_POST['platform'] : 'wap';


                $res = $allyun->Pay($_POST['order_type'], $orderid, $pay_money, $order_info);
                if ($res['status'] == 'OK' && $res['signedResult']['payInfo'] != '') {
                    // dump($res['signedResult']['payInfo']);
                    $this->assign('url', $res['signedResult']['payInfo']);
                    $this->display();
                } else {
                    $this->error_tips($res['message']);
                }
                die;
            }

        }

        //superpay  hark对接的一种支付(只对接支付宝) 餐饮堂扫业务的
        if ($order_info['store_id'] && $_POST['order_type'] == 'plat' && $_POST['pay_type'] == 'alipay') {
            $merchant_store_pay_rate = M("Merchant_store_pay_rate")->where(['store_id' => $order_info['store_id']])->find();
            $is_mini = strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'alipayclient') !== false && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'miniprogram') !== false ? 1 : 0;//是否在支付宝小程序中
            if ($merchant_store_pay_rate['is_open'] == '1' && !empty($merchant_store_pay_rate['superpay_mchid']) && !empty($merchant_store_pay_rate['superpay_key']) && !$is_mini) {
                $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
                $order_info['order_total_money'] = $pay_money;
                $lowfeepay = new LowFeePay('superpay');
                $go_pay_param = $lowfeepay->pay($order_info, $merchant_store_pay_rate);
                redirect($go_pay_param['redirctUrl']);
            } elseif ($is_mini) {
                if ($_POST['order_type'] == 'plat') {
                    M("Plat_order")->where(['order_id' => $_POST['order_id']])->data(['is_mobile_pay' => 3, 'pay_type' => 'alipay'])->save();
                    M("Payment_money_order")->where(['order_id' => $_POST['order_id']])->data(['is_mobile_pay' => 3])->save();
                }
                if ($merchant_store_pay_rate['is_open'] == '1' && !empty($merchant_store_pay_rate['superpay_mchid']) && !empty($merchant_store_pay_rate['superpay_key']) && !empty($merchant_store_pay_rate['superpay_receive_id']) && !empty($merchant_store_pay_rate['superpay_storename'])) {
                    if (empty($now_user['alipay_uid'])) {
                        $this->error_tips('支付宝用户ID为空！');
                    }
                    $order_info['alipay_uid'] = $now_user['alipay_uid'];
                    $noline_orderid = $order_info['order_id'];
                    $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
                    $order_info['order_total_money'] = $pay_money;
                    $order_info['pay_type'] = 'alipay_mini';//目前支持支付宝小程序

                    $lowfeepay = new LowFeePay('superpay');
                    $go_pay_param = $lowfeepay->pay($order_info, $merchant_store_pay_rate);
                    if ($go_pay_param['error']) {
                        $this->error_tips($go_pay_param['msg']);
                    }
                    $this->assign("alipay_order_no", $go_pay_param['param']['trade_no']);
                    $this->assign("order_url", urlencode(C('config.site_url') . '/packapp/foodshop/#/PaySucess?order_id=' . $noline_orderid));
                    $this->display("superpay_alipay");
                    exit;
                } elseif ($merchant_store_pay_rate['is_open_omipay'] == '1' && !empty($merchant_store_pay_rate['omipay_mnumber']) && !empty($merchant_store_pay_rate['omipay_key'])) {
                    if (empty($now_user['alipay_uid'])) {
                        $this->error_tips('支付宝用户ID为空！');
                    }
                    $order_info['alipay_uid'] = $now_user['alipay_uid'];
                    $noline_orderid = $order_info['order_id'];
                    $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
                    $order_info['order_total_money'] = $pay_money;

                    $lowfeepay = new LowFeePay('omipay');
                    $go_pay_param = $lowfeepay->pay($order_info, $merchant_store_pay_rate);
                    if ($go_pay_param['error']) {
                        $this->error_tips($go_pay_param['msg']);
                    }
                    $this->assign("alipay_order_no", $go_pay_param['param']);
                    $this->assign("order_url", urlencode(C('config.site_url') . '/packapp/foodshop/#/PaySucess?order_id=' . $noline_orderid));
                    $this->display("superpay_alipay");
                    exit;
                } elseif ($merchant_store_pay_rate['is_open_royalpay'] == '1' && !empty($merchant_store_pay_rate['royalpay_mid']) && !empty($merchant_store_pay_rate['royalpay_key'])) {
                    if (empty($now_user['alipay_uid'])) {
                        $this->error_tips('支付宝用户ID为空！');
                    }
                    $order_info['alipay_uid'] = $now_user['alipay_uid'];
                    $noline_orderid = $order_info['order_id'];
                    $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
                    $order_info['order_total_money'] = $pay_money;

                    $lowfeepay = new LowFeePay('royalpay');
                    $go_pay_param = $lowfeepay->pay($order_info, $merchant_store_pay_rate);
                    if ($go_pay_param['error']) {
                        $this->error_tips($go_pay_param['msg']);
                    }
                    $this->assign("alipay_order_no", $go_pay_param['param']);
                    $this->assign("order_url", urlencode(C('config.site_url') . '/packapp/foodshop/#/PaySucess?order_id=' . $noline_orderid));
                    $this->display("superpay_alipay");
                    exit;
                } elseif ($merchant_store_pay_rate['is_open_paylinx'] == '1' && !empty($merchant_store_pay_rate['paylinx_mid']) && !empty($merchant_store_pay_rate['paylinx_storeid']) && !empty($merchant_store_pay_rate['paylinx_key'])) {
                    if (empty($now_user['alipay_uid'])) {
                        $this->error_tips('支付宝用户ID为空！');
                    }
                    $order_info['alipay_uid'] = $now_user['alipay_uid'];
                    $noline_orderid = $order_info['order_id'];
                    $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
                    $order_info['order_total_money'] = $pay_money;
                    $order_info['pay_type'] = 'alipay_mini';

                    $lowfeepay = new LowFeePay('paylinx');
                    $go_pay_param = $lowfeepay->pay($order_info, $merchant_store_pay_rate);
                    if ($go_pay_param['error']) {
                        $this->error_tips($go_pay_param['msg']);
                    }
                    $this->assign("alipay_order_no", $go_pay_param['param']);
                    $this->assign("order_url", urlencode(C('config.site_url') . '/packapp/foodshop/#/PaySucess?order_id=' . $noline_orderid));
                    $this->display("superpay_alipay");
                    exit;
                } else {
                    $this->error_tips('店铺支付信息配置出错！');
                }
            } else {
                $this->error_tips('店铺支付信息配置出错！');
            }
        }

        //威富通低费率
        if ($this->config['open_wftpay'] == 1 && ($_POST['pay_type'] == 'weixin' || $_POST['pay_type'] == 'alipay' || $_POST['pay_type'] == 'alipayh5')) {
            $order_info['alipay_uid'] = $now_user['alipay_uid'];
            $order_info['openid'] = $now_user['openid'];
            $_POST['pay_type'] = strtolower($_POST['pay_type']);
            if ($_POST['pay_type'] == 'weixin') {
                $_POST['pay_type'] = 'weixin_wechat';    //公众号环境
            }
            if ($_POST['pay_type'] == 'weixinh5') {
                $_POST['pay_type'] = 'weixin_h5';   //H5
            }
            if ($_POST['pay_type'] == 'alipay') {
                $_POST['pay_type'] = 'alipayh5';
            }
            $order_info['pay_type'] = $_POST['pay_type'];
            $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];

            $order_info['order_total_money'] = $pay_money;

            $lowfeepay = new LowFeePay('wftpay');
            if ($mer_id) {
                $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id' => $mer_id))->find();
                if (!empty($mer_juhe)) {
                    $lowfeepay->userId = $mer_juhe['userid'];
                    $order_info['is_own'] = 1;
                    $order_info['order_id'] = $order_info['order_id'] . '_1';
                }
                $go_pay_param = $lowfeepay->pay($order_info);
            } else {
                $go_pay_param = $lowfeepay->pay($order_info);
            }

            if (($_POST['pay_type'] == 'alipay' || $_POST['pay_type'] == 'alipayh5') && $go_pay_param['status'] == 1 && $go_pay_param['info'] != '') {
                redirect($go_pay_param['info']);
            }
        } else if ($this->config['pay_paylinx_open'] == 1 && ($_POST['pay_type'] == 'weixin')) {
            $_POST['pay_type'] = strtolower($_POST['pay_type']);
            if ($_POST['pay_type'] == 'weixin') {
                $_POST['pay_type'] = 'weixin_wechat';    //公众号支付
                $order_info['pay_type'] = $_POST['pay_type'];
                $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
                $order_info['order_total_money'] = $pay_money;
                $lowfeepay = new LowFeePay('paylinx');
                $go_pay_param = $lowfeepay->pay($order_info);

                if ($go_pay_param['error']) {
                    $this->error_tips($go_pay_param['msg']);
                }
                $arr = [
                    'redirctUrl' => $go_pay_param['redirctUrl']
                ];
                $arr['error'] = 0;
                echo json_encode($arr);
                die;
            }
        } else if (($_POST['order_type'] != 'recharge' || $this->config['juhe_uid'] != '') && $this->config['open_juhepay'] == 1) {
            $order_info['openid'] = $now_user['openid'];
            $order_info['pay_type'] = ($_POST['pay_type'] == 'alipay' || $_POST['pay_type'] == 'alipayh5') ? 'alipay_scan' : $_POST['pay_type'];
            $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];

            $order_info['order_total_money'] = $pay_money;

            $lowfeepay = new LowFeePay('juhepay');
            if ($mer_id) {
                $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id' => $mer_id))->find();
                if (!empty($mer_juhe)) {
                    $lowfeepay->userId = $mer_juhe['userid'];
                    $order_info['is_own'] = 1;
                    $order_info['order_id'] = $order_info['order_id'] . '_1';
                }
                $go_pay_param = $lowfeepay->pay($order_info);
            } else {
                $go_pay_param = $lowfeepay->pay($order_info);
            }
        }
        else if($this->config['open_weikebaba'] == 1 && $this->config['weikebaba_system_user_id']) {//微客支付接口
            $order_info['openid'] = $now_user['openid'];
            $order_info['alipay_uid'] = $now_user['alipay_uid'];
            $order_info['pay_type'] = ($_POST['pay_type'] == 'alipay' || $_POST['pay_type'] == 'alipayh5') ? 'alipay' : $_POST['pay_type'];
            $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];

            $order_info['order_total_money'] = $pay_money;

            $lowfeepay = new LowFeePay('weikebaba');
            if ($mer_id) {
                $mer_juhe = M('Weikebaba')->where(array('mer_id' => $mer_id))->find();
                if (!empty($mer_juhe) && !empty($mer_juhe['user_id']) && !empty($mer_juhe['key'])) {
                    $lowfeepay->user_id = $mer_juhe['user_id'];
                    $lowfeepay->key = $mer_juhe['key'];
                    $lowfeepay->wxappid = $mer_juhe['wxappid'];
                    $lowfeepay->wxappid_mini = $mer_juhe['wxappid_mini'];
                    $order_info['is_own'] = 1;
                    $order_info['order_id'] = $order_info['order_id'] . '_1';
                }
            }
            $go_pay_param = $lowfeepay->pay($order_info);
        }
        else if($this->config['open_pufa_pay'] == 1){
            $order_info['openid'] = $now_user['openid'];
            $order_info['pay_type'] = ($_POST['pay_type'] == 'alipay' || $_POST['pay_type'] == 'alipayh5') ? 'alipay_scan' : $_POST['pay_type'];
            $order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];

            $order_info['order_total_money'] = $pay_money;

            $lowfeepay = new LowFeePay('pufapay');

            $go_pay_param = $lowfeepay->pay($order_info);


            if (($_POST['pay_type'] == 'alipay' || $_POST['pay_type'] == 'alipayh5') && $go_pay_param['status'] == 1 && $go_pay_param['info'] != '') {
                redirect($go_pay_param['info']);
            }
        }
        elseif(strpos($_POST['pay_type'],'xiantao') !== false){// 定制-智慧仙桃支付
            try{
                import('@.ORG.pay.XianTaoCard');
                $pay = new XianTaoCard();

                // 账户检查
                $checkUser = $pay->checkUser($now_user['phone'], '');

                // 查询密码

                $wherePwd = [
                    'order_type' => $_POST['order_type'],
                    'order_id' => $_POST['order_id'],
                    'uid' => $this->user_session['uid'],
                    'add_time' => ['gt', time() - 60],
                ];
                $xiantao_password = M('Xiantao_pay_pwd')->where($wherePwd)->order('id DESC')->getField('password');
                $xiantao_password = ticket::get($xiantao_password, 'xiantaopwd', true);
                $xiantao_password = $xiantao_password['uid'] ?? '';
                if (empty($xiantao_password)) {

                    // 支付检查
                    $checkPay = $pay->checkPay($pay_money, $_POST['pay_type'], $checkUser);
                    // 支付
                    $go_pay_param = $pay->pay($_POST['order_type'] . '_' . $order_info['order_id'], $pay_money, '', $_POST['pay_type'], $checkUser);

                } else {// 支付
                    $go_pay_param = $pay->pay($_POST['order_type'] . '_' . $order_info['order_id'], $pay_money, $xiantao_password, $_POST['pay_type'], $checkUser);
                }

                if ($go_pay_param['data']['payInfo']['paymentUrl']) {// 跳转第三方支付
                    redirect($go_pay_param['data']['payInfo']['paymentUrl']);
                } else {
                    redirect(C('config.site_url') . '/wap.php?g=Wap&c=Pay&a=xiantao_return&order_type=' . $order_info['order_type'] . '&order_id=' . $order_info['order_id'] . '&order_no=' . $go_pay_param['data']['orderId']);
                }

            } catch (Exception $e) {
                if ($e->getCode() == 1225) {// 未授权 去授权
                    redirect(C('config.site_url') . '/wap.php?g=Wap&c=XianTaoPay&a=inputPwd&type=authorization&pay_param=' . $_GET['pay_param']);
                } elseif ($e->getCode() == 2218) {// 密码错误
                    $wherePwd = [
                        'order_type' => $_POST['order_type'],
                        'order_id' => $_POST['order_id'],
                        'uid' => $this->user_session['uid'],
                    ];
                    M('Xiantao_pay_pwd')->where($wherePwd)->setField('password', '');

                    $this->error_tips('支付密码错误', C('config.site_url') . '/wap.php?g=Wap&c=XianTaoPay&a=inputPwd&type=pay_pwd&pay_money=' . $pay_money . '&pay_param=' . $_GET['pay_param']);
                } elseif ($e->getCode() == 2216) {// 需要输入密码
                    redirect(C('config.site_url') . '/wap.php?g=Wap&c=XianTaoPay&a=inputPwd&type=pay_pwd&pay_money=' . $pay_money . '&pay_param=' . $_GET['pay_param']);
                }

                if (in_array($e->getCode(), [1202, 2203, 2233])) {
                    $downloadUrl = 'https://www.renrbang.com/wmxt/download/zhxtdownload.html';
                }

                $this->error_tips($e->getMessage(), $downloadUrl);
            }
        } else if ($_POST['pay_type'] == 'ebankpay') {
            $pay_class_name = 'Ebankpay';
            $pay_method = D('Config')->get_pay_method(0, 0, true);
            if (empty($pay_method[$_POST['pay_type']])) {
                $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
            }
            $import_result = import('@.ORG.pay.' . $pay_class_name);
            if (empty($import_result)) {
                $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
            }

            $env = D('Config')->get_this_env(I('device_id') ?? '', I('app_type') ?? 0);
            $pay_class = new $pay_class_name($order_info, $pay_money, $_POST['pay_type'], $pay_method[$_POST['pay_type']]['config'], $this->user_session, 1);
            fdump($env,'go_pay_ebankpay',1);
            fdump($pay_class_name,'go_pay_ebankpay',1);
            $go_pay_param = $pay_class->pay($env);
        }else if($_POST['pay_type'] == 'farmersbankpay'){
            $importResult = import('@.ORG.pay.Farmersbankpay');
            if (empty($importResult)) {
                $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
            }
            $payClass = new Farmersbankpay($order_info, $pay_money, $_POST['pay_type'], $pay_method[$_POST['pay_type']]['config'], $this->user_session, 1);
            fdump(['farmersBankPay' => $payClass],'go_pay',1);
            $go_pay_param = $payClass->pay();
        }
        else if ($_POST['pay_type'] == 'hqpay_wx' || $_POST['pay_type'] == 'hqpay_al') {
            if (empty($pay_method[$_POST['pay_type']])) {
                $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
            }
            $pay_class_name = 'Hqpay';
            $import_result = import('@.ORG.pay.' . $pay_class_name);
            if (empty($import_result)) {
                $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
            }
        } else {
            $env = $pay_method[$_POST['pay_type']]['config']['env'];
            if ($env == 'app') {
                if ($_POST['pay_type'] == 'hqpay_wx') {
                    $env = 'wxApp';
                } else if ($_POST['pay_type'] == 'hqpay_al') {
                    $env = 'alApp';
                }
            }
            if ($env == 'pc') {
                if ($_POST['pay_type'] == 'hqpay_wx') {
                    $env = 'wxPc';
                } else if ($_POST['pay_type'] == 'hqpay_al') {
                    $env = 'alPc';
                }
            }
            if ($env == 'wap') {
                if ($_POST['pay_type'] == 'hqpay_wx') {
                    $env = 'wxWap';
                } else if ($_POST['pay_type'] == 'hqpay_al') {
                    $env = 'alWap';
                }
            }

            if (empty($pay_method)) {
                $this->error_tips('系统管理员没开启任一一种支付方式！');
            }
            if (empty($pay_method[$_POST['pay_type']])) {
                fdump_api($_POST['pay_type'],'0000',true);
                fdump_api($pay_method,'11111',true);
                $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
            }

            $pay_class_name = ucfirst($_POST['pay_type']);
            $import_result = import('@.ORG.pay.' . $pay_class_name);

            if (empty($import_result)) {
                $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
            }


            //四川农信收银台
            if(C("config.pay_scrcu_cash_open") == '1' && $_POST['pay_type'] == 'scrcu_cash'){
                // if(empty($scrcu_mer_no)){
                //     $this->error_tips('商家未设置商户号');
                // }
                $order_info['scrcu_mer_no'] = $scrcu_mer_no;
            }
            $is_mobile = $this->is_app_browser ? 2 : ($this->is_wxapp_browser ? 3 : 1);
            //这里增加支付金额的手续费 add by lumin
            $pay_class = new $pay_class_name($order_info, $pay_money, $_POST['pay_type'], $pay_method[$_POST['pay_type']]['config'], $this->user_session, $is_mobile);
            fdump($pay_class,'go_pay',1);
            $go_pay_param = $pay_class->pay($env);
        }


        //$pay_method['allinpay']['config']['is_own'] = 1;

        if (empty($go_pay_param['error'])) {
            if (!empty($go_pay_param['url'])) {
                $this->assign('url', $go_pay_param['url']);
                $this->display();
            } else if (!empty($go_pay_param['form'])) {
                $this->assign('form', $go_pay_param['form']);
                $this->display();
            } else if (!empty($go_pay_param['qrcode'])) {
                $this->assign('qrcode', $go_pay_param['qrcode']);
                $this->display('go_pay_qrcode');
            } else if (!empty($go_pay_param['weixin_param'])) {
                if ($pay_method['weixin']['config']['is_own']) {
                    C('open_authorize_wxpay', true);
                    $share = new WechatShare($this->config, $_SESSION['openid']);
                    $this->hideScript = $share->gethideOptionMenu($mer_id);
                    $arr['hidScript'] = $this->hideScript;
                    $redirctUrl = C('config.site_url') . '/' . $this->indep_house . '?g=Wap&c=Pay&a=weixin_back&order_type=' . $order_info['order_type'] . '&order_id=' . $order_info['order_id'] . '&own_mer_id=' . $order_info['mer_id'];
                } else {
                    $redirctUrl = C('config.site_url') . '/' . $this->indep_house . '?g=Wap&c=Pay&a=weixin_back&order_type=' . $order_info['order_type'] . '&order_id=' . $order_info['order_id'];
                }
                $arr['redirctUrl'] = $go_pay_param['redirctUrl'] ? $go_pay_param['redirctUrl'] : $redirctUrl;
                $arr['pay_money'] = $pay_money;
                $arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
                $arr['error'] = 0;
                $this->header_json();
                echo json_encode($arr);
                die;
            } else if (!empty($go_pay_param['alipay_param'])) {
                if ($pay_method['weixin']['config']['is_own']) {
                    $redirctUrl = C('config.site_url') . '/' . $this->indep_house . '?g=Wap&c=Pay&a=alipay_return&out_trade_no=' . ( stripos($order_info['order_id'],'_') === false ? $order_info['order_type'] . '_' : '') . $order_info['order_id'] . '_' . $pay_money . '&own_mer_id=' . $order_info['mer_id'];
                } else {
                    $redirctUrl = C('config.site_url') . '/' . $this->indep_house . '?g=Wap&c=Pay&a=alipay_return&out_trade_no=' . ( stripos($order_info['order_id'],'_') === false ? $order_info['order_type'] . '_' : '') . $order_info['order_id'] . '_' . $pay_money;
                }
                $arr['redirctUrl'] = $go_pay_param['redirctUrl'] ? $go_pay_param['redirctUrl'] : $redirctUrl;
                $arr['pay_money'] = $pay_money;
                $arr['alipay_param'] = json_decode($go_pay_param['alipay_param'],true);
                $arr['error'] = 0;

                if ($this->is_alipay_browser && $_POST['pay_type'] == 'alipayh5') {
                    $this->assign('tradeNO', $arr['alipay_param']['tradeNO']);
                    $this->assign('redirctUrl', $arr['redirctUrl']);
                    $this->display('alipayH5');
                    return false;
                }
                echo json_encode($arr);
                die;
            } else {
                $this->error_tips('调用支付发生错误，请重试。');
            }
        } else {
            $this->error_tips($go_pay_param['msg']);
        }

    }
    public function alipay()
    {
        $is_pay_for_others = isset($_GET['is_pay_for_others']) ? $_GET['is_pay_for_others'] : 0;
        if ($is_pay_for_others && in_array($_GET['order_type'], ['shop', 'mall'])) {
            $this->user_session['uid'] = D('Shop_order')->where(['order_id' => intval($_GET['order_id'])])->getField('uid');
        }

        if($_GET['ticket']!=''){
            $_GET['use_merchant_balance'] = $_GET['use_merchant_money'];
            $_GET['use_balance'] = $_GET['use_balance_money'];
        }

        $need_user = true;
        if($_GET['order_type'] == 'store'){
            $need_user = false;
        }

        if($need_user && empty($this->user_session)){
            $this->error_tips('请先进行登录！',U('Login/index'));
        }
        if(!in_array($_GET['order_type'],array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','mall','plat','balance-appoint','yuedan','mcard'))){
            $this->error_tips('订单来源无法识别，请重试。');
        }
        //平台E卡
        $card_password=I('card_password','','trim');
        $ecard_info=array();
        if(!empty($card_password)){
            $ecard_result=D('Ecard_coupon')->check_ecard_coupon($card_password,0,0);
            if($ecard_result['error']==1){
                $this->error_tips($ecard_result['msg']);
            }else{
                $ecard_info=$ecard_result['data'];
            }
        }
        switch($_GET['order_type']){
            case 'group':
                $now_order = D('Group_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
                break;
            case 'meal':
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order = D('Meal_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']), false,  $_GET['order_type']);
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
                break;
            case 'recharge':
                $now_order = D('User_recharge_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
                break;
            case 'appoint':
                $now_order = D('Appoint_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
                break;
            case 'yuedan':
                $now_order = D('Yuedan_service_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
                break;
            case 'waimai':
                $now_order = D('Waimai_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']),false,$_GET['order_type']);
                if ($now_order['order_info']['pay_type'] !== $_GET['pay_type']) {
                    $this->error_tips('非法的订单');
                }
                break;
            case 'wxapp':
                $now_order = D('Wxapp_order')->get_pay_order(0,intval($_GET['order_id']));
                break;
            case 'store':
                $now_order = D('Store_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
                break;
            case 'shop':
            case 'mall':
                $now_order = D('Shop_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
                break;
            case 'plat':
                $now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
                break;
            case 'balance-appoint':
                $now_order = D('Appoint_order')->get_pay_balace_order($this->user_session['uid'],intval($_GET['order_id']));
                break;
            case 'mcard':
                $now_order = D('Membership_card_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
                break;

            default:
                $this->error_tips('非法的订单');
        }

        if($now_order['error'] == 1){
            $this->error_tips($now_order['msg'],$now_order['url']);
        }
        $order_info = $now_order['order_info'];

        if($order_info['mer_id']){
            $now_merchant = D('Merchant')->get_info($order_info['mer_id']);
            if($now_merchant['status']!=1){
                $this->error_tips('该商家状态异常，无法支付');
            }
        }
        
        $use_discount = true;
        if($this->config['open_score_discount'] && empty($_GET['ticket'])){
            switch($now_merchant['user_discount_type']){
                case 1 :   //优惠
                    $order_info['score_discount_type'] = 2;
                    break;
                case 2 :   //积分
                    $use_discount =false;
                    $order_info['score_discount_type'] = 1;
                    break;
                case 3 :   //2选1
                    $use_discount =  $_GET['discount_type'] == 'score'?false:true;
                    break;
            }
            if($_GET['discount_type']=="score"){
                $order_info['score_discount_type'] = 1;
            }else if($_GET['discount_type']=="coupon"){
                $order_info['score_discount_type'] = 2;
            }

        }

        if($_GET['order_type'] != 'recharge'  && $_GET['order_type'] != 'weidian'  && (empty($order_info['business_type'])||$order_info['business_type']!='card_new_recharge')) {
            //优惠券
            if ($use_discount && (!isset($order_info['discount_status']) || $order_info['discount_status'])) {
                if ((!empty($_GET['card_id'])&&$_GET['card_id']==$_SESSION['merc_id'])||($_GET['ticket']&&$_GET['card_id']&&$_GET['use_mer_coupon'])) {
                    $card_coupon = D('Card_new_coupon')->get_coupon_by_id($_GET['card_id']);
                    $now_coupon['card_price'] = $card_coupon['price'];
                    $now_coupon['merc_id'] = $card_coupon['id'];
                    unset($_SESSION['merc_id']);
                }
                if ((!empty($_GET['coupon_id'])&&$_GET['coupon_id']==$_SESSION['sysc_id'])||($_GET['ticket']&&$_GET['coupon_id']&&$_GET['use_sys_coupon'])) {
                    $system_coupon = D('System_coupon')->get_coupon_by_id($_GET['coupon_id']);
                    $now_coupon['coupon_price'] = $system_coupon['price'];
                    $now_coupon['sysc_id'] = $system_coupon['id'];
                    if($now_coupon['sysc_id']){
                        //判断这个优惠券是不是当前用户的  hsby365的bug
                        if($system_coupon['uid'] != $this->user_session['uid']){
                            $this->error_tips('当前订单优惠券异常（可能是优惠券串号了，请尝试重新支付或更换设备支付。）!');
                        }
                    }
                    unset($_SESSION['sysc_id']);
                }
            }
            if($order_info['mer_id'] ){
                $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'],$order_info['mer_id']);
                if(empty($card_info)){
                    $merchant_balance['card_money'] = 0;
                    $merchant_balance['card_give_money'] = 0;
                    $merchant_balance['card_discount'] = 10;
                }else{
                    $merchant_balance['card_money'] = $card_info['card_money'];
                    $merchant_balance['card_give_money'] = $card_info['card_money_give'];
                    if(!$use_discount || empty($card_info['discount'])||(isset($order_info['discount_status'])&&!$order_info['discount_status'])){
                        $merchant_balance['card_discount'] = 10;
                    }else{
                        $merchant_balance['card_discount'] = $card_info['discount'];
                    }
                }
            }
        }

//        if(!empty($_GET['ticket'])){
//            $merchant_balance = array();
//            $merchant_balance['card_give_money'] = 0;
//            $merchant_balance['card_discount'] = 10;
//        }

        //用户信息
        $now_user = D('User')->get_user($this->user_session['uid']);


        if ($_GET['use_score']) {
            if($now_user['score_count']<$_GET['score_used_count']){
                $this->error_tips('账户'.$this->config['score_name'].'不够，请重试！');
            }
            $order_info['score_used_count']=$_GET['score_used_count'];
            $order_info['score_deducte']=$_GET['score_deducte'];
        }else{
            $order_info['score_used_count']=0;
            $order_info['score_deducte']=0;
        }
        $order_info['use_score'] = $_GET['use_score'];
        if($_GET['use_balance']==0){
            $order_info['use_balance'] = 0;
        }else{
            $order_info['use_balance'] = 1;
        }
        if($need_user && empty($now_user)){
            $this->error_tips('未获取到您的帐号信息，请重试！');
        }

        //如果用户存在余额或使用了优惠券，则保存至订单信息。如果金额满足订单总价，则实时扣除并返回支付成功！若不够则不实时扣除，防止用户在付款过程中取消支付
        $wx_cheap = 0;
        if($order_info['order_type'] == 'group'){
            //判断有没有使用微信，如果是微信，则检测此团购有没有微信优惠！
            if($this->config['weixin_buy_follow_wechat'] == 2 && empty($now_user['is_follow'])){
                $this->error_tips('您未关注公众号，不能购买！请先关注公众号。');
            }elseif($this->config['weixin_buy_follow_wechat'] == 1 && empty($now_user['is_follow'])){
                $wx_cheap = 0;
            }elseif($_SESSION['openid']){
                $now_group = M('Group')->field('`group_id`,`wx_cheap`')->where(array('group_id'=>$order_info['group_id']))->find();
                $wx_cheap = $order_info['order_num'] * $now_group['wx_cheap'];
            }elseif($this->is_app_browser){
                $now_group = M('Group')->field('`group_id`,`wx_cheap`')->where(array('group_id'=>$order_info['group_id']))->find();
                $wx_cheap = $order_info['order_num'] * $now_group['wx_cheap'];
            }
            $save_result = D('Group_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$wx_cheap,$ecard_info);
        }else if($order_info['order_type'] == 'meal' || $order_info['order_type'] == 'takeout' || $order_info['order_type'] == 'food' || $order_info['order_type'] == 'foodPad'){
            $save_result = D('Meal_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user, $order_info['order_type'],$ecard_info);
        }else if($order_info['order_type'] == 'weidian'){
            $save_result = D('Weidian_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$ecard_info);
        }else if($order_info['order_type'] == 'recharge'){
            $save_result = D('User_recharge_order')->web_befor_pay($order_info,$now_user);
        }else if($order_info['order_type'] == 'appoint'){
            $save_result = D('Appoint_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$ecard_info);
        }else if($order_info['order_type'] == 'waimai'){
            $save_result = D('Waimai_order')->wap_befor_pay($order_info,$merchant_balance,$now_user,$ecard_info);
        }else if($order_info['order_type'] == 'wxapp'){
            $save_result = D('Wxapp_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$ecard_info);
        }else if($order_info['order_type'] == 'store'){
            $save_result = D('Store_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$ecard_info);
        }else if($order_info['order_type'] == 'shop' || $order_info['order_type'] == 'mall'){
            $save_result = D('Shop_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user,$ecard_info);
        }else if($order_info['order_type'] == 'plat'){
            $save_result = D('Plat_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$ecard_info);
        }else if($order_info['order_type'] == 'balance-appoint'){
            $save_result = D('Appoint_order')->wap_balace_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$ecard_info);
        }else if($order_info['order_type'] == 'mcard'){
            $save_result = D('Membership_card_order')->wap_befor_pay($order_info, $now_user);
        }


        if($save_result['error_code']){
            $this->error_tips($save_result['msg']);
        }else if($save_result['url']){
            $this->success_tips($save_result['msg'],$save_result['url']);
        }

        //需要支付的钱
        $pay_money = round($save_result['pay_money']*100)/100;

        if(in_array($order_info['order_type'],array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','plat','balance-appoint')) && $order_info['mer_id']){
            $mer_id = $order_info['mer_id'];
        }else{
            $this->config['merchant_ownpay'] = 0;
        }
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);

        switch($this->config['merchant_ownpay']){
            case '0':
                $pay_method = D('Config')->get_pay_method($notOnline,$notOffline,true);
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method($notOnline,$notOffline,true);
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }
                break;
            case '2':
                $pay_method = array();
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method($notOnline,$notOffline,true);
                }
                break;
        }

        if(empty($pay_method)){
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }
        if(empty($pay_method[$_GET['pay_type']])){
            //$this->error_tips('您选择的支付方式不存在，请更新支付方式！');
        }

        $pay_class_name = ucfirst($_GET['pay_type']);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
        }

        $order_id = $order_info['order_id'];
        if ($_GET['order_type'] == 'takeout' || $_GET['order_type'] == 'food' || $_GET['order_type'] == 'foodPad') {
            $order_table = 'Meal_order';
        }else if($_GET['order_type']=='recharge'){

            if(floatval($pay_money)!=floatval($order_info['order_price'])){
                $this->error_tips('充值订单有误');
            }
            $order_table = 'User_recharge_order';
        }else if($_GET['order_type']=='balance-appoint'){
            $order_table = 'Appoint_order';
        }else if($_GET['order_type']=='mall'){
            $order_table = 'Shop_order';
        } else{
            $order_table = ucfirst($_GET['order_type']).'_order';
        }

        $nowtime = date("ymdHis");
        if ($_POST['order_type'] == 'balance-appoint') {
            $nowtime = date("mdHis");
            $orderid = $nowtime . sprintf("%04d", $this->user_session['uid']);
        } else {
            $orderid = $nowtime . rand(10, 99) . sprintf("%08d", $this->user_session['uid']);
        }

        $_SESSION['alipay_order'] =array(
            'order_type'=>$_GET['order_type'],
            'orderid'=>$orderid,
        );

        $data_tmp['pay_type'] = $_GET['pay_type'] ? $_GET['pay_type'] : 'alipay';
        $data_tmp['order_type'] = $_GET['order_type'];
        $data_tmp['order_id'] = $order_id;
        $data_tmp['orderid'] = $orderid;
        $data_tmp['addtime'] = $nowtime;

        if(!D('Tmp_orderid')->add($data_tmp)){
            $this->error_tips('更新失败，请联系管理员');
        }

        $save_pay_id = D($order_table)->where(array("order_id"=>$order_id))->setField('orderid',$orderid);

//支付前保存在线支付金额
        $pay_rate = 0;
        if($pay_method[$_GET['pay_type']]['config']['rates'] >0){
            $pay_rate = $pay_method[$_GET['pay_type']]['config']['rates'];
        }

        if(!$save_pay_id){
            $this->error_tips('更新失败，请联系管理员');
        }else{
            $order_info['order_id']=$orderid;
        }
        if($pay_method[$_GET['pay_type']]['config']['rates'] >0){

            $pay_rate = $pay_method[$_GET['pay_type']]['config']['rates'];
        }

        //支付前保存在线支付金额
        $payment_money_date['payment_money'] = $pay_money;
        $payment_money_date['pay_type'] = $_GET['pay_type'] ? $_GET['pay_type'] : 'alipay';
        $payment_money_date['order_type'] =  $_GET['order_type'];
        $payment_money_date['order_id'] =  $order_id;
        $payment_money_date['orderid'] =  $orderid;
        $payment_money_date['is_mobile_pay'] = 1;
        $payment_money_date['addtime'] = time();
        $payment_money_date['pay_rate'] = round($pay_rate,2);
        M('Payment_money_order')->add($payment_money_date);

        if($pay_rate){
            $pay_money = $pay_money * $pay_rate;
        }

        $pay_class = new $pay_class_name($order_info,$pay_money,$_GET['pay_type'],$pay_method[$_GET['pay_type']]['config'],$this->user_session,1);
        $go_pay_param = $pay_class->pay();
        if(empty($go_pay_param['error'])){
            header("Content-type: text/html; charset=utf-8");
            echo $go_pay_param['form'];
            die;
            if(!empty($go_pay_param['url'])){
                $this->assign('url',$go_pay_param['url']);
                $this->display();
            }else if(!empty($go_pay_param['form'])){
                $this->assign('form',$go_pay_param['form']);
                $this->display();
            }else if(!empty($go_pay_param['weixin_param'])){
                if($pay_method['weixin']['config']['is_own']){
                    C('open_authorize_wxpay',true);
                    $share = new WechatShare($this->config,$_SESSION['openid']);
                    $this->hideScript = $share->gethideOptionMenu($mer_id);
                    $this->assign('hideScript', $this->hideScript);
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Pay&a=weixin_back&order_type='.$order_info['order_type'].'&order_id='.$order_info['order_id'].'&own_mer_id='.$order_info['mer_id'];
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Pay&a=weixin_back&order_type='.$order_info['order_type'].'&order_id='.$order_info['order_id'];
                }
                $this->assign('redirctUrl',$redirctUrl);
                $this->assign('pay_money',$pay_money);
                $this->assign('weixin_param',json_decode($go_pay_param['weixin_param']));
                $this->display('weixin_pay');
            }else{
                $this->error_tips('调用支付发生错误，请重试。');
            }
        }else{
            $this->error_tips($go_pay_param['msg']);
        }
    }

    public function get_orderid($table,$orderid,$offline=0){
        $order =  D($table);
        $tmp_orderid = D('Tmp_orderid');
        if($offline){
            $now_order = $order->where(array('orderid'=>$orderid))->find();
        }else{
            $now_order = $order->where(array('orderid'=>$orderid))->find();
            if(empty($now_order)){
                $res = $tmp_orderid->where(array('orderid'=>$orderid))->find();
                $now_order = $order->where(array('order_id'=>$res['order_id']))->find();
                $order->where(array('order_id'=>$res['order_id']))->setField('orderid',$orderid);
                $now_order['orderid']=$orderid;
            }
        }
        if(empty($now_order)){
            $this->error_tips('该订单不存在');
        }else{
//            $tmp_orderid->where(array('order_id'=>$now_order['order_id']))->delete();
        }

        return $now_order;
    }
    
    public function getPayAgent()
    {
        $appType = $_POST['app_type'];
        $appType = $appType ?: 'pc';
        switch ($appType) {
            case 'pc'://pc电脑端
                $agent = 'pc';
                break;
            case 'packapp'://普通H5网页
                $agent = 'h5';
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                if(strpos($userAgent,'MicroMessenger') !== false){
                    if (strpos($userAgent, 'miniProgram') !== false) {
                        $agent = 'wechat_mini';
                    } else {
                        $agent = 'wechat_h5';
                    }
                }
                elseif(strpos($userAgent,'AlipayClient') !== false){
                    $agent = 'alipay';
                }
                break;
            case 'wxapp'://微信小程序
                $agent = 'wechat_mini';
                break;
            case 'ios'://IOS app
            case '1'://IOS app
                $agent = 'iosapp';
                break;
            case '2'://安卓 APP
            case 'android'://IOS app
                $agent = 'androidapp';
                break;
            case 'alipayapp'://支付宝网页
                $agent = 'alipay';
                break;
            case 'dyapp':
                $agent = 'douyin_mini';
                break;
            default:
                $agent = 'pc';
                break;
        }
        return $agent;
    }
    //微信同步回调页面
    public function weixin_back(){
        switch($_GET['order_type']){
            case 'group':
                $now_order =$this->get_orderid('Group_order',$_GET['order_id']);
                break;
            case 'meal':
                $now_order =$this->get_orderid('Meal_order',$_GET['order_id']);
                break;
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order =$this->get_orderid('Meal_order',$_GET['order_id']);
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->where(array('orderid'=>$_GET['order_id']))->find();
                $now_order =$this->get_orderid('Weidian_order',$_GET['order_id']);
                break;
            case 'recharge':
                $now_order =$this->get_orderid('User_recharge_order',$_GET['order_id']);
                break;
            case 'appoint':
                $now_order =$this->get_orderid('Appoint_order',$_GET['order_id']);
                break;
            case 'waimai':
                $now_order =$this->get_orderid('Waimai_order',$_GET['order_id']);
                break;
            case 'wxapp':
                $now_order =$this->get_orderid('Wxapp_order',$_GET['order_id']);
                break;
            case 'store':
                $now_order =$this->get_orderid('Store_order',$_GET['order_id']);
                break;
            case 'shop':
            case 'mall':
                $now_order = $this->get_orderid('Shop_order', $_GET['order_id']);
                break;
            case 'plat':
                $now_order = $this->get_orderid('Plat_order', $_GET['order_id']);
                break;
            case 'balance-appoint':
                $now_order = $this->get_orderid('Appoint_order', $_GET['order_id']);
                break;
            case 'yuedan':
                $now_order = $this->get_orderid('Yuedan_service_order', $_GET['order_id']);
                break;
            case 'integral_mall':
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $_GET['order_id']);
                break;
            case 'market':
                $now_order = $this->get_orderid('Market_order', $_GET['order_id']);
                break;
            case 'markets':
                $now_order = $this->get_orderid('Market_total',$_GET['order_id']);
                break;
            default:
                $this->error_tips('非法的订单');
        }


        if(empty($now_order)){
            $this->error_tips('该订单不存在');
        }
        $now_order['order_type'] = $_GET['order_type'];
        if($now_order['paid']==1 &&$now_order['order_type']!='balance-appoint'){
            switch($_GET['order_type']){
                case 'group':
                    if (isset($now_order['is_head']) && $now_order['is_head'] > 0) {
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Groupnew&a=invitePin&order_id='.$now_order['order_id'];
                    } else {
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=group_order&order_id='.$now_order['order_id'];
                    }
                    break;
                case 'meal':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'takeout':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'food':
                case 'foodPad':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'weidian':
                    $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']));
                    $this->NoticeWDAsyn($now_order['orderid']);
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                    break;
                case 'wxapp':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                    break;
                case 'store':
                    if(C('config.pay_success_jump_to_homepage')){
                        $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                    }
                    break;
                case 'shop':
                    if($now_order['group_id']&&$now_order['team_id']){
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                    }
                    break;
                case 'mall':
                    if($now_order['group_id']&&$now_order['team_id']){
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                    }

                    break;
                case 'plat':
                    $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                    break;
                case 'balance-appoint':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'integral_mall':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Integral_mall&a=order_detail_new&order_id='.$now_order['order_id'];
                    break;
                case 'market':
                    $redirctUrl = C('config.site_url').'/packapp/merchant/market_detail.html?type=buy&order_id='.$now_order['order_id'];
                    break;
                case 'markets':
                    $redirctUrl = C('config.site_url').'/packapp/merchant/my_buy_wholesale_order.html';
                    break;
            }
            redirect($redirctUrl);exit;
        }


        if(in_array($_GET['order_type'],array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','plat','balance-appoint')) && $now_order['mer_id']){
            $mer_id = $now_order['mer_id'];
        }else if($_GET['order_type'] == 'plat'){
            $get_order_info = D('Plat_order')->get_pay_order(0,$now_order['order_id']);
            if($get_order_info['order_info']['pay_merchant_ownpay'] && $get_order_info['order_info']['mer_id']){
                $mer_id = $get_order_info['order_info']['mer_id'];
            }else{
                $this->config['merchant_ownpay'] = 0;
            }
        }else{
            $this->config['merchant_ownpay'] = 0;
        }
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        switch($this->config['merchant_ownpay']){
            case '0':
                $pay_method = D('Config')->get_pay_method();
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method();
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){

                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }


                }
                break;
            case '2':
                $pay_method = array();
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){

                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }

                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method();
                }

                break;
        }
        if($mer_id) {
            $now_merchant = D('Merchant')->get_info($mer_id);
            if ( $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
                $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
                $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_client_cert'];
                $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
                $pay_method['weixin']['config']['is_own'] = 2 ;
            }
        }
        if($now_order['order_type']=='plat' && ($now_order['business_type']=='house_village_express' || $now_order['business_type']=='house_village_pay')){
            $house_order =  D('Plat_order')->get_pay_order($this->user_session['uid'], intval($now_order['order_id']));
            $now_village = D('House_village')->get_one($house_order['order_info']['order_info']['village_id']);

            if ($now_village['sub_mch_id'] > 0) {
                $pay_method['weixin']['config']['is_own'] = 5;
                $pay_method['weixin']['config']['sub_mch_id'] = $now_village['sub_mch_id'];
                $is_sub_mchid = true;
            }
            $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
            $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
            $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_client_cert'];
            $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
            //$pay_method['weixin']['config']['is_own'] = 5;
        }

        $now_store = D('Merchant_store')->get_store_by_storeId($now_order['store_id']);
        $is_sub_mchid = false;
        if ($now_order['store_id'] != 0 && $now_store['open_sub_mchid'] && $now_store['sub_mch_id'] > 0){
            $pay_method['weixin']['config']['sub_mch_id']    = $now_store['sub_mch_id'];
            $pay_method['weixin']['config']['is_own']         = 3;
            $is_sub_mchid = true;
        }
        if($is_sub_mchid){
            $pay_method['weixin']['config']['pay_weixin_mchid']       = $this->config['pay_weixin_sp_mchid'];
            $pay_method['weixin']['config']['pay_weixin_key']         = $this->config['pay_weixin_sp_key'];
            $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_client_cert'];
            $pay_method['weixin']['config']['pay_weixin_client_key']  = $this->config['pay_weixin_sp_client_key'];
        }
        $now_store = D('Merchant_store')->get_store_by_storeId($now_order['store_id']);

        if ($now_order['store_id'] != 0 && $now_store['open_sub_mchid'] && $now_store['sub_mch_id'] > 0){
            $pay_method['weixin']['config']['sub_mch_id']    = $now_store['sub_mch_id'];
            $pay_method['weixin']['config']['is_own']         = 3;

        }
        $_GET['order_id'] = $now_order['order_id'];
        $now_order['order_id']  =   $now_order['orderid'];
        $import_result = import('@.ORG.pay.Weixin');
        if(empty($pay_method)){
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }
        $pay_class = new Weixin($now_order,0,'weixin',$pay_method['weixin']['config'],$this->user_session,1);
        $go_query_param = $pay_class->query_order();



        if($go_query_param['error'] === 0){
            //判断回调金额
			//商城和快店可能会混淆，用in做查询
			$payment_where = ['orderid'=>$now_order['order_id'],'order_type'=>$now_order['order_type']];
			if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
				$payment_where['order_type'] = ['in',['shop','mall']];
			}
            if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
                M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
                    fdump_sql(array('go_query_param'=>$go_query_param,'payment_money_order'=>$payment_money_order),'weixin_payment_error_back');
                    $this->error_tips('支付异常');
                }
            }else{
                $this->error_tips('支付异常');
            }

            $go_query_param['order_param']['return']=1;
            switch($_GET['order_type']){
                case 'group':
                    D('Group_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'meal':
                case 'takeout':
                case 'food':
                case 'foodPad':
                    D('Meal_order')->after_pay($go_query_param['order_param'], $_GET['order_type']);
                    break;
                case 'weidian':
                    $pay_info = D('Weidian_order')->after_pay($go_query_param['order_param']);
                    if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
                        $this->NoticeWDAsyn($now_order['orderid']);
                    }
                    break;
                case 'recharge':
                    D('User_recharge_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'waimai':
                    D('Waimai_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'appoint':

                    D('Appoint_order')->after_pay($go_query_param['order_param']);

                    break;
                case 'balance-appoint':

                    D('Appoint_order')->balance_after_pay($go_query_param['order_param']);

                    break;
                case 'yuedan':
                    D('Yuedan_service_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'wxapp':
                    D('Wxapp_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'store':
                    D('Store_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'shop':
                case 'mall':
                    D('Shop_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'plat':
                    D('Plat_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'integral_mall':
                    D('Merchant_integral_mall_goods_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'market':
                    D('Market_order')->market_after_pay($go_query_param['order_param']);
                    break;
                case 'markets':
                    D('Market_order')->market_total_after_pay($go_query_param['order_param']);
                    break;
                case 'spread':
                    D('Merchant_spread_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'mar':
                    D('Membership_card_order')->after_pay($go_query_param['order_param']);
                    break;
            }
        }
        switch($_GET['order_type']){
            case 'group':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$_GET['order_id'];
                break;
            case 'meal':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'takeout':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'food':
            case 'foodPad':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'weidian':
                $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
                $this->NoticeWDAsyn($now_order['orderid']);
                break;
            case 'appoint':
            case 'balance-appoint':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$_GET['order_id'];
                break;
            case 'yuedan':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Yuedan&a=order_details&order_id='.$_GET['order_id'];
                break;
            case 'waimai':
                $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$_GET['order_id'];
                break;
            case 'recharge':
                $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                break;
            case 'wxapp':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$_GET['order_id'];
                break;
            case 'store':
                if(C('config.pay_success_jump_to_homepage')){
                    $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$_GET['order_id'];
                }
                break;
            case 'shop':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $_GET['order_id'];
                break;
            case 'mall':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $_GET['order_id'];
                break;
            case 'plat':
                $redirctUrl = D('Plat_order')->get_order_url($_GET['order_id'],true);
                break;
            case 'integral_mall':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $_GET['order_id'];
                break;
            case 'market':
                $redirctUrl = C('config.site_url').'/packapp/merchant/market_detail.html?type=buy&order_id='.$now_order['order_id'];
                break;
            case 'markets':
                $redirctUrl = C('config.site_url').'/packapp/merchant/my_buy_wholesale_order.html';
                break;
        }
        if($go_query_param['error'] == 1){
            $this->error_tips('校验时发生错误！'.$go_query_param['msg'],$redirctUrl);
        }else{
            redirect($redirctUrl);
        }
    }

    /***异步通知*微店**/
    public function NoticeWDAsyn($order_id){
        $now_order = M('Weidian_order')->field(true)->where(array('orderid'=>trim($order_id)))->find();
        if(!empty($now_order) && ($now_order['pay_type']=='weixin' || $now_order['pay_type']=='alipay')){
            $wdAsynarr=array('order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']);
            $wdAsynarr['salt'] = C('config.weidian_sign');
            ksort($wdAsynarr);
            $wdAsynarr['sign_key'] = sha1(http_build_query($wdAsynarr));
            $wdAsynarr['request_time'] = time();
            $returnarr=httpRequest($this->config['weidian_url'].'/api/pay_notify.php','POST',$wdAsynarr);
            if(empty($returnarr['1'])){
                $returnarr=httpRequest($this->config['weidian_url'].'/api/pay_notify.php','POST',$wdAsynarr);
            }
        }
    }

    //异步通知
    public function notify_url(){
        $pay_method = D('Config')->get_pay_method();
        if(empty($pay_method)){
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }
        $pay_type = isset($_GET['paytype']) ? $_GET['paytype'] : $_GET['pay_type'];
        if($pay_type != 'hqpay' && empty($pay_method[$pay_type])){
            $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
        }
        fdump($pay_type, 'notify_url', 1);
        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
        }

        $pay_class = new $pay_class_name('', '', $pay_type, !empty($pay_method[$pay_type]['config']) ? $pay_method[$pay_type]['config'] : [], $this->user_session, 1);
        $notify_return = $pay_class->notice_url();

        if(empty($notify_return['error'])){

        }else{
            $this->error_tips($notify_return['msg']);
        }
    }

    //跳转通知
    public function return_url(){
        $pay_type = isset($_GET['paytype']) ? $_GET['paytype'] : $_GET['pay_type'];
        if($pay_type == 'weixin'){
			$fileContent = file_get_contents("php://input");
			$array_data = json_decode(json_encode(simplexml_load_string($fileContent, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
			
            if($array_data && $array_data['attach'] != 'weixin'){
                $_GET['own_mer_id'] = $array_data['attach'];
            }
        }
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        switch($this->config['merchant_ownpay']){
            case '0':
                $pay_method = D('Config')->get_pay_method();
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method();
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }

                }
                break;
            case '2':
                $pay_method = array();
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method();
                }

                break;
        }
        if($_GET['own_mer_id']) {
            $now_merchant = D('Merchant')->get_info($_GET['own_mer_id']);
            if ( $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                $pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
                $pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
                $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
                $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
                $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_client_cert'];
                $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
                $pay_method['weixin']['config']['is_own'] = 2 ;
            }
        }
        if(empty($pay_method)){
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }
        if(empty($pay_method[$pay_type])){
            $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
        }

        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
        }
        $fileContent = file_get_contents("php://input");
        $xml_data = json_decode(json_encode(simplexml_load_string($fileContent, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        if($xml_data){
            if($_GET['pay_type'] == 'merchantwarrior'){
                $out_trade_no  = explode('_',$xml_data['transactionReferenceID']);
                $trade_type = $out_trade_no[0];
                $orderid = $out_trade_no[1];
                $is_own = $out_trade_no[2];

                $_GET['orderid'] = $orderid;
                $_GET['transactionReferenceID'] = $xml_data['transactionReferenceID'];
                $_GET['reference'] = $xml_data['transactionID'];
                $_GET['hash'] = $xml_data['hash'];
                $_GET['custom1'] = $xml_data['custom1'];
                $_GET['custom2'] = $xml_data['custom2'];
                $_GET['custom3'] = $xml_data['custom3'];
                $_GET['customHash'] = $xml_data['customHash'];
                $_GET['cardID'] = $xml_data['cardID'];
                $_GET['cardKey'] = $xml_data['cardKey'];
                $_GET['ivrCardID'] = $xml_data['ivrCardID'];
                $_GET['paymentCardNumber'] = $xml_data['paymentCardNumber'];
                $_GET['transactionAmount'] = $xml_data['transactionAmount'];
                if($xml_data['responseCode'] == 0){
                    $_GET['status'] = 'approved';
                }
                $_GET['message'] = $xml_data['responseMessage'];
            }else{
                $out_trade_no  = explode('_',$xml_data['out_trade_no']);
                $trade_type = $out_trade_no[0];
                $orderid = $out_trade_no[1];
                $is_own = $out_trade_no[2];
            }
        }

        if ( $this->config['open_sub_mchid'] && $xml_data['sub_mch_id'] ) {
            $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
            $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
            $pay_method['weixin']['config']['sub_mch_id'] = $xml_data['sub_mch_id'];
            $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_client_cert'];
            $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
            $pay_method['weixin']['config']['is_own'] = 2 ;
        }

        if($xml_data['sub_mch_id']!='' && $is_own == 5){
            $now_order =$this->get_orderid(ucfirst($trade_type),$orderid);
            $house_order =  D('Plat_order')->get_pay_order($this->user_session['uid'], intval($now_order['order_id']));
            $now_village = D('House_village')->get_one($house_order['order_info']['order_info']['village_id']);

            if ($now_village['sub_mch_id'] > 0) {
                $pay_method['weixin']['config']['is_own'] = 5;
                $pay_method['weixin']['config']['sub_mch_id'] = $now_village['sub_mch_id'];
                $is_sub_mchid = true;
            }
            $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
            $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
            $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_client_cert'];
            $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
        }

        $pay_class = new $pay_class_name('','',$pay_type,$pay_method[$pay_type]['config'],$this->user_session,1);
        $get_pay_param = $pay_class->return_url();

		fdump_sql(['get_pay_param' => $get_pay_param, 'xml_data' => $xml_data, 'pay_type' => $pay_type, 'pay_method' => $pay_method, 'GET' => $_GET], 'wap_get_pay_param');

        $get_pay_param['order_param']['return']=1;
        $offline = $pay_type!='offline'?false:true;

        if(empty($get_pay_param['error'])){

            //在线支付回调判断是否与支付前金额相等
            if($pay_type!='offline' && $get_pay_param['order_param']['order_type'] != 'newmarket'){
                //商城和快店可能会混淆，用in做查询
				$payment_where = ['orderid'=>$get_pay_param['order_param']['order_id'],'order_type'=>$get_pay_param['order_param']['order_type']];
				if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
					$payment_where['order_type'] = ['in',['shop','mall']];
				}

                if($payment_where['order_type'] == 'appoint'){
                    $payment_where['order_type'] = ['in',['appoint','balance-appoint']];
                }
			
                if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
                    if($payment_money_order['pay_rate']>0){
                        $get_pay_param['order_param']['pay_money'] = getFormatNumber($get_pay_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
                        M('Payment_money_order')->where($payment_where)->setField('paid_money',$get_pay_param['order_param']['pay_money']);
                    }
                    else{
                        M('Payment_money_order')->where($payment_where)->setField('paid_money',$get_pay_param['order_param']['pay_money']);
                        if($payment_money_order['payment_money'] > $get_pay_param['order_param']['pay_money'] + 0.1){
                            fdump_sql(array('get_pay_param'=>$get_pay_param,'payment_money_order'=>$payment_money_order),'wap_payment_error_back');
                            $this->error_tips('支付异常');
                        }
                    }
                }else{
                    $this->error_tips('支付异常');
                }
            }


            if($get_pay_param['order_param']['order_type'] == 'group'){
                $now_order = $this->get_orderid('Group_order',$get_pay_param['order_param']['order_id'],$offline);
//                $get_pay_param['order_param']['order_id']=$offline?$get_pay_param['order_param']['order_id']:$now_order['orderid'];
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);

            }else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
                $now_order = $this->get_orderid('Meal_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Meal_order')->after_pay($get_pay_param['order_param'], $get_pay_param['order_param']['order_type']);
            }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
                $now_order = $this->get_orderid('Weidian_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($get_pay_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($get_pay_param['order_param']['order_id']);
                }
            }else if($get_pay_param['order_param']['order_type'] == 'recharge'){
                $now_order = $this->get_orderid('User_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];

                $pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'waimai'){
                $now_order = $this->get_orderid('Waimai_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Waimai_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
                $now_order = $this->get_orderid('Wxapp_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Wxapp_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'store'){
                $now_order = $this->get_orderid('Store_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
                $now_order = $this->get_orderid('Shop_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'plat'){
                $now_order = $this->get_orderid('Plat_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'market'){
                $now_order = $this->get_orderid('Market_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'markets'){
                $now_order = $this->get_orderid('Market_total', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'spread'){
                $now_order = $this->get_orderid('Merchant_spread_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_spread_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'mcard'){
                $now_order = $this->get_orderid('Membership_card_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Membership_card_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'newmarket'){// 店铺商城
                $now_order = $this->get_orderid('New_marketing_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('New_marketing_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'merrecharge'){
                $now_order = $this->get_orderid('Merchant_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Merchant_recharge_order')->after_pay($get_pay_param['order_param']);
            }else{
                $this->error_tips('订单类型非法！请重新下单。');
            }
			
            fdump_sql(['get' => $_GET, 'get_pay_param' => $get_pay_param, 'pay_info' => $pay_info], 'after_pay');
			
            $urltype = isset($_GET['urltype']) ? $_GET['urltype'] : '';
            if( empty($pay_info['error']) || (stripos($pay_info['error'],L_('订单已支付')) !== false) || (stripos($pay_info['error'],L_('订单已经支付')) !== false) || (stripos($pay_info['error'],L_('订单已付款')) !== false) ){
                if($get_pay_param['order_param']['pay_type'] == 'weixin'){
                    exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
                } elseif ($get_pay_param['order_param']['pay_type'] == 'baidu') {//百度的异步通知返回
                    exit("<html><head><meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\"></head><body><h1>这是一个return_url页面</h1></body></html>");
                } elseif ('unionpay' == $get_pay_param['order_param']['pay_type'] && $urltype == 'back') {
                    exit("验签成功");
                } elseif ('weifutong' == $get_pay_param['order_param']['pay_type'] && $urltype == 'back') {
                    exit("success");
                }
                $pay_info['msg'] = L_('订单付款成功！现在跳转.');
            }
            if(empty($pay_info['url'])){
                $this->error_tips($pay_info['msg']);
            }else{
                if(cookie('is_house')){
                    $pay_info['url'] = str_replace('wap.php',C('INDEP_HOUSE_URL'),$pay_info['url']);
                }else{
                    $pay_info['url'] = preg_replace('#/source/(\w+).php#','/wap.php',$pay_info['url']);
                }
                if ($pay_info['error'] && $urltype == 'front') {
                    $this->redirect($pay_info['url']);
                }
                if ($pay_info['url'] && $_GET['pay_type'] == 'merchantwarrior') {
                    redirect($pay_info['url']);
                }
				if ($pay_info['url'] && $_GET['pay_type'] == 'baodi_card') {
                    redirect($pay_info['url']);
                }
                $this->assign('pay_info', $pay_info);
                $this->display('after_pay');
            }
        }else{
            $this->error_tips($get_pay_param['msg']);
        }
    }

    //跳转通知 农业银行微信支付低费率
    public function nong_return_url(){
        import("@.ORG.pay.ebusclient.Result", "", ".php");
        //1、取得MSG参数，并利用此参数值生成验证结果对象
        $tResult = new Result();
        $tResponse = $tResult->init($_POST['MSG']);

        if ($tResponse->isSuccess()) {
            //2、、支付成功
            $orderNo = explode('_',$tResponse->getValue("OrderNo"));
            $payment_where = ['orderid'=>$orderNo[1],'order_type'=>$orderNo[0]];
            if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
                M('Payment_money_order')->where($payment_where)->setField('paid_money',$tResponse->getValue("Amount"));
            }
            $get_pay_param['order_param'] = [
                'pay_type' => 'weixin',
                'is_mobile' => 1,
                'order_type' => $orderNo[0],
                'order_id' => $orderNo[1],
                'is_own' => 0,
                'third_id' => $tResponse->getValue("ThirdOrderNo"),
                'pay_money' => $tResponse->getValue("Amount")
            ];
            if($get_pay_param['order_param']['order_type'] == 'group'){
                $now_order = $this->get_orderid('Group_order',$get_pay_param['order_param']['order_id'],$offline);
//                $get_pay_param['order_param']['order_id']=$offline?$get_pay_param['order_param']['order_id']:$now_order['orderid'];
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);

            }else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
                $now_order = $this->get_orderid('Meal_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Meal_order')->after_pay($get_pay_param['order_param'], $get_pay_param['order_param']['order_type']);
            }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
                $now_order = $this->get_orderid('Weidian_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($get_pay_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($get_pay_param['order_param']['order_id']);
                }
            }else if($get_pay_param['order_param']['order_type'] == 'recharge'){
                $now_order = $this->get_orderid('User_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];

                $pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'waimai'){
                $now_order = $this->get_orderid('Waimai_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Waimai_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
                $now_order = $this->get_orderid('Wxapp_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Wxapp_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'store'){
                $now_order = $this->get_orderid('Store_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
                $now_order = $this->get_orderid('Shop_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'plat'){
                $now_order = $this->get_orderid('Plat_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'market'){
                $now_order = $this->get_orderid('Market_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'markets'){
                $now_order = $this->get_orderid('Market_total', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'spread'){
                $now_order = $this->get_orderid('Merchant_spread_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_spread_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'mcard'){
                $now_order = $this->get_orderid('Membership_card_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Membership_card_order')->after_pay($get_pay_param['order_param']);
            }
            fdump($pay_info, "lumin_nong_1", 1);
            //tMerchantPage = "http://172.30.7.117/demo/CustomerPage.aspx?请传入必要的参数"  如下：
            $tMerchantPage = $pay_info['url'];

        } else {
            //3、失败
            //tMerchantPage = "http://172.30.7.117/demo/MerchantFailure.aspx?请传入必要的参数" 如下：
            $tMerchantPage = "http://localhost/demo/MerchantFailure.php?OrderNo=" . $tResponse->getValue("OrderNo");
        }
        echo "<html><head><meta http-equiv=\"refresh\" content=\"0; url='".$tMerchantPage."'\"></head></html>";
        exit;
    }

    public function ccb_return(){
        $order_id_arr = explode('_',$_GET['ORDERID']);
        $order_type = $order_id_arr[0];
        $order_id = $order_id_arr[1];

        switch($order_type){
            case 'group':
                $now_order = D('Group_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'meal':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'recharge':
                $now_order = D('User_recharge_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'balance-appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'waimai':
                $now_order = D('Waimai_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'wxapp':
                $now_order = D('Wxapp_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'store':
                $now_order = D('Store_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'shop':
            case 'mall':
                $now_order = D('Shop_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'plat':
                $now_order = D('Plat_order')->where(array('orderid'=>$order_id))->find();
                break;
            default:
                $this->error_tips('非法的订单');
        }
        if($now_order['paid'] && $order_type!='balance-appoint'){
            switch($order_type){
                case 'group':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$now_order['order_id'];
                    break;
                case 'meal':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'takeout':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'food':
                case 'foodPad':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'weidian':
                    $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                    break;
                case 'wxapp':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                    break;
                case 'store':
                    if(C('config.pay_success_jump_to_homepage')){
                        $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                    }
                    break;
                case 'shop':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                    break;
                case 'mall':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                    break;
                case 'plat':
                    $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                    break;
                case 'integral_mall':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                    break;
            }
            redirect($redirctUrl);exit;
        }

        if(in_array($order_type,array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','plat','balance-appoint')) && $now_order['mer_id']){
            $mer_id = $now_order['mer_id'];
        }else if($order_type == 'plat'){
            $get_order_info = D('Plat_order')->get_pay_order(0,$now_order['order_id']);
            if($get_order_info['order_info']['pay_merchant_ownpay'] && $get_order_info['order_info']['mer_id']){
                $mer_id = $get_order_info['order_info']['mer_id'];
            }else{
                $this->config['merchant_ownpay'] = 0;
            }
        }else{
            $this->config['merchant_ownpay'] = 0;
        }
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        switch($this->config['merchant_ownpay']){
            case '0':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                break;
            case '2':
                $pay_method = array();
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method(0, 0, true);
                }
                break;
        }
        if(empty($pay_method)){
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }

        $pay_type = 'ccb';
        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        $pay_class = new $pay_class_name('', '', $pay_type, $pay_method[$pay_type]['config'],$this->user_session,$this->is_app_browser ? 2 : 1);
        $go_query_param = $pay_class->query_order();

        $offline = false;
        if($go_query_param['error'] === 0){
            $go_query_param['order_param']['pay_money'] = isset($go_query_param['order_param']['pay_money']) && $go_query_param['order_param']['pay_money'] ? $go_query_param['order_param']['pay_money'] : $total_fee;
            if($order_type == 'group'){
                $now_order = $this->get_orderid('Group_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Group_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad'){
                $now_order = $this->get_orderid('Meal_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Meal_order')->after_pay($go_query_param['order_param'], $order_type);
            }else if($order_type == 'weidian'){
                $now_order = $this->get_orderid('Weidian_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Weidian_order')->after_pay($go_query_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($go_query_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($go_query_param['order_param']['order_id']);
                }
            }else if($order_type == 'recharge'){
                $now_order = $this->get_orderid('User_recharge_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('User_recharge_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'appoint'){
                $now_order = $this->get_orderid('Appoint_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];


                D('Appoint_order')->after_pay($go_query_param['order_param']);

                // $pay_info = D('Appoint_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'balance-appoint'){
                $now_order = $this->get_orderid('Appoint_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                D('Appoint_order')->balance_after_pay($go_query_param['order_param']);
            }else if($order_type == 'waimai'){
                $now_order = $this->get_orderid('Waimai_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Waimai_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'wxapp'){
                $now_order = $this->get_orderid('Wxapp_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Wxapp_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'store'){
                $now_order = $this->get_orderid('Store_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Store_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'shop' || $order_type == 'mall'){
                $now_order = $this->get_orderid('Shop_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Shop_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'plat'){
                $now_order = $this->get_orderid('Plat_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Plat_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'integral_mall'){
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'spread'){
                $now_order = $this->get_orderid('Merchant_spread_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_spread_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'mcard'){
                $now_order = $this->get_orderid('Membership_card_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Membership_card_order')->after_pay($go_query_param['order_param']);
            }else{
                $this->error_tips('订单类型非法！请重新下单。');
            }
        }
        switch($order_type){
            case 'group':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$now_order['order_id'];
                break;
            case 'meal':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'takeout':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'food':
            case 'foodPad':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'weidian':
                $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
                break;
            case 'appoint':
            case 'balance-appoint':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$now_order['order_id'];
                break;
            case 'waimai':
                $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                break;
            case 'recharge':
                $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                break;
            case 'wxapp':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                break;
            case 'store':
                if(C('config.pay_success_jump_to_homepage')){
                    $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                }
                break;
            case 'shop':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                break;
            case 'mall':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                break;
            case 'plat':
                $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                break;
            case 'integral_mall':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                break;
        }
        if ($pay_info['error']==0) echo 'success';
        redirect($redirctUrl);
    }
    //支付宝支付同步回调
    public function alipay_return(){
        $order_id_arr = explode('_',$_GET['out_trade_no']);
        $order_type = $order_id_arr[0];
        $order_id = $order_id_arr[1];
        $total_fee = $order_id_arr[2];
        if(!empty($_SESSION['alipay_order'])){
            $order_id = $_SESSION['alipay_order']['orderid'];
            $order_type = $_SESSION['alipay_order']['order_type'];
            unset($_SESSION['alipay_order']);
        }
        switch($order_type){
            case 'group':
                $now_order = D('Group_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'meal':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'recharge':
                $now_order = D('User_recharge_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'balance-appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'waimai':
                $now_order = D('Waimai_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'wxapp':
                $now_order = D('Wxapp_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'store':
                $now_order = D('Store_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'shop':
            case 'mall':
                $now_order = D('Shop_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'plat':
                $now_order = D('Plat_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'integral_mall':
                $now_order = D('Merchant_integral_mall_goods_order')->where(array('orderid'=>$order_id))->find();
                break;
            default:
                $this->error_tips('非法的订单');
        }
        if($now_order['paid'] && $order_type!='balance-appoint'){
            switch($order_type){
                case 'group':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$now_order['order_id'];
                    break;
                case 'meal':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'takeout':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'food':
                case 'foodPad':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'weidian':
                    $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                    break;
                case 'wxapp':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                    break;
                case 'store':
                    if(C('config.pay_success_jump_to_homepage')){
                        $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                    }
                    break;
                case 'shop':
                    if($now_order['group_id']&&$now_order['team_id']){
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                    }
                    break;
                case 'mall':
                    if($now_order['group_id']&&$now_order['team_id']){
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                    }
                    break;
                case 'plat':
                    $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                    break;
                case 'integral_mall':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                    break;
            }
            redirect($redirctUrl);exit;
        }

        if(in_array($_GET['order_type'],array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','plat','balance-appoint')) && $now_order['mer_id']){
            $mer_id = $now_order['mer_id'];
        }else if($_GET['order_type'] == 'plat'){
            $get_order_info = D('Plat_order')->get_pay_order(0,$now_order['order_id']);
            if($get_order_info['order_info']['pay_merchant_ownpay'] && $get_order_info['order_info']['mer_id']){
                $mer_id = $get_order_info['order_info']['mer_id'];
            }else{
                $this->config['merchant_ownpay'] = 0;
            }
        }else{
            $this->config['merchant_ownpay'] = 0;
        }
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        switch($this->config['merchant_ownpay']){
            case '0':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                break;
            case '2':
                $pay_method = array();
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method(0, 0, true);
                }
                break;
        }
        if(empty($pay_method)){
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }

        $pay_type = $_GET['pay_type'];
        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        $pay_class = new $pay_class_name('', '', $pay_type, $pay_method[$pay_type]['config'],$this->user_session,1);
        $go_query_param = $pay_class->query_order();

        $offline = false;
        if($go_query_param['error'] === 0){

            //判断回调金额
			//商城和快店可能会混淆，用in做查询
			$payment_where = ['order_id'=>$now_order['order_id'],'order_type'=>$order_type];
			if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
				$payment_where['order_type'] = ['in',['shop','mall']];
			}
            if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
                if($payment_money_order['pay_rate']>0){
                    $go_query_param['order_param']['pay_money'] = getFormatNumber($go_query_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                }
                else{

                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                    if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
                        fdump_sql(array('go_query_param'=>$go_query_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_return');
                        $this->error_tips('支付异常');
                    }
                }
            }else{
                $this->error_tips('支付异常');
            }

            $go_query_param['order_param']['pay_money'] = isset($go_query_param['order_param']['pay_money']) && $go_query_param['order_param']['pay_money'] ? $go_query_param['order_param']['pay_money'] : $total_fee;
            if($order_type == 'group'){
                $now_order = $this->get_orderid('Group_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Group_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad'){
                $now_order = $this->get_orderid('Meal_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Meal_order')->after_pay($go_query_param['order_param'], $order_type);
            }else if($order_type == 'weidian'){
                $now_order = $this->get_orderid('Weidian_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Weidian_order')->after_pay($go_query_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($go_query_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($go_query_param['order_param']['order_id']);
                }
            }else if($order_type == 'recharge'){
                $now_order = $this->get_orderid('User_recharge_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('User_recharge_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'appoint'){
                $now_order = $this->get_orderid('Appoint_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];


                D('Appoint_order')->after_pay($go_query_param['order_param']);

                // $pay_info = D('Appoint_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'balance-appoint'){
                $now_order = $this->get_orderid('Appoint_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                D('Appoint_order')->balance_after_pay($go_query_param['order_param']);
            }else if($order_type == 'waimai'){
                $now_order = $this->get_orderid('Waimai_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Waimai_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'wxapp'){
                $now_order = $this->get_orderid('Wxapp_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Wxapp_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'store'){
                $now_order = $this->get_orderid('Store_order',$go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Store_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'shop' || $order_type == 'mall'){
                $now_order = $this->get_orderid('Shop_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Shop_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'plat'){
                $now_order = $this->get_orderid('Plat_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Plat_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'integral_mall'){
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'spread'){
                $now_order = $this->get_orderid('Merchant_spread_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_spread_order')->after_pay($go_query_param['order_param']);
            }else if($order_type == 'mcard'){
                $now_order = $this->get_orderid('Membership_card_order', $go_query_param['order_param']['order_id'],$offline);
                $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Membership_card_order')->after_pay($go_query_param['order_param']);
            }else{
                $this->error_tips('订单类型非法！请重新下单。');
            }
        }
        switch($order_type){
            case 'group':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$now_order['order_id'];
                break;
            case 'meal':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'takeout':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'food':
            case 'foodPad':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'weidian':
                $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
                break;
            case 'appoint':
            case 'balance-appoint':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$now_order['order_id'];
                break;
            case 'waimai':
                $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                break;
            case 'recharge':
                $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                break;
            case 'wxapp':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                break;
            case 'store':
                if(C('config.pay_success_jump_to_homepage')){
                    $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                }
                break;
            case 'shop':
                if($now_order['group_id']&&$now_order['team_id']){
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                }

                break;
            case 'mall':
                if($now_order['group_id']&&$now_order['team_id']){
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                }
                break;
            case 'plat':
                $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                break;
            case 'integral_mall':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                break;
        }
        if ($pay_info['error']==0) echo 'success';
        redirect($redirctUrl);
    }
    //支付宝异步通知
    public function alipay_notice()
    {
        fdump_sql($_SERVER['original_post'],'wap_alipay_notice');

        $pay_type = $_GET['pay_type'];
		$out_trade_no_arr = explode('_', $_POST['out_trade_no']);
		if($out_trade_no_arr[2] && $_POST['passback_params']){
			$passback_params_arr = explode('_', $_POST['passback_params']);
			$pay_method = D('Merchant_ownpay')->where(['mer_id' => $passback_params_arr[1]])->find();
			$alipay_config = unserialize($pay_method['alipayh5']);
		}else{
			$pay_method = D('Config')->get_pay_method(0, 0, true);
			if(empty($pay_method)){
				$this->error_tips('系统管理员没开启任一一种支付方式！');
			}
			$alipay_config = $pay_method[$pay_type]['config'];
		}
		
        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.' . $pay_class_name);
        $pay_class = new $pay_class_name('', '', $pay_type, $alipay_config, $this->user_session, 1);
        $go_query_param = $pay_class->notice_url();



        $offline = false;
        if($go_query_param['error'] == 0){
            //判断回调金额
			//商城和快店可能会混淆，用in做查询
			$payment_where = ['orderid'=>$go_query_param['order_param']['order_id'],'order_type'=>$go_query_param['order_param']['order_type']];
			if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
				$payment_where['order_type'] = ['in',['shop','mall']];
			}
            if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
                if($payment_money_order['pay_rate']>0){
                    $go_query_param['order_param']['pay_money'] = getFormatNumber($go_query_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                }
                else{
                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                    if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
                        fdump_sql(array('go_query_param'=>$go_query_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_return');
                        $this->error_tips('支付异常');
                    }
                }
            }else{
                $this->error_tips('支付异常');
            }
            if($go_query_param['order_param']['trade_status'] == 'TRADE_FINISHED') {
                $this->error_tips('订单已完成');
            }else if ($go_query_param['order_param']['trade_status'] == 'TRADE_SUCCESS') {
                if ($go_query_param['order_param']['order_type'] == 'group') {
                    $now_order = $this->get_orderid('Group_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Group_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'meal' || $go_query_param['order_param']['order_type'] == 'takeout' || $go_query_param['order_param']['order_type'] == 'food' || $go_query_param['order_param']['order_type'] == 'foodPad') {
                    $now_order = $this->get_orderid('Meal_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Meal_order')->after_pay($go_query_param['order_param'], $go_query_param['order_param']['order_type']);
                } else if ($go_query_param['order_param']['order_type'] == 'weidian') {
                    $now_order = $this->get_orderid('Weidian_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Weidian_order')->after_pay($go_query_param['order_param']);
                    if (($pay_info['error'] == 0) && isset($pay_info['url']) && ($go_query_param['order_param']['pay_type'] == 'weixin')) {
                        /***异步通知***/
                        $this->NoticeWDAsyn($go_query_param['order_param']['order_id']);
                    }
                } else if ($go_query_param['order_param']['order_type'] == 'recharge') {
                    $now_order = $this->get_orderid('User_recharge_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('User_recharge_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'appoint') {
                    $now_order = $this->get_orderid('Appoint_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Appoint_order')->after_pay($go_query_param['order_param']);

                    //$pay_info = D('Appoint_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'balance-appoint') {
                    $now_order = $this->get_orderid('Appoint_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Appoint_order')->balance_after_pay($go_query_param['order_param']);
                    //$pay_info = D('Appoint_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'waimai') {
                    $now_order = $this->get_orderid('Waimai_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Waimai_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'wxapp') {
                    $now_order = $this->get_orderid('Wxapp_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Wxapp_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'store') {
                    $now_order = $this->get_orderid('Store_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Store_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'shop' || $go_query_param['order_param']['order_type'] == 'mall') {
                    $now_order = $this->get_orderid('Shop_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Shop_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'plat') {
                    $now_order = $this->get_orderid('Plat_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Plat_order')->after_pay($go_query_param['order_param']);
                } else if($go_query_param['order_param']['order_type'] == 'integral_mall'){
                    $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($go_query_param['order_param']);
                }else if($go_query_param['order_param']['order_type']  == 'spread'){
                    $now_order = $this->get_orderid('Merchant_spread_order', $go_query_param['order_param']['order_id'],$offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Merchant_spread_order')->after_pay($go_query_param['order_param']);
                } else if ($go_query_param['order_param']['order_type'] == 'mcard') {
                    $now_order = $this->get_orderid('Membership_card_order', $go_query_param['order_param']['order_id'], $offline);
                    $go_query_param['order_param']['order_id'] = $now_order['orderid'];
                    $pay_info = D('Membership_card_order')->after_pay($go_query_param['order_param']);
                } else {
                    $this->error_tips('订单类型非法！请重新下单。');
                }
            }

            $order_id = $go_query_param['order_param']['order_id'];
            switch($go_query_param['order_param']['order_type']){
                case 'group':
                    $now_order = D('Group_order')->where(array('orderid'=>$order_id))->find();
                    break;
                case 'meal':
                case 'takeout':
                case 'food':
                case 'foodPad':
                    $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                    break;
                case 'weidian':
                    $now_order = D('Weidian_order')->where(array('orderid'=>$order_id))->find();
                    break;
                case 'recharge':
                    $now_order = D('User_recharge_order')->where(array('orderid'=>$order_id))->find();
                    break;
                case 'appoint':
                    $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                    break;
                case 'waimai':
                    $now_order = D('Waimai_order')->where(array('orderid'=>$order_id))->find();
                    break;
                case 'wxapp':
                    $now_order = D('Wxapp_order')->where(array('orderid'=>$order_id))->find();
                    break;
                case 'store':
                    $now_order = D('Store_order')->where(array('orderid'=>$order_id))->find();
                    break;
                case 'shop':
                case 'mall':
                    $now_order = D('Shop_order')->where(array('orderid' => $order_id))->find();
                    break;
                case 'plat':
                    $now_order = D('Plat_order')->where(array('orderid' => $order_id))->find();
                    break;
                case 'integral_mall':
                    $now_order = D('Merchant_integral_mall_goods_order')->where(array('orderid' => $order_id))->find();
                    break;
                default:
                    echo "fail";
                    exit;
            }
            if ($now_order['paid']) {
                echo "success";
                exit;
            }
        }
        echo "fail";
        exit;
    }

    //对接支付跳转
    public function butt_pay(){
        if(!empty($_GET['order_id']) && !empty($_GET['order_type'])){
            if(empty($_GET['is_paid'])){
                switch($_GET['order_type']){
                    case 'appoint':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=appoint_order&order_id='.$_GET['order_id'];
                        break;
                }
                if(empty($redirctUrl)){
                    $this->error('该类订单不允许访问');
                }else{
                    redirect($redirctUrl);
                }
            }else{
                $butt_array = array(
                    'order_id' => $_GET['order_id'],
                    'order_type' => $_GET['order_type'],
                    'pay_type' => $_GET['pay_type'],
                    'pay_money' => $_GET['pay_money'],
                    'pay_third_id' => $_GET['pay_third_id'],
                    'encrypt_time' => $_GET['encrypt_time'],
                );
                $key = get_butt_encrypt_key($butt_array,C('butt_key'),true);
                if($key == $_GET['encrypt_key']){
                    $_GET['pay_money'] = $_GET['pay_money']/100;
                    switch($_GET['order_type']){
                        case 'appoint':
                            $order_param = array(
                                'pay_type' => 'weixin',
                                'is_mobile' => '1',
                                'order_type' => $_GET['order_type'],
                                'order_id' => $_GET['order_id'],
                                'is_own' => '0',
                                'third_id' => $_GET['pay_third_id'],
                                'pay_money' => $_GET['pay_money'],
                            );
                            $pay_info = D('Appoint_order')->after_pay($order_param);
                            break;
                    }
                    if(!empty($pay_info)){
                        if(empty($pay_info['url'])){
                            $this->error_tips($pay_info['msg']);
                        }else{
                            if(cookie('is_house')){
                                $pay_info['url'] = str_replace('wap.php',C('INDEP_HOUSE_URL'),$pay_info['url']);
                            }else{
                                $pay_info['url'] = preg_replace('#/source/(\w+).php#','/wap.php',$pay_info['url']);
                            }

                            redirect($pay_info['url']);
                        }
                    }else{
                        $this->error('该类订单不允许访问');
                    }
                }else{
                    $this->error('订单校验失败，请重试');
                }
            }
        }else{
            $this->error('访问出错，请重试');
        }
    }

    //百度的同步通知
    public function baidu_back()
    {
        $pay_type = 'baidu';
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        switch($this->config['merchant_ownpay']){
            case '0':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                break;
            case '2':
                $pay_method = array();
                if($_GET['own_mer_id']){
                    $mer_id = $_GET['own_mer_id'];
                }else if($_SESSION['own_mer_id']){
                    $mer_id = $_SESSION['own_mer_id'];
                    unset($_SESSION['own_mer_id']);
                }
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method(0, 0, true);
                }
                break;
        }
        if(empty($pay_method)){
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }
        if(empty($pay_method[$pay_type])){
            $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
        }

        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
        }
        $pay_class = new $pay_class_name('','',$pay_type,$pay_method[$pay_type]['config'],$this->user_session,1);
        $get_pay_param = $pay_class->return_url();
        if($pay_type!='offline'){
            $get_pay_param['order_param']['return']=1;
        }
        if(empty($get_pay_param['error'])){

            switch($get_pay_param['order_param']['order_type']){
                case 'group':
                    $now_order = D('Group_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'meal':
                    $now_order = D('Meal_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'takeout':
                case 'food':
                case 'foodPad':
                    $now_order = D('Meal_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'weidian':
                    $now_order = D('Weidian_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'recharge':
                    $now_order = D('User_recharge_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'appoint':
                    $now_order = D('Appoint_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'waimai':
                    $now_order = D('Waimai_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'wxapp':
                    $now_order = D('Wxapp_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'store':
                    $now_order = D('Store_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'shop':
                case 'mall':
                    $now_order = D('Shop_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'plat':
                    $now_order = D('Plat_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                case 'integral_mall':
                    $now_order = D('Merchant_integral_mall_goods_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id']))->find();
                    break;
                default:
                    $this->error_tips('非法的订单');
            }


            if(empty($now_order)){
                $this->error_tips('该订单不存在');
            }
            $now_order['order_type'] = $get_pay_param['order_param']['order_type'];
            if($now_order['paid']&&$now_order['order_type']!='balance-appoint'){
                switch($get_pay_param['order_param']['order_type']){
                    case 'group':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=group_order&order_id='.$now_order['order_id'];
                        break;
                    case 'meal':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                        break;
                    case 'takeout':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                        break;
                    case 'food':
                    case 'foodPad':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                        break;
                    case 'weidian':
                        $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']));
                        $this->NoticeWDAsyn($now_order['orderid']);
                        break;
                    case 'appoint':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=appoint_order&order_id='.$now_order['order_id'];
                        break;
                    case 'waimai':
                        $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                        break;
                    case 'recharge':
                        $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                        // $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=index';
                        break;
                    case 'wxapp':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                        break;
                    case 'store':
                        if(C('config.pay_success_jump_to_homepage')){
                            $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                        }else{
                            $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$_GET['order_id'];
                        }
                        break;
                    case 'shop':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                        break;
                    case 'mall':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                        break;
                    case 'plat':
                        $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                        break;
                    case 'integral_mall':
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                        break;
                }
                redirect($redirctUrl);exit;
            }

            if($get_pay_param['order_param']['order_type'] == 'group'){
                $pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
//                 $get_pay_param['order_param']['orderid'] = $get_pay_param['order_param']['order_id'];
//                 unset($get_pay_param['order_param']['order_id']);
                $pay_info = D('Meal_order')->after_pay($get_pay_param['order_param'], $get_pay_param['order_param']['order_type']);
            }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
                $pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($get_pay_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($get_pay_param['order_param']['order_id']);
                }
            }else if($get_pay_param['order_param']['order_type'] == 'recharge'){
                $pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'appoint'){

                $pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);

                //$pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){

                $pay_info =D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);

                //$pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'waimai'){
                $pay_info = D('Waimai_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
                $pay_info = D('Wxapp_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'store'){
                $pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
                $pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'plat'){
                $pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
            } else if($get_pay_param['order_param']['order_type'] == 'spread'){
                $pay_info = D('Merchant_spread_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'mcard'){
                $pay_info = D('Membership_card_order')->after_pay($get_pay_param['order_param']);
            } else{
                $this->error_tips(L_('订单类型非法！请重新下单。'));
            }
            if(empty($pay_info['error'])){
                $pay_info['msg'] = L_('订单付款成功！现在跳转.');
            }
            if(empty($pay_info['url'])){
                $this->error_tips($pay_info['msg']);
            }else{
                if(cookie('is_house')){
                    $pay_info['url'] = str_replace('wap.php',C('INDEP_HOUSE_URL'),$pay_info['url']);
                }else{
                    $pay_info['url'] = preg_replace('#/source/(\w+).php#','/wap.php',$pay_info['url']);
                }
                $this->assign('pay_info',$pay_info);
                $this->display('after_pay');
            }
        }else{
            $this->error_tips($get_pay_param['msg']);
        }
    }

    public function success_order(){
        $referer = $_GET['referer'];
        $this->success_tips('支付成功',htmlspecialchars_decode($referer));

    }

    //通过卡密使用平台E卡
    public function use_ecard(){
        if(IS_POST){
            $data=$_POST;
            if(md5($data['verify']) != $_SESSION['ecrad_verify']){
                $this->error('验证码不正确');
            }
            unset($data['verify']);
            $result=D('Ecard_coupon')->check_ecard_coupon($data['card_password'],$data['need_pay']);
            if($result['error']==1){
                $this->error($result['msg']);
            }
            $url=U('Pay/check',$data);
            $this->success('可以使用',$url);
        }else{
            $param=json_encode($_GET);
            $this->assign('param',$param);
            $this->display();
        }
    }

    //创建校验二维码
    public function get_verify(){
        import('ORG.Util.Image');
        Image::buildImageVerify(4,1,'jpeg',60,36,'ecrad_verify');
    }

    public function get_order_by_id(){
        $order_arr = explode('_',$_POST['order_id']);
        $get_pay_param['order_param']['order_type'] = $order_arr[0];
        $get_pay_param['order_param']['order_id']   = $order_arr[1];
        if($get_pay_param['order_param']['order_type'] == 'group'){
            $now_order = $this->get_orderid('Group_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
            $now_order = $this->get_orderid('Meal_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
            $now_order = $this->get_orderid('Weidian_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'recharge'){
            $now_order = $this->get_orderid('User_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'appoint'){
            $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'waimai'){
            $now_order = $this->get_orderid('Waimai_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
            $now_order = $this->get_orderid('Wxapp_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'store'){
            $now_order = $this->get_orderid('Store_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
            $now_order = $this->get_orderid('Shop_order', $get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'plat'){
            $now_order = $this->get_orderid('Plat_order', $get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
            $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
        }else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
            $now_order = $this->get_orderid('Merchant_integral_mall_goods_order',$get_pay_param['order_param']['order_id']);
        }else{
            echo json_encode(array('error'=>1,'error_msg'=>'订单类型非法'));
            die;
        }
        if(empty($now_order) || empty($now_order['order_id'])){
            echo json_encode(array('error'=>1,'error_msg'=>'订单不存在'));
            die;
        }
        echo json_encode(array('error'=>0,'order_info'=>$now_order));
    }

    public function pay_by_others()
    {
        $temp_data_id = isset($_GET['temp_data_id']) ? intval($_GET['temp_data_id']) : 0;
        $order_id = M('Temp_order_data')->where(['temp_id' => $temp_data_id])->getField('order_id');
        $url = $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/payByOthers/payByOthers?order_id=' . intval($order_id);
        redirect($url);
        exit;
    }
	
	//随行付低费率，微信点金计划展示页面
	public function tianquetech_order(){
		$this->display();
	}

    public function chinaums_notify(){
        import("@.ORG.pay.Chinaums");
        //1、取得MSG参数，并利用此参数值生成验证结果对象
        $pay = new Chinaums();
        fdump_sql($_POST,'chinaums_notify');

        //2、、支付成功
        $orderNo = trim($_POST['merOrderId']);
        if(!empty($orderNo)){
            $order_no = $pay->createOrderNo($orderNo, true);
            $payment_where = ['orderid'=>$order_no];
            $payment_money_order = M('Payment_money_order')->where($payment_where)->find();
            if(empty($payment_money_order)){
                echo "FAILED";
                exit;
            }
            $total_amount = getFormatNumber($_POST["totalAmount"]/100);
            $save = [
                'paid_money' => $total_amount,
                'pay_method' => 'chinaums',
                'third_plat_data' => $_POST['targetOrderId']
            ];
            M('Payment_money_order')->where($payment_where)->data($save)->save();
            
            $pay_types = [
                'Alipay 1.0' => 'alipay',
                'Alipay 2.0' => 'alipay',
                'WXPay' => 'weixin',
                'UnionPay' => 'unionpayapp',
                'UAC' => 'unionpayapp',
            ];
            $get_pay_param['order_param'] = [
                'pay_type' => $pay_types[$_POST['targetSys']],
                'is_mobile' =>$payment_money_order['is_mobile_pay'],
                'order_type' => $payment_money_order['order_type'],
                'order_id' => $order_no,
                'is_own' => 0,
                'third_id' => $_POST['targetOrderId'],
                'pay_money' => $total_amount
            ];
            if($get_pay_param['order_param']['order_type'] == 'group'){
                $now_order = $this->get_orderid('Group_order',$get_pay_param['order_param']['order_id'],$offline);
    //                $get_pay_param['order_param']['order_id']=$offline?$get_pay_param['order_param']['order_id']:$now_order['orderid'];
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);

            }else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
                $now_order = $this->get_orderid('Meal_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Meal_order')->after_pay($get_pay_param['order_param'], $get_pay_param['order_param']['order_type']);
            }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
                $now_order = $this->get_orderid('Weidian_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($get_pay_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($get_pay_param['order_param']['order_id']);
                }
            }else if($get_pay_param['order_param']['order_type'] == 'recharge'){
                $now_order = $this->get_orderid('User_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];

                $pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'waimai'){
                $now_order = $this->get_orderid('Waimai_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Waimai_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
                $now_order = $this->get_orderid('Wxapp_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Wxapp_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'store'){
                $now_order = $this->get_orderid('Store_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
                $now_order = $this->get_orderid('Shop_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'plat'){
                $now_order = $this->get_orderid('Plat_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'market'){
                $now_order = $this->get_orderid('Market_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'markets'){
                $now_order = $this->get_orderid('Market_total', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
            }
            echo "SUCCESS";
        }
        else{
            echo "FAILED";
        }
        exit;
    }
	

    public function sande_notify(){
        import('@.ORG.pay.Sande');
        $pay = new Sande();
        $notify_data = json_decode(htmlspecialchars_decode($_POST['data']), true);

        $orderNo = trim($notify_data['body']['orderCode']);
        if(!empty($orderNo)){
            $order_arr = explode('_', $orderNo);
            $payment_where = ['orderid'=>$order_arr[1]];
            $payment_money_order = M('Payment_money_order')->where($payment_where)->find();
            if(empty($payment_money_order)){
                exit;
            }
            $total_amount = getFormatNumber(ltrim($notify_data['body']["totalAmount"], '0')/100);
            $save = [
                'paid_money' => $total_amount,
                'third_plat_data' => $notify_data['body']['payOrderCode']
            ];
            M('Payment_money_order')->where($payment_where)->data($save)->save();
            
            $get_pay_param['order_param'] = [
                'pay_type' => $payment_money_order['pay_type'],
                'is_mobile' => 1,
                'order_type' => $payment_money_order['order_type'],
                'order_id' => $order_arr[1],
                'is_own' => 0,
                'third_id' => $notify_data['body']['payOrderCode'],
                'pay_money' => $total_amount
            ];
            if($get_pay_param['order_param']['order_type'] == 'group'){
                $now_order = $this->get_orderid('Group_order',$get_pay_param['order_param']['order_id'],$offline);
    //                $get_pay_param['order_param']['order_id']=$offline?$get_pay_param['order_param']['order_id']:$now_order['orderid'];
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);

            }else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
                $now_order = $this->get_orderid('Meal_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Meal_order')->after_pay($get_pay_param['order_param'], $get_pay_param['order_param']['order_type']);
            }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
                $now_order = $this->get_orderid('Weidian_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($get_pay_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($get_pay_param['order_param']['order_id']);
                }
            }else if($get_pay_param['order_param']['order_type'] == 'recharge'){
                $now_order = $this->get_orderid('User_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];

                $pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'waimai'){
                $now_order = $this->get_orderid('Waimai_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Waimai_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
                $now_order = $this->get_orderid('Wxapp_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Wxapp_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'store'){
                $now_order = $this->get_orderid('Store_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
                $now_order = $this->get_orderid('Shop_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'plat'){
                $now_order = $this->get_orderid('Plat_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'market'){
                $now_order = $this->get_orderid('Market_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'markets'){
                $now_order = $this->get_orderid('Market_total', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
            }
            echo "respCode=000000";
        }
        exit;
    }


    //公证处通知我们入账成功
    public function old_age_notice(){
        $input = file_get_contents("php://input");
        $notify_data = json_decode($input, true);
        fdump_sql(['input'=>$input, 'notify_data'=>$notify_data], "old_age_notice");
        $output = [
            'code' => 1,//0=成功 1=失败
        ];
        if(isset($notify_data['transactionSerialNumber'])){
            $record = M("Old_age_pension_records")->where(['transaction_number'=>trim($notify_data['transactionSerialNumber'])])->find();
            if(!empty($record)){
                M("Old_age_pension_records")->where(['id'=>$record['id']])->data(['status'=>($notify_data['isSuccess'] == '2000' ? 2 : 3)])->save();
                $output['code'] = 0;
            }            
        }
        echo json_encode($output);
        exit;
    }

    public function chinaums_ysf_notify(){
        import("@.ORG.pay.ChinaumsYsf");
        //1、取得MSG参数，并利用此参数值生成验证结果对象
        $pay = new ChinaumsYsf();

        //2、、支付成功
        $orderNo = trim($_POST['merOrderId']);
        if(!empty($orderNo)){
            $order_no = $pay->createOrderNo($orderNo, true);
            $payment_where = ['orderid'=>$order_no];
            $payment_money_order = M('Payment_money_order')->where($payment_where)->find();
            if(empty($payment_money_order)){
                echo "FAILED";
                exit;
            }
            $total_amount = getFormatNumber($_POST["totalAmount"]/100);
            $save = [
                'paid_money' => $total_amount,
                'pay_method' => 'chinaums',
                'third_plat_data' => $_POST['targetOrderId']
            ];
            M('Payment_money_order')->where($payment_where)->data($save)->save();
            
            $pay_types = [
                'Alipay 1.0' => 'alipay',
                'Alipay 2.0' => 'alipay',
                'WXPay' => 'weixin',
                'UnionPay' => 'unionpayapp',
                'UAC' => 'unionpayapp',
            ];
            $get_pay_param['order_param'] = [
                'pay_type' => $pay_types[$_POST['targetSys']] ?? 'weixin',
                'is_mobile' =>$payment_money_order['is_mobile_pay'],
                'order_type' => $payment_money_order['order_type'],
                'order_id' => $order_no,
                'is_own' => 0,
                'third_id' => $_POST['targetOrderId'],
                'pay_money' => $total_amount
            ];
            if($get_pay_param['order_param']['order_type'] == 'group'){
                $now_order = $this->get_orderid('Group_order',$get_pay_param['order_param']['order_id'],$offline);
    //                $get_pay_param['order_param']['order_id']=$offline?$get_pay_param['order_param']['order_id']:$now_order['orderid'];
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);

            }else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
                $now_order = $this->get_orderid('Meal_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Meal_order')->after_pay($get_pay_param['order_param'], $get_pay_param['order_param']['order_type']);
            }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
                $now_order = $this->get_orderid('Weidian_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($get_pay_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($get_pay_param['order_param']['order_id']);
                }
            }else if($get_pay_param['order_param']['order_type'] == 'recharge'){
                $now_order = $this->get_orderid('User_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];

                $pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'waimai'){
                $now_order = $this->get_orderid('Waimai_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Waimai_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
                $now_order = $this->get_orderid('Wxapp_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Wxapp_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'store'){
                $now_order = $this->get_orderid('Store_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
                $now_order = $this->get_orderid('Shop_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'plat'){
                $now_order = $this->get_orderid('Plat_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'market'){
                $now_order = $this->get_orderid('Market_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'markets'){
                $now_order = $this->get_orderid('Market_total', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
            }
            echo "SUCCESS";
        }
        else{
            echo "FAILED";
        }
        exit;
    }


    //智慧仙桃支付同步回调
    public function xiantao_return(){
        $order_type = $_GET['order_type'];
        $order_id = $_GET['order_id'];
        $order_no = $_GET['order_no'];

        // 订单详情
        $now_order = D('Pay')->getOnderInfo($order_type, $order_id);

        if(empty($now_order)) $this->error_tips('非法的订单');
        
        // 跳转链接
        $redirctUrl = D('Pay')->getBackUrl($order_type, $now_order, 1);

        if($now_order['paid'] && $order_type!='balance-appoint'){// 已支付过
            redirect($redirctUrl);exit;
        }
        fdump($redirctUrl,'xiantao_return',1);

        // 用户 
        $now_user = D('User')->get_user($now_order['uid']);

        try{

            import('@.ORG.pay.XianTaoCard');
            $pay = new XianTaoCard();
            
            $go_query_param = $pay->payInfo($order_no, $now_user['phone']);

        } catch(Exception $e){
            if($e->getCode() == 1225){// 未授权 去授权
                redirect(C('config.site_url') .'/wap.php?g=Wap&c=XianTaoPay&a=inputPwd&type=authorization&pay_param='.$_GET['pay_param']);
            }
            
            $this->error_tips($e->getMessage());
        }

        //判断回调金额
        //商城和快店可能会混淆，用in做查询
        $payment_where = ['order_id'=>$now_order['order_id'],'order_type'=>$order_type];
        if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
            $payment_where['order_type'] = ['in',['shop','mall']];
        }
        if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
            M('Payment_money_order')->where($payment_where)->setField('orderNo',$order_no);
            if($payment_money_order['pay_rate']>0){
                $go_query_param['order_param']['pay_money'] = getFormatNumber($go_query_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
                M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
            }else{
                M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
                    $this->error_tips('支付异常');
                }
            }
        }else{
            $this->error_tips('支付异常');
        }

        $go_query_param['order_param']['order_type'] = $order_type;
        $go_query_param['order_param']['pay_type'] = $payment_money_order['pay_type'];
        $go_query_param['order_param']['order_id'] = $order_id;

        $offline = false;
        if($order_type == 'group'){
            $now_order = $this->get_orderid('Group_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Group_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad'){
            $now_order = $this->get_orderid('Meal_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Meal_order')->after_pay($go_query_param['order_param'], $order_type);
        }else if($order_type == 'weidian'){
            $now_order = $this->get_orderid('Weidian_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Weidian_order')->after_pay($go_query_param['order_param']);
            if(($pay_info['error']==0) && isset($pay_info['url']) && ($go_query_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                $this->NoticeWDAsyn($go_query_param['order_param']['order_id']);
            }
        }else if($order_type == 'recharge'){
            $now_order = $this->get_orderid('User_recharge_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('User_recharge_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'appoint'){
            $now_order = $this->get_orderid('Appoint_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];


            D('Appoint_order')->after_pay($go_query_param['order_param']);

            // $pay_info = D('Appoint_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'balance-appoint'){
            $now_order = $this->get_orderid('Appoint_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            D('Appoint_order')->balance_after_pay($go_query_param['order_param']);
        }else if($order_type == 'waimai'){
            $now_order = $this->get_orderid('Waimai_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Waimai_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'wxapp'){
            $now_order = $this->get_orderid('Wxapp_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Wxapp_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'store'){
            $now_order = $this->get_orderid('Store_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Store_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'shop' || $order_type == 'mall'){
            $now_order = $this->get_orderid('Shop_order', $go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id'] = $now_order['orderid'];
            $pay_info = D('Shop_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'plat'){
            $now_order = $this->get_orderid('Plat_order', $go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id'] = $now_order['orderid'];
            $pay_info = D('Plat_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'integral_mall'){
            $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id'] = $now_order['orderid'];
            $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'mcard'){
            $now_order = $this->get_orderid('Membership_card_order', $go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id'] = $now_order['orderid'];
            $pay_info = D('Membership_card_order')->after_pay($go_query_param['order_param']);
        }else{
            $this->error_tips('订单类型非法！请重新下单。');
        }
        

        redirect($redirctUrl);
        if ($pay_info['error']==0) echo 'success';
    }

    //支付同步回调
    public function return_back() {
        $order_id_arr = explode('_', $_GET['order_no']);
        $order_type   = $order_id_arr[0];
        $order_id     = $order_id_arr[1];
        $pay_type     = $order_id_arr[2];
        switch($order_type) {
            case 'group':
                $now_order = D('Group_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'meal':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'recharge':
                $now_order = D('User_recharge_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'balance-appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'waimai':
                $now_order = D('Waimai_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'wxapp':
                $now_order = D('Wxapp_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'store':
                $now_order = D('Store_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'shop':
            case 'mall':
                $now_order = D('Shop_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'plat':
                $now_order = D('Plat_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'integral_mall':
                $now_order = D('Merchant_integral_mall_goods_order')->where(array('orderid'=>$order_id))->find();
                break;
            default:
                $this->error_tips('非法的订单');
        }
        if($now_order['paid'] && $order_type!='balance-appoint'){
            switch($order_type){
                case 'group':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$now_order['order_id'];
                    break;
                case 'meal':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'takeout':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'food':
                case 'foodPad':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'weidian':
                    $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>'','payment_method'=>'hqpay'));
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                    break;
                case 'wxapp':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                    break;
                case 'store':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                    break;
                case 'shop':
                    if($now_order['group_id']&&$now_order['team_id']){
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                    }
                    break;
                case 'mall':
                    if($now_order['group_id']&&$now_order['team_id']){
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                    }
                    break;
                case 'plat':
                    $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                    break;
                case 'integral_mall':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                    break;
            }
            redirect($redirctUrl);exit;
        }

        if(in_array($_GET['order_type'],array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','plat','balance-appoint')) && $now_order['mer_id']){
            $mer_id = $now_order['mer_id'];
        }else if($_GET['order_type'] == 'plat'){
            $get_order_info = D('Plat_order')->get_pay_order(0,$now_order['order_id']);
            if($get_order_info['order_info']['pay_merchant_ownpay'] && $get_order_info['order_info']['mer_id']){
                $mer_id = $get_order_info['order_info']['mer_id'];
            }else{
                $this->config['merchant_ownpay'] = 0;
            }
        }else{
            $this->config['merchant_ownpay'] = 0;
        }

        $pay_class_name = ucfirst($pay_type);
        $import_result  = import('@.ORG.pay.'.$pay_class_name);
        $pay_class      = new $pay_class_name('', '', $pay_type, [],$this->user_session,1);
        $go_query_param = $pay_class->searchPay($order_id);

        $offline = false;
        if ($go_query_param) {
            $go_query_param['order_param']['pay_money'] = $go_query_param['pay_money'];
            $go_query_param['order_param']['order_id']  = $order_id;
            $go_query_param['order_param']['pay_type']  = $go_query_param['pay_type'];
            $go_query_param['order_param']['third_id']  = $go_query_param['trade_no'];
            //判断回调金额
            //商城和快店可能会混淆，用in做查询
            $payment_where = ['order_id'=>$now_order['order_id'],'order_type'=>$order_type];
            if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
                $payment_where['order_type'] = ['in',['shop','mall']];
            }
            if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
                $go_query_param['order_param']['pay_type']  = $payment_money_order['pay_type'];
                if($payment_money_order['pay_rate']>0){
                    $go_query_param['order_param']['pay_money'] = getFormatNumber($go_query_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                }
                else{

                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                    if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
                        fdump_sql(array('go_query_param'=>$go_query_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_return');
                        $this->error_tips('支付异常');
                    }
                }
            }else{
                $this->error_tips('支付异常');
            }
            $payInfo = D('Pay')->after_pay($order_type, $go_query_param, $offline);
            if (!$payInfo) {
                $this->error_tips('支付异常');
            }
        }
        switch($order_type){
            case 'group':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$now_order['order_id'];
                break;
            case 'meal':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'takeout':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'food':
            case 'foodPad':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'weidian':
                $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
                break;
            case 'appoint':
            case 'balance-appoint':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$now_order['order_id'];
                break;
            case 'waimai':
                $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                break;
            case 'recharge':
                $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                break;
            case 'wxapp':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                break;
            case 'store':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                break;
            case 'shop':
                if($now_order['group_id']&&$now_order['team_id']){
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                }

                break;
            case 'mall':
                if($now_order['group_id']&&$now_order['team_id']){
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                }
                break;
            case 'plat':
                $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                break;
            case 'integral_mall':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                break;
        }
        redirect($redirctUrl);
    }

    //环球汇通支付同步回调
    public function hqpay_return() {
        $order_id_arr = explode('_', $_GET['order_no']);
        $order_type = $order_id_arr[0];
        $order_id = $order_id_arr[1];
        switch($order_type) {
            case 'group':
                $now_order = D('Group_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'meal':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'recharge':
                $now_order = D('User_recharge_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'balance-appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'waimai':
                $now_order = D('Waimai_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'wxapp':
                $now_order = D('Wxapp_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'store':
                $now_order = D('Store_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'shop':
            case 'mall':
                $now_order = D('Shop_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'plat':
                $now_order = D('Plat_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'integral_mall':
                $now_order = D('Merchant_integral_mall_goods_order')->where(array('orderid'=>$order_id))->find();
                break;
            default:
                $this->error_tips('非法的订单');
        }
        if($now_order['paid'] && $order_type!='balance-appoint'){
            switch($order_type){
                case 'group':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$now_order['order_id'];
                    break;
                case 'meal':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'takeout':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'food':
                case 'foodPad':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'weidian':
                    $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>'','payment_method'=>'hqpay'));
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                    break;
                case 'wxapp':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                    break;
                case 'store':
                    if(C('config.pay_success_jump_to_homepage')){
                        $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                    }
                    break;
                case 'shop':
                    if($now_order['group_id']&&$now_order['team_id']){
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                    }
                    break;
                case 'mall':
                    if($now_order['group_id']&&$now_order['team_id']){
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                    }else{
                        $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                    }
                    break;
                case 'plat':
                    $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                    break;
                case 'integral_mall':
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                    break;
            }
            redirect($redirctUrl);exit;
        }

        if(in_array($_GET['order_type'],array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','plat','balance-appoint')) && $now_order['mer_id']){
            $mer_id = $now_order['mer_id'];
        }else if($_GET['order_type'] == 'plat'){
            $get_order_info = D('Plat_order')->get_pay_order(0,$now_order['order_id']);
            if($get_order_info['order_info']['pay_merchant_ownpay'] && $get_order_info['order_info']['mer_id']){
                $mer_id = $get_order_info['order_info']['mer_id'];
            }else{
                $this->config['merchant_ownpay'] = 0;
            }
        }else{
            $this->config['merchant_ownpay'] = 0;
        }
        $pay_type = 'Hqpay';
        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        $pay_class = new $pay_class_name('', '', $pay_type, [],$this->user_session,1);
        $go_query_param = $pay_class->searchPay($order_id);

        $offline = false;
        if ($go_query_param) {
            $go_query_param['order_param']['pay_money'] = $go_query_param['pay_money'];
            $go_query_param['order_param']['order_id']  = $order_id;
            $go_query_param['order_param']['pay_type']  = 'hqpay';
            $go_query_param['order_param']['third_id']  = $go_query_param['trade_no'];
            //判断回调金额
            //商城和快店可能会混淆，用in做查询
            $payment_where = ['order_id'=>$now_order['order_id'],'order_type'=>$order_type];
            if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
                $payment_where['order_type'] = ['in',['shop','mall']];
            }
            if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
                $go_query_param['order_param']['pay_type']  = $payment_money_order['pay_type'];
                if($payment_money_order['pay_rate']>0){
                    $go_query_param['order_param']['pay_money'] = getFormatNumber($go_query_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                }
                else{

                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
                    if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
                        fdump_sql(array('go_query_param'=>$go_query_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_return');
                        $this->error_tips('支付异常');
                    }
                }
            }else{
                $this->error_tips('支付异常');
            }
            $payInfo = D('Pay')->after_pay($order_type, $go_query_param, $offline);
            if (!$payInfo) {
                $this->error_tips('支付异常');
            }
        }
        switch($order_type){
            case 'group':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=group_order&order_id='.$now_order['order_id'];
                break;
            case 'meal':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'takeout':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'food':
            case 'foodPad':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                break;
            case 'weidian':
                $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
                break;
            case 'appoint':
            case 'balance-appoint':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=My&a=appoint_order&order_id='.$now_order['order_id'];
                break;
            case 'waimai':
                $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                break;
            case 'recharge':
                $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                break;
            case 'wxapp':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                break;
            case 'store':
                if(C('config.pay_success_jump_to_homepage')){
                    $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                }
                break;
            case 'shop':
                if($now_order['group_id']&&$now_order['team_id']){
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Shop&a=status&order_id=' . $now_order['order_id'];
                }

                break;
            case 'mall':
                if($now_order['group_id']&&$now_order['team_id']){
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=group_team_detail&order_id=' . $now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                }else{
                    $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?c=Mall&a=status&order_id=' . $now_order['order_id'];
                }
                break;
            case 'plat':
                $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                break;
            case 'integral_mall':
                $redirctUrl = C('config.site_url').'/'.$this->indep_house.'?g=Wap&c=Integral_mall&a=order_detail_new&order_id=' . $now_order['order_id'];
                break;
        }
        redirect($redirctUrl);
    }

    public function douyin_refund_notify(){
        fdump_sql([file_get_contents("php://input"), $_REQUEST], 'douyinRefundNotify');
        echo json_encode(['err_no' => 0, 'err_tips' => 'success']);
        exit;
    }


    //拉卡拉（钱账通）支付异步通知
    public function lakala_notify(){
        //1、记录异步通知
        $_POST['event'] = html_entity_decode($_POST['event']);
        fdump_api($_POST, 'lakalaNotify',true);
        $eventType = $_POST['event_type'] ?? '';
        if($eventType !== 'order.consume.pay'){
            exit('SUCCESS');
        }
        $eventData = $_POST['event'] ? json_decode($_POST['event'], true) : [];
        $outTradeNo = $eventData['out_order_no'] ?? '';
        list($orderType,$order_no) = explode('_', $outTradeNo);

        //2、、支付成功
        if(!empty($order_no)){
            import('@.ORG.pay.Lakala');
            $pay = new Lakala();

            $payment_where = ['orderid' => $order_no];
            $payment_money_order = M('Payment_money_order')->where($payment_where)->find();
            if (empty($payment_money_order)) {
                exit('FAIL');
            }
            $payResult = $pay->notice();
            $total_amount = getFormatNumber($payResult['paid_money']);
            $save = [
                'paid_money' => $total_amount,
                'pay_method' => 'lakala',
                'third_plat_data' => $payResult['transaction_no']
            ];
            M('Payment_money_order')->where($payment_where)->data($save)->save();
            
            $get_pay_param['order_param'] = [
                'pay_type' => $payment_money_order['pay_type'],
                'is_mobile' =>$payment_money_order['is_mobile_pay'],
                'order_type' => $payment_money_order['order_type'],
                'order_id' => $order_no,
                'is_own' => 0,
                'third_id' => $payResult['transaction_no'],
                'pay_money' => $total_amount
            ];
            if($get_pay_param['order_param']['order_type'] == 'group'){
                $now_order = $this->get_orderid('Group_order',$get_pay_param['order_param']['order_id'],$offline);
    //                $get_pay_param['order_param']['order_id']=$offline?$get_pay_param['order_param']['order_id']:$now_order['orderid'];
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);

            }else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
                $now_order = $this->get_orderid('Meal_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Meal_order')->after_pay($get_pay_param['order_param'], $get_pay_param['order_param']['order_type']);
            }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
                $now_order = $this->get_orderid('Weidian_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
                if(($pay_info['error']==0) && isset($pay_info['url']) && ($get_pay_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                    $this->NoticeWDAsyn($get_pay_param['order_param']['order_id']);
                }
            }else if($get_pay_param['order_param']['order_type'] == 'recharge'){
                $now_order = $this->get_orderid('User_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];

                $pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'waimai'){
                $now_order = $this->get_orderid('Waimai_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Waimai_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
                $now_order = $this->get_orderid('Wxapp_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Wxapp_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'store'){
                $now_order = $this->get_orderid('Store_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
                $now_order = $this->get_orderid('Shop_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'plat'){
                $now_order = $this->get_orderid('Plat_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
                $now_order = $this->get_orderid('Appoint_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
                $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'market'){
                $now_order = $this->get_orderid('Market_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'markets'){
                $now_order = $this->get_orderid('Market_total', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
            }
            exit('SUCCESS');
        }
        else{
            exit('FAIL');
        }
    }
}
?>
