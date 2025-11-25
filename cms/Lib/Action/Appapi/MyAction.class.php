<?php
/*
 * IM聊天
 *
 */
class MyAction extends BaseAction{
    /* 我的 */
    public function my(){
        $activity_arr = array();
        $ticket = I('ticket', false);
        $device_id    =   I('Device-Id',false);
        if($ticket){
            $info = ticket::get($ticket, $device_id, true);
            $now_user = D('User')->get_user($info['uid']);
            if(empty($now_user['avatar'])){
                $now_user['avatar']   =   $this->config['site_url'] . '/static/images/user_avatar.jpg';
            }

            if(!empty($now_user)){
                $activity_arr[][] = array(
                    'code_title'=>  urlencode(L_('会员中心')),
                    'Ctitle'    =>  L_('会员中心'),
                    'phone'     =>  $now_user['phone'],
                    'nickname'  =>  $now_user['nickname'],
                    'now_user'  =>  $now_user['now_user'],
                    'now_money' =>  floatval($now_user['now_money']),
                    'score_count'=> floatval($now_user['score_count']),
                    'url'       =>  $this->config['site_url'].U('My/myinfo'),
                    'status'    =>  $now_user['status'],
                    'level'     =>  $now_user['level'],
                    'avatar'    =>  $now_user['avatar'],
                    'bg'        =>  $this->config['site_url'].'/tpl/Wap/default/static/images/my-photo.png',
                );
            }else{
                $activity_arr[][]   =   array();
            }
        }else{
            $activity_arr[][]   =   array();
        }

        //多行
        $order_array = array(
            array(
                'title'=>L_('X1订单',$this->config['group_alias_name']),
                'intro'=>L_('X1订单',$this->config['group_alias_name']),
                'image'=> $this->config['site_url'] . '/static/images/im/tubaio1_03.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] .  '/wap.php?g=Wap&c=My&a=group_order_list',
            ),
            array(
                'title'=>L_('X1订单',$this->config['group_alias_name']),
                'intro'=>L_('X1订单',$this->config['group_alias_name']),
                'image'=> $this->config['site_url'] . '/static/images/im/tubiao_13.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] .  '/wap.php?g=Wap&c=My&a=meal_order_list',
            ),
            array(
                'title'=>L_('预约订单'),
                'intro'=>L_('预约订单'),
                'image'=> $this->config['site_url'] . '/static/images/im/tubiao_33.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] .  '/wap.php?g=Wap&c=My&a=appoint_order_list',
            ),
        );
        if($this->config['live_service_appid']){
            $order_array[] = array(
                'title'=>L_('生活缴费订单'),
                'intro'=>L_('生活缴费订单'),
                'image'=> $this->config['site_url'] . '/static/images/im/tubiao_14.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] .  '/wap.php?g=Wap&c=My&a=lifeservice',
            );
        }
        $activity_arr[] = isset($order_array) ? $order_array : array();

        //多行
        $activity_arr[] = array(
            array(
                    'title'=>L_('我的收藏'),
                    'intro'=>L_('我的收藏'),
                    'image'=> $this->config['site_url'] . '/static/images/im/tubaio1_06.png', /*图片暂定为 26*26的像素 */
                    'url' => $this->config['site_url'] . '/wap.php?g=Wap&c=My&a=group_collect',
            ),
            array(
                'title'=>L_('我关注的商家'),
                'intro'=>L_('我关注的商家'),
                'image'=> $this->config['site_url'] . '/static/images/im/tubiao_15.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] .  '/wap.php?g=Wap&c=My&a=follow_merchant',
            ),
            array(
                'title'=>L_('我参与的活动'),
                'intro'=>L_('我参与的活动'),
                'image'=> $this->config['site_url'] . '/static/images/im/tubiao_16.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] .  '/wap.php?g=Wap&c=My&a=join_lottery',
            ),
        );
        //多行
        $activity_arr[] = array(
            array(
                    'title'=>L_('我的优惠券'),
                    'intro'=>L_('我的优惠券'),
                    'image'=> $this->config['site_url'] . '/static/images/im/tubaio1_10.png', /*图片暂定为 26*26的像素 */
                    'url' => $this->config['site_url'] . '/wap.php?g=Wap&c=My&a=card_list',
            ),
            array(
                'title'=>L_('我的会员卡'),
                'intro'=>L_('我的会员卡'),
                'image'=> $this->config['site_url'] . '/static/images/im/tubiao_17.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] .  '/wap.php?g=Wap&c=My&a=cards',
            ),
        );
        //检测是否有分类信息
        if(isset($this->config['wap_home_show_classify']) && $this->config['wap_home_show_classify']==1){
            $activity_arr[] = array(
                    'title'=>L_('我的发布'),
                    'intro'=>L_('我的发布'),
                    'image'=> $this->config['site_url'] . '/static/images/im/tubiao_19.png', /*图片暂定为 26*26的像素 */
                    'url'=> $this->config['site_url'] .  '/wap.php?c=Classify&a=myCenter',
            );
        }

        $this->returnCode(0,$activity_arr);
    }

    /**
     * 检测自动升级
     */
    public function check_level()
    {
        $uid = $this->_uid;
        if (!empty($uid)) {
            $now_user = D('User')->get_user($uid);
            if ($this->config['level_update_auto']) {
                $level_arr = $this->user_level;
                $level_arr = sortArrayAsc($level_arr, 'level');
                M('User')->where(array('uid' => $uid))->setField('level', 0);
                for ($i = count($level_arr) - 1; $i >= 0; $i--) {
                    if ($now_user['score_count'] >= $level_arr[$i]['integral']) {
                        $user_data = array();
                        $user_data['level'] = $level_arr[$i]['level'];
                        $user_data['level_time'] = time();
                        M('User')->where(array('uid' => $uid))->save($user_data);
                        fdump_sql(['now_user'=>$now_user,'level_arr'=>$level_arr,'user_data'=>$user_data,'sql'=>M('User')->getLastSql(),'REQUEST_URI'=>$_SERVER['REQUEST_URI']],"level_update_auto");
                        $now_user['level'] = $level_arr[$i]['level'];
                        break;
                    }
                }
            }
            if ($this->config['level_balance']) {
                $now_level = M(_view('User_level'))->where(array('level' => $now_user['level']))->find();
                $time = time();
                //超时去除level
                if ($now_user['level'] != 0 && $now_level['validity'] != 0 && $now_user['level_time'] != 0 && ($time - $now_user['level_time']) / 86400 > $now_level['validity']) {
                    M('User')->where(array('uid' => $uid))->setField('level', 0);
                    $this->user_session['level'] = 0;
                    $log['uid'] = $uid;
                    $log['des'] = '等级到期，系统自动取消';
                    $log['add_time'] = $time;
                    M('User_level_log')->add($log);
                }
            }
        }
    }

    /*选择优惠券*/
    public function select_card(){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}
		
        $_GET['type'] = I('type');
        $_GET['order_id']   =   I('order_id');
        if($_GET['type'] == 'group'){
            $now_order = D('Group_order')->get_order_by_id($this->_uid,$_GET['order_id']);
        }else if($_GET['type'] == 'meal' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad' || $_GET['type'] == 'takeout'){
            $now_order = D('Meal_order')->get_order_by_id($this->_uid,$_GET['order_id']);
        }else if($_GET['type'] == 'weidian'){
            $now_order = D('Weidian_order')->get_order_by_id($this->_uid,$_GET['order_id']);
        }else if($_GET['type'] == 'appoint'){
            $now_order = D('Appoint_order')->get_order_by_id($this->_uid,$_GET['order_id']);
        }else if($_GET['type'] == 'shop'){
            $now_order = D('Shop_order')->get_order_by_id($this->_uid,$_GET['order_id']);
        }else if($_GET['type'] == 'integral_mall'){
            $card_list = array();
            $arr = isset($card_list) ? $card_list:null;
            $this->returnCode(0,$arr);
        }else{
            //$this->error_tips('来源非法，请检查后再访问。');
            $this->returnCode('20031005');
        }
        if(empty($now_order)){
            $this->returnCode('20031004');
        }
        //$arr['back_url']  =   $this->config['site_url'].U('Pay/check',$_GET);
        $card_list = D('Member_card_coupon')->get_coupon($now_order['mer_id'],$this->_uid);

        if(!empty($card_list)){
            foreach($card_list as &$value){
                $value['card_id'] =$value['record_id'];
                $value['pic']   =   $this->config['site_url'].$value['pic'];
            }
        }else{
            $card_list = array();
        }
        $arr = isset($card_list) ? $card_list:null;
        $this->returnCode(0,$arr);
    }

    //平台优惠券
    public function select_coupon(){
		
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}

        $_GET['type'] = I('type');
        $_GET['order_id'] = I('order_id');
        if ($_GET['type'] == 'group') {
            $now_order = D('Group_order')->get_order_by_id($this->_uid, $_GET['order_id']);
        } else if ($_GET['type'] == 'meal' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad' || $_GET['type'] == 'takeout') {
            $now_order = D('Meal_order')->get_order_by_id($this->_uid, $_GET['order_id']);
        } else if ($_GET['type'] == 'weidian') {
            $now_order = D('Weidian_order')->get_order_by_id($this->_uid, $_GET['order_id']);
        } else if ($_GET['type'] == 'appoint') {
            $now_order = D('Appoint_order')->get_order_by_id($this->_uid, $_GET['order_id']);
        } else if($_GET['type'] == 'shop'){
            $now_order = D('Shop_order')->get_order_by_id($this->_uid,$_GET['order_id']);
        } else {
			$this->returnCodeOk(array());
            $this->returnCode('20031005');
        }
        if (empty($now_order)) {
			$this->returnCodeOk(array());
            $this->returnCode('20031004');
        }
        //判断平台
        if($this->is_app_browser){
            $platform = 'app';
        }else if($this->is_weixin_browser){
            $platform = 'weixin';
        }elseif ($this->is_wxapp_browser) { 
            $platform = 'wxapp';
        }  else {
            $platform = 'wap';
        }
        $coupone_list = D('System_coupon')->get_noworder_coupon_list($now_order,I('type'),$now_user['phone'],$now_user['uid'],$platform);
        foreach($coupone_list as $k=>&$v){
            if($v['cate_id']!='0'){
                $v['cate_name'].=$v['cate_id']['cat_name'];
            }
            if($v['cate_name']=='all'){
                $v['cate_name']=L_('全通类优惠券');
            }
            if($v['is_discount']){
                $v['coupon_des'] = ($v['order_money']>0?L_('满X1',C('config.Currency_symbol') . $v['order_money']):'').L_('打X1折',$v['discount_value']);
            }else{
                $v['coupon_des'] = ($v['order_money']>0?L_('满X1',$v['order_money'].C('config.Currency_txt')):'').L_('减X1',C('config.Currency_symbol') . $v['discount']);
            }
            unset($coupone_list[$k]['cate_id']);
        }

        $arr =  isset($coupone_list) ? $coupone_list:array();
        $this->returnCode(0,$arr);
    }

    /**
     * 外卖3.0选择优惠券
     * @author 张涛
     * @date 2020/04/27
     */
    public function coupon_list()
    {
        $this->new_select_coupon(true);
    }


    //新的版本优惠券接口

    public function new_select_coupon($is_new = false){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}

        $_GET['type'] = I('type');
        $_GET['order_id'] = I('order_id');
        $_GET['order_money'] = I('order_money');//跑腿优惠券订单金额
        $_GET['coupon_type'] = I('coupon_type');
        $_GET['merc_id'] = I('merchant_coupon_id');
        $_GET['sysc_id'] = I('system_coupon_id');
        $_GET['card_id'] = I('card_id');
        $temp_order_id = I('temp_order_id', 0);

        //判断平台
        if ($this->is_wxapp_browser) {
            $platform = 'wxapp';
        } elseif ($this->is_weixin_browser) {
            $platform = 'weixin';
        } elseif ($this->is_app_browser) {
            $platform = 'app';
        } else {
            $platform = 'wap';
        }

        if ($_GET['type'] != 'deliver') {

            $isOpenMembershipCard = isset($_POST['open_membership_card']) ? intval($_POST['open_membership_card']) : 0;

            if (empty($_GET['order_id']) && empty($temp_order_id)) {
                $this->returnCodeOk(array());
            }
            $tempOrderData = [];
            $is_system_full_discount_order = false;
            if ($temp_order_id > 0 && $_GET['type'] != 'dining' ) {
                if($_GET['type'] == 'group'){
                    $tempOrderData = M('Group_order_temp')->where(['id' => $temp_order_id])->find();
                    if (empty($tempOrderData)) {
                        $this->returnCodeOk(array());
                    }
                }else{
                    $tempOrderData = M('Temp_order_data')->where(['temp_id' => $temp_order_id])->find();
                    if (empty($tempOrderData)) {
                        $this->returnCodeOk(array());
                    }
                    if($tempOrderData['card_id'] && empty($_GET['merc_id'])){
                        $_GET['merc_id'] = $tempOrderData['card_id'];
                    }
                    if($tempOrderData['coupon_id'] && empty($_GET['sysc_id'])){
                        $_GET['sysc_id'] = $tempOrderData['coupon_id'];
                    }
                    $tempOrderData = json_decode($tempOrderData['order_data'],true);
                    $temp_detail = unserialize($tempOrderData['discount_detail']);
                    $is_system_full_discount_order = D('Shop_order')->is_system_full_discount_order($temp_detail);
                }
                $tempOrderData['temp_order_id'] = $temp_order_id;
            }elseif($_GET['type'] == 'village_group'){
                $tempOrderData = M('Village_group_order_temp')->where(['order_id' => $_GET['order_id']])->find();
                if (empty($tempOrderData)) {
                    $this->returnCodeOk(array());
                }
            }
    
            if($_GET['type'] == 'group'){
                $now_order = D('Group_order')->get_pay_order($this->_uid,intval($_GET['order_id']));
                if($now_order['order_info']['group_share_num']>0||$now_order['order_info']['pin_num']>0)$group_pay_offline=false;
            }else if($_GET['type'] == 'meal' || $_GET['type'] == 'takeout' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad'){
                $now_order = D('Meal_order')->get_pay_order($this->_uid,intval($_GET['order_id']), false, $_GET['type']);
                if ($now_order['order_info']['paid'] == 2) $this->assign('notCard',true);
                $_GET['type']  = 'meal';
            }else if($_GET['type'] == 'weidian'){
                $now_order = D('Weidian_order')->get_pay_order($this->_uid,intval($_GET['order_id']));
                $this->assign('notCard',true);
            }else if($_GET['type'] == 'recharge'){
                $now_order = D('User_recharge_order')->get_pay_order($this->_uid,intval($_GET['order_id']));
                $this->assign('notCard',true);
            }else if($_GET['type'] == 'appoint'){
                $now_order = D('Appoint_order')->get_pay_order($this->_uid,intval($_GET['order_id']));
            }else if($_GET['type'] == 'wxapp'){
                $_GET['notOffline'] = true;
                $now_order = D('Wxapp_order')->get_pay_order($_GET['uid'],intval($_GET['order_id']));
                $this->assign('notCard',true);
            }else if($_GET['type'] == 'store'){
                $_GET['notOffline'] = true;
                $now_order = D('Store_order')->get_pay_order($this->_uid, intval($_GET['order_id']));
                //$this->assign('notCard',true);
            }else if($_GET['type'] == 'shop' ||$_GET['type'] == 'mall'){
                $now_order = D('Shop_order')->get_pay_order($this->_uid, intval($_GET['order_id']),false,false,$tempOrderData);
            }else if($_GET['type'] == 'plat'){
                $now_order = D('Plat_order')->get_pay_order($this->_uid, intval($_GET['order_id']));
            }else if($_GET['type'] == 'balance-appoint'){
                $now_order = D('Appoint_order')->get_pay_balace_order($this->_uid,intval($_GET['order_id']));
            }else if($_GET['type'] == 'appoint'){
                $now_order = D('Appoint_order')->get_pay_balace_order($this->_uid,intval($_GET['order_id']));
            }else if($_GET['type'] == 'village_group'){
                $now_order = D('Village_group_order')->get_pay_order(intval($_GET['order_id']));
            }else if($_GET['type'] == 'integral_mall'){
                $arr = array();
                $this->returnCode(0,$arr);
            }else if($_GET['type'] == 'dining' && $temp_order_id){
                $now_order = D('Dining_order_pay_temp')->where(['id'=>$temp_order_id])->find();
                $now_order['order_info'] = $now_order;
            }else if($_GET['type'] == 'mcard'){
                $now_order = D('Membership_card_order')->get_pay_order($this->_uid, intval($_GET['order_id']));
            }else if (empty($tempOrderData)) {
                $this->returnCode('20044010',L_('非法的订单'));
            }
    
            $now_order = !empty($now_order['order_info']) ? $now_order['order_info'] : $tempOrderData;
            $tmp_order = $now_order;
            $tmp_order['uid'] = $this->_uid;
            $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->_uid, $now_order['mer_id']);
    
    
            if($card_info['discount']<=0||$card_info['discount']>10||empty($card_info['discount'])){
                $card_info['discount'] = 10;
            }
    
            if($_GET['type'] == 'dining'){
                $_GET['type']  = 'meal';
            }else{
                if($_GET['type'] == 'group' && $temp_order_id){
                      $tmp_order['total_money'] = $tmp_order['total_money'];
                }elseif($_GET['type'] == 'village_group'){
                    $tmp_order['total_money'] = $tmp_order['total_money'];
                }else{
                    $tmp_order['total_money'] = $tmp_order['order_total_money']*$card_info['discount']/10;
                }
            }
    
            if (empty($now_order)) {
                $this->returnCode('20031004');
            }
        //判断平台
        if ($this->is_wxapp_browser) {
            $platform = 'wxapp';
        } 
        elseif($this->is_weixin_browser){
            $platform = 'weixin';
        }
        elseif($this->is_app_browser){
            $platform = 'app';   
        }
        else{
            $platform = 'wap';      
        }

            if ($_GET['coupon_type'] == 'mer') {
                if ($_GET['sysc_id']) {
                    $sys_coupon = D('System_coupon')->get_coupon_by_id($_GET['sysc_id']);
                    $tmp_order['total_money'] -= $sys_coupon['discount'];
                }
                $order_detail_goods = [];
                if ($_GET['type'] == 'mall' || $_GET['type'] == 'shop') {//商城和外卖的订单， 筛选商家优惠券不能用can_discount_money，因为增加了优惠券指定商品功能
                    $order_detail_goods = array_column(M("Shop_order_detail")->where(['order_id' => $now_order['order_id']])->field("goods_id,price*num as allprice")->select(), "allprice", 'goods_id');
                    if (empty($order_detail_goods) && $tempOrderData['goods']) {
                        $order_detail_goods = array_column($tempOrderData['goods'], 'discount_price', 'goods_id');
                    }
                    $no_total_money = true;
                }elseif($_GET['type'] == 'group'){
                    $order_detail_goods = [$now_order['group_id']=>$now_order['total_money']];
                }

                $coupon_list = D('Card_new_coupon')->get_user_all_coupon_list($tmp_order['uid'], 1);
                if ($_GET['type'] == 'shop' && !$no_total_money) {
                    $tmp_order['total_money'] = $now_order['can_discount_money'];
                }
            } else if ($_GET['coupon_type'] == 'system') {
                if ($_GET['merc_id']) {
                    $mer_coupon = D('Card_new_coupon')->get_coupon_by_id($_GET['merc_id']);
                    $tmp_order['total_money'] -= $mer_coupon['discount'];
                }

                $coupon_list = D('System_coupon')->get_user_coupon_list($tmp_order['uid'], '', 1);

                //子商户设置不允许使用平台优惠抵扣
                $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                if ($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0 && $now_merchant['sub_mch_discount'] != 1) {
                    $coupon_list = array();
                }

                //平台优惠券增加会员卡赠送优惠券信息
                if ($_GET['type'] == 'shop' && $isOpenMembershipCard) {
                    $membershipCardCoupon = (new Membership_cardModel())->getCouponInfo();
                    if ($membershipCardCoupon) {
                        $coupon_list = array_merge($membershipCardCoupon, $coupon_list);
                    }
                }
            }

            /*2018/12/19 去除商城活动订单使用优惠券功能*/
            if ($_GET['type'] == 'mall' && $now_order['order_from'] == 1 && ($now_order['group_id'] > 0 || $now_order['bar_id'] > 0)) {
                $coupon_list = array();
            }
        } else {//跑腿优惠券
            $coupon_list = D('System_coupon')->get_user_coupon_list($this->_uid, '', 1, $_GET['type']);
            $tmp_order['order_money'] = $_GET['order_money'];
            $tmp_order['uid'] = $this->_uid;
            $_GET['coupon_type'] = 'system';
            $order_detail_goods = [];
        }

        // 处理不能使用优惠券不能使用原因
        $result = D('System_coupon')->getCanUseCoupon($tmp_order, $coupon_list, $_GET['type'], $platform, $_GET['coupon_type'], $order_detail_goods);
        $coupon_list_ = array_merge($result['can_use_coupon_list'],$result['un_use_coupon_list']);

        //旧版本屏蔽掉不可用优惠券返回
        if (!$is_new) {
            foreach ($coupon_list_ as $k => $v) {
                if ($v['can_use'] == 0) {
                    unset($coupon_list_[$k]);
                }
            }
            $coupon_list_ = array_values($coupon_list_);
        }

        $arr =  isset($coupon_list_) ? $coupon_list_:array();
        $this->returnCode(0,$arr);
    }



		//	我的新版页面
    public function my_new(){
        $activity_arr = array();
        if($this->_uid){
            $this->check_level();
            $now_user = D('User')->get_user($this->_uid);
			if($_POST['registrationId'] && $_POST['registrationId'] != $now_user['jpush_registrationId']){
				D('User')->save_user($this->_uid, 'jpush_registrationId', $_POST['registrationId']);
			}
		}
		if(empty($now_user['avatar'])){
			$now_user['avatar']   =   $this->config['site_url'] . '/static/images/user_avatar.jpg';
		}
		$level = M(_view('User_level'))->getField('level,lname');
		$now_user['lname'] = $this->_uid ? ($now_user['level'] ? $level[$now_user['level']] : 'VIP0') : '--';
		
		if($this->_uid){
			D('User')->check_score($this->_uid);
		}
		
		//	我的信息
		if($this->_uid){
			$recently_sign = M('User_sign')->where(array('uid'=>$now_user['uid']))->order('id DESC')->find();
			$today_sing = 0;
			if($recently_sign && strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))==strtotime(date('Ymd',$recently_sign['sign_time']))){
				$today_sing = 1;
			}
		}

		$activity_arr['user'] = array(
            'uid' => $now_user['uid'] ?? '',
			'phone_s'   =>  $now_user['showPhone'],
			'phone'     =>  $now_user['phone'],
			'nickname'  =>  $now_user['nickname'],
			'now_money' =>  $this->_uid ? getFormatNumber($now_user['now_money']) : '--',
			'score_count'=> $this->_uid ? getFormatNumber($now_user['score_count']) : '--',
			'level'     =>  $now_user['lname'],
			'avatar'    =>  $now_user['avatar'],
			'bg'        =>  $this->config['site_url'].'/tpl/Wap/pure/static/images/my-photo.png',
			'sign_today'        => $today_sing,
			'sign_get_score'    => intval($this->config['sign_get_score']),
            'open_custom_sign'  => C('config.open_custom_sign') ? "1" : "0",
            'custom_sign_url'   => $this->config['site_url'].'/wap.php?g=Wap&c=My&a=sign',
			'frozen_money'      => $this->_uid ? 0 : '--',
			'frozen_reason'     => '',
			'frozen_time'       => '',
            'usercenter_gift_open' => $this->config['usercenter_gift_open'] ? : 0
		);

        //增加企业预存款
        $qiye = D('Qiye')->getQiyeStaffByUid($this->_uid);
        $activity_arr['qiye_user'] = $qiye ?: new \stdClass();

		if($now_user['frozen_money'] > 0 && $this->config['open_frozen_money'] == 1 && $now_user['free_time']>$_SERVER['REQUEST_TIME']){
			$activity_arr['user']['frozen_money'] = $this->_uid ? getFormatNumber($now_user['frozen_money']) : '--';
			$activity_arr['user']['frozen_reason'] = $now_user['frozen_reason'];
			$activity_arr['user']['frozen_time'] = date('Y-m-d',$now_user['frozen_time']).'-'.date('Y-m-d',$now_user['free_time']);
		}
		if($this->config['open_score_fenrun']==1){
			$activity_arr['user']['fenrun_money']  = $this->_uid ? $now_user['fenrun_money'] : '--';
			$activity_arr['user']['fenrun_name']  = L_('分润钱包');
			$activity_arr['user']['fenrun_url']  = $this->config['site_url'].'/wap.php?g=Wap&c=Fenrun&a=fenrun_money_list';

			$activity_arr['user']['fenrun_now_money']  = $this->_uid ? getFormatNumber($now_user['now_money']) : '--';
			$activity_arr['user']['fenrun_now_money_name']  = L_('余额');
			$activity_arr['user']['fenrun_now_money_url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=money_list';

			$activity_arr['user']['fenrun_score']  =  $this->_uid ? getFormatNumber($now_user['score_count']) : '--';
			$activity_arr['user']['fenrun_score_name']  = $this->config['score_name'];
			$activity_arr['user']['fenrun_score_url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=score_list';

			$activity_arr['user']['fenrun_level']  = $this->_uid ? $now_user['level'] : '--';
			$activity_arr['user']['fenrun_level_name']  = L_('等级');
			$activity_arr['user']['fenrun_level_url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=levelUpdate';
		}else{
			$activity_arr['user_link']['now_money']  = $this->_uid ? getFormatNumber($now_user['now_money']) : '--';
			$activity_arr['user_link']['now_money_name']  = L_('余额');
			$activity_arr['user_link']['now_money_url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=my_money';

			$activity_arr['user_link']['score']  =  $this->_uid ? getFormatNumber($now_user['score_count']) : '--';
			$activity_arr['user_link']['score_name']  = $this->config['score_name'];
			$activity_arr['user_link']['score_url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=integral';

			$activity_arr['user_link']['level']  = $this->_uid ? $now_user['level'] : '--';
			$activity_arr['user_link']['level_name']  = L_('等级');
			$activity_arr['user_link']['level_url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=levelUpdate';
		}

		$activity_arr['open_company_advance_deposit']  = $this->config['open_company_advance_deposit'];
        $activity_arr['bind_company'] = 0;
        $activity_arr['user_link_arr'] = [];
		if($this->config['open_company_advance_deposit']){
			// 开启企业预存款余额功能
            $activity_arr['user_link_arr'][0]['value'] = $this->_uid ? sprintf('%.2f', $now_user['now_money']) : '--';
            $activity_arr['user_link_arr'][0]['name'] = L_('平台余额');
            $activity_arr['user_link_arr'][0]['url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=My&a=my_money';

            $qiyeStaff = M('Qiye_staff')->alias('staff')->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye AS q ON staff.qiye_id = q.qiye_id')
                ->where(['staff.job_number' => $this->_uid])
                ->field('staff.money,q.name')
                ->find();
            if ($qiyeStaff) {
                $activity_arr['bind_company'] = 1;
                $activity_arr['company_name'] = $qiyeStaff['name'];
                $qiyeBalance = sprintf('%.2f', $qiyeStaff['money']);
            } else {
                $activity_arr['bind_company'] = 0;
                $activity_arr['company_name'] = '';
                $qiyeBalance = 0.00;
            }
			$activity_arr['user_link_arr'][1]['value']  =  $this->_uid ? $qiyeBalance : '--';
			$activity_arr['user_link_arr'][1]['name']  = L_('企业预存款');
			$activity_arr['user_link_arr'][1]['url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=qiye_staff_money_list';

			$activity_arr['user_link_arr'][2]['value']  = 0;
			$activity_arr['user_link_arr'][2]['name']  = L_('商家会员卡');
			$activity_arr['user_link_arr'][2]['url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=cards';
		}else{
			if($this->config['open_user_money'] || getFormatNumber($now_user['now_money'])){
				$activity_arr['user_link_arr'][0]['value']  = $this->_uid ? sprintf('%.2f',$now_user['now_money']) : '--';
				$activity_arr['user_link_arr'][0]['name']  = L_('余额');
				$activity_arr['user_link_arr'][0]['url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=my_money';
			}

            if (is_null(C('config.my_score_show')) || C('config.my_score_show') == 1) {
                $activity_arr['user_link_arr'][1]['value']  =  $this->_uid ? getFormatNumber($now_user['score_count']) : '--';
                $activity_arr['user_link_arr'][1]['name']  = $this->config['score_name'];
                $activity_arr['user_link_arr'][1]['url']  = $this->config['site_url'] . '/wap.php?g=Wap&c=My&a=integral';
            }

            if($this->config['open_yanglao_and_sande'] == '1'){
                $activity_arr['user_link_arr'][2]['value']  = getFormatNumber((M("Old_age_pension_records")->where(['uid'=>$this->_uid, 'status'=>['lt', 3]])->sum("money"))/100);
                $activity_arr['user_link_arr'][2]['name']  = $this->config['old_age_pension_alias'] ?: '养老金';
                $activity_arr['user_link_arr'][2]['url']  = $this->config['site_url'].D('House_village')->base_url.'pages/my/oldAgePension/manage';

    			$activity_arr['user_link_arr'][3]['value']  = $this->_uid ? $now_user['lname'] : '--';
    			$activity_arr['user_link_arr'][3]['name']  = L_('等级');
    			$activity_arr['user_link_arr'][3]['url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=levelUpdate';
            }
            else{
                if($level){
				$activity_arr['user_link_arr'][2]['value']  = $this->_uid ? $now_user['lname'] : '--';
				$activity_arr['user_link_arr'][2]['name']  = L_('等级');
				$activity_arr['user_link_arr'][2]['url']  = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=levelUpdate';
			}
            }
			$activity_arr['user_link_arr'] = array_values($activity_arr['user_link_arr']);
		}

		if(isset($config['specificfield'])){
			$activity_arr['user']['is_data']	=	1;
			$activity_arr['user']['data_url']	=	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=inputinfo';
		}else{
			$activity_arr['user']['is_data']	=	0;
		}


        //我的佣金
        if($this->config['open_score_fenrun']==1){
            $activity_arr['fenrun'] = array(
                array(
                   // 'title'=>'可用佣金',
                    'money'=>$now_user['free_award_money'],
                    //'image'=> $this->config['site_url'].'、tpl/Wap/pure/static/images/grzx_03.png', /*图片暂定为 26*26的像素 */
                    'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=Fenrun&a=user_free_award_list',
                ),
                array(
                   // 'title'=>'冻结佣金',
                    'money'=>$now_user['frozen_award_money'],
                    //'image'=> $this->config['site_url'].'、tpl/Wap/pure/static/images/grzx_05.png', /*图片暂定为 26*26的像素 */
                    'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=Fenrun&a=frozen_award_index',
                ),
            );
        }
	    //	我的订单
		$order_array = array();
		if($this->config['group_alias_name']&&!$this->config['open_company_advance_deposit']){
			$order_array[] = array(
                'title'=>L_('X1订单',$this->config['group_alias_name']),
                'image'=> $this->config['site_url'].'/static/images/my_new/tuangou.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=group_order_list',
            );
		}
		if($this->config['shop_alias_name']){
			$order_array[] = array(
                'title'=>L_('X1订单',$this->config['shop_alias_name']),
                'image'=> $this->config['site_url'].'/static/images/my_new/kuaidian.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=shop_order_list',
            );
		}

        if (!$this->config['open_company_advance_deposit']) {
            /*商城订单*/
            if($this->config['mall_alias_name']){
                $order_array[] = array(
                    'title'=>L_('X1订单',$this->config['mall_alias_name']),
                    'image'=> $this->config['site_url'].'/static/images/my_new/mall.png', /*图片暂定为 26*26的像素 */
                    'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=Mall&a=mall_order_list',
                );
            }
        }
		if($this->config['meal_alias_name']){
			$order_array[] = array(
                'title'=>L_('X1订单',$this->config['meal_alias_name']),
                'image'=> $this->config['site_url'].'/static/images/my_new/canyin.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=foodshop_order_list',
            );
		}
		if($this->config['appoint_page_row']&&!$this->config['open_company_advance_deposit']){
			$order_array[] = array(
				'title'=>L_('X1订单',$this->config['appoint_alias_name']),
                'image'=> $this->config['site_url'].'/static/images/my_new/yuyue1.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=appoint_order_list',
            );
		}
		if($this->config['live_service_appid']){
            $order_array[] = array(
                'title'=>L_('缴费订单'),
                'image'=> $this->config['site_url'].'/tpl/Wap/default/static/images/new_my/pay.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=lifeservice',
            );
        }

        if(!$this->config['open_company_advance_deposit']){
    		if($this->config['wap_home_show_classify']==1){
    			$order_array[] = array(
    				'title'=>L_('分类信息'),
                    'image'=> $this->config['site_url'].'/static/images/my_new/xinxi.png', /*图片暂定为 26*26的像素 */
                    'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=classify_order_list',
                );
    		}
        }
		if($this->config['pay_in_store']){
			$order_array[] = array(
				'title'=>L_('到店付'),
                'image'=> $this->config['site_url'].'/static/images/my_new/apply.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=store_order_list',
            );
		}
		if($this->config['gift_alias_name']&&!$this->config['open_company_advance_deposit']){
			$order_array[] = array(
				'title'=>$this->config['gift_alias_name'],
				'image'=> $this->config['site_url'].'/static/images/my_new/jifen.png', /*图片暂定为 26*26的像素 */
				'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=gift_order_list',
			);
		}
        if($this->config['mobile_recharge_APIKey'] && $this->config['mobile_recharge_openid']){
            $order_array[] = array(
                'title'=>L_('话费订单'),
                'image'=> $this->config['site_url'].'/static/images/my_new/huafei.png',
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=Third_recharge&a=mobile_recharge_list'
            );
        }
		if(isset($this->config['provider_is_real_name'])&&!$this->config['open_company_advance_deposit']){
			$order_array[] = array(
				'title'=>L_('服务快派'),
				'image'=> $this->config['site_url'].'/static/images/my_new/kuaipai.png',
				'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=Service&a=need_list_app'
			);
		}
        /*景区订单*/
        if($this->config['scenic_alias_name']){
            $order_array[] = array(
                'title'=>L_('X1订单',$this->config['scenic_alias_name']),
                'image'=> $this->config['site_url'].'/static/images/my_new/scenic.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=Scenic_user&a=ticket_order_list',
            );
        }

        //商家管理
        if($this->config['show_merchant_in_personal_center'] == 2){
            $order_array[] = array(
                'title' => L_('商家中心'),
                'image' => $this->config['site_url'] . '/static/images/my_new/merchant2.png', /*图片暂定为 26*26的像素 */
                'url'   => $this->config['site_url'] . '/packapp/merchant/',
            );
        }

        if (!$this->config['open_company_advance_deposit']) {
            $order_array[] = array(
                'title' => L_('福利商城'),
                'image' => $this->config['site_url'] . '/static/images/my_new/fuli.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=Mall&a=mall_order_list',
            );
        }

        $activity_arr['order'] = isset($order_array) ? $order_array : array();
		
		if($this->_uid){
			$collect_count = D('Merchant_user_relation')->get_collect_num($this->_uid,$now_user['openid']);
		}

		//	我的收藏
        if (!$this->config['open_company_advance_deposit']) {
            if($this->config['shop_alias_name']){
                $activity_arr['collection'] = array(
                	array(
                        'title'=>L_('X1',$this->config['shop_alias_name']),
                        'count'=> $this->_uid ? intval($collect_count['shop_count']) : '--',
                        'url'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/my/collect?type=shop',
                    )
                );
            }

    		if($this->config['group_alias_name']){
    			$activity_arr['collection'][] = array(
                	'title'=>L_('X1',$this->config['group_alias_name']),
                    'count'=> $this->_uid ? intval($collect_count['group_count']) : '--',
                    'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/collect?type=group',
                );
    		}
    		if($this->config['meal_alias_name']){
    			$activity_arr['collection'][] = array(
                	'title'=>L_('X1',$this->config['meal_alias_name']),
                    'count'=> $this->_uid ? intval($collect_count['meal_count']) : '--',
                    'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/collect?type=foodshop',
                );
    		}
            if($this->config['appoint_alias_name']){
                $activity_arr['collection'][] = array(
                    'title'=>L_('X1',$this->config['appoint_alias_name']),
                    'count'=> $this->_uid ? intval($collect_count['appoint_count']) : '--',
                    'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/collect?type=appoint',
                );
            }
    		if($this->config['mall_alias_name']){
    			$activity_arr['collection'][] = array(
                	'title'=>L_('X1',$this->config['mall_alias_name']),
                    'count'=> $this->_uid ? intval($collect_count['mall_count']+$collect_count['mallgoods_count']) : '--',
                    'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/collect?type=mall',
                );
    		}
        }
		

        //  我的记录 TODO
        $myRecord = array();
        $myRecord[] = array(
            'title'=>L_('我的足迹'),
            'image'=> $this->config['site_url'].'/static/images/my_new/my_tracks.png',
            'url'=> $this->config['site_url']. D('House_village')->base_url . 'pages/my/my_tracks',
        );
        $myRecord[] = array(
            'title'=>L_('我的评价'),
            'image'=> $this->config['site_url'].'/static/images/my_new/my_reply.png',
            'url'=> $this->config['site_url']. D('House_village')->base_url . 'pages/my/my_evaluate',
        );
        if($this->config['my_release_show']==1){
            $myRecord[] = array(
                'title'=>L_('我的发布'),
                'image'=> $this->config['site_url'].'/static/images/my_new/my_release.png',
                'url'=> $this->config['site_url']. '/packapp/platn/pages/wantToBuy/v1/releaseList/index',
            );
        }
        $myRecord[] = array(
            'title'=>L_('我的收藏'),
            'image'=> $this->config['site_url'].'/static/images/my_new/my_collect.png',
            'url'=> $this->config['site_url']. D('House_village')->base_url . 'pages/my/my_collect',
        );
        $activity_arr['my_record'] = $myRecord;

        $site_phone = empty($this->config['site_phone']) ? array() : explode(' ',$this->config['site_phone']);
        $activity_arr['kefu']['phone'] =$site_phone;

        $kf = M('Merchant_store_kefu')->where(['store_id' => 0])->find();
        if ($kf && $this->_uid > 0) {
            $activity_arr['kefu']['url'] = build_im_chat_url('user_' . $this->_uid, $kf['username'], 'user2plat');
        }else if($this->config['open_kefu_plat'] != 'zendesk'){
			if ($this->config['open_kefu_url'] && $services = D('Customer_service')->where(array('mer_id' => $this->config['kefu_mer_id']))->select()) {
				$activity_arr['kefu']['url'] = $this->config['site_url'].'/wap.php?c=My&a=concact_kefu&mer_id='.$this->config['kefu_mer_id'];
			}
		}else{
			$zend_tags = [];
			if($now_user['city_id']){
				$zend_tags[] = $now_city_name = M('Area')->where(['area_id'=>$now_user['city_id']])->getField('area_name');
			}
			$zend_tags[] = 'usercenter';
			
			$zChatDepartmentIdList = [
				'goldcoast'=>1818127635,
				'brisbane'=>1818140155,
				'sydney'=>1818140157,
				'canberra'=>1818234383,
				'melbourne'=>1818405615,
				'aldelaide'=>1818405617,
				'perth'=>1818405619
			];
			if($now_city_name){
				$zChatDepartment = strtolower(str_replace(' ', '', $now_city_name));
				$zChatDepartmentId = $zChatDepartmentIdList[$zChatDepartment];
			}
			
			$activity_arr['kefu']['url'] = packapp_url('my/zendesk_chat.html?account_key='.$this->config['open_kefu_zendesk'].'&zendesk_tag='.urlencode(implode('^&^',$zend_tags)) . ($now_city_name ? '&city='.urlencode($now_city_name) : '') . ($zChatDepartmentId ? '&departmentId='.urlencode($zChatDepartmentId) : ''), $now_user);	
			
			$activity_arr['kefu']['type'] = 'zendesk';
			$activity_arr['kefu']['kefu_info'] = ['zend_tags'=>$zend_tags, 'account_key'=>$this->config['open_kefu_zendesk']];
			$activity_arr['kefu']['kefu_info']['departmentId'] = $now_city_name ? : '';
			$activity_arr['kefu']['kefu_info']['department'] = $now_city_name ? : '';
		}

        //开启微信小程序客服
        $activity_arr['kefu']['open_wechatmini_kefu'] = (C('config.open_wechatmini_kefu') == 1);

        if($this->config['show_scroll_msg']){
            $tmp_scroll =  D('Scroll_msg')->get_msg();
            foreach ($tmp_scroll as $v) {

                $activity_arr['scroll_msg'][] = $v['content'];
            }
            $activity_arr['scroll_image'] = $this->config['site_url'].'/static/images/my_new/scroll.png';
        }

        $uid	=	$this->_uid;
		//	商家优惠券
        $mer_list = D('Card_new_coupon')->get_user_all_coupon_list($uid,1);
		if($mer_list){
			$mer_number	=	count($mer_list);
		}else{
			$mer_number = 0;
		}

		//	平台优惠券
		$coupon_list = D('System_coupon')->get_user_coupon_list($uid,$now_user['phone'],1);
        foreach ($coupon_list as $key => $v) {
            if ($v['valid_time']) {
                if ($v['receive_time']<$v['start_time']) {
                    $end_time = $v['start_time'] + $v['valid_time']*86400;
                }else{
                    $v['start_time'] = $v['receive_time'];
                    $end_time = $v['receive_time'] + $v['valid_time']*86400;
                }
                $v['end_time'] = $end_time>$v['end_time'] ? $v['end_time'] : $end_time;
                if($v['end_time']<$_SERVER['REQUEST_TIME']&&$v['is_use']!=1){
                    unset($coupon_list[$key]);
                }
            }
        }
//		$coupon_number	=	count($coupon_list);

        //商家优惠券 + 平台优惠券 合并
        $mer_number = $mer_number + count($coupon_list);


        //	统计我参与的活动
       	$sql	=	"SELECT COUNT(*) AS tp_count FROM `pigcms_extension_activity_record` `ear`,`pigcms_extension_activity_list` `eal`,`pigcms_merchant` `m` WHERE(`ear`.`activity_list_id` = `eal`.`pigcms_id` AND `eal`.`mer_id` = `m`.`mer_id`AND `ear`.`uid` = '$uid') GROUP BY `eal`.`pigcms_id` ";
		$mod = new Model();
		$result = $mod->query($sql);
		$activity_number	=	count($result);
		//	统计我的会员卡
		$sql = 'SELECT c.card_id,c.bg,c.diybg,m.name,c.discount,cl.id as cardid,cl.card_money,cl.card_money_give,m.pic_info,m.mer_id FROM '
				.C('DB_PREFIX').'card_userlist `cl`  left join '
				.C('DB_PREFIX').'card_new `c` on cl.mer_id = c.mer_id left join '
				.C('DB_PREFIX').'merchant m on c.mer_id  = m.mer_id WHERE ( cl.uid = '.$uid.' AND c.status=1 AND cl.status=1 AND m.status=1 ) ';
		$res =  M('')->query($sql);
		foreach ($res as $v) {
			$tmp[$v['card_id']]['id'] = $v['cardid'];
		}
		$card_number = count($tmp);

		//获取未使用转赠券数量
        $receive_num = 0;
        if($this->_uid){
            $card_new_coupon_list = D('System_coupon')->get_user_card_new_coupon(['uid'=>$uid], ['status'=>1]);
            $receive_num = count($card_new_coupon_list);
        }
        $activity_arr['discount'] = array(
            array(//商家优惠券 + 平台优惠券 合并
                'title'=>L_('优惠券'),
//                'title'=>L_('商家优惠券'),
                'url' => $this->config['site_url'] . D('House_village')->base_url.'pages/coupon/myCoupon',
//                'url' => $this->config['site_url'].'/wap.php?g=Wap&c=My&a=card_list&coupon_type=mer',
                'number'=> $this->_uid ? $mer_number : '--',
                'pic_url'=> $this->config['site_url'].'/static/images/my_new/card_merchant.png',
                'hide_number'=>0,
            ),
//            array(
//                'title'=>L_('平台优惠券'),
//                'url' => $this->config['site_url'].'/wap.php?g=Wap&c=My&a=card_list&coupon_type=system',
//                'number'=> $this->_uid ? $coupon_number : '--',
//                'pic_url'=> $this->config['site_url'].'/static/images/my_new/card_coupon.png',
//            ),
            array(
                'title'=>L_('参与活动'),
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=join_activity',
                'number'=> $this->_uid ? $activity_number : '--',
                'pic_url'=> $this->config['site_url'].'/static/images/my_new/card_active.png',
                'hide_number'=>0,
            ),
        );
        if(C('customization.group_order_giving')==1){
            $activity_arr['discount'][] = array(
                'title'=>L_('团购券'),
                'url'=> $this->config['site_url'] . D('House_village')->base_url.'pages/coupon/myGroupCoupon',
                'number'=> $this->_uid ? $receive_num : '--',
                'pic_url'=> $this->config['site_url'].'/static/images/my_new/tuangou_zhuanzeng.png',
                'hide_number'=>0,
            );
        }

        // 是否开启企业预存款余额功能
        if($this->config['open_company_advance_deposit']){
            $activity_arr['user_link_arr'][2]['value']  = $card_number . '张';
        }else{
            $activity_arr['discount'][] =  array(
                'title'=>L_('会员卡'),
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=cards',
                'number'=> $this->_uid ? $card_number : '--',
                'pic_url'=> $this->config['site_url'].'/static/images/my_new/card_vip.png',
                'hide_number'=>0,
            );
        }

        if(C('customization.internal_membership_card') == 1){
            $cardCount = 0;
            if($this->_uid) {
                $cardCount = D('Internal_membership_card')->where(['uid' => $this->_uid, 'status' => 1])->count();
            }
            $activity_arr['discount'][] = array(
                'title'=>L_('一卡通'),
                'url'=> $this->config['site_url'] . '/packapp/plat/pages/my/internalCard/myCard',
                'number'=> $cardCount,
                'pic_url'=> $this->config['site_url'].'/static/images/my_new/card_vip.png',
            );
        }


        if (C('customization.user_level_giftbag') == 1 && $now_user['level'] != 0) {
            $activity_arr['discount'][] =  array(
                'title'=>L_('我的产品'),
                'url'=> $this->config['site_url'].'/packapp/plat/pages/membership/giftBag/myGiftList',
                'number'=> 0,
                'pic_url'=> $this->config['site_url'].'/static/images/my_new/myGiftList.png',
                'hide_number'=>1,
            );
        }


        if(C('customization.life_tools')){
            // 员工卡数量
            $where = [
                'u.uid' => $this->_uid,
                'c.status' => 1,
                'u.status' => 1
            ];
            $count = M('Employee_card_user')->join(' as u left join '.C('DB_PREFIX').'employee_card as c on u.card_id = c.card_id')->where($where)->count();
            // 次卡数量
            $where = [
                'o.uid' => $this->_uid,
                'o.order_status' => ['not in','51,60'],
                'o.out_time' => ['gt',time()],
            ];

            $countCard = M('Life_tools_card_order')->join(' as o left join '.C('DB_PREFIX').'life_tools_card as c on o.card_id = c.pigcms_id')->where($where)->count();

            //平台会员卡
            $platform_count = 0;
            $user = M('User')->where(['uid'=>$this->_uid])->find();
            if(C('config.level_onoff') == 1 && $user['level'] > 0){
                $levelData = M('User_level')->where(['level' => $user['level']])->find();
                if($levelData && ($levelData['validity'] == 0 || ($user['level_time'] + $levelData['validity']*86400) > time() )){
                    $platform_count = 1;
                }
            }
            
            //商家会员卡
            $merchant_count = 0;
            $sql = 'SELECT COUNT(*) AS num FROM '
            .C('DB_PREFIX').'card_userlist `cl`  left join '
            .C('DB_PREFIX').'merchant m on m.mer_id  = cl.mer_id left join '
            .C('DB_PREFIX').'card_new `c` on m.mer_id = c.mer_id   where ( cl.uid = '.$this->_uid.' AND m.status=1 AND  cl.status =1 AND c.status =1 ) group by cl.mer_id ';
            $res =  M('')->query($sql);
            if($res){
                $merchant_count = count($res);
            }

            $activity_arr['discount'][] =  array(
                'title'=>L_('卡包'),
                'url'=> $this->config['site_url'] . D('House_village')->base_url.'pages/lifeTools/pocket/list',
                'number'=> $count + $countCard + $platform_count + $merchant_count,
                'pic_url'=> $this->config['site_url'].'/static/images/my_new/card_bag.png',
                'hide_number'=>0,
            );
        }

        //活动预约
        if(C('customization.life_tools')){
            $activity_arr['release'][] = array(
                'title' => '我的预约',
                'image' => $this->config['site_url'].'/static/images/my_new/lifetools_appoint.png', /*图片暂定为 26*26的像素 */
                'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/appointment/mineAppoint/list',
	    );
        }
	
	//是否开启房产推荐
        $plugin = D('Plugin')->where(['status'=> 1,'plugin_label'=>'fangchandengji'])->find();
        if($plugin){
            $activity_arr['release'][] = array(
                'title' => '房产推荐管理',
                'image' => $this->config['site_url'].'/static/images/my_new/realstate.png', /*图片暂定为 26*26的像素 */
                'url' => $this->config['site_url'].'/packapp/plat/pages/my/property_recommendation',

            );
	}

        //平台会员卡
        if ($membershipCard = (new Membership_cardModel())->getCurrentMembershipCard()) {
            $activity_arr['release'][] = array(
                'title' => $membershipCard['card_name'],
                'image' => $this->config['site_url'].'/static/images/my_new/membership.png', /*图片暂定为 26*26的像素 */
                'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/membership/platMembership',
            );
        }

        //养老金
        if($this->config['open_old_age_pension'] == '1'){
            if(!empty($this->config['old_age_pension_pcpc_video'])){
                $activity_arr['release'][] = array(
                    'title' => "公证处告知",
                    'image' => $this->config['site_url'].'/static/images/my_new/membership.png', /*图片暂定为 26*26的像素 */
                    'url' => '',
                    'video' => $this->config['site_url'].$this->config['old_age_pension_pcpc_video'],
                    'desc' => $this->config['old_age_pension_pcpc_desc'],
                    'type' => 'old_age_pension'
                );
            }
            $activity_arr['release'][] = [
                'title' => '养老金管理中心',
                'image' => $this->config['site_url'].'/static/images/my_new/membership.png', /*图片暂定为 26*26的像素 */
                'url' => $this->config['site_url'].D('House_village')->base_url.'pages/my/oldAgePension/manage',//暂时还不知道地址
                'type' => 'old_age_pension'
            ];
        }

        // 我的发布
        if($this->config['open_distributor']==1&&$this->config['my_release_show']==1){

			$activity_arr['release'][] = array(
				'title'=>L_('我是X1',$this->config['agent_alias_name']),
				'image'=> $this->config['site_url'].'/tpl/Wap/default/images/new_my/tubiao2_11.png', /*图片暂定为 26*26的像素 */
				'url'=> $this->config['site_url'].'/wap.php?c=Distributor_agent&a=agent',
			);
        }

        if($this->config['open_score_fenrun']){
            $activity_arr['release'][] = array(
                'title'=>L_('推广有奖'),
                'image'=> $this->config['site_url'].'/static/images/my_new/share.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?c=My&a=my_spread_code',
            );
        }
        //app红包记录
        if($this->config['open_app_redpack']){
            $activity_arr['release'][] = array(
                'title'=>L_(L_('红包记录')),
                'image'=> $this->config['site_url'].'/static/images/my_new/hongbao.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?c=My&a=redpack_list',
            );
        }

        //我的社区
        if($this->config['house_open'] == 1 && $this->config['house_position'] == 3){
            $house_alias_name = isset($this->config['house_name']) && $this->config['house_name'] ? $this->config['house_name'] : L_('社区');
            $base_url = D('House_village')->base_url;
            $referer_url = $base_url . 'pages/village_menu/index';
            $url = $this->config['site_url'] . $referer_url;
//            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=House&a=village_list';
            $activity_arr['release'][] = array(
                'title' => L_('我的X1服务',$house_alias_name),
                'image' => $this->config['site_url'] . '/static/images/my_new/village.png', /*图片暂定为 26*26的像素 */
                'url'   => $url,
            );
        }


        //增加会员等级推广判断
        $nowUserLevel = (new UserModel())->getLevelByUid($now_user['uid']);
        if((($now_user['level'] > 0 && $nowUserLevel['can_spread']) || $now_user['level'] == '0') && $this->config['open_user_spread'] && !$this->config['open_single_spread'] && (!$this->config['open_spread_appoint_user'] || $now_user['can_spread']==1)) {
            $activity_arr['release'][] = array(
                'title' => L_('我的推广'),
                'image' => $this->config['site_url'] . '/static/images/my_new/tuiguang.png', /*图片暂定为 26*26的像素 */
                'url'   => $this->config['site_url'] . '/wap.php?c=My&a=my_spread',
            );
        }

        // 是否开启企业预存款余额功能
        if($this->config['open_company_advance_deposit']){
            $activity_arr['release'][] = array(
                'title' => L_('餐卡充值'),
                'image' => $this->config['site_url'] . '/static/images/my_new/xiaoyuanqia.png', /*图片暂定为 26*26的像素 */
                'url'   => $this->config['site_url'] . '/wap.php?g=Wap&c=Company_campus&a=searchSchool',
            );
        }

        if($this->config['open_single_spread']) {
            $activity_arr['release'][] = array(
                'title' => L_('申请成为分销人员'),
                'image' => $this->config['site_url'] . '/static/images/my_new/spread_money.png', /*图片暂定为 26*26的像素 */
                'url'   => $this->config['site_url'] . '/wap.php?c=My&a=distributor_apply',
            );
        } 

        //商家管理
        if($this->config['show_merchant_in_personal_center'] == 1){
            $activity_arr['release'][] = array(
                'title' => L_('商家中心'),
                'image' => $this->config['site_url'] . '/static/images/my_new/merchant.png', /*图片暂定为 26*26的像素 */
                'url'   => $this->config['site_url'] . '/packapp/merchant/',
            );
        }

        //店员管理
        if($this->config['show_store_staff_in_personal_center'] == 1){
            $activity_arr['release'][] = array(
                'title' => L_('店员中心'),
                'image' => $this->config['site_url'] . '/packapp/storestaff/images/logo.png', /*图片暂定为 26*26的像素 */
                'url'   => $this->config['site_url'] . '/packapp/storestaff/',
            );
        }

		if(($activity_arr['kefu']['phone'] || $activity_arr['kefu']['url'])) {
            $activity_arr['release'][] = array(
                'title' => L_('在线客服'),
                'image' => $this->config['site_url'] . '/static/images/my_new/my_kefu.png', /*图片暂定为 26*26的像素 */
                'url'   => $activity_arr['kefu']['url'] ? $activity_arr['kefu']['url'] : '',
                'phone'   =>$this->config['site_phone'],
                'type'   => $activity_arr['kefu']['type'] ? $activity_arr['kefu']['type'] : '',
                'kefu_info'   => $activity_arr['kefu']['kefu_info'] ? $activity_arr['kefu']['kefu_info'] : [],
            );
        }
        if(isset($this->config['wap_home_show_classify']) && $this->config['wap_home_show_classify']==1&&$this->config['my_release_show']==1){
//            if($now_user['openid']){
//				$activity_arr['release'][] = array(
//						'title'=>'我的推广二维码',
//						'image'=> $this->config['site_url'].'/tpl/Wap/default/static/images/new_my/qr_code.png', /*图片暂定为 26*26的像素 */
//						'url'=> $this->config['site_url'].'/wap.php?c=My&a=my_spread',
//				);
//			}
            $activity_arr['release'][] = array(
                    'title'=>L_('我的发布'),
                    'image'=> $this->config['site_url'].'/static/images/my_new/fabu.png', /*图片暂定为 26*26的像素 */
                    'url'=> $this->config['site_url'].'/wap.php?c=Classify&a=myfabu&uid='.$this->_uid,
            );
        }
        if(isset($this->config['open_bd_spread']) && $this->config['open_bd_spread']==1){
            $bd_info = M("Bd")->where(['uid'=>$this->_uid, 'is_del' => 0, 'status' => 1])->find();
            if($bd_info){
                $base_url = D('House_village')->base_url;
                $referer_url = $base_url . 'pages/my/myextension/mine_extension';
                $url = $this->config['site_url'] . $referer_url;
                $activity_arr['release'][] = array(
                        'title'=>L_('我推广的商家'),
                        'image'=> $this->config['site_url'].'/static/images/my_new/bd.png', /*图片暂定为 26*26的像素 */
                        'url'=> $url,
                );
            }
        }

        // 开启新版营销
        if ($this->config['open_bd_spread_new']) {
            $marketPerson = D('New_marketing_person')->where(['uid' => $this->_uid, 'is_del' => 0])->find();
            $artisan = D('New_marketing_artisan')->where(['uid' => $this->_uid, 'status' => 0])->find();
            if ($marketPerson || $artisan) {
                $activity_arr['release'][] = array(
                    'title' => L_('我的业务'),
                    'image' => $this->config['site_url'] . '/static/images/my_new/my_business.png',
                    'url' => $this->config['site_url'] . '/packapp/plat/pages/marketing/index',
                );
            }
        }

        $database_house_worker = D('House_worker');
        $house_worker_condition['status'] = 1;
        $house_worker_condition['openid'] = $now_user['openid'];
        $house_worker_condition['is_del'] = 0;
        $now_house_worker = $database_house_worker->where($house_worker_condition)->find();
        if(!empty($now_house_worker)){
            if($now_house_worker["type"] == 0){
                $house_name = L_('客服');
            }else{
                $house_name = L_('维修');
            }
            $activity_arr['release'][] = array(
                'title'=>L_('社区').$house_name,
                'image'=> $this->config['site_url'].'/tpl/Wap/pure/static/images/new_my/order.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'].'/wap.php?c=Worker&a=index',
            );
        }

        $activity_arr['system_order_status'] = [];
        $where_system_order = [
            'uid' => $this->_uid, 
            'is_del'=>0,
            'type' => ['in', ['mall', 'shop', 'meal', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3','lifetools', 'lifetoolscard', 'life_tools_appoint_join']] //, 'dining'
        ];
        $activity_arr['system_order_status'][] = [
            'status' => 0, //全部
            'text' => L_('全部订单'),
            'icon' => C("config.site_url").'/static/status/0.png',
            'nums' => -1,//不显示
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=0'
        ];
        $nopayList = M("System_order")->where(array_merge($where_system_order, ['is_del' => 0, 'paid' => 0, 'system_status' => 0]))->select();
        if (!empty($nopayList)) {//先删除无效订单
            foreach ($nopayList as $nv) {
                if ($nv['type'] == 'mall3') {
                    $order_info1 = M('mall_order')->where(['order_id' => $nv['order_id']])->find();
                    if(empty($order_info1)){//屏蔽掉无效订单
                        M("System_order")->where(['id' => $nv['id']])->setField('is_del', 1);
                    }
                }
            }
        }
        $activity_arr['system_order_status'][] = [
            'status' => 1, 
            'text' => L_('待付款'),
            'icon' => C("config.site_url").'/static/status/1.png',
            'nums' => M("System_order")->where(array_merge($where_system_order, ['is_del' => 0, 'paid'=>0,'system_status'=>0]))->count(),
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=1'
        ];
        $activity_arr['system_order_status'][] = [
            'status' => 2, 
            'text' => L_('待发货'),
            'icon' => C("config.site_url").'/static/status/2.png',
            'nums' => M("System_order")->where(array_merge($where_system_order, ['paid'=>1,'system_status'=>0, 'type'=>['in', ['mall', 'shop', 'group', 'appoint', 'mobile_recharge','gift','village_group','mall3','lifetools', 'lifetoolscard', 'life_tools_appoint_join','service']]]))->count(),
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=2'
        ];
        $activity_arr['system_order_status'][] = [
            'status' => 3, 
            'text' => L_('待收货'),
            'icon' => C("config.site_url").'/static/status/3.png',
            'nums' => M("System_order")->where(array_merge($where_system_order, ['system_status'=>1]))->count(),
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=3'
        ];
        $activity_arr['system_order_status'][] = [
            'status' => 4, 
            'text' => L_('待评价'),
            'icon' => C("config.site_url").'/static/status/4.png',
            'nums' => M("System_order")->where(array_merge($where_system_order, ['system_status'=>2, 'type'=>['in', ['mall', 'shop', 'group', 'appoint', 'mobile_recharge','gift','village_group', 'mall3','lifetools', 'lifetoolscard', 'life_tools_appoint_join']]]))->count(),
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=4'
        ];
        $activity_arr['system_order_status'][] = [
            'status' => 5, 
            'text' => L_('退款/售后'),
            'icon' => C("config.site_url").'/static/status/5.png',
            'nums' => -1,//不显示
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=5'
        ];

        if($this->config['find_msg'] == 1) {
            $activity_arr['release'][] = array(
                'title' => L_('我的发现'),
                'image' => $this->config['site_url'] . '/tpl/Wap/default/static/images/new_my/find.png', /*图片暂定为 26*26的像素 */
                'url'   => $this->config['site_url'] . '/wap.php?c=Discover&a=my_discover',
            );
        }
        $activity_arr['sign_get_score'] =  $this->config['sign_get_score'] ? $this->config['sign_get_score'] : 0;
		
        $this->returnCode(0, $activity_arr);
    }
    
	//	优惠活动
    public function discount_action(){
    	if($this->_uid){
            $uid = $this->_uid;
		}else{
			$this->returnCode('20046009');
		}
        
		//	商家优惠券
        $mer_list = D('Member_card_coupon')->get_all_coupon($uid);
        $mer_number	=	count($mer_list);
        //	平台优惠券
        $coupon_list = D('System_coupon')->get_user_coupon_list($uid);
        $coupon_number	=	count($coupon_list);
        //	统计我参与的活动
       	$sql	=	"SELECT COUNT(*) AS tp_count FROM `pigcms_extension_activity_record` `ear`,`pigcms_extension_activity_list` `eal`,`pigcms_merchant` `m` WHERE(`ear`.`activity_list_id` = `eal`.`pigcms_id` AND `eal`.`mer_id` = `m`.`mer_id`AND `ear`.`uid` = '$uid') GROUP BY `eal`.`pigcms_id` ";
		$mod = new Model();
		$result = $mod->query($sql);
		$activity_number	=	count($result);
		//	统计我的会员卡
        $card_number = D('Member_card_set')->get_all_card_count($uid);
        $activity_arr = array(
            array(
                'title'=>L_('商家优惠券'),
                'url' => $this->config['site_url'].'/wap.php?g=Wap&c=My&a=card_list&coupon_type=mer',
                'number'=>$mer_number,
            ),
            array(
                'title'=>L_('平台优惠券'),
                'url' => $this->config['site_url'].'/wap.php?g=Wap&c=My&a=card_list&coupon_type=system',
                'number'=>$coupon_number,
            ),
            array(
                'title'=>L_('我参与的活动'),
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=join_activity',
                'number'=>$activity_number,
            ),
            array(
                'title'=>L_('我的会员卡'),
                'url'=> $this->config['site_url'].'/wap.php?g=Wap&c=My&a=cards',
                'number'=>$card_number,
            ),
        );
        $this->returnCode(0,$activity_arr);
    }
    //	账号管理页面
    public function account_management(){
		if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}
		
		if($now_user){
			$arr[]	=	array(
				'title'		=>	L_('昵称'),
				'content'	=>	$now_user['nickname'],
				'url'		=> 	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=username',
			);
			if($now_user['phone']){
                if(!$now_user['pwd']){
                    $arr[]	=	array(
                        'title'	=>	L_('设置密码'),
                        'content'	=>	L_('未设置'),
                        'url'	=>	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=password',
                    );
                }else{
                    $arr[]	=	array(
                        'title'	=>	L_('修改密码'),
                        'content'	=>	'',
                        'url'	=>	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=password',
                    );
                }
				$arr[]	=	array(
					'title'	=>	L_('修改手机号'),
					'content'	=>	$now_user['showPhone'],
					'url'	=>	$this->config['site_url'].'/wap.php?c=My&a=bind_user',
				);
			}else{
				$arr[]	=	array(
					'title'	=>	L_('绑定手机号'),
					'content'	=>	'',
					'url'	=>	$this->config['site_url'].'/wap.php?c=My&a=bind_user',
				);
			}
			$arr[]	=	array(
				'title'	=>	L_('收货地址'),
				'content'	=>	'',
				'url'	=>	$this->config['site_url'].'/wap.php?c=My&a=adress',
			);
			if($this->config['user_is_real_name']){
				$find	=	M('User_authentication')->field('authentication_id')->where(array('uid'=>$now_user['uid']))->order('authentication_time DESC')->find();
				if($find){
					$arr[]	=	array(
						'title'	=>	L_('我的实名认证'),
						'content'	=>	'',
						'url'	=>	$this->config['site_url'].'/wap.php?c=My&a=authentication_index',
					);
				}else{
					$arr[]	=	array(
						'title'	=>	L_('我的实名认证'),
						'content'	=>	'',
						'url'	=>	$this->config['site_url'].'/wap.php?c=My&a=authentication',
					);
				}
			}
			$arr[]	=	array(
                'title'	=>	L_('我的银行卡'),
                'content'	=>	'',
                'url'	=>	$this->config['site_url'].'/wap.php?c=Fenrun&a=bank_list',
            );
			$arr[]	=	array(
                'title'	=>	L_('我的生日'),
                'content'	=>	$now_user['birthday'] != '0000-00-00' ? $now_user['birthday'] : L_('未设置'),
                'url'	=>	packapp_url('my/birthday.html',$now_user),
            );
			$arr[]	=	array(
                'title'	=>	L_('我的邮箱'),
                'content'	=>	$now_user['showEmail'] != '' ? $now_user['showEmail'] :  L_('未设置'),
				'url'	=>	packapp_url('my/email.html',$now_user),
            );
			/* $arr[]	=	array(
				'title'	=>	L_('我的平台实体卡'),
				'content'	=>	'',
				'url'	=>	$this->config['site_url'].'/wap.php?c=My&a=cardcode',
			); */
		}else{
			$this->returnCode('20044011');
		}
		$this->returnCode(0,$arr);
    }

    //设置密码
    public  function set_passwd(){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}
		
        if(!empty($now_user['pwd']) && md5($_POST['currentpassword']) != $now_user['pwd']){
            //$this->assign('error','当前密码输入错误！');
            $this->returnCode('10045009');
        }else if($_POST['currentpassword'] == $_POST['password']){
            $this->returnCode('10045010');
        }else if($_POST['password2'] != $_POST['password']){
            $this->returnCode('10045011');
        }else{
            $result = D('User')->save_user($now_user['uid'],'pwd',md5($_POST['password']));
            if($result['error']){
                $this->returnCode('10045012','',$result['msg']);
            }else{
                $this->returnCode(0);
            }
        }

    }
    //	我的钱包
    public function my_wallet(){
		if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}

        $level = M(_view('User_level'))->getField('level,lname');
        $now_user['lname'] = $now_user['level']?$level[$now_user['level']]:'VIP0';
		if($now_user){
			$arr['user']	=	array(
				'title'		=>	L_('我的余额'),
				'now_money'	=>	strval($now_user['now_money']),
				'now_money2'	=>	strval($now_user['now_money']),
				'recharge'	=> 	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=recharge',
				'withdraw'	=> 	$this->config['site_url'].'/wap.php?g=Wap&c=Fenrun&a=withdraw',
				'frozen_money'	=> 	0,
				'frozen_reason'	=> 	'',
				'frozen_time'	=> 	'',
			);
            if($now_user['frozen_money'] > 0 && $this->config['open_frozen_money'] == 1 && $now_user['free_time']>$_SERVER['REQUEST_TIME']){
                $arr['user']['frozen_money'] = strval($now_user['frozen_money']);
                $arr['user']['frozen_reason'] = $now_user['frozen_reason'];
                $arr['user']['frozen_time'] = date('Y-m-d',$now_user['frozen_time']).'-'.date('Y-m-d',$now_user['free_time']);
            }
            if(!$this->config['open_user_withdraw'] || $this->config['company_pay_open']!=1 || !isset($this->config['company_pay_encrypt'])) {
                $arr['user']['is_withdraw'] = 0;
            }else{
                $arr['user']['is_withdraw'] = 1;
            }

            if($this->config['open_user_recharge']!=1||!isset($this->config['open_user_recharge'])) {
                $arr['user']['is_recharge'] = 0;
            }else{
                $arr['user']['is_recharge'] = 1;
            }
			
			if($this->config['score_recharge'] && $this->config['open_extra_price'] == 0 && $this->config['open_score_fenrun'] == 0) {
                $arr['user']['score_recharge'] = 1;
            }else{
                $arr['user']['score_recharge'] = 0;
            }
			
			$arr['data']	=	array(
				array(
					'label' => 'transaction',
					'title'	=>	L_('余额记录'),
					'content'	=>	'',
					'url'	=> 	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=transaction',
				),
				array(
					'label' => 'integral',
					'title'	=>	$this->config['score_name'].L_('记录'),
					'content'	=>	'',
					'url'	=> 	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=integral',
				),
				array(
					'label' => 'levelUpdate',
					'title'	=>	L_('等级管理'),
					'content'	=>	$now_user['lname'],
					'url'	=> 	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=levelUpdate',
				),
			);
            $live_arr = [];
            if($now_user['open_live_show'] == '1' && $this->config['live_open'] == '1' && $this->app_version >= 30000){
                $live_arr = [
                    'label' => 'liveMoney',
                    'title' =>  L_('直播/短视频佣金'),
                    'content'   =>  '',
                    'url'   =>  $this->config['site_url']."/wap.php?g=Wap&c=My&a=live_money_list"
                ];
                array_unshift($arr['data'], $live_arr);
            }

			if($arr['user']['score_recharge'] || $this->app_version < 1300){
				$arr['data'][] = array(
					'label' => 'score_recharge',
					'title'	=>	L_('X1换余额',$this->config['score_name']),
					'content'	=>	$now_user['score_count'],
					'url'	=> 	$this->config['site_url'].'/wap.php?g=Wap&c=My&a=score_recharge',
				);
			}
            // 我的付款码
            if ($this->config['merchant_buy_points_switch'] != 1 && $this->config['user_transfer_balance_switch'] != 1) {
                $img_show = false;
                $code_url = '';
            } else {
                $img_show = true;
                if ($this->config['merchant_buy_points_switch'] == 1) {
                    $code_url = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=my_store_card_code';
                } else {
                    $code_url = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=my_receipt_code';
                }
            }

            $arr['pay_other'] = array(
                'img_show' => $img_show,
                'code_url' => $code_url,
            );
		}else{
			$this->returnCode('20044011');
		}
		$this->returnCode(0,$arr);
    }
	
	public function edit_profile(){
    	if(!$this->_uid){
            $this->returnCode('20046009');
		}

		$data_user['nickname'] = strval($_POST['nickname']);
		$data_user['avatar']   = D('User')->down_avatar($_POST['avatar'], $this->_uid);
		$data_user['province'] = strval($_POST['province']);
		$data_user['city']     = strval($_POST['city']);
		$data_user['sex']      = intval($_POST['sex']);

		$result = D('User')->data($data_user)->where(['uid'=>$this->_uid])->save();
		if($result === FALSE){
			$this->returnCode('20090100');
		}else{
			//如果存在即 自动登录
            $now_user = D('User')->get_user($this->_uid);
            if(!empty($now_user)){
                //回调路径
                $ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
                //统一数据结构
                $return = array(
                    'ticket' => $ticket["ticket"],
                    'user' => $now_user
                );
                $this->returnCode(0, $return);
            }
		}
    }
	
    public function username(){
    	if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}
		
		if($now_user){
			if(empty($_POST['nickname'])){
				$this->returnCode('20090098');
			}else if($_POST['nickname'] == $this->now_user['nickname']){
				$this->returnCode('20090099');
			}else if($_POST['nickname'] == $this->config['site_name']){
				$this->returnCode('20090101');
			}else{
				$result = D('User')->save_user($now_user['uid'],'nickname',$_POST['nickname']);
				if($result['error']){
					$this->returnCode('20090100');
				}else{
					$this->returnCode(0);
				}
			}
		}else{
			$this->returnCode('20044011');
		}
    }
    //	到店自提
    public function pick_address()
    {
        $ticket = I('ticket');
        if (empty($ticket)) {
            $this->returnCode('20046009');
        }
        $mer_id   = I('mer_id');
        $fromType = I('fromType');
        $store_id = I('store_id', 0);
        $long     = I('long');
        $lat      = I('lat');
        $addressName  = I('address_name');
        cookie('userLocationLong', $long);
        cookie('userLocationLat', $lat);
        
        $addressId = '';
        if(!empty($addressName)){
            $address = D('Pick_address')->where(['pick_addr' => ['like',"%{$addressName}%"]])->field('id')->select();
            if(empty($address)){
                $this->returnCode(0, []);
            }
            $addressId = implode(',',array_column($address,'id'));
        }
        $is_system   = $fromType == 'shop' || $fromType == 'mall' ? true : false;
        $adress_list = D('Pick_address')->get_pick_addr_by_merid($mer_id, $is_system, $store_id, false, [], 1, $addressId);
        $pickId = M('Shop_order')->where(['uid' => $this->_uid])->field('pick_id')->order('order_id desc')->find();
        $store_arr   = array();
        if (empty($adress_list)) {
            $this->returnCode('20046015');
        } else {
            foreach ($adress_list as $key => &$v) {
                $v['province'] = $v['area_info']['province'];
                $v['city']     = $v['area_info']['city'];
                $v['area']     = $v['area_info']['area'];

                $v['last_order_pick_text'] = '';
                if(!empty($pickId) && 'p'.$pickId == $v['pick_addr_id']){
                    $v['last_order_pick_text'] = '上次下单';
                }
                unset($v['area_info']);
                if (strpos($v['pick_addr_id'], 's') !== false) {
                    $tmp_store_id = preg_match('/\d+/', $v['pick_addr_id'], $r);
                    if (!in_array($r[0], $store_arr)) {
                        unset($adress_list[$key]);
                        continue;
                    }
                }
                if (!$this->config['open_multilingual']) {
                    $v['show_address'] = $v['area'] . $v['name'];
                } else {
                    $v['show_address'] = $v['name'] . ' ' . $v['area'];
                }
                
                if ($v['distance'] > 1000) {
                    $v['distance'] = round($v['distance'] / 1000, 2) . 'km';
                } else {
                    $v['distance'] .= 'm';
                }
            }
        }

        $adress_list = $adress_list ? array_values($adress_list) : array();

        $this->returnCode(0, $adress_list);
    }
	//	用户地址列表
	public function adress(){
		$store_id = I('store_id', 0);
        $order_from = I('order_from'); //fromWorkerOrder-技师代下单
		if (!$this->_uid) {
			$this->returnCode(0);
		}
        $model_addr = $order_from=='fromWorkerOrder'?D('Merchant_workers_address'):D('User_adress');
		$adress_list = $model_addr->get_adress_list($this->_uid);

		$lat = 0;
		$lng = 0;
		$delivery_radius = 0;
		if ($_POST['order_id']) {
            $store_shop = array();
            $order  = D('Village_group_order_temp')->order_detail($_POST['order_id']);
            $header = D('Village_group')->get_header_info(0,$order['start_uid'],true);
            $header = $header['msg'];
            $lat = $header['pick_address']['lat'];
            $lng = $header['pick_address']['long'];
            $delivery_radius = floatval($header['pick_address']['delivery_radius']);
        } else {
            if ($store_id && ($store = D('Merchant_store')->field('long, lat')->where(array('store_id' => $store_id))->find())) {
                $lat = $store['lat'];
                $lng = $store['long'];
                if ($store_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find()) {
                    $delivery_radius = $store_shop['delivery_radius'];
                    $store_shop['delivery_range_polygon'] = substr($store_shop['delivery_range_polygon'], 9, strlen($store_shop['delivery_range_polygon']) - 11);
                    $lngLatData = explode(',', $store_shop['delivery_range_polygon']);
                    array_pop($lngLatData);
                    $lngLats = array();
                    foreach ($lngLatData as $lnglat) {
                        $lng_lat = explode(' ', $lnglat);
                        $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
                    }
                    $store_shop['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
                }
            }
        }

		if($adress_list){
			//非百度下的经纬度
			if($_POST['longlatType']){
				$longlatClass = new longlat();
			}
            $device_id = I('Device-Id', '', 'trim');
			foreach($adress_list as $v){
                if ('wxapp_paotui' == $device_id && $v['latitude'] && $v['longitude']) {
                    // 指定地点 获得省市区id
                    $area_info = D('Area')->cityMatching($v['latitude'], $v['longitude']);
                    if(empty($area_info['area_id']) || $area_info['is_open'] != 1){
                        continue;
                    }
                    if(empty($area_info['area_info']['area_id']) || $area_info['area_info']['area_is_open'] != 1){
                        continue;
                    }
                }
				$is_deliver = false;
				$distance = 9999;
				
				 if ($lat != 0 && $lng != 0) {
                    $distance = getDistance($v['latitude'], $v['longitude'], $lat, $lng);
                    $distance = $distance / 1000;
                    $distance = getFormatNumber($distance,3);
                }

                if (empty($store_shop) && $header && $header['pick_address']['delivery_range_type'] == 0) {
                    if ($distance <= $delivery_radius) {
                            $is_deliver = true;
                    }
                } elseif ($store_shop && $store_shop['delivery_range_type'] == 0) {
				    if ($distance <= $delivery_radius) {
				            $is_deliver = true;
				    }
				} else {
				    if ($store_shop['delivery_range_polygon']) {
				        if (isPtInPoly($v['longitude'], $v['latitude'], $store_shop['delivery_range_polygon'])) {
				            $is_deliver = true;
				        }
				    }
				}
				$formartLng = 0;
				$formartLat = 0;
				if($_POST['longlatType']){
					$longlat = $longlatClass->baiduToGcj02($v['latitude'],$v['longitude']);
					$formartLng = $longlat['lng'];
					$formartLat = $longlat['lat'];
				}
				if(!$this->config['open_multilingual']){
					$v['show_address'] = $v['adress'] . $v['detail'];
				}else if($this->config['country_code'] == 'au'){
					$v['show_address'] = trim(($v['detail'] ? $v['detail']  . ', ' : '') . $v['adress']  . ', ' . $v['area_txt'] . ' ' . $v['province_txt'] . ' ' . $v['zip_code']);
				}else{
					$v['show_address'] = trim(($v['detail'] ? $v['detail']  . ', ' : '') . $v['adress']  . ', ' . $v['area_txt'] . ' ' . $v['city_txt']);
				}

				//快递配送不需要判断配送范围
				if($store_shop['deliver_type'] == 5){
                    $is_deliver = true;
                }
                $addressChangeOrderId = I('address_change_order_id');
                $orderType = I('type');

                switch ($orderType){
                    case 'shop':
                        $changeAddress = D('Shop_order')->changeAddressDistance($addressChangeOrderId, $v['latitude'], $v['longitude']);
                        $hasSelect = $changeAddress['has_select'];
                        $exceedDistance = $changeAddress['exceed_distance'];
                        
                        break;
                }
				$arr[]	=	array(
					'adress_id'	=>	$v['adress_id'],
					'phone'		=>	$v['phone'],
					'phone_country_type' =>	$v['phone_country_type'],
                    'phone_show' =>	$v['phone_country_type'] ? '+'.$v['phone_country_type'].' '.$v['phone'] : $v['phone'],
                    'country_code'	=>	$v['country_code'],
					'province'	=>	$v['province'],
					'city'		=>	$v['city'],
					'area'		=>	$v['area'],
					'province_txt'	=>	$v['province_txt'],
					'city_txt'	=>	$v['city_txt'],
					'area_txt'	=>	$v['area_txt'],
					'adress'	=>	$v['adress'],
					'address_name'	=>	$v['adress'],
					'detail'	=>	$v['detail'],
					'show_address'	=>	$v['show_address'],
					'defaults'	=>	$v['default'],
					'name'		=>	$v['name'],
					'zipcode'	=>	$v['zipcode'],
					'lng'		=>	$v['longitude'],
					'lat'		=>	$v['latitude'],
					'sex'		=>	$v['sex'],
					'is_deliver' => $is_deliver,
					'distance' => $distance,
					'formartLng' => $formartLng,
					'formartLat' => $formartLat,
                    'has_select' => $hasSelect ?? false,
                    'exceed_distance' => $exceedDistance ? $exceedDistance.'km' : '',
				);
				$distances[] = $distance;
				array_multisort($distances, SORT_ASC, $arr);
			}
		}else{
			$arr	=	array();
		}
		if(!empty($addressChangeOrderId)){
		    return $arr;
        }
		$this->returnCode(0,$arr);
	}
	
	//订单收货信息
	public function orderAddressInfo()
    {
        $type = I('type');
        $orderId = $_GET['order_id'];
        empty($orderId) && $orderId = $_POST['order_id'];
        
        unset($_POST['order_id'], $_GET['order_id']);
        if(empty($type) || empty($orderId)){
            $this->returnCodeError('参数缺失！');
        }
        $orderAddress = NULL;
        $addressList = NULL;
        $hasChangeAddress = NULL;
        switch ($type){
            case 'shop':
                $orderAddress = M('Shop_order')
                    ->where(['order_id' => $orderId])
                    ->field(['username','userphone','address','address_id', 'mer_id', 'store_id'])
                    ->find();
                
                $hasChangeAddress = D('Merchant_store_shop')->hasChangeAddress($orderAddress['mer_id'], $orderAddress['store_id'], $orderId);
                if($hasChangeAddress['has_change']){
                    $msg = L_("仅能修改1次;当前订单支持修改距原地址{$hasChangeAddress['distance']}km内的地点，超出{$hasChangeAddress['distance']}km不可修改");
                }
                $_POST['address_change_order_id'] = $orderId;
                $_POST['store_id'] = $orderAddress['store_id'];
                $addressList = $this->adress();
        }
      
        $this->returnCodeOk([
            'address_change_info' => $msg ?? '',
            'order_address' => $orderAddress,
            'address_list' => $addressList
        ]);
    }
    
	/*添加编辑地址*/
	public function edit_adress(){
        $order_from = I('order_from'); //fromWorkerOrder-技师代下单
		if(!$this->_uid){
			$this->returnCode('20046009');
		}
		
		if(empty($_POST['adress'])){
            $_POST['adress'] = '[位置]';
		}
		if(empty($_POST['adress'])){
			$this->returnCode('20046017');
		}
		if(empty($_POST['name'])){
			$this->returnCode('20046018');
		}
		if(empty($_POST['phone'])){
			$this->returnCode('20046019');
		}
        $now_city = D('Area')->cityMatching($_POST['latitude'],$_POST['longitude']);
//        fdump_api(['my' => $now_city],'address/areaModelCityMatching',true);
		if (isset($now_city['error']) && $now_city['error'] == 1) {
            $this->returnCodeError(L_($now_city['message']));
        }
        $_POST['province'] = $now_city['area_info']['province_id'] ? $now_city['area_info']['province_id'] : '';
        $_POST['city'] = $now_city['area_info']['city_id'] ? $now_city['area_info']['city_id'] : '';
        $_POST['area'] = $now_city['area_info']['area_id'] ? $now_city['area_info']['area_id'] : '';
		
		if(!$this->config['open_multilingual']){
			if(empty($now_city['area_info']['city_id'])){
				$this->returnCodeError(L_('城市未查找到'));
			}
			if(empty($now_city['area_info']['province_id'])){
				$this->returnCodeError(L_('省/州未查找到'));
			}
		}
        $model_addr = $order_from=='fromWorkerOrder'?D('Merchant_workers_address'):D('User_adress');
		$return	= $model_addr->post_form_save($this->_uid);
		if($return !== false){
			$this->returnCodeOk($_POST['adress_id'] ? $_POST['adress_id'] : $return);
		}else{
			fdump($_POST,'user_adress_save',true);
			fdump(D('User_adress'),'user_adress_save',true);
			$this->returnCode('20046016');
		}
	}
	//	添加编辑地址页面
	public function adress_show(){
		if(!$this->_uid){
			$this->returnCode('20046009');
		}
		
		$adress_id = $_POST['adress_id'];
		$database_area = D('Area');
        $order_from = $_POST['order_from'];
        $model_addr = $order_from=='fromWorkerOrder'?D('Merchant_workers_address'):D('User_adress');
        $now_adress = $model_addr->get_adress($this->_uid,$adress_id);
		
		if(!$_POST['only_adress']){
			if ($now_adress) {
				$province_list = $database_area->get_arealist_by_areaPid(0);
				$city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
				$area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
			} else {
				$now_city_area = $database_area->where(array('area_id'=>$this->config['now_city']))->find();
				$province_list = $database_area->get_arealist_by_areaPid(0);
				foreach($province_list as $k=>$v){
					if($v['area_id'] == $now_city_area['area_pid']){
						$province_lists[]	=	$province_list[$k];
						unset($province_list[$k]);
						break;
					}
				}
				foreach($province_list as $kk=>$vv){
					$province_lists[]	=	$vv;
				}
				$province_list	=	array_values($province_lists);
				$city_list = $database_area->get_arealist_by_areaPid($now_city_area['area_pid']);
				if($city_list[0]['area_id']!=$_POST['now_city']){
					foreach($city_list as $k=>$vc){
						if($vc['area_id'] == $_POST['now_city']){
							$tmp = $vc;
							unset($city_list[$k]);
							array_unshift($city_list,$tmp);
							break;
						}
					}
				}
				$area_list = $database_area->get_arealist_by_areaPid($now_city_area['area_id']);
			}
		}
		if($now_adress){
			$now_adress['details']	=	$now_adress['detail'];
			$now_ad[] 	=	$now_adress;
		}else{
			$now_ad	=	array();
		}
		$arr	=	array(
			'now_adress'	=>	$now_ad,
			'province_list'	=>	isset($province_list)?$this->del_field($province_list,2):array(),
			'city_list'	=>	isset($city_list)?$this->del_field($city_list,2):array(),
			'area_list'	=>	isset($area_list)?$this->del_field($area_list,2):array(),
		);
		$this->returnCode(0,$arr);
	}

    /* 地图 */
    public function adres_map(){
        //得到所有城市并以城市首拼排序
        $database_area = D('Area');
        $all_city = S('all_city_address');
        if(empty($all_city)){
            $database_field = '`area_id`,`area_name`,`area_url`,`first_pinyin`,`is_hot`';
            $condition_all_city['area_type'] = 2;
            $all_city_old = $database_area->field($database_field)->where($condition_all_city)->order('`first_pinyin` ASC,`area_id` ASC')->select();
            foreach($all_city_old as &$value){
                $value['area_name'] = L_($value['area_name']);
                //首拼转成大写
                if(!empty($value['first_pinyin'])){
                    $first_pinyin = strtoupper($value['first_pinyin']);
                    $all_city[$first_pinyin][] = $value;
                }
            }
            S('all_city_address',$all_city);
        }
        $this->returnCode(0,$all_city);
    }

	//	删除多余字段
	private function del_field($arr,$type){
		if($type == 2){
			foreach($arr as &$v){
				unset($v['area_sort'],$v['first_pinyin'],$v['is_open'],$v['area_url'],$v['area_ip_desc'],$v['area_type'],$v['is_hot'],$v['url']);
			}
		}else if($type == 1){
			unset($arr['area_sort'],$arr['first_pinyin'],$arr['is_open'],$arr['area_url'],$arr['area_ip_desc'],$arr['area_type'],$arr['is_hot'],$arr['url']);
		}
		return $arr;
	}
	//	更改地址
	public function select_area(){
		$city	=	$this->select_area_array($_POST['pid']);
		$area_type	=	I('area_type',1);
		if(!empty($city)){
			if($area_type == 1){
				$arr['city_list'] = isset($city[0])?$this->del_field($city[0],2):array();
				$arr['area_list'] = isset($city[1])?$this->del_field($city[1],2):array();
			}else if($area_type == 2){
				$arr['area_list'] = isset($city[0])?$this->del_field($city[0],2):array();
				$city_list =	M('Area')->where(array('area_pid'=>$_POST['pid']))->find();
				if($city_list){
					$city_p =	M('Area')->where(array('area_id'=>$city_list['area_pid']))->find();
					if($city_p){
						$city_pp = D('Area')->get_arealist_by_areaPid($city_p['area_pid']);
						if($city_pp){
							$arr['city_list'] =	$this->del_field($city_pp,1);
						}else{
							$arr['city_list'] =	$this->del_field($city_p,1);
						}
					}else{
						$arr['city_list'][] =	$this->del_field($city_list,1);
					}
				}else{
					$arr['city_list'] =	array();
				}
			}
			$this->returnCode(0,$arr);
		}else{
			$this->returnCode('20046027');
		}
	}
	private function select_area_array($pid){
		$area_list[] = D('Area')->get_arealist_by_areaPid($pid);
		if($area_list){
			if($area_list[0][0]['area_type'] == 3){
				return $area_list;
			}else{
				$area_list[] = D('Area')->get_arealist_by_areaPid($area_list[0][0]['area_id']);
				return $area_list;
			}
		}else{
			return null;
		}
	}
	/*删除地址*/
	public function del_adress(){
		if(!$this->_uid){
			$this->returnCode('20046009');
		}
        $order_from = $_POST['order_from'];
		$model_addr = $order_from=='fromWorkerOrder'?D('Merchant_workers_address'):D('User_adress');
        $result = $model_addr->delete_adress($this->_uid,$_POST['adress_id']);
		if($result){
			$this->returnCode(0);
		}else{
			$this->returnCode('20046026');
		}
	}

    //提现
    public function withdraw(){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}
		
        if($this->config['company_pay_open']=='0') {
            $this->returnCode('20140059');
        }

        if(empty($_POST['truename'])){
            $this->returnCode('20046031');
        }

        $user_info = $now_user;
        $can_withdraw_money = $user_info['now_money']>=$user_info['score_recharge_money']?floatval((int)(($user_info['now_money']-$user_info['score_recharge_money'])*100)/100):$user_info['now_money'];
        $user_info['can_withdraw_money'] = $can_withdraw_money;
        $this->assign('user_info',$user_info);
        if(empty($user_info['openid'])){
            $this->returnCode('20046029');
        }

        $money = $_POST['money'];
        if($money<$this->config['company_least_money']){
            $this->returnCode(1,array(),L_('不能低于最低提款额 X1!',$this->config['company_least_money'].C('config.Currency_txt')));
        }
        if($money>$can_withdraw_money){
            $this->returnCode('20046030');
        }
        $data_companypay['pay_type'] = 'user';
        $data_companypay['pay_id'] = $user_info['uid'];
        $data_companypay['openid'] = $user_info['openid'];
        $data_companypay['nickname'] = $_POST['truename'];
        $data_companypay['phone'] = $user_info['phone'];
        $data_companypay['money'] = bcmul($money,100);
        $data_companypay['desc'] = L_("用户提现{$money}X1，用户ID:X2 " ,array('X1'=>C('config.Currency_txt'),'X2'=>$user_info['uid'])) ;
        $data_companypay['status'] = 0;
        $data_companypay['add_time'] = time();

        $use_result = D('User')->user_money($user_info['uid'],$money,L_('提款 X1 扣除余额',$money));
        if($use_result['error_code']){
            $this->returnCode(1,array(),$use_result['msg']);
        }else{
            D('Companypay')->add($data_companypay);
            $this->returnCode(0);
        }
    }

    //提现列表
    public function withdraw_list(){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}
		
        $where['pay_type']='user';
        $where['pay_id']=$now_user['uid'];
        $withdraw = M('Companypay');
        $count = $withdraw->where($where)->count();
        if($pigcms_id){
            $where['pigcms_id'] = array('lt',$pigcms_id);
        }
        $withdraw_list = $withdraw->field('pigcms_id,money,status,add_time,pay_time')->where($where)->page('1,10')->order('pigcms_id DESC')->select();
        foreach($withdraw_list as &$v){
            $v['money'] = floatval(round($v['money']/100));
            $v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            if($v['pay_time']>0){
                $v['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
            }
        }

        if(empty($withdraw_list)){
            $arr['withdraw_list']	=	array();
        }else{
            $arr['withdraw_list']	=	$withdraw_list;
        }
        $arr['page']	=	ceil($count/10);
        $arr['count'] = $count;
        $this->returnCode(0,$arr);
    }

    public function finishOrder()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $where = array('order_id' => $order_id, 'uid' => $this->_uid);
        $order = D('Shop_order')->field(true)->where($where)->find();
        if (empty($order)) {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单信息不正确')));
        }
        if ($order['status'] == 2 || $order['status'] == 3) {
            exit(json_encode(array('errcode' => 1, 'msg' => '此单已确认，请不要重复确认')));
        }
        $data = array('status' => 2);
        $data['use_time'] = $_SERVER['REQUEST_TIME'];
        $data['last_time'] = $_SERVER['REQUEST_TIME'];
        if (D('Shop_order')->where($where)->save($data)) {
            D('Shop_order')->shop_notice($order);
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 6, 'name' => '', 'phone' => ''));
            exit(json_encode(array('errcode' => 0, 'msg' => 'ok', 'url' => U('Shop/status', array('order_id' => $order_id)))));
        } else {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单状态修改失败，稍后重试')));
        }
    }

    //快店详情接口
    public function shop_order_detail()
    {
        $order_id = $_POST['order_id'];
        if (!$this->_uid) {
            fdump_sql($_POST, 'shop_order_detail_login');
            $this->returnCode('20046009');
        }
        //order_id有可能是长orderid
        if (strlen($order_id) > 12) {
            $order_id = D('Shop_order')->where(['orderid' => $order_id])->getField('order_id');
        }


        if ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $this->_uid))) {

            $status = D('Shop_order_log')->field(true)->where(array('order_id' => $order_id))->order('id DESC')->select();
			
            $arr['statusCount'] = count($status);
            //配送员轨迹
			$order['is_fetch_order'] = true;
            $isThirdDelivery = true;
            if (in_array($order['order_status'], array(2, 3, 4, 5))) {
                //如果是快跑者，调用接口获取快递员当前位置
                if ($order['keloop_trade_no'] != '') {
                    $keloopDeliver = $order['deliver_info'] ? unserialize($order['deliver_info']) : [];
                    $keloop = new Keloop();
                    $Data = ['trade_no' => $order['keloop_trade_no']];
                    $keloopDeliverPos = $keloop->getCourierTag($Data);
                    $keloopDeliverPos = $keloopDeliverPos['data'];
                    if($keloopDeliver){
                        $arr['deliver_user'] = [
                            'uid' => -1,
                            'name' => $keloopDeliver['name'],
                            'phone' => $keloopDeliver['phone'],
                        ];
                    }
                    if ($keloopDeliverPos) {
                        $arr['deliver_user']['now_lng'] = $keloopDeliverPos['longitude'];
                        $arr['deliver_user']['now_lat'] = $keloopDeliverPos['latitude'];
                        $arr['deliver_user']['goto'] = 'none';
                        $arr['deliver_user']['togo'] = 'none';
                    }
                } else if ($order['is_ele_delivery'] == 1) {
                    //蜂鸟配送获取配送员当前位置
                    $merchantStore = D('Merchant_store')->field('ele_is_open,ele_app_id,ele_secret_key,ele_transport_name,ele_chain_store_code')->where(['store_id' => $order['store_id'], 'ele_is_open' => 1])->find();
                    $ele = new EleDeliveryNew();
                    $ele->setBusinessData('partner_order_code', $order['real_orderid']);
                    try {
                        $result = $ele->getKnightInfo();
                        //蜂鸟返回的是高德地图坐标，需要转换成百度
                        list($result['carrier_driver_longitude'], $result['carrier_driver_latitude']) = Coordinate::gcj02ToBd09($result['carrier_driver_longitude'], $result['carrier_driver_latitude']);
                        $arr['deliver_user'] = array(
                            'uid' => -1,
                            'name' => $result['carrier_driver_name'],
                            'phone' => $result['carrier_driver_phone'],
                            'now_lng' => $result['carrier_driver_longitude'],
                            'now_lat' => $result['carrier_driver_latitude'],
                            'goto' => 'none',
                            'togo' => 'none',
                        );
                    } catch (\Exception $e) {
                        //do nothing
                    }
                }else if($order['sf_order_id'] != ''){
                    //顺丰配送获取配送员坐标
                    $sfDeliver = $order['deliver_info'] ? unserialize($order['deliver_info']) : [];
                    $sfData = [
                        'order_id' => $order['sf_order_id'],
                        'order_type' => 1,
                        'push_time' => time()
                    ];
                    $merchantStore = D('Merchant_store')->field('shunfeng_is_open,shunfeng_dev_id,shunfeng_dev_key,shunfeng_shop_id')->where(['store_id' => $order['store_id'], 'shunfeng_is_open' => 1])->find();
                    $sfDeliverPos = (new Shunfeng($merchantStore))->riderlatestposition($sfData);
                    if ($sfDeliverPos && $sfDeliver) {
                        $arr['deliver_user'] = array(
                            'uid' => -1,
                            'name' => $sfDeliver['name'],
                            'phone' => $sfDeliver['phone'],
                            'now_lng' => $sfDeliverPos ? $sfDeliverPos['rider_lng'] : $sfDeliver['longitude'],
                            'now_lat' => $sfDeliverPos ? $sfDeliverPos['rider_lat'] : $sfDeliver['latitude'],
                            'goto' => 'none',
                            'togo' => 'none',
                        );
                    }
                } else {
                    $deliverInfo = $order['deliver_info'] ? unserialize($order['deliver_info']) : [];
                    $supply = D("Deliver_supply")->where(array('order_id' => $order_id,'item'=>2))->find();
                    $start_time = $supply['start_time'];
                    $end_time = $supply['end_time'] ? $supply['end_time'] : time();
                    $where = array();
                    $where['uid'] = $supply['uid'];
                    $deliver_user = D("Deliver_user")->where($where)->find();
//                    if(!$deliver_user){
//                        $deliver_user['now_lng'] = $deliver_user['now_lat'] = $deliver_user['lng'] = $deliver_user['lat'] = 0;
//                    }
                    $dadaStatus = false;//是否是达达订单，默认否
                    if(!$supply){
                        //达达的订单
                        $dada = new Dada();
                        $dadaInfo = $dada->query($order['real_orderid']);
                        if ($dadaInfo['status'] == 'success') {
                            $deliver_user['now_lng'] = $deliver_user['lng'] = ($dadaInfo['result']['transporterLng']??0);
                            $deliver_user['now_lat'] = $deliver_user['lat'] = ($dadaInfo['result']['transporterLat']??0);
                            $dadaStatus = true;
                        } else {
                            fdump($dadaInfo,'dada_error');
                        }
                    }else{
                        $isThirdDelivery = false;
                    }
					if($deliver_user){
						$arr['deliver_user'] = array(
							'uid' => $deliver_user['uid'],
                            'name' => $deliver_user['name'] ?? $deliverInfo['name'],
                            'phone' => $deliver_user['phone'] ?? $deliverInfo['phone'],
							'now_lng' => abs($deliver_user['now_lng']) > 0 ? $deliver_user['now_lng'] : $deliver_user['lng'],
							'now_lat' => abs($deliver_user['now_lat']) > 0 ? $deliver_user['now_lat'] : $deliver_user['lat'],
							'goto' => 'none',
							'togo' => 'none',
						);

						if(isset($_POST['app_type']) && ($_POST['app_type']=='packapp' || $_POST['app_type']=='wxapp') && !$dadaStatus){//达达订单、app端都不需要百度转腾讯
                            //配送员返回腾讯坐标系坐标
                            $longlatClass = new longlat();
                            $user_gcj_longlat = $longlatClass->baiduToGcj02($arr['deliver_user']['now_lat'], $arr['deliver_user']['now_lng']);
                            $arr['deliver_user']['gcj_lng'] = $user_gcj_longlat['lng'];
                            $arr['deliver_user']['gcj_lat'] = $user_gcj_longlat['lat'];
                        }else{
                            $arr['deliver_user']['gcj_lng'] = $arr['deliver_user']['now_lng'];
                            $arr['deliver_user']['gcj_lat'] = $arr['deliver_user']['now_lat'];
                        }
					}
                    if ($order['order_status'] == 2 && !$supply['is_fetch_order']) {
                        $order['deliver_status_str'] = L_('分配配送员');
                        $order['is_fetch_order'] = false;
                    }
                }
            }
            $status_arr = D('Shop_order')->shop_status($order['is_pick_in_store']);
            foreach ($status as &$status_v) {
                $status_v['dateline'] = date('Y-m-d H:i', $status_v['dateline']);
                $status_v['status_txt'] = $status_arr[$status_v['status']]['txt'];
                if($status_v['status']==9){
                    $status_v['status_txt'] = D('Shop_order')->checkOrderRefund($order_id);
                }
                if ($status_v['name']) {
                    $name_str = '【' . $status_v['name'] . '】';
                } else {
                    $name_str = '';
                }

                if ($status_v['status'] == 0) {
                    $status_v['des'] = L_('订单编号：X1', $order['real_orderid']);
                } else if ($status_v['status'] == 1) {
                    $status_v['des'] = L_('订单编号：X1', $order['real_orderid']);
                } else if ($status_v['status'] == 2) {
                    $status_v['des'] = L_('店员：X1正在为您准备商品', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 3) {
					if ($status_v['from_type'] == 0) {
						$status_v['des'] = L_('配送员：X1正在赶往店铺取货', $name_str . $status_v['phone']);
					}else{ 
						$status_v['status_txt'] = L_('分配配送员');
						$status_v['des'] = L_('配送员：订单已分配给X1，等待配送员接单', $name_str . $status_v['phone']);
					}
                } else if ($status_v['status'] == 4) {
                    $status_v['des'] = L_('配送员：X1已取货，准备配送，请耐心等待', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 5) {
                    $status_v['des'] = L_('配送员：X1正快速向您靠拢，请耐心等待！', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 6) {
                    $status_v['des'] = L_('配送员：X1已完成配送，欢迎下次光临！', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 7) {
                    if ($order['is_pick_in_store'] == 3) {
                        $status_v['des'] = L_('店员：X1已发货给快递公司,快递单号:X2', array('X1' => $name_str . $status_v['phone'], 'X2' => $order['express_number']));
                    } else {
                        $status_v['des'] = L_('店员：X1将订单改成已消费', $name_str . $status_v['phone']);
                    }
                } else if ($status_v['status'] == 8) {
                    $status_v['des'] = L_('您已完成评论，谢谢您提出宝贵意见！');
                } else if ($status_v['status'] == 9) {
                    $status_v['des'] = L_('您已完成退款');
                } else if ($status_v['status'] == 10) {
                    $status_v['des'] = L_('您已经取消订单');
                } else if ($status_v['status'] == 11) {
                    $status_v['des'] = L_('店员：X1给您分配', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 12) {
                    $status_v['des'] = L_('店员：X1已经给您发货到配送点', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 13) {
                    $status_v['des'] = L_('自提点X1已经接到您的货物了', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 14) {
                    $status_v['des'] = L_('自提点X1已经给您发货了', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 15) {
                    $status_v['des'] = L_('您在自提点X1已经把您的货提走了！', $name_str . $status_v['phone']);
                } else if ($status_v['status'] == 30) {
                    $status_v['des'] = L_('店员：X1已将订单的总价修改成X2', array('X1' => $name_str . $status_v['phone'], 'X2' => $status_v['note']));
                } else if ($status_v['status'] == 35) {
					$status_v['des'] = L_('配送员：X1正在赶往店铺取货', $name_str . $status_v['phone']);
                }
            }
            $arr['status_list'] = $status ? $status : array();
			if(isset($_POST['wxapp_own_mer_id'])){
				$arr['status'] = $arr['status_list'];
			}
        } else {
            fdump_sql($_POST, 'shop_order_detail_login');
            $this->returnCode('20130006');
        }


        // 获得最后一次状态
        $last_status = $status[0];
        if (in_array($last_status['status'],[3,4,5,35])) {
            if ($last_status['status']==3&&$last_status['from_type']!=0) {
                $arr['user_reminder_show'] = 0;
            }else{
                $arr['user_reminder_show'] = 1;
            }
        }

        // 用户是否可以催单 平台配送才会显示
        if (C('config.open_deliver_reminder')==0  || $order['is_pick_in_store']!=0 || $order['expect_use_time']+900>time()) 
        {
            $arr['user_reminder_show'] = 0;            
        }else{
            $arr['user_reminder_click'] = 1;
            if (time()-$order['reminder_time']<120) {
                $arr['user_reminder_outtime'] = 1;
            }
        }

        $store = D('Merchant_store')->get_store_by_storeId($order['store_id']);
        $shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
        if($order['is_pick_in_store']==2 && $order['order_from']==1){
            $store['adress']=$order['address'];
        }
        $discount_price = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'] + $order['other_money'], 2));
        $order['create_time'] = $order['create_time'] > 0 ? date('Y-m-d H:i', $order['create_time']) : '';
        $order['pay_time'] = $order['pay_time'] > 0 ? date('Y-m-d H:i', $order['pay_time']) : '';
        $order['date'] = $order['date'] > 0 ? date('Y-m-d H:i', $order['date']) : 0;
        $order['short_expect_use_time'] = $order['expect_use_time'] > 0 ? date('H:i', $order['expect_use_time']) : 0;
        $order['expect_use_time'] = $order['expect_use_time'] > 0 ? date('Y-m-d H:i', $order['expect_use_time']) : 0;
        $order['goods_price'] = getFormatNumber($order['goods_price']);
        $order['total_price'] = getFormatNumber($order['total_price'] + $order['upgrade_level_price']);
        $order['balance_reduce'] = getFormatNumber($order['balance_reduce']);
        $order['price'] = getFormatNumber($order['price']);
        $order['minus_price'] = getFormatNumber($order['merchant_reduce'] + $order['balance_reduce']);//平台和商家的优惠金额
        $order['go_pay_price'] = getFormatNumber($discount_price - $order['merchant_reduce'] - $order['balance_reduce'] - $order['coupon_price'] - $order['card_price'], 2);//应付的金额
		if($order['go_pay_price'] < 0){
			$order['go_pay_price'] = 0;
		}
		
		$order['shop_order_cancel_time'] = $this->config['shop_order_cancel_time'];
		
        if (empty($store['logo']) && $store['all_pic'][0]) {
            $store['logo'] = thumb_img($store['all_pic'][0], 150, 100, 'fill');
        }

        $arr['store'] = array_merge($store, $shop);
        $arr['store']['lng'] = $arr['store']['long'];

        //自提订单存在自提点，则取自提点地址和经纬度
        if ($order['pick_id'] > 0) {
            $pickAddress = M('Pick_address')->where(['id' => $order['pick_id']])->find();
            if ($pickAddress) {
                $arr['store']['adress'] = $pickAddress['pick_addr'];
                $arr['store']['lat'] = $pickAddress['lat'];
                $arr['store']['lng'] = $pickAddress['long'];
            }
        }
		
		//返回腾讯地图的坐标体系值
		$longlatClass = new longlat();
		$gcj_longlat = $longlatClass->baiduToGcj02($arr['store']['lat'],$arr['store']['lng']);
		$arr['store']['gcj_lng'] = $gcj_longlat['lng'];
		$arr['store']['gcj_lat'] = $gcj_longlat['lat'];

        //收货地址返回腾讯坐标体系值
        $user_gcj_longlat = $longlatClass->baiduToGcj02($order['lat'], $order['lng']);
        $order['gcj_lng'] = $user_gcj_longlat['lng'] ?? $order['lng'];
        $order['gcj_lat'] = $user_gcj_longlat['lat'] ?? $order['lat'];
		
        if ($order['deliver_info']) {
            $order['deliver_info'] = unserialize($order['deliver_info']);
        }else{
            $order['deliver_info'] = (object)[];
        }

        $isShowMap = 0;
        if ($arr['deliver_user'] && $order['order_status'] != 5) {	//已送达的不显示地图
            $isShowMap = 1;
        } else if ($order['is_pick_in_store'] != 2 && $order['is_pick_in_store'] != 3 && $order['order_status'] < 5 && $order['status'] != 4 && $order['status'] != 5) {
            /* $isShowMap = 1; */
        }
        $order['isShowMap'] = $isShowMap;

        //优惠
        $discount_price = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'] + $order['other_money'], 2));
        if($order['card_discount'] > 0){
            $order['minus_card_discount'] = getFormatNumber(round(($discount_price - $order['merchant_reduce'] - $order['balance_reduce'] - $order['freight_charge']) * (1 - $order['card_discount'] * 0.1), 2));//折扣与优惠的优惠金额
        }else{
            $order['minus_card_discount'] = 0;
        }

        //配送费优惠
        $freightCharge = $order['freight_charge'];
        $freightChargeOriginal = $order['freight_charge_original'];
        $order['minus_freight_charge'] = $freightChargeOriginal - $freightCharge;
        $order['total_price'] = getFormatNumber($order['total_price'] + $order['minus_freight_charge']);

        $order['minus_price'] = getFormatNumber($order['minus_price'] + $order['minus_card_discount'] + $order['minus_freight_charge']);

        if ($order['minus_card_discount'] > 0) {
            $order['go_pay_price'] = getFormatNumber($order['go_pay_price'] - $order['minus_card_discount']);
            $order['go_pay_price'] < 0 && $order['go_pay_price'] = 0;
        }

        // 组合附属商品
        $order['info'] = D('Shop_goods')->combination_data($order['info'], 'spec');

        $subsidiary_goods_list = '';
        $is_seckill = '';
        
        // 超值换购商品列表
        $repGoodsList = [];

        foreach ($order['info'] as $v) {
            $discount_price = floatval($v['discount_price']) > 0 ? floatval($v['discount_price']) : floatval($v['price']);

            $tGoods = array(
                'name'                  => $v['name'],
                'discount_type'         => $v['discount_type'],
                'price'                 => getFormatNumber($v['price']),
                'discount_price'        => getFormatNumber($discount_price),
                'spec'                  => empty($v['spec']) ? '' : $v['spec'],
                'num'                   => getFormatNumber($v['num']),
                'total'                 => getFormatNumber($v['price'] * $v['num']),
                'discount_total'        => getFormatNumber($discount_price * $v['num']),
                'subsidiary_goods_list' => $subsidiary_goods_list,
                'is_seckill'            => $is_seckill ? true : false,
                'show_seckill'          => $v['is_seckill'] ? true : false,    //限
                'show_discount'         => in_array($v['discount_type'], array(1, 4, 2, 5)) ? true : false,
                'rep_id'                => $v['rep_id'],//超值换购活动id
                'is_give'               => $v['is_give'],
                'show_upgrade'          => $v['rep_id'] > 0 ? true : false,        //换购
                'show_give'             => $v['is_give'] > 0 ? true : false,        //满赠
                'refund_nums'           => 0,  //退款商品数
            );
            
            $refund_goods_num = D('Shop_order_refund')->join(' as r inner join pigcms_shop_refund_detail as rd on r.id = rd.refund_id')
                ->where(['r.status'=>1,'r.order_id'=>$v['order_id'],'rd.uniqueness_number'=>$v['uniqueness_number']])
                ->sum('rd.num');
            $tGoods['refund_nums'] = (int)$refund_goods_num?:0;

            //如果VIP折扣（discount_type = 3），则不展示划线价格
            if($v['discount_type'] == 3){
                $tGoods['discount_total'] = $tGoods['total'];
            }

            //加上会员等级折扣金额
            $d_rate = explode(',', $v['discount_rate']);

            $index = isset($v['packname']) && $v['packname'] ? $v['packname'] : 0;

            if ($v['rep_id']) {
                // 超值换购不参与其他任何折扣
                $tGoods['discount_total'] = getFormatNumber($discount_price * $v['num']);
                if (isset($repGoodsList[$index])) {
                    $repGoodsList[$index]['list'][] = $tGoods;
                } else {
                    $repGoodsList[$index] = array('name' => $v['packname'], 'list' => array($tGoods));
                }
            }else{
                if ($v['discount_type'] != 3 && $d_rate[0]) {
                    //$tGoods['discount_total'] = getFormatNumber(($tGoods['price'] * $tGoods['num']) * ($d_rate[0] * 0.01));
                } else {
                    //$tGoods['discount_total'] = getFormatNumber($tGoods['price'] * $tGoods['num']);
                } 
                if (isset($tempList[$index])) {
                    $tempList[$index]['list'][] = $tGoods;
                } else {
                    $tempList[$index] = array('name' => $v['packname'], 'list' => array($tGoods));
                }
            }
           
        }
        if (count($tempList) == 1) {
            $tempList[$index]['name'] = '';
        }
        unset($order['info']);
        $arr['goods_list'] = array_values($tempList);
        $arr['rep_goods_list'] = $repGoodsList;

        if ($order['discount_detail']) {
            $order['discount_detail'] = array_values($order['discount_detail']);
        }

        //订单进度条
        $order['progress_bar'] = 66;
        if ($order['is_pick_in_store'] != 2 && $order['is_pick_in_store'] != 3) {
            if ($order['paid'] == 0) {                  //未支付
                $progress_bar = 1;
            } else if ($order['status'] == 0) {        //未确认
                $progress_bar = 2;
            } else if ($order['status'] == 3) {        //已评论
                $progress_bar = 7;
            } else if ($order['status'] == 4) {        //退款
                $progress_bar = 7;
            } else if ($order['order_status'] == 1) {    //店员接单
                $progress_bar = 3;
            } else if ($order['order_status'] == 2) {    //配送员接单
                $progress_bar = 4;
            } else if ($order['order_status'] == 3) {    //配送员取货
                $progress_bar = 5;
            } else if ($order['order_status'] == 4) {    //送达
                $progress_bar = 6;
            } else if ($order['order_status'] == 5 || $order['order_status'] == 6 || $order['status'] == 3) {    //确认收货 //配送结束或已经评论
                $progress_bar = 7;
            }else {                                    //其他状态
                $progress_bar = 7;
            }
			
            $order['progress_bar'] = round($progress_bar / 7, 2) * 100;
        }
		if($order['is_pick_in_store'] == 2){
			if ($order['paid'] == 0) {                 //未支付
                $progress_bar = 1;
            } else if ($order['status'] == 0) {        //未确认
                $progress_bar = 2;
            } else if ($order['status'] == 1) {        //店员接单
                $progress_bar = 3;
            } else if ($order['status'] == 2 || $order['status'] == 3) {        //已经消费
                $progress_bar = 4;
            }
			$order['progress_bar'] = round($progress_bar / 4, 2) * 100;
		}
		
		if($order['is_pick_in_store'] == 3){
			if ($order['paid'] == 0) {                 //未支付
                $progress_bar = 1;
            } else if ($order['status'] == 0) {        //未确认
                $progress_bar = 2;
            } else if ($order['status'] == 1) {        //店员接单
                $progress_bar = 3;
            } else if ($order['status'] == 2) {        //已经消费
                $progress_bar = 4;
            } else if ($order['status'] == 3) {        //已评价
                $progress_bar = 5;
            }
			$order['progress_bar'] = round($progress_bar / 5, 2) * 100;
		}
		
		//MW支付返回
		if($order['pay_type'] == 'merchantwarrior' && $order['status'] != 4){
            $mw_pay_info = M('Hake_credit_order')->field('id,credit_id')->where(['order_id'=>$order['orderid'], 'order_type'=>'shop', 'is_redirect'=>'1', 'pay_time'=>['neq','0'], 'add_card_tip'=>'0'])->find();
            if($mw_pay_info){
                $mw_redirect_info = M('Hake_credit_list')->field('id,card_number,is_verify')->where(['id'=>$mw_pay_info['credit_id']])->find();
                $order['mw_redirect_info'] = $mw_redirect_info && $mw_redirect_info['is_verify'] != '1' ? $mw_redirect_info : new \stdClass();
            }
        }
		
		if($order['express_number'] && $this->config['kdniao_appkey']){
			$order['kdniao_url'] = $this->config['site_url'] . '/wap.php?c=Mall&a=express_new&type=' . $order['express_code'] . '&postid=' . $order['express_number'];
		}else{
			$order['kdniao_url'] = '';
		}
		
		if(!file_exists('conf/is_hake.no_delete')){
			$order['shop_plat_tip'] = '我们';
		}else{
			$order['shop_plat_tip'] = '哈客外卖';
		}

        //订单评分
        $order['order_score'] = '0';
        if ($order['status'] == 3) {
            $order['order_score'] = M('Reply')->where(array('order_id' => $order['order_id'], 'store_id' => $order['store_id']))->getField('score');
        }

        //支付手续费
        $payment_money_order_db = M("payment_money_order");
        $payment_money_order = $payment_money_order_db->where("order_id=".$order['order_id'])->find();
        $order['service_charge'] = '';
        if($payment_money_order && $payment_money_order['service_charge'] != '0.00'){
            $order['service_charge'] = $payment_money_order['service_charge'];
            $order['go_pay_price'] += $order['service_charge'];
        }
		$order['go_pay_price'] = getFormatNumber($order['go_pay_price']);

        //如果开通升级会员，增加会员名称返回
        $order['upgrade_level_show'] = false;
        $order['upgrade_level_name'] = '';
        if($order['upgrade_level'] > 0){
            $order['upgrade_level_show'] = true;
            $upgradeLevelName = M('User_level')->where(['level'=>$order['upgrade_level']])->getField('lname');
            $order['upgrade_level_name'] = $this->config['vip_card_alias'].$upgradeLevelName;
        }

        //优惠信息返回
        $order['discount_list'] = $this->parseDiscountList($order['discount_detail'], ['freight_charge' => $order['freight_charge_original']]);
        $arr['order'] = $order;
        if ($arr['order']['is_open_membership_card']) {
            if (empty($arr['order']['membership_card_name'])) {
                $card_name = M('Membership_card_user_open_record')->where(['business_type' => 'shop', 'business_order_id' => $order_id])->getField('card_name');
            } else {
                $card_name = $arr['order']['membership_card_name'];
            }
            $arr['order']['membership_card_name'] = L_('开通X1', ['X1' => $card_name]);
            $arr['order']['total_price'] += $arr['order']['membership_card_price'];
            $arr['order']['go_pay_price'] += $arr['order']['membership_card_price'];
        } else {
            $arr['order']['membership_card_name'] = '';
        }
        $arr['order']['go_pay_price'] = getFormatNumber($arr['order']['go_pay_price']);
        $arr['order']['total_price'] = getFormatNumber($arr['order']['total_price']);
        

        //配送员展现距离
        if ($arr['status_list'] && $arr['deliver_user']) {
            if ($arr['status_list'][0]['status'] == 2 || $arr['status_list'][0]['status'] == 3 || $arr['status_list'][0]['status'] == 4 || $arr['status_list'][0]['status'] == 35 || $arr['status_list'][0]['status'] == 36) {
                $arr['deliver_user']['goto'] = 'store';
                $arr['deliver_user']['togo'] = 'store';
                // if($arr['store']['lat'] && $arr['store']['lng']){
                //     $store_gcj_longlat = $longlatClass->baiduToGcj02($arr['store']['lat'], $arr['store']['lng']);
                //     $arr['store']['lat'] = $store_gcj_longlat['lat'];
                //     $arr['store']['lng'] = $store_gcj_longlat['lng'];
                // }
                $arr['deliver_user']['distance'] = getRange(getDistance($arr['deliver_user']['now_lat'], $arr['deliver_user']['now_lng'], $arr['store']['lat'], $arr['store']['lng']));
            } else if ($arr['status_list'][0]['status'] == 5 || $arr['status_list'][0]['status'] == 6) {
                $arr['deliver_user']['goto'] = 'user';
                $arr['deliver_user']['togo'] = 'user';
                $arr['deliver_user']['distance'] = getRange(getDistance($arr['deliver_user']['now_lat'], $arr['deliver_user']['now_lng'], $arr['order']['lat'], $arr['order']['lng']));
            }
        }

        if (isset($arr['deliver_user']['gcj_lng'])) {
            $arr['deliver_user']['now_lng'] = $arr['deliver_user']['gcj_lng'];
            $arr['deliver_user']['now_lat'] = $arr['deliver_user']['gcj_lat'];
        }

        if($arr['order']['deliver_str'] != '自提'){
            if($arr['order']['expect_use_time']){
                $arr['order']['short_expect_use_time'] .= ' - '.date('H:i', strtotime($arr['order']['expect_use_time']) + 900);
                $arr['order']['expect_use_time'] .= ' - '.date('H:i', strtotime($arr['order']['expect_use_time']) + 900);
            }else{
                $arr['order']['short_expect_use_time'] = '';
                $arr['order']['expect_use_time'] = '';
            }
			
            $arr['order']['expect_use_time_str'] = L_('期望时间');
        }else{
            if($arr['order']['expect_use_time']){
                $arr['order']['short_expect_use_time'] .= ' - '.date('H:i', strtotime($arr['order']['expect_use_time']) + 1200);
                $arr['order']['expect_use_time'] .= ' - '.date('H:i', strtotime($arr['order']['expect_use_time']) + 1200);
            }else{
                $arr['order']['short_expect_use_time'] = '';
                $arr['order']['expect_use_time'] = '';
            }
			
            $arr['order']['expect_use_time_str'] = L_('自提取货时间');
        }

        if($arr['order']['is_pick_in_store'] == 3){
            $index = 0;
            $statusLogs = array_reverse($arr['status_list']);
            foreach ($statusLogs as $k => $v) {
                if ($v['status'] == 2) {
                    $index += 1;
                    if ($index == 1) {
                        continue;
                    } else if ($index == 2) {
                        $statusLogs[$k]['status_txt'] = L_('店铺已发货');
                    } else {
                        unset($statusLogs[$k]);
                    }
                }
            }
            $arr['status_list'] = array_values(array_reverse($statusLogs));
            /*if(isset($arr['status_list'][0]['status']) AND $arr['status_list'][0]['status'] == 2){
                $arr['status_list'][0]['status_txt'] = L_('店铺已发货');
                //$arr['order']['status'] = 2;
            }*/
        }
		$arr['is_open_shop_refund'] = C('config.is_open_shop_refund');//是否开启用户申请退款功能（开启：用户前台的快店订单详情中会出现“申请售后”按钮;关闭：则用户不可申请退款；只有验证消费后的快店订单才可申请退款）

        //定制抽奖
        $where['mer_id'] = $order['mer_id'];
        $where['status'] = 1;
        $where['_string'] = '((start_time<='.time().' AND end_time >='.time() .') OR (start_time=0 AND end_time=0)) AND (total_money="0.00" OR total_money>give_out_money)';
        $lottery  = M('Lottery_shop')->where($where)->find();
        $lottery_info  = M('Lottery_shop_list')->where(array('order_id'=>$order['order_id']))->find();
        $arr['open_share_lottery'] = $this->config['open_share_lottery'];

        // 是否显示抽奖
        $arr['show_lottery'] = 0;
        $order_is_pay = ($order['paid'] == 1 && $order['status'] <= 3);
        $order_is_use = ($order['paid'] == 1 && $order['status'] == 2);
        if($order_is_use && $this->config['open_share_lottery'] == 1 && $lottery){
            $arr['show_lottery'] = 1 ;
        }

        //判断有无分享抽奖，没有则判断是否存在分享抢券,排除订单窗台未付款、已退款
        $arr['share_coupon_show'] = 0;
        $arr['share_coupon_alert'] = 0;
        if ($order_is_pay && $arr['show_lottery'] != 1 && $this->config['share_coupon'] == 1 && !in_array($order['status'], [4, 6])) {
            $res = D('System_coupon')->share_coupon(['order_id' => $order['order_id'], 'uid' => $this->_uid, 'type' => 'shop']);
            $arr['share_coupon_show'] = 1;
            if ($res['error_code']==0) {
                $arr['share_coupon_alert'] = 1;
            }
            $arr['share_coupon_info'] = [
                'share_title' => L_($this->config['share_coupon_title']),
                'share_content' => sprintf('%d张优惠券，快来抢啊!', intval($this->config['share_coupon_num'])),
                'share_image' => $this->config['order_wx_share_img'],
                'share_url' => C('config.site_url') . '/wap.php?c=Share_lottery&a=share_coupon&type=shop&order_id=' . $order['order_id'],
                'share_coupon_num' => intval($this->config['share_coupon_num']),
                'share_coupon_get_num' => intval($this->config['share_coupon_get_num']),
                'go_to_view' => str_replace('/appapi.php', '/wap.php', U('Share_lottery/my_get_coupon', ['order_id' => $order['order_id'], 'type' => 'shop'],true,false,true)),
            ];

            //小程序跳转链接调整一下
            if ($_POST['Device-Id'] == 'wxapp') {
                $arr['share_coupon_info']['share_url'] = D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode($arr['share_coupon_info']['share_url']);
            }
        }


        if ($arr['order']['isShowMap']) {
            $mapIconMod = D('MapIcon');
            $store_map_icon = $mapIconMod->createStoreMapIcon($order['store_id']);
            $user_map_icon = $mapIconMod->createUserMapIcon($order['uid']);
            $default_map_icon = $this->config['site_url'] . '/static/images/shop_order_deliver_icon.png';
            $arr['order']['store_map_icon'] = $store_map_icon ? replace_file_domain($store_map_icon) : $default_map_icon;
            $arr['order']['user_map_icon'] = $user_map_icon ? replace_file_domain($user_map_icon) : $default_map_icon;
        }
		
		//订单的按钮栏
			//支付
			$arr['btn_bar']['pay_by_other'] = false;
			if($arr['order']['paid'] == 0 && $arr['order']['status'] != 5){
				$arr['btn_bar']['pay_order'] = true;

                $arr['btn_bar']['pay_by_other'] = $this->config['is_open_pay_for_others'] == 1 ? true : false;
                $arr['btn_bar']['pay_by_other_url'] = $this->config['site_url'] . '/packapp/plat/pages/shop_new/payByOthers/payByOthers?order_id=' . intval($order['order_id']);
			}else{
				$arr['btn_bar']['pay_order'] = false;
			}
			//取消订单
            $arr['btn_bar']['cancel_order'] = false;
			if($arr['order']['paid']){
				if($arr['order']['paid'] && $arr['deliver_user']){
					$arr['btn_bar']['cancel_order'] = false;
				}
                if ($arr['order']['order_status'] < 1 && !in_array($arr['order']['status'], [4, 5])) {
					$arr['btn_bar']['cancel_order'] = true;
				}else{
					$arr['btn_bar']['cancel_order'] = false;
				}
			}else if($order['status'] != 5){
                $arr['btn_bar']['cancel_order'] = true;
            }
			$arr['btn_bar']['cancel_order_url'] = $this->config['site_url'] . '/wap.php?c=My&a=shop_order_refund&order_id=' . $order['order_id'];
			
			//联系商户
            if($arr['order']['paid'] && $arr['store']['phone']){
                $arr['btn_bar']['callStaff'] = true;
            }else{
                $arr['btn_bar']['callStaff'] = false;
            }

			//联系骑手
            if (($arr['order']['order_status'] == 5) || ($arr['order']['order_status'] < 5 && $arr['deliver_user']['phone'] != '')) {
                $arr['btn_bar']['callDeliver'] = true;
            } else {
                $arr['btn_bar']['callDeliver'] = false;
            }

			//催单
			$arr['btn_bar']['reminder'] = false;
            if ($arr['user_reminder_show']) {
                $arr['btn_bar']['reminder'] = true;
            }

			//评价
            if($arr['order']['status'] == 2){
                $arr['btn_bar']['reply'] = true;
                $arr['btn_bar']['reply_url'] = $this->config['site_url'] . "/packapp/plat/pages/my/make_comments?order_id=" . $order['order_id'];
            }else{
                $arr['btn_bar']['reply'] = false;
            }

			//申请售后
            if(((!C('customization.yuanjino2o_customized_func')&&$arr['order']['order_status'] >=5 && in_array($arr['order']['status'], [2, 3]))||(C('customization.yuanjino2o_customized_func')&&$arr['order']['order_status'] >=1 && in_array($arr['order']['status'], [1, 2, 3, 4, 7, 8, 9, 10]))) && $arr['is_open_shop_refund'] == 1){
                $arr['btn_bar']['after_sale'] = true;
                $arr['btn_bar']['after_sale_url'] = $this->config['site_url'] . '/wap.php?c=Shop&a=refund&order_id=' . $order['order_id'];
            }else{
                $arr['btn_bar']['after_sale'] = false;
            }
            //查询是否已全部退款
            if($arr['order']['status']==4){
                $goods_total_all = D('Shop_order_detail')->where(['order_id'=>$arr['order']['order_id']])->sum('num');
                $refund_goods_total_all = D('Shop_order_refund')->join(' as r inner join '.C("DB_PREFIX").'shop_refund_detail as rd on rd.refund_id = r.id')
                    ->where(['r.order_id'=>$arr['order']['order_id'],'r.status'=>1])->sum('rd.num');
                if(!$refund_goods_total_all||$refund_goods_total_all >= $goods_total_all){
                    $arr['btn_bar']['after_sale'] = false;
                }  
            }

			//确认收货
            if($arr['order']['paid'] == 1 && $arr['order']['is_pick_in_store'] == 3 && $arr['order']['status'] == 1){
                $arr['btn_bar']['confirm'] = true;
            }else{
                $arr['btn_bar']['confirm'] = false;
            }

			//订单备注
            $arr['btn_bar']['order_mask'] = '';

			//是否配送单
			if($arr['order']['paid'] && ($arr['order']['is_pick_in_store'] == 0 || $arr['order']['is_pick_in_store'] == 1 || $arr['order']['is_pick_in_store'] == 3)){
				$arr['btn_bar']['isDelivOrder'] = true;
				if($arr['deliver_user']){
					$arr['btn_bar']['order_mask'] = '由' . $arr['deliver_user']['name'] . '配送';
				}
			}else{
				$arr['btn_bar']['isDelivOrder'] = false;
			}
			
			//是否自提单
			if($arr['order']['paid'] && $arr['order']['is_pick_in_store'] == 2){
				$arr['btn_bar']['isPickupOrder'] = true;
				$arr['btn_bar']['order_mask'] = '预留手机号：' . $arr['order']['userphone'];
			}else{
				$arr['btn_bar']['isPickupOrder'] = false;
			}
			
			//订单的状态栏时间
			if($arr['btn_bar']['expect_use_time']){
				$arr['btn_bar']['expect_use_time'] = $arr['order']['expect_use_time'] . $arr['order']['short_expect_use_time'];
				$arr['btn_bar']['expect_use_time'] = $arr['order']['short_expect_use_time'];
			}else{
				$arr['btn_bar']['expect_use_time'] = $arr['order']['short_expect_use_time'];
			}
			
			//是否展示评分栏
			if($arr['order']['order_score']){
				$arr['btn_bar']['showUserScore'] = true;
			}else{
				$arr['btn_bar']['showUserScore'] = false;
			}
			
			//是否展示一行字文本，例如 您当前的订单已被取消
			$arr['btn_bar']['showBigTip'] = false;
			if($arr['order']['status'] == '4'){
				$arr['btn_bar']['showBigTip'] = true;
				$arr['btn_bar']['order_mask'] = '您当前的订单已被取消';
			}else if($arr['order']['status'] == '5'){
				$arr['btn_bar']['showBigTip'] = true;
				$arr['btn_bar']['order_mask'] = '您当前的订单已被取消';
			}else if(in_array($arr['order']['status'],[2,3])){
                $arr['btn_bar']['showBigTip'] = true;
                $arr['btn_bar']['order_mask'] = "感谢您对本店的信任，期待再次光临！";
                $arr['btn_bar']['order_tip'] = '温馨提示：请厉行节约，拒绝浪费。';
            }
			
			//是否展示自取地址
			$arr['btn_bar']['show_pickup_address'] = true;
		
		if (file_exists('conf/is_hake.no_delete')) {

            $arr['is_hark'] = 1;
        } else {
            $arr['is_hark'] = 0;
        }

        //增加自提二维码
        if ($order['is_pick_in_store'] == 2) {
            $arr['pick_qrcode'] = $this->config['site_url'] . str_replace('appapi.php', 'index.php', U('Index/Recognition/get_own_qrcode', array('qrCon' => 'shop_'.$order['real_orderid'].'_'.createRandomStr(32))));
        } else {
            $arr['pick_qrcode'] = '';
        }

        //增加快递100查询物流信息地址
        $arr['order']['express_travel_url'] = '';
        if ($order['is_pick_in_store'] == 3 && $order['express_id']) {
            $arr['order']['express_travel_url'] = sprintf('https://m.kuaidi100.com/app/query/?com=%s&nu=%s&coname=%s', $order['express_code'], $order['express_number'], 'cms');
        }

        // 按钮列表
        $arr['buttonList'] = [];
        $hasChangeAddress = D('Merchant_store_shop')->hasChangeAddress($order['mer_id'], $order['store_id'], $order['order_id']);
        // 申请退款
        if($hasChangeAddress['has_change'] && $order['paid']>0){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/store/address.png',
                'name'      => '修改地址',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'address',
                'extra'     => [
                    'url'=>C("config.site_url").'/packapp/plat/pages/my/address/modifyShippingAddress?type=shop&order_id='.$order['order_id']
                ]
            );
        }
        $changeAddressStatus = M('System_order')
            ->where([
                'type' => 'shop',
                'order_id' => $order['order_id']
            ])
            ->field('change_address_status')
            ->find();
        //`change_address_status` '修改地址状态  0 未修改  1 等待商家审核 2 商家拒绝修改 3 骑手接单，修改地址自动撤销 4 收货地址修改成功'
        $statusText = [
            '',
            '等待商家审核修改地址',
            '商家拒绝修改地址',
            '商家未审核，修改地址自动撤销 ',
            '收货地址修改成功',
            '收货地址修改成功',
        ];
        $arr['change_address_info'] = [
            'change_address_status'  => $changeAddressStatus['change_address_status'] ?: 0,
            'change_address_text' => $statusText[$changeAddressStatus['change_address_status']] ?? ''
        ];
            
        // 申请退款
        if($arr['btn_bar']['after_sale']){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/refund.png',
                'name'      => '申请退款',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'refund',
                'extra'     => ['url'=>$arr['btn_bar']['after_sale_url']]
            );
        }
		
        // 打赏骑手
		$isReward = 0;
        if( !$isThirdDelivery && $order['is_pick_in_store'] == 0 && ($order['order_status'] == 5 || $order['order_status'] == 6)) {   // 判断订单状态
			$rewardOrderId  = M('Deliver_reward_order')->field('paid,create_time')->where(array('takeout_id'=>$order['order_id']))->find();
			$isReward = (int)$rewardOrderId['paid'];
            if($isReward){
                $arr['buttonList'][] = array(
                    'img'       => C("config.site_url").'/static/images/reward/reward.png',
                    'name'      => '已打赏骑手',
                    "weight"    => "bold",
                    "typeface"  => "PingFang-SC-Bold",
                    'color'     => '#000000',
                    'type'      => 'reward'
                );
            }else{
                if(C('config.is_open_system_reward')==1){   // 判断系统设置
                    $reward_dateline = D('Shop_order_log')->field('dateline')->where(array('order_id'=>$order['order_id'],'status'=>'6'))->find();
                    $endtime = $reward_dateline['dateline'] ? $reward_dateline['dateline'] : time();
                    $timeUse = time() - (int)$endtime;
                    $isTime = floor($timeUse/3600);
                    if($isTime < 24*7){ // 判断是否超过七天
                        $arr['buttonList'][] = array(
                            'img'       => C("config.site_url").'/static/images/reward/reward.png',
                            'name'      => '打赏骑手',
                            "weight"    => "bold",
                            "typeface"  => "PingFang-SC-Bold",
                            'color'     => '#000000',
                            'type'      => 'reward'
                        );
                    }
                }
            }
        }
        $arr['isReward'] = $isReward;
		
        // 再来一单
        $againDb = M("System_order")->where(['order_id'=>$order_id])->field('type,store_id,system_status')->find();
        if($againDb['system_status'] == '2' && $againDb['type'] == 'shop'){
            $againUrl = $this->config['site_url'] . "/packapp/plat/pages/shop_new/shopDetail/shopDetail?store_id=".$againDb['store_id'];
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/again.png',
                'name'      => '再来一单',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'again',
                'extra'     => ['url'=>$againUrl]
            );
        }
        // 评价得积分
        if($arr['btn_bar']['reply']){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/evaluate.png',
                'name'      => $this->config['feedback_score_add'] > 0 ? '评价得'.C('config.score_name')??"积分" : '去评价',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'evaluate',
                'extra'     => ['url'=>$arr['btn_bar']['reply_url'], 'integral'=> $this->config['feedback_score_add'] > 0 ? '+' . $this->config['feedback_score_add'] : '']
            );
        }
        // 致电骑手
        if($arr['btn_bar']['callDeliver']){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/call.png',
                'name'      => '致电骑手',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'call'
            );
        }
        // 联系商家
        if($arr['btn_bar']['callStaff']){
            //如果店铺有配合客服，则跳转到客服页面
            $btnParams = array(
                'img'       => C("config.site_url") . '/static/images/reward/business.png',
                'name'      => '联系商家',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'business'
            );
            $storeKefu = M('Merchant_store_kefu')->where(['store_id' => $order['store_id'], 'belong' => 'store'])->find();
            if ($storeKefu) {
                $btnParams['extra'] = [
                    'url' => build_im_chat_url('user_' . $order['uid'], $storeKefu['username'], 'user2store')
                ];
            }
            $arr['buttonList'][] = $btnParams;
        }
        //联系自提点
        if($arr['btn_bar']['isPickupOrder']){
            if ($address_p = M('Pick_address')->field(true)->where(array('id' => $arr['order']['pick_id']))->find()) {
                $arr['buttonList'][] = array(
                    'img'       => C("config.site_url").'/static/images/reward/business.png',
                    'name'      => '联系自提点',
                    "weight"    => "bold",
                    "typeface"  => "PingFang-SC-Bold",
                    'color'     => '#000000',
                    'type'      => 'pick_self',
                    'phone'     => $address_p['phone'],
                );
            }
        }
        // 取消订单
        if($arr['btn_bar']['cancel_order']){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/cancel_order.png',
                'name'      => '取消订单',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'cancel_order'
            );
        }
        // 催单
        if($arr['btn_bar']['reminder']){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/reminder.png',
                'name'      => '催单',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'reminder'
            );
        }
        // 确定收货
        if($arr['btn_bar']['confirm']){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/confirm.png',
                'name'      => '确认收货',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'confirm'
            );
        }
        // 支付订单
        if($arr['btn_bar']['pay_order']){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/pay_order.png',
                'name'      => '支付订单',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'pay_order'
            );
        }
        // 找人付
        if($arr['btn_bar']['pay_by_other']){
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/pay_by_other.png',
                'name'      => '找人付',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'pay_by_other',
                'extra'     => ['url'=>$arr['btn_bar']['pay_by_other_url']]
            );
        }

        //三方聊天
        $supply = M('Deliver_supply')->where(['order_id' => $order_id, 'item' => 2])->find();
        if($supply && in_array($supply['deliver_status'],[2,3,4,5])){
            if ($order['use_time'] && $order['use_time'] - time() > 2 * 86400) {
                //当订单已完成超过2天的时候不在显示在线沟通
            } else {
                //判断是否有新消息
                $chatIcon = C("config.site_url") . '/static/images/reward/im.png';
                $groupChatUnreadMsgCount = 0;
                if($groupChatUnreadMsgCount > 0){
                    $chatIcon = C("config.site_url") . '/static/images/reward/im_new_message.png';
                }
                $arr['buttonList'][] = array(
                    'img'       => $chatIcon,
                    'name'      => '在线沟通',
                    "weight"    => "bold",
                    "typeface"  => "PingFang-SC-Bold",
                    'color'     => '#000000',
                    'type'      => 'im',
                    'extra' => ['url' => build_im_chat_url('user_' . $order['uid'], 'deliver_'.$supply['uid'], 'user2deliver',['order_id'=>$order['order_id']])]
                );
            }
        }

        // 种草
        if(C('config.single_system_type') != 'shop' && in_array($againDb['system_status'],[2,3,6]) && $againDb['type'] == 'shop'){
            $areaName = '';
            if(isset($store['circle_id']) && $store['circle_id']){
                $area = M('Area')->where(['area_id'=>$store['circle_id']])->field('area_name')->find();
                $areaName = $area['area_name'];
            }
            $cateName = '';
            if(isset($store['cat_fid']) && $store['cat_fid']){
                $cate = M('Merchant_category')->where(['cat_id'=>$store['cat_fid']])->find();
                $cateName = $cate['cat_name'] ?? '';
            }
            if(isset($store['cat_id']) && $store['cat_id']){
                $cate = M('Merchant_category')->where(['cat_id'=>$store['cat_id']])->find();
                $cate && $cateName = $cateName ? $cateName.'/'.$cate['cat_name'] : $cate['cat_name'];
            }
            $arr['buttonList'][] = array(
                'img'       => C("config.site_url").'/static/images/reward/grow_grass.png',
                'name'      => '种草',
                "weight"    => "bold",
                "typeface"  => "PingFang-SC-Bold",
                'color'     => '#000000',
                'type'      => 'grow_grass',
                'extra'     => [
                    'url'=>get_base_url('pages/wantToBuy/v1/release/index',1),
                    'area_name'=>$areaName,
                    'cate_name'=>$cateName,
                    'image'=>replace_file_domain(explode(';',$arr['store']['pic_info'])[0]),
                    'name'=>$arr['store']['name'],
                    'storeOrGoods'=>'store',
                    'store_id'=>$order['store_id']
                ]
            );
        }

        //外卖店铺主页
        $arr['store']['store_url'] = $this->config['site_url'] . D('House_village')->base_url . "pages/shop_new/shopDetail/shopDetail?store_id=" . $order['store_id'];

        $this->returnCode(0, $arr);
    }

	public function shop_finishOrder()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $where = array('order_id' => $order_id, 'uid' => $this->_uid);
        $order = D('Shop_order')->field(true)->where($where)->find();
        if (empty($order)) {
			$this->returnCodeError('订单信息不正确');
        }
        if ($order['status'] == 2 || $order['status'] == 3) {
			$this->returnCodeError('此单已确认，请不要重复确认');
        }
        $data = array('status' => 2, 'order_status' => 5);
        $data['use_time'] = $_SERVER['REQUEST_TIME'];
        $data['last_time'] = $_SERVER['REQUEST_TIME'];
        if (D('Shop_order')->where($where)->save($data)) {
            D('Shop_order')->shop_notice($order);
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 6, 'name' => '', 'phone' => ''));
            if($order['status'] != 2){
                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'shop';
                $params['order_id'] = $order['order_id'];
                $params['mer_id'] = $order['mer_id'];
                $params['store_id'] = $order['store_id'];
                $params['uid'] = $order['uid'];
                $params['title'] = '核销成功，已经领取商品';
                $params['content'] = '您已经领取团购商品，请核对订单';
                invoke_v20_service($url, [$params]);
            }
			$this->returnCodeOk($order_id);
        } else {
			$this->returnCodeError('订单状态修改失败，稍后重试');
        }
    }

    // 用户催单
    public function user_reminder()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $where = array('order_id' => $order_id, 'uid' => $this->_uid);
        $order = D('Shop_order')->field(true)->where($where)->find();
        if (empty($order)) {
            $this->returnCodeError('订单信息不正确');
        }
         // 用户是否可以催单 平台配送才会显示
        if ($order['is_pick_in_store']==0 && in_array($order['order_status'], [2,3,4]) && $order['expect_use_time']+900<time()) 
        {
            if ($order['user_reminder']>=3) {
                $this->returnCodeError('本单催单次数已达上限，不能催单了');
            }
            if (time()-$order['reminder_time']<120) {
                $this->returnCodeError('您已催过单了，配送员已经在快马加鞭的为您配送了');
            }

            $data['user_reminder'] = $order['user_reminder'] + 1;
            $data['reminder_status'] = 0;
            $data['reminder_time'] = $_SERVER['REQUEST_TIME'];
            if(M('Shop_order')->where(array('order_id'=>$order['order_id']))->save($data)){
                $supply = D("Deliver_supply")->where(array('order_id' => $order_id))->find();
                $deliver_user = D("Deliver_user")->where(['uid'=>$supply['uid']])->find();

                D('Deliver_supply')->sendReminderNotice($deliver_user, $supply);

                $this->returnCodeOk('系统已帮您催单配送员尽快送达');
            }else{
                $this->returnCodeError('催单失败');
            }
        }else{
            $this->returnCodeError('不能催单');
        }
    }

    //抽奖概率
    public function lottery_shop(){
        $type = $_POST['type'] ? $_POST['type'] : 'shop';
        $order_id = $_POST['order_id'];
        if (!$this->_uid) {
            $this->returnCode('20046009');
        }

        $where['order_id']=$order_id;
        $where['uid']=$this->_uid;
        switch($type){
            case 'shop':
                $now_order = M('Shop_order')->where($where)->find();
                break;
            case 'store':
                $now_order = M('Store_order')->where($where)->find();
                break;
            default:
                $this->error('无效请求');
                break;
        }

        if(!$now_order) {
            $this->returnCode('20046009');
        }

        $mer_id = $now_order['mer_id'];
        $lottery   = M('Lottery_shop')->where(array('mer_id' => $mer_id, 'status' => 1))->find();
        $lottery['content'] = unserialize($lottery['content']);
        $lottery['sys_content'] = unserialize($lottery['sys_content']);

        $prize_arr = array(
                '0' => array('id' => 0,'image_url'=>$lottery['sys_content'][0]['image_url'],'is_win'=>1, 'title'=>$lottery['sys_content'][0]['title'],'v' => $lottery['sys_content'][0]['probability'],'type'=>$lottery['sys_content'][0]['type'],'link'=>$lottery['sys_content'][0]['link'],'advertisement'=>$lottery['sys_content'][0]['advertisement']),
                '1' => array('id' => 1,'image_url'=>$lottery['content'][0]['image_url'],'is_win'=>$lottery['content'][0]['is_win'], 'title'=>$lottery['content'][0]['title'],'v' => $lottery['content'][0]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][0]['link'],'advertisement'=>$lottery['content'][0]['advertisement']),
                '2' => array('id' => 2,'image_url'=>$lottery['content'][1]['image_url'],'is_win'=>$lottery['content'][1]['is_win'], 'title'=>$lottery['content'][1]['title'],'v' => $lottery['content'][1]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][1]['link'],'advertisement'=>$lottery['content'][1]['advertisement']),
                '3' => array('id' => 3,'image_url'=>$lottery['sys_content'][1]['image_url'],'is_win'=>1, 'title'=>$lottery['sys_content'][1]['title'],'v' => $lottery['sys_content'][1]['probability'],'type'=>$lottery['sys_content'][0]['type'],'link'=>$lottery['sys_content'][1]['link'],'advertisement'=>$lottery['sys_content'][1]['advertisement']),
                '4' => array('id' => 4,'image_url'=>$lottery['content'][2]['image_url'],'is_win'=>$lottery['content'][2]['is_win'], 'title'=>$lottery['content'][2]['title'],'v' => $lottery['content'][2]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][2]['link'],'advertisement'=>$lottery['content'][2]['advertisement']),
                '5' => array('id' => 5,'image_url'=>$lottery['sys_content'][2]['image_url'],'is_win'=>1, 'title'=>$lottery['sys_content'][2]['title'],'v' => $lottery['sys_content'][2]['probability'],'type'=>$lottery['sys_content'][0]['type'],'link'=>$lottery['sys_content'][2]['link'],'advertisement'=>$lottery['sys_content'][2]['advertisement']),
                '6' => array('id' => 6,'image_url'=>$lottery['content'][3]['image_url'],'is_win'=>$lottery['content'][3]['is_win'], 'title'=>$lottery['content'][3]['title'],'v' => $lottery['content'][3]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][3]['link'],'advertisement'=>$lottery['content'][3]['advertisement']),
                '7' => array('id' => 7,'image_url'=>$lottery['content'][4]['image_url'],'is_win'=>$lottery['content'][4]['is_win'], 'title'=>$lottery['content'][4]['title'],'v' => $lottery['content'][4]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][4]['link'],'advertisement'=>$lottery['content'][4]['advertisement']),
                '8' => array('id' => 8,'image_url'=>$lottery['sys_content'][3]['image_url'],'is_win'=>1, 'title'=>$lottery['sys_content'][3]['title'],'v' => $lottery['sys_content'][3]['probability'],'type'=>$lottery['sys_content'][0]['type'],'link'=>$lottery['sys_content'][3]['link'],'advertisement'=>$lottery['sys_content'][3]['advertisement']),
                '9' => array('id' => 9,'image_url'=>$lottery['content'][5]['image_url'],'is_win'=>$lottery['content'][5]['is_win'], 'title'=>$lottery['content'][5]['title'],'v' => $lottery['content'][5]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][5]['link'],'advertisement'=>$lottery['content'][5]['advertisement']),
        );

        foreach ($prize_arr as $key => $val) {
            $arr[$val['id']] = $val['v'];
        }

        $rid = $this->get_rand($arr); //根据概率获取奖项id

        if(!$res = M('Lottery_shop_list')->where(array('uid'=>$this->user_session['uid'],'order_id'=>$order_id))->find()){
            $data['lottery_id'] = $rid;
            $data['mer_id'] = $mer_id;
            $data['uid'] = $this->_uid;
            $data['order_id'] = $order_id;
            $data['add_time'] = $_SERVER['REQUEST_TIME'];
            $data['status'] = 0;
            $data['is_win'] = $prize_arr[$rid]['is_win'];
            M('Lottery_shop_list')->add($data);
            fdump_sql(M('Lottery_shop_list')->_sql(),'lottery_shop_list_appapi_lottery_shop');
        }else{
            if($res['status']==1){
                $this->error_tips('您已经抽过奖了，请在获奖记录里查看',U('lottery_shop_list'));
            }
            $rid = $res['lottery_id'];
        }

        $lottery['lottery_rule'] = str_replace(PHP_EOL,'<br>',$lottery['lottery_rule']);
        $arr['rid'] = $rid;
        $arr['lottery'] = $lottery;
        $arr['url'] = $this->config['site_url'].'/wap.php?g=Wap&c=My&a=lottery_shop&type=shop&order_id='.$order_id;
        $this->returnCode(0, $arr);
    }

    public function get_rand($proArr) {
        $result = '';

        //概率数组的总概率精度
        $proSum = array_sum($proArr);

        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);

        return $result;
    }

    //快店订单
    public function shop_feedback(){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}

        $now_order = D('Shop_order')->get_order_detail(array('uid' => $this->_uid, 'order_id' => $_POST['order_id']));
        if (empty($now_order)) {
            $this->returnCode('20130006');
        }
        if (empty($now_order['paid'])) {
            $this->returnCode('20046032');
        }
        if ($now_order['status'] < 2) {
            $this->returnCode('20046033');
        }
        if ($now_order['status'] == 3) {
            $this->returnCode('20046034');
        }

        if (isset($now_order['info'])) {
            $list = array();
            $goods_ids = array();
            foreach ($now_order['info'] as $row) {
                if (!in_array($row['goods_id'], $goods_ids)) {
                    $goods_ids[] = $row['goods_id'];
                    $list[] = $row;
                }
            }
            $now_order['info'] = $list;
        }
        if($list){
            $this->returnCode(0,$list);
        }else{
            $this->returnCode('20046035');
        }

    }

    //添加快店评论
    public function add_comment(){
		if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}

        $goods_ids = isset($_POST['goods_ids']) ? $_POST['goods_ids'] : 0;
        $score = isset($_POST['whole']) ? $_POST['whole'] : 5;
        $comment = isset($_POST['textAre']) ? htmlspecialchars($_POST['textAre']) : 0;
        
        $dscore = isset($_POST['score']) ? $_POST['score'] : 5;
        $dcomment = isset($_POST['comment']) ? htmlspecialchars(trim($_POST['comment'])) : '';

        $now_order = D('Shop_order')->get_order_detail(array('uid' => $this->_uid, 'order_id' => $_POST['order_id']));

        if (empty($now_order)) {
            $this->returnCode('20130006');
        }
        if (empty($now_order['paid'])) {
            $this->returnCode('20046032');
        }
        if ($now_order['status'] < 2) {
            $this->returnCode('20046033');
        }
        if ($now_order['status'] == 3) {
            $this->returnCode('20046034');
        }


        $goodsids = array();

        $goods = '';
        $pre = '';
        if (isset($now_order['info'])) {
            foreach ($now_order['info'] as $row) {
                if (!in_array($row['goods_id'], $goodsids)) {
                    $goodsids[] = $row['goods_id'];
                    if (in_array($row['goods_id'], $goods_ids)) {
                        $goods .= $pre . $row['name'];
                        $pre = '#@#';
                    }
                }
            }
        }
        $database_reply = D('Reply');
        $data_reply['parent_id'] = $now_order['store_id'];
        $data_reply['store_id'] = $now_order['store_id'];
        $data_reply['mer_id'] = $now_order['mer_id'];
        $data_reply['score'] = $score;
        $data_reply['order_type'] = 3;
        $data_reply['order_id'] = intval($now_order['order_id']);
        $data_reply['anonymous'] = 1;
        $data_reply['comment'] = $comment;
        $data_reply['uid'] = $this->_uid;
        $data_reply['pic'] = '';
        $data_reply['add_time'] = $_SERVER['REQUEST_TIME'];
        $data_reply['add_ip'] = get_client_ip(0);
        $data_reply['goods'] = $goods;
        $data_reply['deliver_score'] = $dscore;

        if ($database_reply->data($data_reply)->add()) {
            D('Merchant_store')->setInc_shop_reply($now_order['store_id'], $score);
            D('Shop_order')->change_status($now_order['order_id'], 3);
            D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 8));
            foreach ($goods_ids as $goods_id) {
                if (in_array($goods_id, $goodsids)) {
                    D('Shop_goods')->where(array('goods_id' => $goods_id))->setInc('reply_count', 1);
                    D('Shop_order_detail')->where(array('goods_id' => $goods_id, 'order_id' => $now_order['order_id']))->save(array('is_goods' => 1));
                }
            }
            if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $now_order['order_id'], 'item' => 2))->find()) {
                D('Deliver_supply')->where(array('order_id' => $now_order['order_id'], 'item' => 2))->save(array('score' => $dscore, 'status' => 6, 'comment_time' => time(), 'comment' => $dcomment));
                if ($user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find()) {
                    $userData = array();
                    $userData['reply_count'] = $user['reply_count'] + 1;
                    $userData['total_score'] = $user['total_score'] + $dscore;
                    $userData['average_score'] = round($userData['total_score'] / $userData['reply_count'], 2);
                    D('Deliver_user')->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->save($userData);
                }
            }
            
            $this->returnCode(0);
        }else{
            $this->returnCode('10100101');
        }
    }

    //优惠买单展示
    public function pay(){
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $mer_id = isset($_POST['mer_id']) ? intval($_POST['mer_id']) : 0;
		if($store_id){
			$now_store = D('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();
            if($this->config['store_ticket_have'] && $now_store['bind_store_trade'] == 'ticket'){
                $this->returnCode(0,array('url'=>$this->config['site_url'].'/wap.php?c=My&a=pay&go_to=web&store_id='.$store_id));
            }
			if(empty($now_store)){
				$this->returnCode('20046001');
			}
			$now_store['discount_txt'] = unserialize($now_store['discount_txt']);
			$arr['store_id']= $now_store['store_id'];
			$arr['store_name']= $now_store['name'];
			$arr['discount_type']= isset($now_store['discount_txt']['discount_type']) ? $now_store['discount_txt']['discount_type'] : 0;
			$arr['discount_percent']= isset($now_store['discount_txt']['discount_percent']) ? $now_store['discount_txt']['discount_percent'] : 0;
			$arr['condition_price']=isset($now_store['discount_txt']['condition_price']) ? $now_store['discount_txt']['condition_price'] : 0;
			$arr['minus_price']= isset($now_store['discount_txt']['minus_price']) ? $now_store['discount_txt']['minus_price'] : 0;
		}else{
			$store_list = D('Merchant_store')->get_store_list_by_merId($mer_id);
			if(empty($store_list)){
				$this->returnCode('1001',array(),'商家暂未创建/开启店铺');
			}
			if(count($store_list) == 1){
				$_POST['store_id'] = $store_list[0]['store_id'];
				$_POST['mer_id'] = 0;
				$this->pay();
				die;
			}
			if($_POST['lng'] && $_POST['lat']){
				$user_long_lat['lat'] = $_POST['lat'];
				$user_long_lat['long'] = $_POST['lng'];
			}
			foreach ($store_list as &$vo) {
				if($user_long_lat){
					$vo['Srange'] = getDistance($user_long_lat['lat'], $user_long_lat['long'], $vo['lat'], $vo['long']);
					$vo['range'] = getRange($vo['Srange'], false);
				}
				$vo['discount_txt'] = unserialize($vo['discount_txt']);
				$vo['discount_type']= isset($vo['discount_txt']['discount_type']) ? $vo['discount_txt']['discount_type'] : 0;
				$vo['discount_percent']= isset($vo['discount_txt']['discount_percent']) ? $vo['discount_txt']['discount_percent'] : 0;
				$vo['condition_price']=isset($vo['discount_txt']['condition_price']) ? $vo['discount_txt']['condition_price'] : 0;
				$vo['minus_price']= isset($vo['discount_txt']['minus_price']) ? $vo['discount_txt']['minus_price'] : 0;
				$rangeSort[] = array('store_id'=>$vo['store_id'],'juli'=>$vo['Srange']);
			}
			$arr['store_list'] = sortArrayAsc($store_list,'Srange');
		}
		
		if($this->config['open_score_get_percent']){
			$arr['score_precent'] = $this->config['score_get_percent']*1;
		}else{
			$arr['score_precent'] = $this->config['user_score_get']*100;
		}
		
		$arr['pay_notice'] = L_('1.X1仅限到店消费后使用，请勿提前支付',$this->config['cash_alias_name']).PHP_EOL;
		$arr['pay_notice'].= L_('2.请在付款前与商家确认门店信息和消费金额').PHP_EOL;
		$arr['pay_notice'].= L_('3.遇节假日能否享受优惠，请详询店家').PHP_EOL;
		$arr['pay_notice'].= L_('4.请与商家确认到店付能否与店内其他优惠同享').PHP_EOL;
		
		$arr['score_notice'] = L_('1.平台积分仅在实付金额中产生，使用优惠券、商家余额等不获得积分');
			
        $this->returnCode(0,$arr);
    }


    //优惠买单订单
    public function store_order(){
		if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}
		
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $total_money = isset($_POST['total_money']) ? (intval($_POST['total_money'] * 100) / 100) : 0;
        $no_discount_money = isset($_POST['no_discount_money']) ? (intval($_POST['no_discount_money'] * 100) / 100) : 0;

//        $total_price = isset($_POST['total_price']) ? (intval($_POST['total_price'] * 100) / 100) : 0;
//        $minus_price = isset($_POST['minus_price']) ? (intval($_POST['minus_price'] * 100) / 100) : 0;
//        $price = isset($_POST['price']) ? (intval($_POST['price'] * 100) / 100) : 0;

        $now_store = D('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();
        if(empty($now_store)){
            $this->returnCode('20046001');
        }
        if ($total_money <= 0)  $this->returnCode('10100302');;
        $minus_price_true = $price_true = 0;
        $now_store['discount_txt'] = unserialize($now_store['discount_txt']);

        if (isset($now_store['discount_txt']['discount_type'])) {
            if ($now_store['discount_txt']['discount_type'] == 1) {
                if (isset($now_store['discount_txt']['discount_percent']) && $now_store['discount_txt']['discount_percent'] > 0) {
                    $price_true = ($total_money - $no_discount_money) * $now_store['discount_txt']['discount_percent'] / 10 + $no_discount_money;
                    $minus_price_true = $total_money - $price_true;

                }
            } elseif ($now_store['discount_txt']['discount_type'] == 2) {
                if (isset($now_store['discount_txt']['condition_price']) && $now_store['discount_txt']['condition_price'] > 0 && isset($now_store['discount_txt']['minus_price']) && $now_store['discount_txt']['minus_price']) {
                    $minus_price_true = floor(($total_money - $no_discount_money) / $now_store['discount_txt']['condition_price']) * $now_store['discount_txt']['minus_price'];
                    $price_true = $total_money - $minus_price_true;

                }
            }
        }

        if ($minus_price_true == 0 && $price_true == 0) {
            $minus_price_true = 0;
            $price_true = $total_money;
        }

        $data = array('store_id' => $now_store['store_id']);
        $data['mer_id'] = $now_store['mer_id'];
        $data['uid'] = $this->_uid;
        $data['orderid'] = date("YmdHis") . mt_rand(10000000, 99999999);
        $data['name'] = '顾客现场自助支付-' . $now_store['name'];
        $data['total_price'] = $total_money;
        $data['discount_price'] = $minus_price_true;
        $data['price'] = $price_true;
        $data['dateline'] = time();
        $data['from_plat'] = 1;
        $order_id = D("Store_order")->add($data);
        if ($order_id) {
            $data['business_type'] = 'store';
            $data['paid'] = 0;
			D('Store_order')->after_store_change($order_id,'store',1,$data);
            $this->returnCode(0,array('order_type'=>'store','order_id'=>(string)$order_id));
        } else {
            $this->returnCode('20046014');
        }
    }

    //优惠买单列表
    public function store_order_list(){
		$order_id =   $_POST['order_id'];
        $where['uid'] = $this->_uid;
        $where['paid'] = 1;
        if($order_id){
            $where['order_id'] = array('lt',$order_id);
        }
		if($_POST['mer_id']){
			$where['mer_id'] = $_POST['mer_id'];
		}
        $order_list = D("Store_order")->field('order_id,store_id,mer_id,name,total_price,pay_time,from_plat,discount_price,price')->where($where)->page('1,10')->order('order_id DESC')->select();

        $temp = $store_ids = array();
        foreach ($order_list as &$st) {
            $store_ids[] = $st['store_id'];
        }
        $m = array();
        if ($store_ids) {
            $store_image_class = new store_image();
            $merchant_list = D("Merchant_store")->field('store_id,name,mer_id,discount_txt,pic_info')->where(array('store_id' => array('in', $store_ids)))->select();
            foreach ($merchant_list as $li) {
                $images = $store_image_class->get_allImage_by_path($li['pic_info']);
                $li['image'] = $images ? array_shift($images) : array();

                $tmp = unserialize($li['discount_txt']);
                if ($tmp['discount_type'] == 1) {
                    $li['discount_str'] = L_( "X1折优惠",$tmp['discount_percent']);
                } else if ($tmp['discount_type'] == 2) {
                    $li['discount_str'] = L_('满X1 减X2',array('X1'=>$tmp['condition_price'],'X2'=>$tmp['minus_price'])) ;
                }
                unset($li['status']);
                unset($li['discount_txt']);

                $m[$li['store_id']] = $li;
            }
        }
        $list = array();
        foreach ($order_list as $ol) {
            $ol['pay_time'] = date('Y-m-d H:i',$ol['pay_time']);
            if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
                $list[] = array_merge($ol, $m[$ol['store_id']]);
            } else {
                $list[] = $ol;
            }
        }
        $last_id  = 0;
        if(!empty($list)){
            $last_arr = array_slice($list,-1,1);
            $last_id = $last_arr[0]['order_id'];
        }
        $this->returnCode(0,array('list'=>$list,'order_id'=>$last_id));
    }


    public function group_order_list(){
        $status = $_POST['status'];
        $last_order_id = $_POST['last_order_id'];

        if(empty($this->_uid)){
            $this->returnCode('20046009');
        }
        $order_list = D('Group')->wap_get_order_list($this->_uid,intval($status),$last_order_id,1,intval($_POST['mer_id']));
		
		if($_POST['Device-Id'] == 'wxapp'){
			$order_list= array();
			$this->returnCode(2001,array(),L_('请至微信公众号 “X1” 管理X2订单',array('X1'=>$this->config['wechat_name'],'X2'=>$this->config['group_alias_name'])));
		}
		
        foreach ($order_list as &$v) {
            if($v['pay_time']>0){
                $v['pay_time'] = date('Y-m-d H:i',$v['pay_time']);
            }
        }
		$last_id = 0;
        if(!empty($order_list)){
            $last_arr = array_slice($order_list,-1,1);
            $last_id = $last_arr[0]['order_id'];
        }
        $this->returnCode(0,array('list'=>$order_list,'order_id'=>$last_id));
    }

    public function group_order_detail(){

        $order_id    =  $_POST['order_id'];
        if(empty($this->_uid)){
            $this->returnCode('20046009');
        }

        $now_order = D('Group_order')->get_order_detail_by_id($this->_uid,$order_id,true);
        $now_order['order_type'] = 'group';
        $laste_order_info=D('Tmp_orderid')->get_laste_order_info($now_order['order_type'],$now_order['order_id']);
        if(!$now_order['paid'] && !empty($laste_order_info)) {
            if ($laste_order_info['pay_type']=='weixin') {
                $redirctUrl = C('config.site_url') . '/wap.php?g=Wap&c=Pay&a=weixin_back&order_type='.$now_order['order_type'].'&order_id=' . $laste_order_info['orderid'];
                file_get_contents($redirctUrl);
                $now_order = D('Group_order')->get_order_detail_by_id($this->_uid, intval($_GET['order_id']), true);
            }
        }
        $now_group = D('Group')->where(array('group_id'=>$now_order['group_id']))->find();

        $database_merchant = D('Merchant');
        $now_merchant = $database_merchant->get_info($now_group['mer_id']);
        $now_group['merchant_name'] = $now_merchant['name'];

        if(empty($now_order)){
            $this->returnCode('20130006');
        }
        if(empty($now_order['paid'])){
            $now_order['status_txt'] = L_('未付款');
        }else if(empty($now_order['third_id']) && $now_order['pay_type'] == 'offline'){
            $now_order['status_txt'] = L_('线下未付款');
        }else if(empty($now_order['status'])){
            if($now_order['tuan_type'] != 2){
                $now_order['status_txt'] = L_('未消费');
            }else{
                $now_order['status_txt'] = L_('未发货');
            }
        }else if($now_order['status'] == '1'){
            $now_order['status_txt'] = L_('待评价');
        }else if($now_order['status'] == '2'){
            $now_order['status_txt'] = L_('已完成');
        }else if($now_order['status'] == '3'){
            $now_order['status_txt'] = L_('已退款');
            $now_order['group_pass_txt'] = L_('退款订单无法查看');
        }

        $uid = $this->_uid;
        $group_share_num = D('Group_share_relation')->get_share_num($uid,$now_order['order_id']);
        $group_image_class = new group_image();
        $pic = explode(';',$now_group['pic']);
        $now_group['pic'] = $group_image_class->get_image_by_path($pic[0],'s');


        if($now_group['pin_num']>0 && $now_order['single_buy']==0 && $now_order['status']<3 && $now_order['paid']){
            $my_group_join = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            if(empty($my_group_join)&&$now_order['paid']==1){
                $this->returnCode('20020014');
            }
            $buyer = D('Group_start')->get_buyerer_by_order_id($now_order['order_id']);
            $end_time = $my_group_join['start_time'] + $now_group['pin_effective_time'] * 3600;
            $effective_time = $end_time - $_SERVER['REQUEST_TIME'];
            $efftime['h'] = floor($effective_time / 3600);
            $efftime['m'] = floor(($effective_time - $efftime['h'] * 3600) / 60);
            $efftime['s'] = $effective_time - $efftime['h'] * 3600 - $efftime['m'] * 60;

            if ($effective_time > 0) {
                $now_order['effective_time']= $efftime;
            }
            $end_time = $my_group_join['start_time']+$now_group['pin_effective_time']*3600;
            $effective_time= $end_time-$_SERVER['REQUEST_TIME'];
            if($effective_time<=0&&$my_group_join['status']==0){
                D('Group_start')->timeout($now_order['order_id']);
                $my_group_join['status'] = 2;
            }
            $arr['effective_time']=$effective_time<0?array():$efftime;
            $arr['buyer']=$buyer;
            $my_group_join['start_time'] = date('Y-m-d H:i',$my_group_join['start_time']);
            //$my_group_join['last_time'] = date('Y-m-d H:i',$my_group_join['last_time']);
            $my_group_join['end_time'] = date('Y-m-d H:i',$end_time);

            $arr['my_group_join']=$my_group_join;
        }else {
            if ($now_group['group_share_num'] == 0 && $now_group['open_now_num'] == 0 && $now_group['open_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['group_share_num'] != 0 && $now_group['group_share_num'] <= $group_share_num) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['open_now_num'] <= $now_group['sale_count'] && $now_group['open_now_num'] != 0 && $now_group['group_share_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['open_num'] <= $now_group['sale_count'] && $now_group['open_num'] != 0 && $now_group['open_now_num'] == 0 && $now_group['group_share_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            }
            if ($now_group['group_share_num'] > 0) {
                $share_user = D('Group_share_relation')->get_share_user($this->_uid, $now_order['order_id']);
                $arr['share_user']= $share_user;
            }
        }

        if($now_order['pass_array']){
            $pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
            $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
            $unconsume_pass_num = $now_order['num']-$consume_num;
            $now_order['pass_array']=$pass_array;
        }else{
            $now_order['pass_array']=array();
        }
        if($now_order['status']==6){
            if($now_order['num']!=$unconsume_pass_num){
                $refund_money = $now_order['refund_money'];
            }else{
                $refund_money = $now_order['merchant_balance']+$now_order['balance_pay']+$now_order['payment_money'];
            }
            $now_order['refund_total'] = $refund_money;
        }else{
            $now_order['refund_total'] = $now_order['balance_pay']+$now_order['payment_money']+$now_order['merchant_balance'];
        }

        if($now_order['trade_info']){
            $trade_info_arr = unserialize($now_order['trade_info']);
            if($trade_info_arr['type'] == 'hotel'){
                $trade_hotel_info = D('Trade_hotel_category')->format_order_trade_info($now_order['trade_info']);
                $has_refund = $trade_hotel_info['has_refund'];
                $trade_refund = true;
                if($has_refund==1){
                    $trade_refund = false;
                }elseif($has_refund==2&&$now_order['add_time']+3600*$trade_hotel_info['refund_hour']>time()){
                    $trade_refund = false;
                }
                $trade_hotel_info['refund'] = $trade_refund;
                $arr['trade_hotel_info']=$trade_hotel_info;
            }
        }
        $now_order['pay_time'] = date('Y-m-d H:i',$now_order['pay_time']);
        $now_order['add_time'] = date('Y-m-d H:i',$now_order['add_time']);
        $arr['now_order']=$now_order;
        $arr['now_group']=array(
            'merchant_name'=>$now_group['merchant_name'],
            'group_refund_fee'=>$now_group['group_refund_fee'],
            'pin_num'=>$now_group['pin_num'],
            's_name'=>$now_group['s_name'],
            'pic'=>$now_group['pic'],
        );

        $this->returnCode(0,$arr);
    }
	public function personal(){
		if(empty($this->_uid)){
            $this->returnCode('20046009');
        }
        /*
        else{
                $store['age_tips_status'] = 2;
                $store['age_tips'] = '澳洲法律规定'.$row['age_limit_buy'].'岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满18周岁。';    
                $store['age_tips_title'] = L_('您已达到购买烟酒产品法定年龄');
            }

         */
		$data_user['truename']  = $_POST['truename'];
		$data_user['birthday']  = $_POST['birthday'];
		$data_user['last_time'] = time();
		$condition_user['uid'] = $this->_uid;
		if(M('User')->where($condition_user)->data($data_user)->save()){
			$this->returnCode(0,$data_user);
		}else{
			$this->returnCode(1001,array(),L_('修改失败，请重试'));
		}
	}

    //修改生日
    public function birthday(){
        if(empty($this->_uid)){
            $this->returnCode('20046009');
        }
        // if (empty($_POST['store_id'])) {
        //     $this->returnCodeError('请携带 store_id 参数');
        // }
        $return = array();
        $today_time = time();
        $birthday_time = strtotime($_POST['birthday']);
        if($_POST['birthday'] == '0000-00-00' || $birthday_time == 0 || $today_time <= $birthday_time){//数据不合法
            $this->returnCode(1001,array(),L_('数据不合法'));
        }

        $thisUser = M('User')->where(['uid' => $this->_uid])->find();
        if (empty($thisUser)) {
            $this->returnCode(1001, array(), L_('当前用户不存在'));
        }
        //如果用户已设置生日，检查平台是否允许修改生日
        if (C('config.open_user_update_birthday') != 1 && $thisUser['birthday'] != '0000-00-00') {
            $this->returnCode(1001, array(), L_('当前用户已经设置生日，禁止修改'));
        }

        $data_user['birthday']  = $_POST['birthday'];
        $condition_user['uid'] = $this->_uid;
        if(M('User')->where($condition_user)->data($data_user)->save()){
            $store['age_tips_status'] = 0;//0=正常 1=重新输入生日 2=未达到法定年龄
            $store['age_tips'] = '';
            $store['age_tips_title'] = '';
            $store['age'] = 0;
            if($_POST['birthday'] == '0000-00-00' || $birthday_time <= 0 || $today_time <= $birthday_time){
                $store['age'] = -1;
            }
            else{
                $store['age'] = floor((($today_time - $birthday_time)/(3600*24*365))*100)/100;
            }
            if(!empty($_POST['store_id'])){
                $where = array('store_id' => $_POST['store_id']);
                $row = D('Merchant_store')->field(true)->where($where)->find(); 
                //判断用户生日
                if($row['age_limit_buy_switch'] == 1 && $row['age_limit_buy'] > 0){ // 1=开启验证
                    if(strtotime('+'.intval($row['age_limit_buy']).' years',$birthday_time) > $today_time){
                        $store['age_tips_status'] = 2;
                        $store['age_tips'] = L_('澳洲法律规定X1岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满X1周岁。',$row['age_limit_buy']); 
                        $store['age_tips_title'] = L_('您未达到购买烟酒产品法定年龄');
                    }
                    else{
                        $store['age_tips'] = L_('澳洲法律规定X1岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满X1周岁。',$row['age_limit_buy']); 
                        $store['age_tips_title'] = L_('您已达到购买烟酒产品法定年龄');
                    }           
                }  
            }
            $this->returnCode(0,$store);
        }else{
            $this->returnCode(1001,array(),L_('修改失败，请重试'));
        }
    }
	
	//修改邮箱
	public function email(){
		if(empty($this->_uid)){
            $this->returnCode('20046009');
        }
		if(empty($_POST['email'])){
            $this->returnCodeError(L_('邮箱未填写'));
        }
		$save_result = D('User')->save_user($this->_uid,'email',$_POST['email']);
		if($save_result['error']){
			$this->returnCodeError($save_result['msg']);
		}else{
			$this->returnCodeOk(L_('修改成功'));
		}
	}

	public function store_order_detail(){
		$order = D('Store_order')->get_order_by_id($this->_uid,$_POST['order_id']);
		if(empty($order)){
			$this->returnCode(1001,array(),L_('订单不存在'));
		}
		M('Store_order')->where(array('order_id'=>$order['order_id']))->setInc('show_lottery_first',1);
		$order['pay_type_str']  = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid']);
		$store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
		$store_image_class = new store_image();
		$images = $store_image_class->get_allImage_by_path($store['pic_info']);
		$store['image'] = isset($images[0]) ? $images[0] : '';
		
		$order['pay_time_txt'] = date('Y-m-d H:i:s',$order['pay_time']);
		$order['price'] = getFormatNumber($order['price']);
		$order['total_price'] = getFormatNumber($order['total_price']);
		$order['discount_price'] = getFormatNumber($order['discount_price']);
		$order['card_price'] = getFormatNumber($order['card_price']);
		$order['coupon_price'] = getFormatNumber($order['coupon_price']);
		$order['score_deducte'] = getFormatNumber($order['score_deducte']);
		$order['score_used_count'] = getFormatNumber($order['score_used_count']);
		$order['card_give_money'] = getFormatNumber($order['card_give_money']);
		$order['merchant_balance'] = getFormatNumber($order['merchant_balance']);
		$order['balance_pay'] = getFormatNumber($order['balance_pay']);
		$order['payment_money'] = getFormatNumber($order['payment_money']);
		if($order['card_discount'] == 0){
			$order['card_discount'] = 10;
		}else{
			$order['merchant_discount'] = getFormatNumber($order['price']-($order['price']-$order['no_discount_money'])*$order['card_discount']/10-$order['no_discount_money']);
		}
		
		$return['store'] = $store;
		$return['order'] = $order;
		$this->returnCode(0,$return);
	}


    public  function sign(){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
		}else{
			$this->returnCode('20046009');
		}
        $return = D('User')->sign_in($this->_uid);
        if($return['error_code']){
            $this->returnCode('10052017','',$return['msg']);
        }else{
            $this->returnCode(0,['msg'=>$return['msg']]);
        }
    }
    
    public function share_type(){
        $ticket = I('ticket', false);
        $device_id    =   I('Device-Id',false);
        if($ticket && $device_id){
            $info = ticket::get($ticket, $device_id, true);
            $now_user = D('User')->get_user($info['uid']);
        }else{
            $this->returnCode('20046009');
        }
        $type = $_POST['type'];
        
        $order = M(ucfirst($type).'_order')->where(array('order_id'=>$_POST['order_id']))->find();
        if($this->config['share_coupon'] && $this->config['open_share_lottery']==0){
            $arr['type'] = 'share_coupon';
            $arr['url'] = $this->config['site_url'].'/wap.php?c=Share_lottery&a=share_coupon&type='.$type.'&order_id='.$_POST['order_id'];
        }else if($this->config['open_share_lottery']){
            $arr['type'] = 'share_lottery';
            if($type=='shop'){
                $arr['url'] = $this->config['site_url'].'/wap.php?c=Shop&a=index&shop-id='.$order['store_id'];
            }else{
                $arr['url'] = $this->config['site_url'].'/wap.php?c=My&a=pay&store_id='.$order['store_id'];
            }
            
        }
        $this->returnCode(0,$arr);
    }
    
    /**
     * 获取话费充值列表
     * 4:充值成功，3:等待充值，0:未扣款，12:充值失败
     */
    public function get_mobile_recharge_list()
    {
        $ticket = I('ticket', false);
        $device_id = I('Device-Id', false);
        if ($ticket && $device_id) {
            $info = ticket::get($ticket, $device_id, true);
            $now_user = D('User')->get_user($info['uid']);
        } else {
            $this->returnCode('20046009');
        }
        $status = I('status', -1, 'intval');
        if ($status == 12) {
            $where['status'] = array('in', array(5, 6, 12));
        } elseif ($status >= 0) {
            $where['status'] = $status;
        }
 
        $where['uid'] = $info['uid'];
        $where['paid'] = 1;
        $order_list = D("Mobile_recharge_order")->field(true)->where($where)->order('order_id DESC')->select();
        foreach($order_list as $key=>$val){
            $order_list[$key]['order_url'] = $this->config['site_url'] . '/wap.php?c=Third_recharge&a=mobile_recharge_detial&order_id=' . $val['order_id'];
        }
        $this->returnCode(0, $order_list);
    }

    // 收藏店铺 商品
    public function do_collect(){
        $data['type'] = $_POST['type']; // 收藏类型
        if(!$this->_uid){
            $this->returnCode('20046009');
        }
        $data['uid'] = $this->_uid; // 用户id
        switch ($_POST['type']) {
            case 'foodshop':
            case 'store':
            case 'shop':
            case 'mall':
                $data['store_id'] = $_POST['collect_id'];
                break;
            case 'mallgoods':
                $data['goods_id'] = $_POST['collect_id'];
                break;
            case 'group':
                $data['group_id'] = $_POST['collect_id'];
                break;
            case 'appoint':
                $data['appoint_id'] = $_POST['collect_id'];
                break;
            case 'mer':
                $data['mer_id'] = $_POST['collect_id'];
                break;
            default:
                $this->returnCode(1,'',L_('参数收藏类型错误'));
                break;
        }
        $info = D('Merchant_user_relation')->where($data)->find();

        // 取消收藏
        if($info){
            if(D('Merchant_user_relation')->where($data)->delete()){
                $this->returnCode(0,L_('取消收藏成功'));
            }else{
                $this->returnCode(1,'',L_('取消收藏失败！请重试'));
            }
        }

        // 收藏
        $data['dateline'] = time();
        $res = D('Merchant_user_relation')->data($data)->add();
        if($res){
            $this->returnCode(0,L_('收藏成功'));
        }else{
            $this->returnCode(1,'',L_('收藏失败！请重试'));
        }
    }

    //【ID1001800】定制---用户前台的个人中心增加一个所有业务的订单汇总列表
    public function system_order_status(){
        $store_id = intval($_POST['store_id']) ? : 0;
        $mer_id = intval($_POST['mer_id']) ? : 0;
        $where_system_order = [
            'uid' => $this->_uid, 
            'is_del'=>0,
            'type' => ['in', ['mall', 'shop', 'meal', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3','lifetools', 'lifetoolscard', 'life_tools_appoint_join']]
        ];

        if($store_id > 0){
            $where_system_order['store_id'] = $store_id;
        }
        if($mer_id > 0){
            $where_system_order['mer_id'] = $mer_id;
        }

        if ($this->appType == 3) {
            //跑腿
            $where_system_order['type'] = 'service';
        }

        $activity_arr['system_order_status']['list'][] = [
            'status' => 0, //全部
            'text' => L_('全部订单'),
            'icon' => '',
            'nums' => -1,//不显示
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=0'
        ];
        $nopayList = M("System_order")->where(array_merge($where_system_order, ['is_del' => 0, 'paid' => 0, 'system_status' => 0]))->select();
        if (!empty($nopayList)) {//先删除无效订单
            foreach ($nopayList as $nv) {
                if ($nv['type'] == 'mall3') {
                    $order_info1 = M('mall_order')->where(['order_id' => $nv['order_id']])->find();
                    if(empty($order_info1)){//屏蔽掉无效订单
                        M("System_order")->where(['id' => $nv['id']])->setField('is_del', 1);
                    }
                }
            }
        }
        $activity_arr['system_order_status']['list'][] = [
            'status' => 1, 
            'text' => L_('待付款'),
            'icon' => C("config.site_url").'/static/status/1.png',
            'nums' => M("System_order")->where(array_merge($where_system_order, ['is_del' => 0, 'paid' => 0, 'system_status' => 0]))->count(),
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=1'
        ];
        $activity_arr['system_order_status']['list'][] = [
            'status' => 2, 
            'text' => L_('待发货'),
            'icon' => C("config.site_url").'/static/status/2.png',
            'nums' => M("System_order")->where(array_merge($where_system_order, ['paid'=>1,'system_status'=>0, 'type'=>['in', ['mall', 'shop', 'group', 'appoint', 'mobile_recharge','gift','village_group', 'mall3','lifetools', 'lifetoolscard', 'life_tools_appoint_join','service']]]))->count(),            
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=2'
        ];
        $activity_arr['system_order_status']['list'][] = [
            'status' => 3, 
            'text' => L_('待收货'),
            'icon' => C("config.site_url").'/static/status/3.png',
            'nums' => M("System_order")->where(array_merge($where_system_order, ['system_status'=>1]))->count(),
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=3'
        ];
        $activity_arr['system_order_status']['list'][] = [
            'status' => 4, 
            'text' => L_('待评价'),
            'icon' => C("config.site_url").'/static/status/4.png',
            'nums' => M("System_order")->where(array_merge($where_system_order, ['system_status'=>2, 'type'=>['in', ['mall', 'shop', 'group', 'appoint', 'mobile_recharge','gift','village_group', 'mall3', 'lifetools', 'lifetoolscard', 'life_tools_appoint_join']]]))->count(),
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=4'
        ];
        $activity_arr['system_order_status']['list'][] = [
            'status' => 5, 
            'text' => L_('退款/售后'),
            'icon' => C("config.site_url").'/static/status/5.png',
            'nums' => -1,//不显示
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=5'
        ];

        $activity_arr['system_order_status']['other'][] = [
            'text' => C('config.meal_alias_name').L_('订单'),
            'icon' => C("config.site_url").'/static/images/order.png',
            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/foodshop/order/orderList'
        ];
		
		//老版本仅支持列表 2021/03/15之前
		if($_POST['app_version'] < 30200 && $this->DEVICE_ID != 'wxapp'){
			$activity_arr['system_order_status'] = $activity_arr['system_order_status']['list'];
		}

        if($_POST['app_type'] == 'dyapp'){
			$activity_arr['system_order_status']['other'] = [];
			$kf = M('Merchant_store_kefu')->where(['store_id' => 0])->find();
			$activity_arr['system_order_status']['other'][] = [
				'text' => L_('联系客服'),
				'icon' => C("config.site_url").'/static/images/order.png',
				'url' => build_im_chat_url('user_' . $this->_uid, $kf['username'], 'user2plat')
			];
		}
		
        $this->returnCode(0,$activity_arr['system_order_status']);
    }
    
    // 收藏店铺 列表
    public function my_collect(){
        $data['type'] = $_POST['type']; // 收藏类型

        if(!$this->_uid){
            $this->returnCode('20046009');
        }

        $condition = [];
        $condition['uid'] = $this->_uid; // 用户id
        $condition['type'] = $_POST['type'];


        $return = D('Merchant_user_relation')->getCollectList($this->_uid,$_POST['type']);
        $this->returnCode(0,$return);

    }

    //【ID1003862】--个人中心的订单汇总---搜索订单与按业务筛选订单需求的接口
    public function get_type_list() {

        $type_list = [
            'mall' => $this->config['mall_alias_name'] ? : '商城',
            'shop' => $this->config['shop_alias_name'] ? : '快店',
            // 'meal' => $this->config['meal_alias_name'] ? : '餐饮',
            'group' => $this->config['group_alias_name'] ? : '团购',
            'appoint' => $this->config['appoint_alias_name'] ? : '预约',
            'service' => $this->config['service_alias_name'],
            'store' => $this->config['cash_alias_name'] ? : '到店付',
            'mobile_recharge' => '在线充值',
            'gift' => $this->config['gift_alias_name'] ? : '积分商城',
            'village_group' => '社区团购',
        ];

        if ($this->config['single_system_type'] == 'shop') {
            $type_list = [
                'shop' => $this->config['shop_alias_name'] ?: '快店'
            ];
        }
		
		if(!$this->config['service_alias_name']){
			unset($type_list['service']);
		}

        $type_lists = [];
        foreach($type_list as $key => $value) {
            $list['name'] = $key;
            $list['value'] = $value;

            $type_lists[] = $list;
        }

        $this->returnCode(0,$type_lists);
    }


    //【ID1001800】定制---用户前台的个人中心增加一个所有业务的订单汇总列表
    public function system_order_list(){
        $status = intval($_POST['status']); // 状态
        $page = intval($_POST['page']) ? : 1;
        $store_id = intval($_POST['store_id']) ? : 0;
        $mer_id = intval($_POST['mer_id']) ? : 0;
        $pagesize = 10;
        $orderType=isset($_POST['type'])?$_POST['type']:"";
        if(!$this->_uid){
            $this->returnCode('20046009');
        }
        $where = [
            'uid' => $this->_uid,
            'is_del' => 0,
            'type' => ['in', ['mall', 'shop', 'meal', 'group', 'appoint', 'service', 'store', 'mobile_recharge', 'gift', 'village_group', 'mall3', 'dining', 'lifetools', 'lifetoolscard', 'life_tools_appoint_join', 'robot']]
        ];
        $where_tools_order['s.uid'] =$this->_uid;
        $where_tools_order['s.is_del'] =0;
        if ($this->appType == 3) {
            //跑腿
            $where_system_order['type'] = 'service';
        }

        switch ($status) {
            case '1':
                $where_tools_order['s.paid'] =$where['paid'] = 0;
                $where_tools_order['s.system_status'] =$where['system_status'] = 0;
                break;
            case '2':
                $where_tools_order['s.paid'] =$where['paid'] = 1;
                $where_tools_order['s.system_status'] =$where['system_status'] = 0;
                $where['type'] = ['in',['mall', 'shop', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3', 'dining', 'lifetools', 'lifetoolscard','life_tools_appoint_join', 'robot']];
                break;
            case '3':
                $where_tools_order['s.system_status'] =$where['system_status'] = 1;
                $where['type'] = ['in',['mall', 'shop', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3', 'dining', 'lifetools', 'lifetoolscard','life_tools_appoint_join', 'robot']];
                break;
            case '4':
                $where_tools_order['s.system_status'] =$where['system_status'] = 2;
                $where['type'] = ['in', ['mall', 'shop', 'group', 'appoint', 'mobile_recharge','gift','village_group', 'mall3', 'dining', 'lifetools', 'lifetoolscard', 'life_tools_appoint_join', 'robot']];
                break;
            case '5':
                $where_tools_order['s.system_status'] =$where['system_status'] = ['in', [4, 5, 7]];
                $where['type'] = ['in',['mall', 'shop', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3', 'dining', 'lifetools', 'lifetoolscard',  'robot']];
                break;
        }
		if($store_id > 0){
            $where['store_id'] = $store_id;
        }
		
		//根据业务 和 keywords 筛选
        if($_POST['type'] == 'mall'){
            $_POST['type'] = ['mall','mall3'];
        }

        $_POST['type'] && $_POST['type'] != 'village' && $where['type'] = ['in', $_POST['type']];
        $_POST['keywords'] && $where['keywords'] =  ['like', '%'. trim($_POST['keywords']).'%'];
        $_POST['keywords'] && $where_tools_order['s.keywords'] = ['like', '%'. trim($_POST['keywords']).'%'];
		
//        $left_join = 'LEFT JOIN '.C('DB_PREFIX').'system_order_detail as d on `o`.`order_id`=`d`.`order_id`';
//        $data = M("System_order")->alias('o')->join($left_join)->where($where)->order('create_time desc')->limit(($page-1)*$pagesize, $pagesize)->select();
        if (empty($orderType) || ($orderType != 'sports' && $orderType != 'scenic')) {
            $data = M("System_order")->where($where)->order('create_time desc')->limit(($page-1)*$pagesize, $pagesize)->select();
        } else {//景区体育健身订单列表
            $where_tools_order['s.type'] = 'lifetools';
            if ($orderType == 'sports') {
                $where_tools_order['l.type'] = array('neq', 'scenic');
            } else {
                $where_tools_order['l.type'] = 'scenic';
            }

            $where_tools_order['o.activity_type'] = array('neq', 'sports_activity'); //去掉运动约战类型订单
 
            $data = M("System_order")->alias("s")->field('s.*,o.sku_id,l.title,l.money,o.num,l.cover_image,o.price,l.tools_id,o.order_status,l.tools_id')->join(' LEFT JOIN '.C('DB_PREFIX').'life_tools_order as o on o.order_id = s.order_id LEFT JOIN '

                .C('DB_PREFIX').'life_tools as l on l.tools_id = o.tools_id')->where($where_tools_order)->order("s.id desc")
                ->limit(($page-1)*$pagesize, $pagesize)->group('s.order_id')->select();
        }

        $mer_ids = array_unique(array_column($data, 'mer_id'));
        $merchant_img = [];
        $merchant_name = [];
        if($mer_ids){
            $merchants = M("Merchant")->where(['mer_id'=>['in', $mer_ids]])->field("mer_id, logo, pic_info, name")->select();
            
            $merchant_image_class = new merchant_image();
            foreach ($merchants as $key => $value) {
                if(!$value['logo']){
                    $tmp_pic_arr = explode(';', $value['pic_info']);
                    $list_pic = $merchant_image_class->get_image_by_path($tmp_pic_arr[0]);
                    $value['logo'] = $list_pic;
                }
                $merchant_img[$value['mer_id']] = replace_file_domain($value['logo']);
                $merchant_name[$value['mer_id']] = $value['name'];
            }
        }
        $store_img = [];
        $store_names = [];

        $store_ids = array_unique(array_filter(array_column($data, 'store_id')));
        if($store_ids){
            $stores = M("Merchant_store")->where(['store_id'=>['in', $store_ids]])->field("store_id, logo, pic_info, name")->select();
            
            $store_image_class = new store_image();
            foreach ($stores as $key => $value) {
                if(!$value['logo']){
                    $tmp_pic_arr = explode(';', $value['pic_info']);
                    $list_pic = $store_image_class->get_image_by_path($tmp_pic_arr[0]);
                    $value['logo'] = $list_pic;
                }
                $store_img[$value['store_id']] = replace_file_domain($value['logo']);
                $store_names[$value['store_id']] = $value['name'];
            }
        }

        foreach ($data as $key => $value) {
            $data[$key]['business_name'] = "";
            $data[$key]['redirect_url'] = "";
            $data[$key]['image'] = "";
            $data[$key]['store_name'] = $store_names[$value['store_id']] ? : "";
            $data[$key]['status_txt'] = "";
            $data[$key]['title'] = "";
            $data[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $system_order_detail = M("System_order_detail")->where(['system_id'=>$value['id']])->find();
            $data[$key]['title'] = $system_order_detail['name'] ? : "";
            $data[$key]['store_url'] = "";
            if($value['paid'] == '1'){
                switch ($value['system_status']) {
                    case '0':
                        if ('appoint'==$value['type']) {
                            $data[$key]['status_txt'] = L_("待服务");
                        } else {
                            if($value['type']=='lifetools'){
                                $data[$key]['status_txt'] = L_('待核销');
                            }else{
                                $data[$key]['status_txt'] = L_('待发货');
                            }
                        }
                        break;
                    case '1':
                        $data[$key]['status_txt'] = L_("已发货");
                        break;
                    case '2':
                        $data[$key]['status_txt'] = L_("已完成");
                        if (isset($value['order_status']) && $value['order_status'] == 80) {
                            $data[$key]['status_txt'] = L_("已转增");
                        }
                        break;
                    case '3':
                        $data[$key]['status_txt'] = L_("已评价");
                        break;
                    case '4':
                        $data[$key]['status_txt'] = L_("已退款");
                        break;
                    case '5':
                        $data[$key]['status_txt'] = L_("已取消");
                        break;
                    case '6':
                        $data[$key]['status_txt'] = L_("已完成");
                        break;
                    case '7':
                        $data[$key]['status_txt'] = L_("退款中");
                        break;
                }

                //退款申请中
                $order_refund = D('Mall_order_refund')->where('order_id = '.$value['order_id'].' and type = 1')->order('create_time desc')->getField('status');
                if(!is_null($order_refund) && $order_refund == 0 && $value['status'] < 30){
                    $data[$key]['status_txt'] = L_('退款申请中');
                }
            }
            elseif($value['paid'] == '0' && in_array($value['system_status'],[4,5])){
                switch ($value['system_status']) {
                    case '4':
                        $data[$key]['status_txt'] = L_("已退款");
                        break;
                    case '5':
                        $data[$key]['status_txt'] = L_("已取消");
                        break;
                }
            }
            else{
                $data[$key]['status_txt'] = L_("待付款");
            }
            $data[$key]['btn_arr'] = [];
            $data[$key]['label_tag'] = [];
            $data[$key]['goods_list'] = [];
            $data[$key]['is_del'] = ($value['paid'] == '1' && in_array($value['system_status'], [2,3,4,5,6])) || ($value['paid'] == '0' && $value['system_status'] == '5') ? 1 : 0;
            
			$data[$key]['feedback_score_add'] = 0; //评价送积分，默认0分。外卖才用。
			switch ($value['type']) {
                case 'lifetools'://体育健身订单
                        $life_msg = M("System_order")->alias("s")->field('s.*
                        ,o.ticket_id,o.sku_id,l.title,l.money,o.num,l.cover_image,o.price,o.is_group,o.activity_type,o.ticket_title,o.act_id
                        ,l.tools_id,l.type')
                            ->join(' LEFT JOIN '.C('DB_PREFIX').'life_tools_order as o on o.order_id = s.order_id LEFT JOIN '
                            .C('DB_PREFIX').'life_tools as l on l.tools_id = o.tools_id')->where(['s.order_id'=>$value['order_id']])->order("s.id desc")
                            ->find();
                    if($life_msg['type']=='scenic'){
                        $data[$key]['business_name'] =  L_('景区服务');
                    }else{
                        $data[$key]['business_name'] =  L_('体育健身');
                    }
                    $order_type_txt = '';
                    if($life_msg['activity_type'] == 'sports_activity'){
                        $order_type_txt = '约战';
                    }else if($life_msg['act_id'] != 0){
                        $order_type_txt = '秒杀';
                    }
                    $data[$key]['order_type_txt'] = $order_type_txt;
                    $data[$key]['is_group'] =  $life_msg['is_group'];
                    if($life_msg['is_group'] == 1){
                        $data[$key]['label_tag']['title'] = "团";
                        $data[$key]['label_tag']['type'] = "life_tools_group";
                    }
                    $data[$key]['redirect_url'] = $this->config['site_url']. D('House_village')->base_url .'pages/lifeTools/order/orderDetail?order_id='.$value['order_id'];
                    $goods_list = M("Life_tools_order_detail")->where(['order_id'=>$value['order_id']])->group('order_id')->select();
                    $goods_list_count = M("Life_tools_order_detail")->where(['order_id'=>$value['order_id']])->count();
                    $data[$key]['num'] = $goods_list_count;
                    $data[$key]['sku_id']=$life_msg['sku_id'];
                    $sku=[];
                    if($life_msg['sku_id']){
                        $sku=M('Life_tools_ticket_sku')->where(['sku_id'=>$life_msg['sku_id'],'ticket_id'=>$life_msg['ticket_id']])->find();
                    }
                    $data[$key]['price'] = $data[$key]['total_price'] = $value['price'];
                    $data[$key]['store_name'] =$life_msg['title'];
                    $comments_param = '[';
                    $comments_detail_param = '[';
                    foreach ($goods_list as $g) {
                        $goods_image="";
                        if(!empty($value['cover_image'])){
                            $goods_image=replace_file_domain($life_msg['cover_image']);
                        }
                        $data[$key]['goods_list'][] = [
                            'num' => $goods_list_count,
                            'goods_image' => empty($life_msg['cover_image'])?$goods_image:replace_file_domain($life_msg['cover_image']),
                            'name' => $life_msg['ticket_title'],
                            'price' => $g['price'],
                            'goods_id' => $life_msg['tools_id'],
                            'spec_txt'=>empty($sku)?"":str_replace(",","|",$sku['sku_str'])
                        ];
                        $comments_detail_param .= $g['detail_id'];
                        $comments_param .= '['.$life_msg['tools_id'].'],';
                    }
                    if($comments_param != '['){
                        $comments_param = rtrim($comments_param, ',');
                    }
                    if($comments_detail_param != '['){
                        $comments_detail_param = rtrim($comments_detail_param, ',');
                    }
                    $comments_detail_param .= ']';
                    $comments_param .= ']';
                    $data[$key]['time_txt'] = '';
                    if($value['paid'] == '0' && $value['system_status']=='0'){
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去支付'),
                            'type' => 'normal',//normal = 普通付款（包括付定金）  prepare = 付尾款
                            'url' => $this->config['site_url']. D('House_village')->base_url  . 'pages/pay/check?order_id='. $value['order_id'].'&order_type=lifetools',
                        ];
                        $data[$key]['is_del'] = 1;
                    }
                    if($data[$key]['system_status'] == '2'){
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => '去评价',
                            'url' => $this->config['site_url']. D('House_village')->base_url  . 'pages/lifeTools/comment/add?order_id='. $value['order_id'],
                        ];
                    }
                    $data[$key]['book_btn_status']=0;
                    $data[$key]['time_txt']=0;
                    $order_info1 = M('life_tools_order')->where(['order_id'=>$value['order_id']])->find();
                    if(empty($order_info1)){//屏蔽掉无效订单
                        unset($data[$key]);
                    }
                    break;
                case 'lifetoolscard'://景区次卡订单
                    $life_msg = M("System_order")->alias("s")
                        ->field('s.*,o.title,o.num,l.image,o.total_price,o.total_price,o.type as card_type')
                        ->join(' LEFT JOIN '.C('DB_PREFIX').'life_tools_card_order as o on o.order_id = s.order_id LEFT JOIN '
                            .C('DB_PREFIX').'life_tools_card as l on l.pigcms_id = o.card_id')
                        ->where(['s.order_id' => $value['order_id']])
                        ->order("s.id desc")
                        ->find();
                    $data[$key]['business_name'] =  $life_msg['card_type'] == 'scenic' ? L_('景区次卡') : L_('体育次卡');
                    $data[$key]['redirect_url']  = $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/pocket/timeCardDetail?order_id=' . $value['order_id'];
                    $data[$key]['num']   = $life_msg['num'];
                    $data[$key]['price'] = $data[$key]['total_price'] = $value['price'];
                    $data[$key]['store_name'] = $life_msg['title'];
                    $data[$key]['time_txt']   = '';
                    $data[$key]['goods_list'][] = [
                        'num'      => $life_msg['num'],
                        'name'     => $life_msg['title'],
                        'price'    => $life_msg['total_price'],
                        'goods_id' => $life_msg['card_id'],
                        'goods_image' => replace_file_domain($life_msg['image'])
                    ];
                    if ($value['paid'] == '0' && $value['system_status'] == '0') {
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去支付'),
                            'type' => 'normal',//normal = 普通付款（包括付定金）  prepare = 付尾款
                            'url'  => $this->config['site_url']. D('House_village')->base_url  . 'pages/pay/check?order_id=' . $value['order_id'] . '&order_type=lifetoolscard',
                        ];
                    }
                    $data[$key]['book_btn_status'] = 0;
                    $data[$key]['time_txt'] = 0;
                    break;
                    
                case 'life_tools_appoint_join'://活动预约

                    $appoint_order = M('Life_tools_appoint_join_order')->alias('o')
                                    ->field('a.image_small,a.title,a.appoint_id,a.start_time,a.end_time,o.price,o.paid,k.sku_str')
                                    ->join(' LEFT JOIN '.C('DB_PREFIX').'life_tools_appoint as a on o.appoint_id = a.appoint_id
                                    LEFT JOIN '.C('DB_PREFIX').'life_tools_appoint_sku as k on o.sku_id = k.sku_id
                                    ')
                                    ->where(['pigcms_id'=>$value['order_id']])->find();

                    $data[$key]['store_name'] = $appoint_order['title'];
                    $data[$key]['business_name'] =  L_('活动预约');

                    // 未开始、进行中、已结束
                    $status_txt = '';
                    $time = time();
                    if($appoint_order['start_time'] > $time) {
                        $status_txt = '未开始';
                    }else if($appoint_order['start_time'] < $time && $appoint_order['end_time'] > $time){
                        $status_txt = '进行中';
                    }else if($appoint_order['end_time'] < $time){
                        $status_txt = '已结束';
                    }
                    if($appoint_order['paid'] == 0 && $value['system_status'] != 5){
                        $status_txt = '待付款';
                        $data[$key]['is_del'] = 1;
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去支付'),
                            'type' => 'normal',//normal = 普通付款（包括付定金）  prepare = 付尾款
                            'url' => $this->config['site_url']. D('House_village')->base_url  . 'pages/pay/check?order_id='. $value['order_id'].'&order_type=life_tools_appoint_join',
                        ];
                    }
                    if($value['system_status'] == 5){
                        $status_txt = '已取消';
                    }

                    $data[$key]['status_txt'] =  $status_txt;
                    $data[$key]['redirect_url'] = $this->config['site_url']. D('House_village')->base_url . 'pages/lifeTools/appointment/mineAppoint/detail?order_id=' . $value['order_id'];
                    $data[$key]['goods_list'][] = [
                        'num' => 1,
                        'goods_image' => $appoint_order['image_small'],
                        'name' => $appoint_order['sku_str'] ?? $appoint_order['title'],
                        'price' => $appoint_order['price'],
                        'goods_id' => $appoint_order['appoint_id'],
                        'spec_txt'=> $appoint_order['sku_str'] ?? ''
                    ];

                    // if($appoint_order['paid'] == 0 && $status != 1){
                    //     unset($data[$key]);
                    // }

                break;
                case 'mall3'://新版商城3.0版本
                    $data[$key]['business_name'] = $this->config['mall_alias_name_new'] ? : L_('商城');
                    $data[$key]['redirect_url'] = $this->config['site_url']. D('House_village')->base_url .'pages/shopmall_third/orderDetails?order_id='.$value['order_id'];
                    $data[$key]['image'] = $store_img[$value['store_id']] ? : '';
                    $data[$key]['store_url'] = $this->config['site_url'] . D('House_village')->base_url . "pages/shopmall_third/store_home/index?store_id=".$value['store_id'];
                    $order_info = M("Mall_order")->where(['order_id'=>$value['order_id']])->find();
                    if($order_info['status'] == 13){
                        $data[$key]['status_txt'] = L_("待成团");
                    }
                    $goods_list = M("Mall_order_detail")->where(['order_id'=>$value['order_id'], 'is_gift'=>0])->select();
                    $data[$key]['num'] = array_sum(array_column($goods_list, 'num'));
                    $data[$key]['price'] = $data[$key]['total_price'] = $order_info['money_real'];
                    $comments_param = '[';
                    $comments_detail_param = '[';
                    foreach ($goods_list as $g) {
                        $goods_image="";
                        $goods=M("Mall_goods")->where(['goods_id'=>$g['goods_id']])->find();
                        if(!empty($goods)){
                            $goods_image=replace_file_domain($goods['image']);
                        }
                        $data[$key]['goods_list'][] = [
                            'num' => $g['num'],
                            'goods_image' => empty($g['image'])?$goods_image:replace_file_domain($g['image']),
                            'name' => $g['name'],
                            'price' => $g['price'],
                            'sku_info' => $g['sku_info']
                        ];
                        $comments_detail_param .= $g['id'].',';
                        $comments_param .= '['.$g['goods_id'].','.$g['sku_id'].'],';
                    }
                    if($comments_param != '['){
                        $comments_param = rtrim($comments_param, ',');
                    }
                    if($comments_detail_param != '['){
                        $comments_detail_param = rtrim($comments_detail_param, ',');
                    }
                    $comments_detail_param .= ']';
                    $comments_param .= ']';
                    $data[$key]['book_btn_status'] = 2;
                    $data[$key]['time_txt'] = '';
                    if($order_info['goods_activity_type'] == 'prepare' && $value['system_status'] != 5){
                        $prepare_info = M('Mall_prepare_order')->where(['order_id'=>$value['order_id']])->find();
                        if($prepare_info['pay_status'] == '0'){
                            $data[$key]['btn_arr'][] = [
                                'important' => true,
                                'text' => '付定金',
                                'type' => 'normal',//normal = 普通付款（包括付定金）  prepare = 付尾款
                                'url' => '',
                            ];
                            $data[$key]['book_btn_status'] = 2;
                            $data[$key]['status_txt'] = "定金待付";
                        }
                        elseif($prepare_info['pay_status'] == '1'){
                            $prepare_act = M("Mall_prepare_act")->where(['id'=>$prepare_info['act_id']])->find();
                            if($prepare_act['rest_type'] == '0'){
                                if($prepare_info['second_pay'] == '1' && (($prepare_info['second_pay_time']+15*60) > $prepare_act['rest_end_time'])){
                                    $prepare_act['rest_end_time'] = $prepare_info['second_pay_time']+15*60;
                                }
                                if(time() > $prepare_act['rest_end_time'] || time() < $prepare_act['rest_start_time']){
                                    $data[$key]['book_btn_status'] = 1;
                                }
                            }
                            elseif($prepare_act['rest_type'] == '1'){
                                $stoptime = $prepare_act['rest_start_time'] + $prepare_act['rest_end_time'] + $prepare_info['pay_time'];
                                if($prepare_info['second_pay'] == '1' && (($prepare_info['second_pay_time']+15*60) > $stoptime)){
                                    $stoptime = $prepare_info['second_pay_time']+15*60;
                                }
                                if(time() > $stoptime){
                                    $data[$key]['book_btn_status'] = 1;
                                }
                            }
                            $data[$key]['btn_arr'][] = [
                                'important' => true,
                                'text' => '付尾款',
                                'type' => 'prepare',//normal = 普通付款（包括付定金）  prepare = 付尾款
                                'url' => '',
                            ];
                            $data[$key]['status_txt'] = "尾款待付";
                        }
                    }
                    else{
                        if($value['paid'] == '0' && $value['system_status']=='0'){
                            $data[$key]['is_del']=1;
                            $data[$key]['btn_arr'][] = [
                                'important' => true,
                                'text' => L_('去支付'),
                                'type' => 'normal',//normal = 普通付款（包括付定金）  prepare = 付尾款
                                'url' => '',
                            ];
                            $order_info1 = M('mall_order')->where(['order_id'=>$value['order_id']])->find();
                            if(empty($order_info1)){//屏蔽掉无效订单
                                unset($data[$key]);
                            }
                        }
                        if($data[$key]['system_status'] == '2'){
                            $data[$key]['btn_arr'][] = [
                                'important' => true,
                                'text' => '去评价',
                                'url' => $this->config['site_url']. D('House_village')->base_url  . 'pages/shopmall_third/publishComments?info='.$comments_param.'&order_detail_id_arr='.$comments_detail_param.'&order_id=' . $value['order_id'],
                            ];
                        }
                    }
                    break;
                case 'mall':
                    $data[$key]['business_name'] = $this->config['mall_alias_name'] ? : L_('商城');
                    $data[$key]['redirect_url'] = $this->config['site_url'] . "/wap.php?c=Mall&a=status&order_id=".$value['order_id'];
                    $data[$key]['image'] = $store_img[$value['store_id']] ? : '';
                    $data[$key]['store_url'] = $this->config['site_url'] . "/wap.php?g=Wap&c=Mall&a=store&store_id=".$value['store_id'];
                    $order_info = M('Shop_order')->where(['order_id'=>$value['order_id']])->find();
                    //预售订单
                    $book_btn_status=0;
                    if($order_info['book_id']>0){
                        /*找到预售的商品*/
                        $book_act = D('Mall_book_goods')->get_info($order_info['book_id']);
                        //book_btn_status 1置灰色不可支付 2可支付  3过期,4未付定金的订单
                        if($order_info['book_status']=='1'){
                            $data[$key]['status_txt'] = "已支付定金";
                        //不同的支付时间类型
                        if($book_act['rest_pay_type']==1){
                            if(time()>$book_act['end_time'] || time()>$book_act['rest_end_time']){
                                $book_btn_status = 3;//过期
                            }elseif (time()<$book_act['end_time'] && time()<$book_act['rest_start_time']){
                                $book_btn_status = 1;//灰色
                            }else{
                                $book_btn_status = 2; //可支付
                            }
                            $book_tips = '尾款支付时间：'.date('Y-m-d',$book_act['rest_start_time']).'至'.date('Y-m-d',$book_act['rest_end_time']);
                        }else{
                            $r_time = $order_info['book_time']+$book_act['rest_day']*60*60*24;
                            if(time()>$book_act['end_time'] || time()>$r_time){
                                $book_btn_status = 3;//过期
                            }elseif (time()<$book_act['end_time'] && time()<$r_time){
                                $book_btn_status = 1;//灰色
                            }else{
                                $book_btn_status = 2; //可支付
                            }
                            $book_tips = '尾款支付时间：'.date('Y-m-d H:i:s',$r_time).'开始支付';
                        }
                      }
                    }

                    if($value['paid'] == '0' && $value['system_status']=='0'){
                        if($order_info['book_status']=='1'){
                            /*预售订单添加尾款支付时间*/
                            $data[$key]['btn_arr'][] = [
                                'important' => true,
                                'text' => '付尾款'.$this->config['Currency_symbol'].$order_info['rest_price'],
                                'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $value['order_id'] . '&type=mall',
                            ];
                        }else{
                            $data[$key]['btn_arr'][] = [
                                'important' => true,
                                'text' => '去支付',
                                'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $value['order_id'] . '&type=mall',
                            ];
                            $book_tips = '定金支付时间：'.date('Y-m-d',$book_act['start_time']).'至'.date('Y-m-d',$book_act['end_time']);
                        }
                    }

                    if($data[$key]['system_status'] == '2'){
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => '去评价',
                            'url' => $this->config['site_url'] . '/wap.php?c=My&a=mall_feedback&order_id=' . $value['order_id'],
                        ];
                    }

                    $data[$key]['book_btn_status']=$book_btn_status;
                    $data[$key]['time_txt']=$book_tips;
                    $data[$key]['goods_list'] = D("Shop_order")->get_good_details($value['order_id']);
                    if (!empty($data[$key]['goods_list'][0]) && $data[$key]['goods_list'][0]['book_id']>0){
                        $data[$key]['price']=$data[$key]['goods_list'][0]['bargin_price']+$data[$key]['goods_list'][0]['rest_price']+$data[$key]['goods_list'][0]['book_discount_price'];
                    }
                    break;
                case 'shop':
                    $shop_order = M("Shop_order")->where(['order_id'=>$value['order_id']])->find();
                    //如果商品有改价，列表展示改价后的总价
                    if ($shop_order['change_price'] > 0) {
                        $data[$key]['price'] = getFormatNumber($shop_order['price']);
                    }

                    $data[$key]['business_name'] = $this->config['shop_alias_name'] ? : L_('外卖');
                    
					if($this->config['map_config'] == 'google'){
						$data[$key]['redirect_url'] = packapp_url('my/shop_order.html?order_id='.$value['order_id'].($_GET['is_first'] ? '&is_first='.$_GET['is_first'] : ''), D('User')->get_user($this->_uid));
					}else{
						$data[$key]['redirect_url'] = C("config.site_url")."/wap.php?c=Shop&a=status&order_id=".$value['order_id'];
					}
					
                    $data[$key]['image'] = $store_img[$value['store_id']] ? : '';
                    $data[$key]['store_url'] = $this->config['site_url'] . D('House_village')->base_url . "pages/shop_new/shopDetail/shopDetail?store_id=" . $value['store_id'];
                    
                    
                    if($value['paid'] == '0' && $value['system_status']=='0'){
                        $data[$key]['is_del']=1;
                        $use_sys_coupon = 'false';
                        $system_coupon_id = '';
                        $use_mer_coupon = 'false';
                        $merchant_coupon_id = '';
                        if($shop_order['coupon_id'] > 0){
                            $use_sys_coupon = 'true';
                            $system_coupon_id = $shop_order['coupon_id'];
                        }
                        if($shop_order['card_id'] > 0){
                            $use_mer_coupon = 'true';
                            $merchant_coupon_id = $shop_order['card_id'];
                        }
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去支付'),
                            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $value['order_id'].'&type=shop&order_from=&use_sys_coupon='.$use_sys_coupon.'&system_coupon_id='.$system_coupon_id.'&use_mer_coupon='.$use_mer_coupon.'&merchant_coupon_id='.$merchant_coupon_id,
                        ];
                    }
                    if($data[$key]['system_status'] == '2'){
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('再来一单'),
                            'type' => 'again',
                            'url' => $this->config['site_url'] . "/packapp/plat/pages/shop_new/shopDetail/shopDetail?store_id=".$value['store_id'],
                        ];
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去评价'),
                            'url' => $this->config['site_url'] . "/packapp/plat/pages/my/make_comments?order_id=".$value['order_id'],
                        ];
						
						//评价送积分，默认0分。外卖才用。
						$data[$key]['feedback_score_add'] = $this->config['feedback_score_add'] > 0 ? $this->config['feedback_score_add'] : 0;  
                    
					}
                    $data[$key]['goods_list'] = D("Shop_order")->get_good_details($value['order_id']);
                    if($shop_order['status']==4){
                        $shop_total_num = array_sum(array_column($data[$key]['goods_list'],'num'));
                        $data[$key]['status_txt'] = D('Shop_order')->checkOrderRefund($value['order_id'],$shop_total_num);
                    }
                    $data[$key]['book_btn_status']=0;
                    $data[$key]['time_txt']=0;
                    $data[$key]['num'] = array_sum(array_column($data[$key]['goods_list'], 'num'));
                    break;
                case 'village_group':
                    $data[$key]['book_btn_status']=0;
                    $data[$key]['time_txt']=0;
                    $plat_order_id = M('Plat_order')->where(['business_type'=>'village_group','business_id'=>$value['order_id']])->getField('order_id');
                    $start_uid = M("Village_group_order")->where(['order_id'=>$value['order_id']])->getField('start_uid');
                    $data[$key]['business_name'] = L_('社区团购');
                    $data[$key]['redirect_url'] = C("config.site_url")."/wap.php?c=Village_group&a=orderDetail&order_id=".$value['order_id'];
                    $data[$key]['image'] = $store_img[$value['store_id']] ? : '';
                    $data[$key]['store_url'] = '';
                    
                    
                    if($value['paid'] == '0' && $value['system_status']=='0'){
//                        $use_sys_coupon = 'false';
//                        $system_coupon_id = '';
//                        $use_mer_coupon = 'false';
//                        $merchant_coupon_id = '';
//                        if($shop_order['coupon_id'] > 0){
//                            $use_sys_coupon = 'true';
//                            $system_coupon_id = $shop_order['coupon_id'];
//                        }
//                        if($shop_order['card_id'] > 0){
//                            $use_mer_coupon = 'true';
//                            $merchant_coupon_id = $shop_order['card_id'];
//                        }
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => '去支付',
                            'url' => $this->config['site_url']  . '/packapp/community_group/index.html#/confirm?leader_id='.$start_uid.'&order_id='.$value['order_id'],
                        ];
                    }
                    if($data[$key]['system_status'] == '2'){

                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去评价'),
                            'url' => $this->config['site_url'] . '/wap.php?g=Wap&c=Village_group&a=reply&order_id=' . $value['order_id'],
                        ];
                    }
                    //echo $value['order_id'];exit;
                    $data[$key]['goods_list'] = D("Village_group_order")->get_good_details($value['order_id']);
                    break;
                   case 'dining':
                    $data[$key]['business_name'] = C('config.meal_alias_name');;//业务名称
                    $data[$key]['image'] = $store_img[$value['store_id']] ? : '';//圆图（一般标识店铺图片的那种）
                    $data[$key]['store_url'] =  $this->config['site_url'] . '/packapp/plat/pages/store/homePage?store_id='.$value['store_id'];//店铺主页

                    $order = D('Dining_order')->geOrderDetail($value['order_id']);
                    $data[$key]['price'] = $order['show_price'];//订单总价
                    if($order['order_status'] == 1){// 待支付
                        $data[$key]['redirect_url'] = $this->config['site_url'] . '/packapp/plat/pages/pay/check?order_type=dining&order_id='.$order['pay_order_id'];//跳转链接

                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去支付'),
                            'url' =>  $data[$key]['redirect_url']
                        ];//下方展示的按钮
                    }else{

                        $data[$key]['status_txt'] = $order['order_status_txt'];
                        $data[$key]['redirect_url'] = $this->config['site_url'] . '/packapp/plat/pages/foodshop/order/orderDetail?order_id='.$order['order_id'];//跳转链接
                    }

                    if ($data[$key]['system_status'] == '2') {
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去评价'),
                            'url' => get_base_url('pages/foodshop/order/comments?order_id=' . $value['order_id'])
                        ];
                    }
                    $data[$key]['goods_list'] = $order['goods_list'];//订单商品数组 里面需要包含 num\goods_image\name\price几个基本字段
                    $data[$key]['num'] = $order['goods_num'];
                    break;
                case 'meal':
                    $data[$key]['book_btn_status']=0;
                    $data[$key]['time_txt']=0;
                    $data[$key]['business_name'] = $this->config['meal_alias_name'] ? : '餐饮';
                    $data[$key]['redirect_url'] = C("config.site_url")."/wap.php?c=Foodshop&a=sync&sync=".$value['real_orderid'];
                    $data[$key]['image'] = $store_img[$value['store_id']] ? : '';
                    $data[$key]['store_url'] = C("config.site_url")."/wap.php?g=Wap&c=Foodshop&a=shop&store_id=".$value['store_id'];
                    $now_order = M("Foodshop_order")->where(['order_id'=>$value['order_id']])->find();
                    $data[$key]['price'] = $now_order['can_discount_table_money'];
                    if($value['paid'] == '0' && $value['system_status']=='0'){
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去支付'),
                            'url' => $this->config['site_url'] . '/wap.php?c=Foodshop&a=pay&order_id=' . $value['real_orderid'],
                        ];
                    }
                    if($data[$key]['system_status'] == '2'){
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去评价'),
                            'url' => $this->config['site_url'] . '/wap.php?c=My&a=foodshop_feedback&order_id=' . $value['order_id'],
                        ];
                    }
                    $now_order_detail = D('Foodshop_order')->get_order_detail([],2,$now_order);
                    $now_order['total_price'] = D('Foodshop_order')->count_price($now_order_detail);
                    $goods_list = M("Foodshop_order_detail")->alias("a")->join("left join ".C("DB_PREFIX")."foodshop_goods as b on a.goods_id=b.goods_id")->where(['a.order_id'=>$value['order_id']])->field("b.name,b.image,a.num,a.price")->select();
                    $goods_image_class = new foodshop_goods_image();
                    $total_price = 0;
                    foreach ($goods_list as $gkey => $gvalue) {
                        $goods_list[$gkey]['num'] = intval($gvalue['num']);
                        $goods_list[$gkey]['goods_image'] = '';
                        $tmp_pic_arr = explode(';', $gvalue['image']);
                        if($tmp_pic_arr[0]){
                            $goods_list[$gkey]['goods_image'] = $goods_image_class->get_image_by_path($tmp_pic_arr[0])['image'];
                        }
                    }
                    $data[$key]['goods_list'] = $goods_list;
                    $data[$key]['total_price'] =  $now_order['total_price'];
                    // $data[$key]['price'] = $now_order['can_discount_table_money'];
                    if ($now_order['status']) {
                         $now_order['total_price'] = getFormatNumber($now_order['total_price'] + $now_order['book_price']);
                    }
                    $data[$key]['price'] = $now_order['total_price'];
                    break;
                case 'group':
                    $data[$key]['book_btn_status']=0;
                    $data[$key]['time_txt']=0;
                    $data[$key]['business_name'] = $this->config['group_alias_name'] ? : '团购';
                    $data[$key]['price'] = getFormatNumber($data[$key]['total_price']);

                    //获得团购订单
                    $where['order_id'] = $value['order_id'];
                    $now_order = M("Group_order")->field(true)->where($where)->find();
                    $data[$key]['total_price'] = getFormatNumber($now_order['payment_money']+$now_order['balance_pay']+$now_order['merchant_balance']+$now_order['score_deducte']+$now_order['card_give_money']);
                    if($now_order['is_group_combine']){
                        //获得团购组合订单详情
                        $condition_table = array(C('DB_PREFIX').'group_combine_activity'=>'g',C('DB_PREFIX').'group_order'=>'o');
                        $condition_where = "";
                        $condition_where .= "`o`.`order_id`='".$value['order_id']."' AND `o`.`combine_id`=`g`.`combine_id`";
                        $now_order = M("Group_order")->field('`g`.`title` as name,`g`.`banner_img`,`o`.`price`,o.can_use_count,o.combine_id,o.tuan_type,o.specifications_id,o.can_use_count,o.combine_used_count')->where($condition_where)->table($condition_table)->find();

                         $data[$key]['redirect_url'] = C("config.site_url")."/packapp/plat/pages/group/groupCombineOrderDetail?order_id=".$value['order_id'];
                        // $data[$key]['image'] = $now_order['banner_img'];
                        $data[$key]['store_name'] = L_('优惠组合详情');
                        $data[$key]['store_url'] = C("config.site_url")."/packapp/plat/pages/group/groupCombineDetail?combine_id=".$now_order['combine_id'];
                        $now_order['goods_image'] = replace_file_domain($now_order['banner_img']);
                        $now_order['num'] = 1;
                        $now_order['price'] = getFormatNumber($now_order['price']);
//                        $data[$key]['goods_list'][] = $now_order;

                        //$used_count = D('Group_combine_activity')->getOrderUsedCount(['order_id'=>$value['order_id']]);
                        // var_dump($used_count);
                        //$no_use_count = $now_order['can_use_count'] - $used_count;//未核销次数
                        $no_use_count = max(0, $now_order['can_use_count'] - $now_order['combine_used_count']);

                    }else{
                        //获得团购订单
                        $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o');
                        $condition_where = "";
                        $condition_where .= "`o`.`order_id`='".$value['order_id']."' AND `o`.`group_id`=`g`.`group_id`";
                        $now_order = M("Group_order")->field('`g`.`name`,`g`.`pic`,o.price,o.uid,o.specifications_id')->where($condition_where)->table($condition_table)->find();

                        $buyInfo = M('Group_buyer_list')->where(['order_id' => $value['order_id'], 'uid' => $now_order['uid']])->find();
                        $groupStart = M('Group_start')->where(['id' => $buyInfo['fid']])->find();
                        if($groupStart && $groupStart['status'] == 0){
                            $data[$key]['redirect_url'] = C("config.site_url")."/packapp/plat/pages/shopmall_third/inviteFriends?orderid={$value['order_id']}&groupPurchase=1";
                        }else{
                            $data[$key]['redirect_url'] = C("config.site_url")."/wap.php?c=Groupnew&a=order_detail&order_id=".$value['order_id'];
                        }
                        $data[$key]['image'] = $merchant_img[$value['mer_id']] ? : '';
                        $data[$key]['store_name'] = $merchant_name[$value['mer_id']];
                        $data[$key]['store_url'] = C("config.site_url")."/wap.php?g=Wap&c=Groupnew&a=merchant&mer_id=".$value['mer_id'];

                        $group_image_class = new group_image();
                        $tmp_pic_arr = explode(';',$now_order['pic']);
                        $now_order['goods_image'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'');
                        if(!$now_order['goods_image']){
                            $now_order['goods_image']=C("config.site_url")."/static/images/group/cashing.png";
                            $now_order['goods_image'] = replace_file_domain($now_order['goods_image']);
                        }
                        $now_order['num'] = $value['num'];
//                        $data[$key]['goods_list'][] = $now_order;

                    }

                    //获取规格
                    $sku_info = '';
                    if($now_order['specifications_id']){
                        $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->find();
                        if ($specifications_info) {
                            $sku_info = $specifications_info['specifications_name'] ?:'';
                        }
                    }
                    $now_order['sku_info'] = $sku_info;
                    $data[$key]['goods_list'][] = $now_order;
                    
                    if($value['paid'] == '0' && $value['system_status']=='0'){
                        $data[$key]['is_del']=1;
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去支付'),
                            'url' => get_base_url('pages/pay/check?order_id='.$value['order_id'].'&order_type=group'),
                        ];
                    }
                    
                    //$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o');
                    $condition_where = "";

                    //$condition_where .= "`o`.`order_id`='".$value['order_id']."' AND `o`.`group_id`=`g`.`group_id`";

                   // $now_order = M("Group_order")->field('`g`.`name`,`g`.`pic`,g.price,g.pin_num,o.*')->where($condition_where)->table($condition_table)->find();
                   
                    $condition_where .= "`o`.`order_id`=".$value['order_id'];

                    $now_order = M("Group_order")->join(' AS o left join '.C('DB_PREFIX').'group as g on `o`.`group_id`=`g`.`group_id`')->field('`g`.`name`,`g`.`pic`,g.price,g.pin_num,o.*')->where($condition_where)->find();

                    $hasPass = 0;
                    if($now_order['pass_array'] || ($now_order['pass_num'] && $now_order['pass_num']>1) || $now_order['group_pass']){
                       $hasPass = 1;
                    }

                    if ($now_order['pin_num']>0 && !$now_order['single_buy'] && $value['paid'] == '1' && $value['system_status']=='0') {
                        $pin_fid = $now_order['is_head']?$now_order['is_head']:$now_order['pin_fid'];
                        $pin_status = D('Group_start')->where(['id'=> $pin_fid])->getField('status');
                        if(1==$pin_status){
                            $data[$key]['status_txt'] = '待核销';
                            if($no_use_count){
                                $data[$key]['status_txt'] .= L('次数:').$no_use_count;
                            }
							if($now_order['is_pick_in_store'] != 1 && $now_order['tuan_type']!=2){
								$data[$key]['btn_arr'][] = [
									'important' => true,
									'text' => L_('核销二维码'),
									'url' => $this->config['site_url'] . '/wap.php?c=Groupnew&a=order_detail&order_id=' . $value['order_id'],
								];
							}
                        }
                    } elseif($value['paid'] == '1' && $value['system_status']=='0'){
                        $data[$key]['status_txt'] = '待核销';
                        if($no_use_count){
                            $data[$key]['status_txt'] .= L('次数:').$no_use_count;
                        }
						if($now_order['is_pick_in_store'] != 1 && $now_order['tuan_type']!=2){
							$data[$key]['btn_arr'][] = [
								'important' => true,
								'text' => L_('核销二维码'),
								'url' => $this->config['site_url'] . '/wap.php?c=Groupnew&a=order_detail&order_id=' . $value['order_id'],
							];
						}
                    }

                    if($data[$key]['system_status'] == '2' && $now_order['status'] == 1){
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去评价'),
                            'url' => $this->config['site_url'] . '/wap.php?g=Wap&c=My&a=group_feedback&order_id=' . $value['order_id'],
                        ];
                    }
		    
		            if ($now_order['status'] == 9) { //已过期平台冻结
                        $data[$key]['status_txt'] = '已过期';
                        $data[$key]['btn_arr'] = [];
                    }
                    break;
                    
                case 'robot':
                    $robotOrder = M('Third_robot_delivery_order')
                        ->where(['id' => $value['order_id']])
                        ->field('delivery_price')
                        ->find();
                    $robot = M('Third_robot_device')
                        ->where(['id' => $robotOrder['robot_id']])
                        ->field('robot_type')
                        ->find();
                    $data[$key]['goods_list'][] = [
                        'num' => 1,
                        'goods_image' => D('Third_robot_delivery_order')->getRobotImg($robot['robot_type']),
                        'name' => '召唤机器人',
                        'price' => $robotOrder['delivery_price'],
                    ];
                     $data[$key]['business_name'] = '召唤机器人';
                     $data[$key]['redirect_url'] = get_base_url('pages/robot/call_robot_release_order?call_from=platCallRobot&order_id='.$value['order_id'].'&order_type=robot');
                     break;
                case 'appoint':
                    $data[$key]['book_btn_status']=0;
                    $data[$key]['time_txt']=0;
                    $appoint_image_class = new appoint_image();
                    $data[$key]['business_name'] = $this->config['appoint_alias_name'] ? : L_('预约');
                    $data[$key]['redirect_url'] = C("config.site_url")."/wap.php?c=My&a=appoint_order&order_id=".$value['order_id'];
                    $appoint_order_detail = M('Appoint_order')->where(['order_id' => $value['order_id']])->find();
                    if($appoint_order_detail['appoint_id'] == 0){
                        if($appoint_order_detail['cat_id']){
                            $appoint_cate = D('Appoint_category')->where(array('cat_id'=>$appoint_order_detail['cat_id']))->find();
                            $data[$key]['image'] = file_domain().'/upload/system/'.$appoint_cate['cat_pic'];
                        }
                    }
                    else{
                        $appoint_info = D('Appoint')->field('`pic`, `appoint_name`, `appoint_price`, `payment_status`')->where(['appoint_id'=>$appoint_order_detail['appoint_id']])->find();
                        $tmp_pic_arr = explode(';', $appoint_info['pic']);
                        $appoint_image_class = new appoint_image();
                        $data[$key]['image'] = $appoint_image_class->get_image_by_path($tmp_pic_arr[0],'s');
                    }

                    $appoint_order_detail['appoint_price'] = floatval($appoint_order_detail['appoint_price']);
                    $appoint_order_detail['product_price'] = floatval($appoint_order_detail['product_price']);
                    
                    if($appoint_order_detail['product_id'] > 0){
                        $price = $appoint_order_detail['product_price'] ?: '面议';
                    } else {
                        if(empty($appoint_order_detail['payment_status'])){
                            if($appoint_order_detail['appoint_price']){
                                $price = '一口价';
                            }else{
                                $price = '面议';
                            }
                        }else{
                            if($appoint_order_detail['appoint_price']){
                                $price = $appoint_order_detail['appoint_price'];
                            }else{
                                $price = '面议';
                            }
                        }
                    }
                    $data[$key]['goods_list'][] = [
                        'goods_image' => $data[$key]['image'],
                        'name' => $appoint_order_detail['appoint_id'] == 0 && $appoint_order_detail['cat_id'] ? $appoint_cate['cat_name'] : $appoint_info['appoint_name'],
                        'num' => 1,
                        'price' => $price
                    ];
                    $data[$key]['price'] = floatval($appoint_order_detail['product_price'])>0 ? floatval($appoint_order_detail['product_price']) : floatval($appoint_order_detail['appoint_price']);

                    if (floatval($data[$key]['price'])<=0 && intval($appoint_order_detail['product_id'])>0 && intval($appoint_order_detail['payment_status'])==1){
                        if (0<floatval($appoint_order_detail['product_payment_price']) && $appoint_order_detail["type"]<=2 && $appoint_order_detail["payment_status"]) {
                            if (0<floatval($appoint_order_detail["balance_pay"])) {
                                $data[$key]['price'] = $appoint_order_detail["balance_pay"];
                            } else {
                                $data[$key]['price'] = $appoint_order_detail["product_payment_price"];
                            }
                        }
                    } elseif(floatval($data[$key]['price'])<=0 && floatval($appoint_order_detail['payment_money']) > 0 && intval($appoint_order_detail['payment_status'])==1) {
                        if (0<floatval($appoint_order_detail['payment_money'])) {
                            $data[$key]['price'] = $appoint_order_detail['payment_money'];
                        }
                    }
                    if (floatval($data[$key]['total_price'])<=0 && floatval($data[$key]['price'])>0) {
                        $data[$key]['total_price'] = $data[$key]['price'];
                    }

                    $data[$key]['store_url'] = C("config.site_url")."/wap.php?g=Wap&c=Appoint&a=shop&store_id=".$value['store_id'];
                    if($value['paid'] == '0' && $value['system_status']=='0'){
                        $base_url = D('House_village')->base_url;
                        if(!$appoint_order_detail['payment_money']&&!$appoint_order_detail['product_payment_price']||($appoint_order_detail['payment_money']=='0.00'&&$appoint_order_detail['product_payment_price']=='0.00')){
                            $appoint_order_url = $this->config['site_url'] . $base_url . 'pages/appoint/goods/submitDetail?orderid='. $value['order_id'] .'&appoint_id=' . $appoint_order_detail['appoint_id'];
                        }else{
                            $appoint_order_url = $this->config['site_url'] . $base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $value['order_id'] . '&type=appoint';
                        }
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' =>  floatval($price) >0 ? L_('去支付') : $price,
                            'url' => $appoint_order_url,
                        ];
                    }
                    if($data[$key]['system_status'] == '2'){
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => L_('去评价'),
                            'url' => $this->config['site_url'] . '/wap.php?c=My&a=appoint_feedback&order_id=' . $value['order_id'],
                        ];
                    }
                    $status_txt = '';
                    if ($appoint_order_detail["is_del"]==0 && $appoint_order_detail["paid"]==2) {
                        if ($status_txt) {
                            $status_txt .= ' 已退款';
                        } else {
                            $status_txt = '已退款';
                        }
                    }else
                    if (0==$appoint_order_detail['service_status']) {
                        $now_supply_id = D('Appoint_supply')->where(array('order_id'=>intval($value['order_id']),'status'=>3))->getField('supply_id');
                        $appoint_worker_name = $this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : '技师';
                        if($now_supply_id && $appoint_order_detail['payment_status'] && !$appoint_order_detail['is_initiative']){
                            $status_txt = $appoint_worker_name . '已服务，用户未支付余额';
                        } elseif ($now_supply_id){
                            $status_txt = '已服务,待验证';
                        } else {
                            $status_txt = '未服务';
                        }
                    } elseif(1==$appoint_order_detail['service_status']) {
                        $status_txt = '已服务';
                    } elseif(2==$appoint_order_detail['service_status']) {
                        $status_txt = '已评价';
                    }
                    if ($appoint_order_detail["is_del"]!=0 || $appoint_order_detail["paid"]==3) {
                        if ($status_txt) {
                            $status_txt .= ' 已取消';
                        } else {
                            $status_txt = '已取消';
                        }
                    }
                    $data[$key]['status_txt'] = $status_txt;
                    break;
                case 'service':
                    $data[$key]['book_btn_status']=0;
                    $data[$key]['time_txt']=0;
                    $data[$key]['business_name'] = $this->config['service_alias_name'];
                    $plat_order = M("Plat_order")->where(['order_id' => $value['order_id']])->find();
                    $data[$key]['redirect_url'] = C("config.site_url")."/wap.php?c=Service&a=price_list&publish_id=".$plat_order['business_id'];
                    $data[$key]['price'] = $plat_order['total_money'];

                    $publishOrder = D('Service_user_publish')->where(['publish_id'=> $plat_order['business_id']])->find();
                    $goPay = 0;// 是否需要支付
                    $payOrderId = $plat_order['order_id'];
                    if($publishOrder['is_voice']){// 语音发单订单
                        // 订单未完成时状态处理
                        if($value['system_status'] < 2 && $plat_order['paid'] == 1){
                            $goPay = 0;
                            $data[$key]['status_txt']= '基础费用已支付';

                            // 查询尾款订单
                            $plat_order_final = M("Plat_order")->where(['business_id' => $plat_order['business_id'],'business_type'=>'service','is_final_pay'=>1])->find();
                            if($plat_order_final){
                                $data[$key]['price'] += $plat_order_final['total_money'];
                                if($plat_order_final['paid'] == 0){
                                    $payOrderId = $plat_order_final['order_id'];
                                    $data[$key]['status_txt']= '尾款待支付';
                                    $goPay = 1;
                                }else{
                                    $data[$key]['status_txt']= '待发货';
                                }
                            }
                        }
                    }


                    $now_time = time();
                    $fetch_time = 0;
                    if($publishOrder['catgory_type'] == 2){
                        $service_user_publish_buy = M('Service_user_publish_buy')->field('arrival_time_info')->where(['publish_id'=>$publishOrder['publish_id']])->find();
                        $fetch_time = strtotime($service_user_publish_buy['arrival_time_info']);

                    }else if($publishOrder['catgory_type'] == 3){
                        $service_user_publish_give = M('Service_user_publish_give')->field('fetch_time')->where(['publish_id'=>$publishOrder['publish_id']])->find();
                        $fetch_time = strtotime($service_user_publish_give['fetch_time']);
                    }
                    
                    if($value['paid'] == '0' && $value['system_status'] < 2 || ($value['system_status'] < 2 && $goPay)){
                        $data[$key]['is_del']=1;
                        if($now_time < $fetch_time){
                            $data[$key]['btn_arr'][] = [
                                'important' => false,
                                'text' => L_('去支付'),
                                'url' => $this->config['site_url'] . '/packapp/plat/pages/shop_new/confirmOrder/pay?order_id='.$payOrderId.'&type=plat&order_from=',
                            ];
                        }  
                    }
                    if($value['paid'] == '1' && $value['system_status'] == '2'){
                        $data[$key]['btn_arr'][] = [
                            'important' => false,
                            'text' => L_('去评价'),
                            'url' => $this->config['site_url'] . '/packapp/plat/pages/my/make_comments?order_id='.$payOrderId,
                        ];
                    }
                    $cat_name = D('Service_user_publish')
                        ->join('as s LEFT JOIN '.C('DB_PREFIX').'service_category as c ON s.cid=c.cid')
                        ->where(['s.publish_id'=> $plat_order['business_id']])
                        ->getField('c.cat_name');

                    $data[$key]['goods_list'][] = [
                        'goods_image' => $this->config['wechat_share_img'] ? replace_file_domain($this->config['wechat_share_img']) : replace_file_domain($this->config['site_logo']),
                        'name' => $cat_name ? $cat_name .'订单' : $plat_order['order_name'],
                        'num' => 1,
                        'price' => getFormatNumber($data[$key]['price'])
                    ];
                    break;
                // case 'cash':
                //     $data[$key]['business_name'] = $this->config['cash_alias_name'] ? : '优惠买单';
                //     $data[$key]['redirect_url'] = U("Mall/status",['order_id'=>$value['order_id']]);
                //     break;
                case 'store':
                    $data[$key]['book_btn_status']=0;
                    $data[$key]['time_txt']=0;
                    $data[$key]['business_name'] = $this->config['cash_alias_name'] ? : '到店付';
                    $data[$key]['redirect_url'] = C("config.site_url")."/wap.php?c=My&a=store_order_detail&order_id=".$value['order_id'];
                    $data[$key]['image'] = $store_img[$value['store_id']] ? : '';
                    $data[$key]['store_url'] = C("config.site_url")."/packapp/my/store/index.html?store_id=".$value['store_id'];
                    $now_order = M("Store_order")->where(['order_id'=>$value['order_id']])->find();
                    $data[$key]['goods_list'][] = [
                        'goods_image' => $data[$key]['image'],
                        'name' => L_('顾客现场自助支付'),
                        'num' => 1,
                        'price' => $now_order['price']
                    ];
                    if($value['paid'] == '0' && $value['system_status']=='0'){
                        $data[$key]['is_del'] = 1;
                        $data[$key]['btn_arr'][] = [
                            'important' => true,
                            'text' => '去支付',
                            'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $value['order_id'] . '&type=store',
                        ];
                    }
                    break;
                case 'mobile_recharge':
                    $data[$key]['business_name'] = L_('在线充值');
                    $now_order = M("Plat_order")->where(['order_id' => $value['order_id']])->find();
                    $data[$key]['goods_list'][] = [
                        'goods_image' => $this->config['wechat_share_img'] ? replace_file_domain($this->config['wechat_share_img']) : replace_file_domain($this->config['site_logo']),
                        'name' => $data[$key]['business_name'],
                        'num' => 1,
                        'price' => $now_order['total_money']
                    ];
                    break;

                case 'gift':
                    $data[$key]['business_name'] = $this->config['gift_alias_name'] ? : L_('积分商城');
                    $data[$key]['redirect_url'] = $this->config['site_url'] . "/wap.php?c=My&a=gift_order&order_id=".$value['order_id'];
                    $now_order = M("Gift_order")->where(['order_id'=>$value['order_id']])->find();


                    if(!empty($now_order['wap_pic'])){
                        $gift_image_class = new gift_image();
                        $wap_pic_arr = explode(';',$now_order['wap_pic']);
                        $wap_pic_list = array();
                        foreach($wap_pic_arr as $keys=>$pic){
                            $tmp_pic = $gift_image_class->get_image_by_path($pic);
                            $wap_pic_list[$keys]['image_url'] = $tmp_pic['image'];
                        }
                    }
                    $data[$key]['goods_list'][] = [
                        'goods_image' => $wap_pic_list[0]['image_url'],
                        'name' => $now_order['order_name'],
                        'num' => $now_order['num'],
                        'price' => $now_order['price']
                    ];

                    break;
                // case 'merchant_user_recharge':
                //     $data[$key]['business_name'] = '充值';
                //     $data[$key]['redirect_url'] = U("Mall/status",['order_id'=>$value['order_id']]);
                //     break;
            }
            !empty($data[$key]['goods_list']) && $data[$key]['goods_list'] = array_values($data[$key]['goods_list']);
        }
        $data=array_values($data);
        $this->returnCode(0,$data);
    }
    
    //删除各个业务的订单
    public function del_order(){
        $id = I("id");
        if(empty($id)){
            $this->returnCode(1, array(), L_('参数缺失！'));
        }
        $where = [
            'id' => $id,
            'uid' => $this->_uid,
        ];
        $order = M("System_order")->where($where)->find();
        if (empty($order)) {
            $this->returnCode(1, array(), L_('订单不存在！'));
        }
        switch ($order['type']) {
             case 'shop':
                 $shopOrder =  D('Shop_order')->where([
                         'order_id' => $order['order_id'],
                         'paid' => 0,
                         'uid' => $this->_uid,
                         'status' => ['NEQ', 5]
                     ])->find();
                 if(!empty($shopOrder)){
                     D("System_order")->save_order_status('shop', $shopOrder['order_id'], 5);
                     D('Shop_order_log')->add_log(array('order_id' =>  $shopOrder['order_id'], 'status' => 10));
                 }
                 break;
        //     case 'meal':
        //     case 'group':
        //         $this->returnCode('70000010');
        //         break;
        //     case 'appoint':
        //         $database_appoint_order = D('Appoint_order');
        //         $now_order = $database_appoint_order->get_order_detail_by_id($this->_uid,intval($order_id));
        //         $condition_group_order['order_id'] = $now_order['order_id'];
        //         $data_group_order['paid'] = 3;
        //         $database_appoint_order->where($condition_group_order)->data($data_group_order)->save();
        //         break;
        //     default:
        //         $this->returnCode('70000010');
        //         break;
        }
        // $this->returnCode(0);
        M("System_order")->where($where)->data(['is_del' => 1])->save();
        $this->returnCode(0);
    }

    // 我的收藏头部
    public function my_collect_type()
    {
        $returnArr = [];
        if (C('config.shop_alias_name')) {
            $returnArr[] = ['text' => C('config.shop_alias_name'), 'type' => 'shop'];
        }
        if (C('config.group_alias_name')) {
            $returnArr[] = ['text' => C('config.group_alias_name'), 'type' => 'group'];
        }
        if (C('config.meal_alias_name')) {
            $returnArr[] = ['text' => C('config.meal_alias_name'), 'type' => 'foodshop'];
        }
        if (C('config.meal_alias_name')) {
            $returnArr[] = ['text' => C('config.appoint_alias_name'), 'type' => 'appoint'];
        }
        if (C('config.mall_alias_name')) {
            $returnArr[] = ['text' => C('config.mall_alias_name'), 'type' => 'mall'];
        }
        $this->returnCode(0, $returnArr);
    }

    /**
     * 上传图片
     * @author 张涛
     * @date 2020/06/16
     */
    public function uploadPic()
    {
        $picType = $_POST['picType'] ?? 'reply';
        if ($_FILES['file']['error'] != 4) {
            $param = [
                'size' => 10,
                'thumb' => false,
                'imageClassPath' => 'ORG.Util.Image',
                'thumbPrefix' => 'm_,s_',
                'thumbRemoveOrigin' => false
            ];
            $image = D('Image')->handle($this->_uid, $picType, 1, $param);
            if ($image['error']) {
                $this->returnCode(1, array(), $image['msg']);
            } else {
                $title = $image['title']['file'];
                $url = (new shop_order_reply_image())->get_image_by_path($title, 's');
                $this->returnCode(0, array('url' => $url, 'title' => $image['url']['file'], 'show_url' => thumb_img($url, 240, 240, 'fill')));
            }
        } else {
            $this->returnCode(1, array(), L_('没有选择图片'));
        }
    }

    /**
     * 外卖订单评价
     * @author 张涛
     * @date 2020/06/16
     */
    public function shopFeedback()
    {
        if (empty($this->_uid)) {
            $this->returnCode(1, array(), L_('请先进行登录！'));
        }
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $now_order = D('Shop_order')->get_order_detail(array('uid' => $this->_uid, 'order_id' => $order_id));

        if (empty($now_order)) {
            $this->returnCode(1, array(), L_('当前订单不存在！'));
        }
        if (empty($now_order['paid'])) {
            $this->returnCode(1, array(), L_('当前订单未付款！无法评论'));
        }
        if ($now_order['status'] < 2) {
            $this->returnCode(1, array(), L_('当前订单未消费！无法评论'));
        }
        if ($now_order['status'] == 3) {
            $this->returnCode(1, array(), L_('当前订单已评论'));
        }

        $goods = [];
        if (isset($now_order['info'])) {
            $list = array();
            $goods_ids = array();
            foreach ($now_order['info'] as $row) {
                if (!in_array($row['goods_id'], $goods_ids)) {
                    $goods_ids[] = $row['goods_id'];
                    $list[] = $row;
                    $goods[] = [
                        'goods_id' => $row['goods_id'],
                        'name' => $row['name']
                    ];
                }
            }
            $now_order['info'] = $list;
        }
        $arr = [
			'feedback_score_add' => $this->config['feedback_score_add'] > 0 ? $this->config['feedback_score_add'] : 0,
            'order_id' => $now_order['order_id'] ?? 0,
            'is_pick_in_store' => $now_order['is_pick_in_store'],
            'info' => $goods
        ];
        $this->returnCode(0, $arr);
    }

    /**
     * 提交评价
     * @author 张涛
     * @date 2020/06/16
     */
    public function addComment()
    {
        
        if (empty($this->_uid)) {
            $this->returnCode(1, array(), L_('请先进行登录！'));
        }
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $goods_ids = isset($_POST['goods_ids']) ? $_POST['goods_ids'] : 0;
        $score = isset($_POST['store_score']) ? $_POST['store_score'] : 5;
        $comment = isset($_POST['store_comment']) ? htmlspecialchars(trim($_POST['store_comment'])) : '';
        $dscore = isset($_POST['deliver_score']) ? $_POST['deliver_score'] : 5;
        $dcomment = isset($_POST['deliver_comment']) ? htmlspecialchars(trim($_POST['deliver_comment'])) : '';
        $reply_pic = isset($_POST['reply_pics']) && is_array($_POST['reply_pics']) ? $_POST['reply_pics'] : [];
        $anonymous = isset($_POST['anonymous'])?intval($_POST['anonymous']):0;
        $now_order = D('Shop_order')->get_order_detail(array('uid' => $this->_uid, 'order_id' => $order_id));
        if (empty($comment) && empty($reply_pic)) {
            $this->returnCode(1, array(), L_('请填写您的宝贵意见！'));
        }
        if (empty($now_order)) {
            $this->returnCode(1, array(), L_('当前订单不存在！'));
        }
        if (empty($now_order['paid'])) {
            $this->returnCode(1, array(), L_('当前订单未付款！无法评论'));
        }
        if ($now_order['status'] < 2) {
            $this->returnCode(1, array(), L_('当前订单未消费！无法评论'));
        }
        if ($now_order['status'] == 3) {
            $this->returnCode(1, array(), L_('当前订单已评论'));
        }

        $goodsids = array();
        $goods = '';
        $pre = '';
        if (isset($now_order['info'])) {
            foreach ($now_order['info'] as $row) {
                if (!in_array($row['goods_id'], $goodsids)) {
                    $goodsids[] = $row['goods_id'];
                    if (in_array($row['goods_id'], $goods_ids)) {
                        $goods .= $pre . $row['name'];
                        $pre = '#@#';
                    }
                }
            }
        }
        $database_reply = D('Reply');
        $data_reply = [
            'parent_id' => $now_order['store_id'],
            'store_id' => $now_order['store_id'],
            'mer_id' => $now_order['mer_id'],
            'score' => $score,
            'order_type' => 3,
            'order_id' => $order_id,
            'comment' => $comment,
            'uid' => $this->_uid,
            'add_time' => $_SERVER['REQUEST_TIME'],
            'add_ip' => get_client_ip(0),
            'goods' => $goods,
            'deliver_score' => $dscore,
            'reply_pic' => $reply_pic ? implode(';', $reply_pic) : '',
            'anonymous' => $anonymous
        ];
        $login_user = M('User')->where(['uid' => $this->_uid])->find();
        if ($res = $database_reply->data($data_reply)->add()) {
            if ($this->is_weixin_browser) {
                $from_type = 0;
            } elseif ($this->is_wxapp_browser) {
                $from_type = 3;
            } elseif ($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'android') !== false) {
                $from_type = 2;
            } elseif ($this->is_app_browser) {
                $from_type = 1;
            } else {
                $from_type = 0;
            }
            //记录下用户评论行为分析$reply_id,$uid,$content,$business_type,$comment_time,$from_type,$add_ip
            D('User_behavior_reply')->add_record($res, $this->_uid, $comment, 1, time(), $from_type, get_client_ip(0));

            D('Merchant_store')->setInc_shop_reply($now_order['store_id'], $score, $dscore);
            D('Shop_order')->change_status($now_order['order_id'], 3, 'shop');
            D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 8));
            foreach ($goods_ids as $goods_id) {
                if (in_array($goods_id, $goodsids)) {
                    D('Shop_goods')->where(array('goods_id' => $goods_id))->setInc('reply_count', 1);
                    D('Shop_order_detail')->where(array('goods_id' => $goods_id, 'order_id' => $order_id))->save(array('is_goods' => 1));
                }
            }

            if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $now_order['order_id'], 'item' => 2))->find()) {
                D('Deliver_supply')->where(array('order_id' => $now_order['order_id'], 'item' => 2))->save(array('score' => $dscore, 'status' => 6, 'comment_time' => time(), 'comment' => $dcomment, 'userId' => $this->_uid));

                if ($user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'], 'status' => array('neq', 4)))->find()) {
                    $userData = array();
                    $userData['reply_count'] = $user['reply_count'] + 1;
                    $userData['total_score'] = $user['total_score'] + $dscore;
                    $userData['average_score'] = round($userData['total_score'] / $userData['reply_count'], 2);
                    D('Deliver_user')->where(array('uid' => $supply['uid'], 'status' => array('neq', 4)))->save($userData);

                    // 只对系统配送员有效
                    if (C('config.open_deliver_level') && $user['group'] == 1 && $dscore >= 4) {
                        $score = floatval(C('config.deliver_level_good_reply_get_score'));
                        $desc = '用户好评获得' . $score . '积分';
                        $res = D('Deliver_user')->add_score($supply['uid'], $score, $desc, 3, $supply);
                    } elseif (C('config.open_deliver_level') && $user['group'] == 1 && $dscore < 3) {
                        $score = floatval(C('config.deliver_level_poor_reply_deduct_score'));
                        $desc = '用户差评评扣除' . $score . '积分';
                        $res = D('Deliver_user')->deduct_score($supply['uid'], $score, $desc, 5, $supply);
                    }

                }
            }

            if ($this->config['feedback_score_add'] > 0) {
                D('User')->add_extra_score($this->_uid, $this->config['feedback_score_add'], $this->config['shop_alias_name'] . '评论获得' . $this->config['feedback_score_add'] . '个' . $this->config['score_name'],[
                    'mer_id'=>$now_order['mer_id'],
                    'store_id'=>$now_order['store_id'],
                    'order_id'=>$now_order['order_id'],
                    'order_type'=>'shop',
                ]);
                D('Scroll_msg')->add_msg('feedback', $this->_uid, '用户' . str_replace_name($login_user['nickname']) . '于' . date('Y-m-d H:i', $_SERVER['REQUEST_TIME']) . '对' . $this->config['shop_alias_name'] . '评论获得' . $this->config['feedback_score_add'] . '个' . $this->config['score_name']);
            }
            $order_detail_url = $this->config['site_url'].str_replace('appapi.php','wap.php',U('Shop/status',['order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']]));
            $this->returnCode(0, ['url' => $order_detail_url]);
        } else {
            $this->returnCode(1, array(), L_('评论失败'));
        }
    }

    
    /**
     * 提交跑腿评价
     * @author 汪晨
     * @date 2021/09/15
     */
    public function addServiceComment()
    {
        if (empty($this->_uid)) {
            $this->returnCode(1, array(), L_('请先进行登录！'));
        }
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $dscore = isset($_POST['deliver_score']) ? $_POST['deliver_score'] : 5;
        $dcomment = isset($_POST['deliver_comment']) ? htmlspecialchars(trim($_POST['deliver_comment'])) : '';
        $now_order = D('Service_user_publish')->get_order_detail(array('uid' => $this->_uid, 'order_id' => $order_id));
        if (empty($now_order)) {
            $this->returnCode(1, array(), L_('当前订单不存在！'));
        }
        if (empty($now_order['paid'])) {
            $this->returnCode(1, array(), L_('当前订单未付款！无法评论'));
        }
        if ($now_order['status'] < 2) {
            $this->returnCode(1, array(), L_('当前订单未消费！无法评论'));
        }
        if ($now_order['status'] == 3) {
            $this->returnCode(1, array(), L_('当前订单已评论'));
        }

        if (D('Deliver_supply')->field(true)->where(array('order_id' => $now_order['order_id'], 'item' => 3))->find()) {
            D('Deliver_supply')->where(array('order_id' => $now_order['order_id'], 'item' => 3))->save(array('score' => $dscore, 'status' => 6, 'comment_time' => time(), 'comment' => $dcomment, 'userId' => $this->_uid));
        }

        $order_detail_url = $this->config['site_url'].str_replace('appapi.php','wap.php',U('Shop/status',['order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']]));
        $this->returnCode(0, ['url' => $order_detail_url]);
    }

    /**
     * 解析优惠信息
     * @param $discountList
     * @param array $ext
     * @return array
     * @author 张涛
     * @date 2020/06/30
     */
    public function parseDiscountList($discountList, $ext = [])
    {
        if (empty($discountList)) {
            return [];
        }
        //1:平台首单 2：平台满减 3：店铺首单 4：店铺满减 5：平台配送费满减
        $indexDiscounsList = [];
        foreach ($discountList as $v) {
            if ($v['discount_type'] == 1) {
                $indexDiscounsList['system_newuser'] = $v;
            } else if ($v['discount_type'] == 2) {
                $indexDiscounsList['system_minus'] = $v;
            } else if ($v['discount_type'] == 3) {
                $indexDiscounsList['newuser'] = $v;
            } else if ($v['discount_type'] == 4) {
                $indexDiscounsList['minus'] = $v;
            } else if ($v['discount_type'] == 5) {
                if (isset($v['percentage_status']) && $v['percentage_status'] == 1) {
                    $indexDiscounsList['delivery_zhekou'] = $v;
                } else {
                    $indexDiscounsList['delivery'] = $v;
                }
            } else if ($v['discount_type'] == 30) {
                $indexDiscounsList['membership_delivery'] = $v;
            }
        }

        $type = ['newuser' => 1, 'minus' => 2, 'system_newuser' => 3, 'system_minus' => 4, 'delivery' => 5, 'membership_delivery' => 6, 'delivery_zhekou' => 7];
        uksort($indexDiscounsList, function ($k1, $k2) use ($type) {
            if ($type[$k1] == $type[$k2]) {
                return 0;
            } else if ($type[$k1] > $type[$k2]) {
                return 1;
            } else {
                return -1;
            }
        });

        $return['delivery_fee'] = $ext['freight_charge'] ?? 0;
        foreach ($indexDiscounsList as $key => $dval) {
            if ($dval['percentage_status'] == 1) {
                $dis_text = '享X2折';
                $dis_num = floatval($dval['minus'] * 10);
            } elseif ($dval['type'] == 2) {
                $dis_text = '减X2';
                $dis_num = C('config.Currency_symbol') . floatval($dval['minus']);
                if ($dval['minus'] > $return['delivery_fee']) {
                    $outprice = $dval['minus'] - $return['delivery_fee'];
                    $dval['minus'] = $return['delivery_fee'];
                    $dval['plat_money'] = $dval['minus'];

                }
            } else {
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
                        $text = L_('店铺首单满X1' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } else {
                        $text = L_('店铺首X0单满X1' . $dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));

                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
                case 'minus':
                    if ($dval['money'] > 0) {
                        $text = L_('店铺优惠满X1' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } else {
                        $text = L_('店铺优惠' . $dis_text, array('X2' => $dis_num));
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
                case 'system_newuser':
                    if ($dval['order_count'] == 1) {
                        $text = L_('平台首单满X1' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } else {
                        $text = L_('平台首X0单满X1' . $dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
                case 'system_minus':
                    if($dval['money'] > 0){
                        $text = L_('平台优惠满X1' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    }else{
                        $text = L_('平台优惠' . $dis_text, array('X2' => $dis_num));
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
                case 'delivery':
                    if ($dval['order_count'] == 1) {
                        if ($dval['money'] > 0) {
                            $text = L_('首单满X1,配送费' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        } else {
                            $text = L_('首单配送费立减X1', array('X1' => $dis_num));
                        }
                    } elseif ($dval['order_count'] > 1) {
                        if ($dval['money'] > 0) {
                            $text = L_('首X0单满X1,配送费' . $dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        } else {
                            $text = L_('首X0单配送费立减X1', array('X0' => $dval['order_count'], 'X1' => $dis_num));
                        }
                    } else {
                        if ($dval['money'] > 0) {
                            $text = L_('商品满X1,配送费' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        } else {
                            $text = L_('配送费立减X1', array('X1' => $dis_num));
                        }
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
                case 'delivery_zhekou':
                    if ($dval['order_count'] == 1) {
                        $text = L_('首单满X1,配送费' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } elseif ($dval['order_count'] > 1) {
                        $text = L_('首X0单满X1,配送费' . $dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } else {
                        $text = L_('商品满X1,配送费' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    }
                    if($dval['percentage_status'] == 1){
                        $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']*$return['delivery_fee']));
                    }else{
                        $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    }
                    break;
                case 'membership_delivery':
                    if ($dval['money'] > 0) {
                        $text = L_('商品满X1,配送费' . $dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } else {
                        $text = L_('配送费立减X1', array('X1' => $dis_num));
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
            }
        }
        return $new_discount_list;
    }

    /**
     * 未支付订单取消
     * @author 张涛
     * @date 2020/07/31
     */
    public function cancelOrder()
    {
        $orderId = $_POST['order_id'] ?? 0;
        $nowOrder = D('Shop_order')->where(['order_id' => $orderId, 'uid' => $this->_uid])->find();
        if ($nowOrder['paid'] == 0) {
            if ($nowOrder['status'] == 5) {
                $this->returnCode(1, array(), L_('当前订单已取消，请勿重复操作'));
            }
            D('Shop_order')->where(['order_id' => $orderId])->save(['last_time' => time(), 'status' => 5]);
            D("System_order")->save_order_status($nowOrder['order_from'] == '1' ? 'mall' : 'shop', $orderId, 5);
            D('Shop_order_log')->add_log(['order_id' => $orderId, 'status' => 10]);
        }
        $this->returnCode(0, []);
    }

    
    /**
     * 跑腿订单详情
     * @author 汪晨
     * @date 2021/09/16
     */
    public function serviceDemandDetails()
    {
        // if (empty($this->_uid)) {
        //     $this->returnCode(1, array(), L_('请先进行登录！'));
        // }
        $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$_POST['publish_id']))->find();
        $catInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))->field('cat_name,accept_time')->find();
        $publishInfo['cat_name'] = $catInfo['cat_name'];

        if($publishInfo['catgory_type'] == 1){
            $publishInfo['cat_field'] = unserialize($publishInfo['cat_field']);
            foreach ($publishInfo['cat_field'] as $kk => $vv) {
                if($vv['type'] == 7){
                   $publishInfo['address_start'] = $vv['value']['address_start'];
                   $publishInfo['address_start_lng'] = $vv['value']['address_start_lng'];
                   $publishInfo['address_start_lat'] = $vv['value']['address_start_lat'];
                   $publishInfo['address_end'] = $vv['value']['address_end'];
                   $publishInfo['address_end_lng'] = $vv['value']['address_end_lng'];
                   $publishInfo['address_end_lat'] = $vv['value']['address_end_lat'];
                }
            }
            unset($publishInfo['cat_field']['cid']);

            $offer_list = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->select();
            foreach ($offer_list as $key => $value) {
                $offer_list[$key]['msg_list'] = D('Service_offer_message')->where(array('offer_id'=>$value['offer_id']))->field('message,type,add_time')->order('add_time asc')->select();
                $offer_list[$key]['msg_count'] = count($offer_list[$key]['msg_list']);
                if (empty($offer_list[$key]['avatar'])) {
                    // 没有头像取默认头像
                    $offer_list[$key]['avatar'] = C('config.site_url') . '/static/default.png';
                }
            }
            $offer_info = $offer_list;
        }else if($publishInfo['catgory_type'] == 2){
            $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
            if($publishInfo['deliver_type'] == 1){
                $offer_info = D('')->table(array(C('DB_PREFIX').'deliver_supply'=>'ds', C('DB_PREFIX').'deliver_user'=>'du'))->where("ds.order_id= '".$publishInfo['publish_id']."' AND du.uid = ds.uid AND ds.order_from in (4,5)")->field(true)->find();
                if (!empty($offer_info)) {
                    $offer_info['avatar'] = C('config.wechat_share_img') ? C('config.wechat_share_img') : C('config.site_logo');
                }
            }else{
                $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name,u.uid')->find();
            }
            $register = time() - $offer_info['create_time'];
            if($register > 86400){
                // 显示天
                $registers = $register / 86400;
                if((int)$registers < $registers){
                    $register_time = (int)$registers + 1 . '天';
                }else{
                    $register_time = (int)$registers . '天';
                }
            }else{
                // 显示小时
                $registers = $register / 86400;
                if((int)$registers < $registers){
                    $register_time = (int)$registers + 1 . '小时';
                }else{
                    $register_time = (int)$registers . '小时';
                }
            }
            $offer_info['register_time'] = $register_time;

            $offer_record_list = D('service_offer_record')->where(array('publish_id'=>$_POST['publish_id']))->order('rid desc')->select();

            $offer_info['cat_field_info'] = $cat_field_info;
            $offer_info['offer_record_list'] = $offer_record_list;
        }else if($publishInfo['catgory_type'] == 3){
            $catInfo['accept_time']*60*60;
            $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
            if($publishInfo['deliver_type'] == 1){
                $offer_info = D('')->table(array(C('DB_PREFIX').'deliver_supply'=>'ds', C('DB_PREFIX').'deliver_user'=>'du'))->where("ds.order_id='".$publishInfo['publish_id']."' AND du.uid=ds.uid AND ds.order_from=3")->field(true)->find();
                if (!empty($offer_info)) {
                    $offer_info['avatar'] = C('config.wechat_share_img') ? C('config.wechat_share_img') : C('config.site_logo');
                }
            }else{
                $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name,u.uid')->find();
            }
            // 任职时间
            $register = time() - $offer_info['create_time'];
            $register_time = '未知';
            if($register > 86400){
                // 显示天
                $registers = $register / 86400;
                if((int)$registers < $registers){
                    $register_time = (int)$registers + 1 . '天';
                }else{
                    $register_time = (int)$registers . '天';
                }
            }else{
                // 显示小时
                $registers = $register / 86400;
                if((int)$registers < $registers){
                    $register_time = (int)$registers + 1 . '小时';
                }else{
                    $register_time = (int)$registers . '小时';
                }
            }
            $offer_info['register_time'] = $register_time;
            // 配送员等级标签
            if (C('config.open_deliver_level')) {
                $now_level = D('Deliver_user')->get_user_level($offer_info['score']);
                $level_name = $now_level['name'];
            }else{
               $level_name = ''; 
            }
            $offer_info['level_name'] = $level_name;

            $offer_record_list = D('service_offer_record')->where(array('publish_id'=>$_POST['publish_id']))->order('rid desc')->select();
            foreach( $offer_record_list as $k=>$v){
                $offer_record_list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            }

            $offer_info['cat_field_info'] = $cat_field_info;
            $offer_info['offer_record_list'] = $offer_record_list;
        }

        //显示联系配送员
        $publishId = $_POST['publish_id'] ?? 0;
        $imUrl = '';
        $tm = time();
        $expireSecond = 6 * 3600;
        $supply = M('Deliver_supply')->where(['item' => 3, 'order_id' => $publishId])->find();
        if (in_array($supply['deliver_status'], [2, 3, 4]) || ($supply['deliver_status'] == 5 && $supply['finish_time'] + $expireSecond > $tm)) {
            //$imUrl = C('config.site_url') . '/packapp/project_message/pages/shop/groupConversation/index?order_type=paotui&username=user_' . $this->user_session['uid'] . '&order_id=' . $publishId;
            $imUrl = build_im_chat_url('user_' . $this->user_session['uid'],'deliver_'.$supply['uid'],'user2deliver');
        }
        // 我是XX配送员
        $offer_info['site_name'] = '我是 '.C('config.site_name').' 配送员';
        // 订单类型名称
        $offer_info['cat_name'] = $publishInfo['cat_name'];
        // 重量
        if($offer_info['cat_field_info']['weight_tip']){
            $offer_info['cat_field_info']['weight_tip'] = $offer_info['cat_field_info']['weight_tip'];
        }else{
            $offer_info['cat_field_info']['weight_tip'] = $publishInfo['weight_tip'];
        }
        // 购买类型
        if($offer_info['cat_field_info']['buy_type'] == 1){
            $offer_info['cat_field_info']['buy_type'] = '就近购买';
        }else{
            $offer_info['cat_field_info']['buy_type'] = '指定地址';
        }
        // 状态
        $offer_info['cat_field_info']['status'] = $publishInfo['status'];
        if($publishInfo['status']==1){
            $offer_info['cat_field_info']['status_text'] = '待支付';
        }elseif($publishInfo['status']==2){
            $offer_info['cat_field_info']['status_text'] = '待接单';
        }elseif($publishInfo['status']==3){
            $offer_info['cat_field_info']['status_text'] = '已服务';
        }elseif($publishInfo['status']==4){
            $offer_info['cat_field_info']['status_text'] = '订单完成';
        }elseif($publishInfo['status']==5){
            $offer_info['cat_field_info']['status_text'] = '申请退款';
        }elseif($publishInfo['status']==6){
            $offer_info['cat_field_info']['status_text'] = '退款成功';
        }elseif($publishInfo['status']==7){
            $offer_info['cat_field_info']['status_text'] = '评价成功';
        }elseif($publishInfo['status']==8){
            $offer_info['cat_field_info']['status_text'] = '等待取货';
        }elseif($publishInfo['status']==9){
            $offer_info['cat_field_info']['status_text'] = '配送中';
        }elseif($publishInfo['status']==10){
            $offer_info['cat_field_info']['status_text'] = '订单已取消';
        }elseif($publishInfo['status']==11){
            $offer_info['cat_field_info']['status_text'] = '已经过期';
        }elseif($publishInfo['status']==12){
            $offer_info['cat_field_info']['status_text'] = '过期退款';
        }elseif($publishInfo['status']==13){
            $offer_info['cat_field_info']['status_text'] = '退款失败';
        }elseif($publishInfo['status']==2){
            $offer_info['cat_field_info']['status_text'] = '待支付';
        }
        // 在线聊天
        $offer_info['imUrl'] = $imUrl;
        // 聊天消息数量
        $imUrlNumber = 0;
        $count = D('Im_chat_record')->where(['from_id'=>'user_'.$offer_info['uid'],'show_rule'=>0,'is_read'=>0])->count();
        if($count > 0){
            $imUrlNumber = (int)$count;
        }
        $offer_info['imUrlNumber'] = $imUrlNumber;
        $this->returnCode(0, $offer_info);
    }
    
    /**
     * 养老金管理个人中心
     * @return [type] [description]
     */
    public function oldAgeCenter(){
        $login_user = M('User')->where(['uid' => $this->_uid])->find();
        $user_auth = M("User_authentication")->where(['uid'=>$this->_uid, 'authentication_status'=>1])->find();
        $get_date = '';
        if($user_auth['user_id_number']){
            $birthday = substr($user_auth['user_id_number'], 6, 8);
            $sex = substr($user_auth['user_id_number'], -2, 1)%2 != 0 ? 'male' : 'female';
            $age = $sex == 'male' ? 60 : 55;
            $get_date = date('Y-m-d', strtotime($birthday)+$age*365*86400);
        }
        $output = [
            'is_auth' => empty($user_auth) ? 0 : 1,
            'avatar' => $login_user['avatar'],
            'name' => empty($user_auth) ? '未认证' : $user_auth['user_truename'],
            'id_card' => empty($user_auth) ? '--' : $user_auth['user_id_number'],
            'video' => $this->config['site_url'].$this->config['old_age_pension_pcpc_video'],
            'video_desc' => $this->config['old_age_pension_pcpc_desc'],
            'get_date' => $get_date,
            'is_get' => strtotime($get_date) > time() ? 0 : 1,
            'money' => (M("Old_age_pension_records")->where(['uid'=>$this->_uid, 'status'=>['lt', 3]])->sum("money"))/100
        ];
        $this->returnCode(0, $output);
    }

    public function oldAgeList(){
        $start_time = trim(I("post.start"));
        $page = intval(I("post.page"));

        $output = [
            'money' => 0,
            'list' => []
        ];
        $where = [
            'uid' => $this->_uid,
            'status' => ['lt', 3],
        ];
        if($start_time){
            $start_time_int = strtotime($start_time);
            $where['time'] = ['gt', $start_time_int];
            $end_time = strtotime("+1 month", $start_time_int);
            $where['time'] = ['lt', $end_time];
        }
        $list = M("Old_age_pension_records")->where($where)->limit(($page-1)*10, 10)->order('id desc')->select();
        $output['money'] = M("Old_age_pension_records")->where($where)->sum("money");
        foreach ($list as $key => $value) {
            $value['money'] = $value['money']/100;
            $value['date'] = date('Y-m-d H:i', $value['time']);
            $output['list'][] = $value;
        }
        $output['money'] = $output['money'] > 0 ? getFormatNumber($output['money']/100) : 0;
        $this->returnCode(0, $output);
    }

    /**
     * 修改用户昵称和头像
     * @author: zt
     * @date: 2022/11/22
     */
    public function updateInfo()
    {
        $avatar = $_POST['avatar'] ?? '';
        $nickname = $_POST['nickname'] ?? '';
        $uid = $this->_uid;

        if (empty($uid)) {
            $this->returnCode(1, array(), L_('用户未登录'));
        }
        if (empty($avatar) && empty($nickname)) {
            $this->returnCode(1, array(), L_('请检查是否填写了用户信息'));
        }

        $update = [];
        $avatar && $update['avatar'] = $avatar;
        $nickname && $update['nickname'] = $nickname;
        $rs = D('User')->where(['uid' => $uid])->save($update);
        if ($rs !== false) {
            hook::hook_exec('user.after_update', array('type' => 'user', 'uid' => $uid));
            $this->returnCode();
        } else {
            $this->returnCode(1, array(), L_('更新失败请重试'));
        }
    }
    public function getWeiXinInfoTest()
    {
        $detailId = $_POST['detail_id'] ?? '';
        $batchId = $_POST['batch_id'] ?? '';
        import('@.ORG.withdraw.WeixinPayV3');
        $weixinObj = new WeixinPayV3();
        dd($weixinObj->checkWithdrawNew($batchId,$detailId));
    }
}
?>