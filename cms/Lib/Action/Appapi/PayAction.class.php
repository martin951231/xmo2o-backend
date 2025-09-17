<?php

class PayAction extends BaseAction{
	//支付接口
	public function check(){
		if($this->config['site_close'] == 3){
			$this->returnCode('1',array(),$this->config['site_close_reason']);
		}
		$app_version = I('app_version');
		if($app_version<80||empty($app_version)){
			$this->old_check();
		}else{
			$this->new_check();
		}
	}

	/**
	 * @return 版本小于80
	 */
	public function old_check(){
		$ticket = I('ticket', false);
		if ($ticket) {
			$info = ticket::get($ticket, $this->DEVICE_ID, true);
			$this->user_session['uid'] = $info['uid'];
		}
		if(empty($this->user_session)){
			$this->returnCode('20044010');
		}
		$_GET['type'] = I('type');
		$app_version = I('app_version');
		$app_type  = I('app_type');
		$_GET['order_id']   =   I('order_id');
		$_GET['card_id']    =   I('card_id');
		$_GET['coupon_type']    =   I('coupon_type');
		if(!in_array($_GET['type'],array('group','meal','weidian','takeout', 'food', 'foodPad','recharge','appoint','wxapp','shop','store','plat','balance-appoint', 'integral_mall'))){
			$this->returnCode('20045005');
		}

		$group_pay_offline = true;
		if($_GET['type'] == 'group'){
			$now_order = D('Group_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
			if($now_order['order_info']['group_share_num']>0||$now_order['order_info']['pin_num']>0)$group_pay_offline=false;
		}else if($_GET['type'] == 'meal' || $_GET['type'] == 'takeout' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad'){
			$now_order = D('Meal_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']), false, $_GET['type']);
		}else if($_GET['type'] == 'weidian'){
			$now_order = D('Weidian_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
		}else if($_GET['type'] == 'recharge'){
			$now_order = D('User_recharge_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
		}else if($_GET['type'] == 'appoint'){
			$now_order = D('Appoint_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
		}else if($_GET['type'] == 'wxapp'){
			$_GET['notOffline'] = true;
			$now_order = D('Wxapp_order')->get_pay_order($_GET['uid'],intval($_GET['order_id']));
		}else if($_GET['type'] == 'shop'){
			$now_order = D('Shop_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']),false,true);
		}else if($_GET['type'] == 'store'){
			$now_order = D('Store_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']),false,true);
		}else if($_GET['type'] == 'plat'){
			$now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
		}else if($_GET['type'] == 'balance-appoint'){
			$now_order = D('Appoint_order')->get_pay_balace_order($this->user_session['uid'],intval($_GET['order_id']));
		}else if($_GET['type'] == 'mcard'){
            $now_order = D('Membership_card_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
        }else{
			$this->returnCode('20045010');
		}
		if($now_order['error'] == 1){
			if($now_order['url']){
				$this->returnCode('20045010');
			}else{
				$this->returnCode('20045010');
			}
		}
		if(!$now_order['order_info']['order_txt_type']){
			$now_order['order_info']['order_txt_type']  =   '';
		}
		if(!$now_order['order_info']['order_num']){
			if($_GET['type'] == 'appoint'){
				$now_order['order_info']['order_num']   =   1;
			}else{
				$now_order['order_info']['order_num']   =   0;
			}
		}
		if(!$now_order['order_info']['order_price']){
			$now_order['order_info']['order_price']   =   0;
		}
		if(!$now_order['order_info']['delivery_comment']){
			$now_order['order_info']['delivery_comment']   =   0;
		}
		$arr['order_info'] = $order_info =   isset($now_order) ? $now_order['order_info'] : array();
		//得到微信优惠金额,判断用户能否购买此团购
		$cheap_info = array('can_buy'=>true,'can_cheap'=>false,'wx_cheap'=>0);
		if($_GET['type'] == 'group'  && ($this->is_weixin_browser || $this->is_wxapp_browser || $this->is_app_browser)){
			$now_user = D('User')->get_user($this->user_session['uid']);
			$cheap_info['wx_cheap'] = D('Group')->get_group_cheap($order_info['group_id']);
			$cheap_info['wx_cheap'] = $cheap_info['wx_cheap']*$order_info['order_num'];
			if($cheap_info['wx_cheap']){
				$cheap_info['can_cheap'] = true;
			}
		}
		$arr['cheap_info']    =   isset($cheap_info) ? $cheap_info : array();
		//用户信息
		$now_user = D('User')->get_user($this->user_session['uid']);
		if(empty($now_user)){
			$this->returnCode('20045011');
		}
		$now_user = array(
			'uid'=>$now_user['uid'],
			'nickname'=>$now_user['nickname'],
			'now_money'=>strval($now_user['now_money']),
			'score_count'=>$now_user['score_count']
		);
		$arr['now_user']    =   isset($now_user) ? $now_user : array();
		$arr['show_pay_info'] = $_GET['type'] != 'recharge' ? 1 : 0;
		if($_GET['type'] != 'recharge' && $_GET['type'] != 'weidian'&& $_GET['type'] != 'plat'){
			//优惠券
			if(!empty($_GET['card_id'])){
				$now_coupon = D('Member_card_coupon')->get_coupon_by_recordid($_GET['card_id'],$this->user_session['uid']);
				$now_coupon['type']='mer';
			}
			if(!empty($_GET['coupon_id'])){
				$now_coupon = D('System_coupon')->get_coupon_by_id($_GET['coupon_id']);
				$now_coupon['type']='system';
			}
			//商家会员卡余额
			$merchant_balance = D('Member_card')->get_balance($this->user_session['uid'],$order_info['mer_id']);
			$pay_money = $order_info['order_total_money'] - $now_coupon['price'] - $merchant_balance - $now_user['now_money'];
		}else{
			$pay_money = $order_info['order_total_money'];
		}
		//使用积分
		$score_can_use_count=0;
		$score_deducte=0;
		if ($_GET['type'] == 'group'||$_GET['type'] == 'meal'||$_GET['type'] == 'takeout'||$_GET['type'] == 'food'||$_GET['type'] == 'foodPad'||$_GET['type'] == 'plat') {
			$type_ = $_GET['type'];
			// if($order_info['business_type']=='foodshop'){
			// 	$type_ = 'meal';
			// }
			$score_config = D('Config')->field('name,value')->where('tab_id="user_score"')->getField('name,value');
			$user_score_use_condition=$score_config['user_score_use_condition'];
			//$user_score_max_use=D('Percent_rate')->get_max_core_use($order_info['mer_id'],$type_);
			$user_score_max_use=$score_config['user_score_max_use'];
			if($_GET['type']=='group'){
				$group_info = D('Group')->where(array('group_id'=>$order_info['group_id']))->find();
				if($group_info['score_use']){
					if($group_info['group_max_score_use']!=0){
						$user_score_max_use = $group_info['group_max_score_use'];
					}
				}else{
					$user_score_max_use = 0;
				}
			}
			$user_score_use_percent=(float)$score_config['user_score_use_percent'];
			$score_max_deducte=bcdiv($user_score_max_use,$user_score_use_percent,2);
			if($user_score_use_percent>0&&$score_max_deducte>0&&$user_score_use_condition>0&&$now_user['score_count']>0){   //如果设置没有错误
				$total_ = isset($now_coupon['price'])?$order_info['order_total_money']-$now_coupon['price']:$order_info['order_total_money'];
				if ($cheap_info['can_cheap']) {
					$total_-=$cheap_info['wx_cheap'];
				}
				if ($total_>=$user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
					if($total_>$score_max_deducte){                    //判断积分最大抵扣金额是否比这个订单的总额大
						$score_can_use_count = (int)($now_user['score_count']>$user_score_max_use?$user_score_max_use:$now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
						$score_deducte =bcdiv($score_can_use_count,$user_score_use_percent,2);
						$score_deducte = $score_deducte>$total_?$total_:$score_deducte;

					}else{                                                                      //最大可抵扣的金额比总单金额大 只扣掉总单范围内的积分 扣除积分=总单*积分抵扣比例
						$score_can_use_count = ceil($total_*$user_score_use_percent)>(int)$now_user['score_count']?(int)$now_user['score_count']:ceil($total_*$user_score_use_percent);
						$score_deducte = bcdiv($score_can_use_count,$user_score_use_percent,2);
						$score_deducte = $score_deducte>$total_?$total_:$score_deducte;
					}
				}
			}
		}
		$arr['score']['user_score_use_condition'] = isset($score_config['user_score_use_condition'])?intval($score_config['user_score_use_condition']):0;    // 满一百块钱，可以使用积分
		$arr['score']['score_can_use_count'] = isset($score_can_use_count)?intval($score_can_use_count):0;    // 积分可兑换数量
		$arr['score']['score_deducte'] = isset($score_deducte)?rtrim(rtrim(number_format($score_deducte,2,'.',''),'0'),'.'):0;  //积分抵扣
		$arr['score']['score_count'] = isset($now_user['score_count'])?intval($now_user['score_count']):0;    //用户积分总数
        $arr['score_no_use_tips']=0;
        $arr['score_no_use_tips_msg']='';
        $getIntegralPool=D('Config_customization')->getIntegralPool();
        if($getIntegralPool && in_array($_GET['type'],array('group','shop','mall','appoint','store','balance-appoint'))){
            $strBindWhere='uid='.$this->user_session['uid'].' AND village_id>0 AND type in(0,1,2,3) AND vacancy_id>0 AND status<2';
            $user_bind_rooms=D('House_village_user_bind')->where($strBindWhere)->field('pigcms_id,village_id,uid,vacancy_id')->select();
            $room_ids=array();
            if($user_bind_rooms){
                foreach ($user_bind_rooms as $bvv){
                    $room_ids[]=$bvv['vacancy_id'];
                }
                $room_ids=array_unique($room_ids);
            }
            $date_tmp_time=strtotime(date('Y-m-d')) +1;
            if($room_ids){
                $room_ids_str=implode(',',$room_ids);
                $strWhere=' order_type="property" AND is_paid=2 AND is_discard=1 AND room_id in ('.$room_ids_str.') AND village_id>0  AND ( ( service_start_time<='.$date_tmp_time.' AND service_end_time>'.$date_tmp_time.' ) OR service_end_time<='.$date_tmp_time.' )' ;
                $new_pay_order=D('House_new_pay_order')->where($strWhere)->field('*')->order('order_id desc')->find();
                if($new_pay_order && isset($new_pay_order['order_id'])){
                    /*
                    $arr['score']['user_score_use_condition']=0;
                    $arr['score']['score_can_use_count']=0;
                    $arr['score']['score_deducte']=0;
                    $arr['score']['score_count']=0;
                    */
                    $villageRoomBindInfo=$this->getVillageRoomBindInfo($new_pay_order);
                    $arr['score_no_use_tips']=1;
                    $arr['property_order_id']=$new_pay_order['order_id'];
                    $arr['property_room_id']=$new_pay_order['room_id'];
                    $arr['score_no_use_tips_msg']=$villageRoomBindInfo['tips'];
                }
            }
        }
		if($arr['cheap_info']['wx_cheap']){
			$pay_money  =   $pay_money  -   $arr['cheap_info']['wx_cheap'];
		}
		//需要支付的钱
		$arr['now_coupon']    =   isset($now_coupon) ? $now_coupon : 0;
		$arr['show_card']       =   in_array($_GET['type'],array('weidian','wxapp')) ? 0 : 1;
		$arr['merchant_balance']    =   isset($merchant_balance) ? $merchant_balance : 0;
		$arr['pay_money']    =   $pay_money > 0 ? rtrim(rtrim(number_format($pay_money,2,'.',''),'0'),'.') : 0;

		//调出支付方式
		$notOnline = intval($_GET['notOnline']);
		if($_GET['type'] != 'recharge' && $_GET['type'] != 'appoint'){
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


		$merchant = D('Merchant')->get_info($order_info['mer_id']);
		if ($merchant) {
			$notOffline = $merchant['is_close_offline'] == 0 && $merchant['is_offline'] == 1 ? 0 : 1;
		}

		if (isset($_GET['online']) && $_GET['type'] == 'foodPad') {
			$online = isset($_GET['online']) ? intval($_GET['online']) : 1;
			$notOnline = $online ? 0 : 1;
			$notOffline = $online ? 1 : $notOffline;
		}

		$pay_method = D('Config')->get_pay_method($notOnline,$notOffline,true);
		if($_GET['type'] == 'appoint'){
			unset($pay_method['offline']);
		}
		unset($pay_method['weixin']);


		if(C('config.pay_alipay_app_open')){
			if($app_type==1&&$app_version>=65){
				unset($pay_method['alipay']);
			}elseif($app_type==2&&$app_version>=60){
				unset($pay_method['alipay']);
			}
		}

		$pay_offline = D('Percent_rate')->pay_offline($order_info['mer_id'],$_GET['type']);
		if(!$pay_offline||!$group_pay_offline){
			unset($pay_method['offline']);
		}


		if(!empty($pay_method)){
			foreach($pay_method as $k => $v){
				$array[]  =   array(
					'name'  =>  $v['name'],
					'type'  =>  $k,
					'pic'   =>  file_domain().'/upload/pay/'.$k.'.png',
				);
			}
		}
		if ($_GET['type'] == 'takeout' || $_GET['type'] == 'food' || $_GET['type'] == 'foodPad') {
			$order_table = 'Meal_order';
		}else if($_GET['type']=='recharge'){
			$order_table = 'User_recharge_order';
		}else{
			$order_table = ucfirst($_GET['type']).'_order';
		}

		$arr['order_info']['order_price']   =   rtrim(rtrim(number_format($arr['order_info']['order_price'],2,'.',''),'0'),'.');
		$arr['order_info']['order_total_money']   =   rtrim(rtrim(number_format($arr['order_info']['order_total_money'],2,'.',''),'0'),'.');
		$arr['pay_method']  =   isset($array)?$array:array();
		$arr['notOnline'] = $notOnline;
		$arr['notOffline'] = $notOffline;
		$this->returnCode(0,$arr);
	}
    
    public function getVillageRoomBindInfo($new_pay_order=array()){
        $village_id=$new_pay_order['village_id'];
        $room_id=$new_pay_order['room_id'];
        $uid=$new_pay_order['uid'];
        $pigcms_id=$new_pay_order['pigcms_id'];
        $whereRoom=array('pigcms_id'=>$room_id,'village_id'=>$village_id);
        $vacancyRoom=D('House_village_user_vacancy')->where($whereRoom)->field('*')->find();
        $word_data = array();
        $room_diy_name='';
        if ($vacancyRoom) {
            $village_single = M('House_village_single')->where(array('id'=>$vacancyRoom['single_id'],'village_id' => $village_id))->field('id,single_name')->find();
            if($village_single){
                $word_data['single_name'] = $village_single['single_name'];
            }
            $floor_info = M('House_village_floor')->where(['floor_id' => $vacancyRoom['floor_id'],'village_id' => $village_id])->field('floor_id,floor_name')->find();
            if($floor_info){
                $word_data['floor_name'] = $floor_info['floor_name'];
            }
            $village_layer = M('House_village_layer')->where(['id' => $vacancyRoom['layer_id'],'village_id' => $village_id])->field('id,layer_name')->find();
            if($village_layer){
                $word_data['layer_name'] = $village_layer['layer_name'];
            }
            $word_data['room']=$vacancyRoom['room'];
            $room_diy_name = D('House_village')->word_replce_msg($word_data,$village_id);
        }
        $house_village=M('House_village')->where(array('village_id'=>$village_id))->field('village_id,village_name')->find();
        //您的房号（蓝光花园小区1栋2单元2层201）物业费当前处于欠费状态，积分暂时只能用于物业费缴费抵扣，请及时缴清物业费
        //蓝光花园小区1栋2单元2层201房间有欠缴物业费账单，请联系业主缴清物业费才可使用积分抵扣功能
        $whereArr=array('village_id'=>$village_id,'vacancy_id'=>$room_id,'status'=>1,'type'=>array('in',array(0,1,2,3)),'uid'=>$this->user_session['uid']);
        $village_user_bind=D('House_village_user_bind')->where($whereArr)->field('*')->order('pigcms_id desc')->find();
        $returnInfo=array('tips'=>'');
        $tips='';
        if($village_user_bind && ($village_user_bind['type']==0 || $village_user_bind['type']==3)){
            $tips='您的房号（'.$house_village['village_name'].$room_diy_name.'）物业费当前处于欠费状态，积分暂时只能用于物业费缴费抵扣，请及时缴清物业费';
        }else{
            $tips=$house_village['village_name'].$room_diy_name.'房间有欠缴物业费账单，请联系业主缴清物业费才可使用积分抵扣功能';
        }
        //$returnInfo['sql']=D('House_village_user_bind')->getLastSql();
        $returnInfo['tips']=$tips;
        return $returnInfo;
    }
	/**
	 * @return 版本大于80
	 */
	public function new_check(){
		// 判断是否需要登录才能支付
		$need_user = true;
		if($_POST['type'] == 'plat'){
            $now_order = D('Plat_order')->get_pay_order(0, intval($_POST['order_id']));
			
			// 扫码点餐不需登录
            if($now_order['order_info']['business_type']=='foodshop' && $now_order['order_info']['order_info']['order_from'] =='1'){ 	
                $need_user = false;
            }
			
			// 停车缴费不需要登录
			if($now_order['order_info']['business_type']=='house_village_pay' && $now_order['order_info']['order_info']['order_type'] =='park'){
                $need_user = false;
            }
        }   
        if($_POST['type'] == 'store'){
            // 快速买单不需登录
            $need_user = false;
        }
        if($_POST['is_pay_for_others']){
            //微信代付
            $need_user = false;
        }

		$now_user = [];
		if ($need_user || $this->_uid) {
			if ($this->_uid) {
				$this->user_session['uid'] = $this->_uid;
			}else{
				$this->returnCode('20044010');
			}

        	$now_user = D('User')->get_user($this->_uid);        	
			if(empty($now_user)){
				$this->returnCode('20045011');
			}
		}

        $order_type = I('type');
        $app_version = I('app_version');
        $app_type = I('app_type');
        $_GET['order_id'] = I('order_id');
        $merchant_coupon_id = I('merchant_coupon_id');
        $system_coupon_id = I('system_coupon_id');
        $use_score = I('use_score');
        $use_merchant_money = I('use_merchant_money');
        $use_balance_money = I('use_balance_money');
        $use_sys_coupon = I('use_sys_coupon');
        $use_mer_coupon = I('use_mer_coupon');
        $use_qiye_money = I('use_qiye_money');
        $union_mer_id = I('union_mer_id');//联盟商家id
        $is_pay_for_others = I('is_pay_for_others');
        $use_employee_balance = I('use_employee_balance');
        $use_employee_score = I('use_employee_score');
        $arr['only_pay'] = 0;
        if ($is_pay_for_others && in_array($order_type, ['shop', 'mall'])) {
            $this->_uid = D('Shop_order')->where(['order_id' => intval($_GET['order_id'])])->getField('uid');
            $arr['only_pay'] = 1;
			$tempOrder = D('Temp_order_data')->where(['order_id'=>intval($_GET['order_id'])])->find();
			if ($tempOrder) {
				$merchant_coupon_id = $tempOrder['card_id'];
				$system_coupon_id = $tempOrder['coupon_id'];
				$use_mer_coupon = $merchant_coupon_id ? true : false;
				$use_sys_coupon = $system_coupon_id ? true : false;
			} else {
				$use_mer_coupon = false;
				$use_sys_coupon = false;
				$merchant_coupon_id = 0;
				$system_coupon_id = 0;
			}
        }
		if ($order_type == 'integral_mall') {
			$use_score=false;
			$use_merchant_money=false;
			$use_mer_coupon=false;
			$use_sys_coupon=false;
		}

		if (!in_array($order_type, array('group', 'meal', 'weidian', 'takeout', 'food', 'foodPad', 'recharge', 'appoint', 'wxapp', 'shop', 'store', 'plat', 'mall', 'balance-appoint', 'integral_mall','yuedan','ticket','guide','mcard'))) {

			$this->returnCode('20045005');
		}
		if ($now_user && empty($now_user['phone'])) {
			$need_bind_phone = true;

			//跑腿小程序独立支付，不校验手机号
			if($this->user_session['from_device_id'] == 'wxapp_paotui'){
				$need_bind_phone = false;
			}

			if($_POST['type'] == 'plat'){
				$now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
				
				// 扫码点餐不需要绑定手机号
				if($now_order['order_info']['business_type']=='foodshop' && $now_order['order_info']['order_info']['order_from'] =='1'){
					$need_bind_phone = false;
				}
				
				// 停车缴费不需要绑定手机号
				if($now_order['order_info']['business_type']=='house_village_pay' && $now_order['order_info']['order_info']['order_type'] =='park'){
					$need_bind_phone = false;
				}
			
			}

			if($this->config['open_extra_price']){
				$need_bind_phone = false;
			}

			if($_POST['type'] == 'store' ){
				$need_bind_phone = false;
			}

			if(($_POST['type'] == 'shop') && !$this->config['cash_bind_phone']){
				$need_bind_phone = false;
			}

			if($need_bind_phone){
			    fdump_api(['$_POST' => $_POST,'$_SERVER' => $_SERVER,'$now_user' => $now_user],'appapi/errPayCheckLog',1);
				$this->returnCode('10070013');
			}
		}

		$group_pay_offline = true;
		if ($order_type == 'group') {
			$now_order = D('Group_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
			if ($now_order['order_info']['group_share_num'] > 0 || $now_order['order_info']['pin_num'] > 0) $group_pay_offline = false;
            // 是否使用商家会员余额
			if(isset($now_order['order_info']['is_group_combine']) && $now_order['order_info']['is_group_combine']){//优惠组合订单不能使用商家会员卡支付
                $use_merchant_money=false;
            }
		} else if ($order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad') {
			$now_order = D('Meal_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']), false, $order_type);
		} else if ($order_type == 'weidian') {
			$now_order = D('Weidian_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
		} else if ($order_type == 'recharge') {
			$now_order = D('User_recharge_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
		} else if ($order_type == 'appoint') {
			$now_order = D('Appoint_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
		}else if($order_type == 'yuedan'){//郭先森 //约单处理
			$now_order = D('Yuedan_service_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
		}else if ($order_type == 'wxapp') {
			$_GET['notOffline'] = true;
			$now_order = D('Wxapp_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
		} else if ($order_type == 'shop' || $order_type == 'mall') {
			$now_order = D('Shop_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']), false, true);
		} else if ($order_type == 'store') {
			if ($res = M('Store_order')->where(array('order_id' => $_GET['order_id']))->find()) {
				if ($res['uid'] == 0) {
					M('Store_order')->where(array('order_id' => $_GET['order_id']))->setField('uid', $this->user_session['uid']);
				}
			}
			$now_order = D('Store_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']), false, true);
		} else if ($order_type == 'plat') {
			$now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']), false, true);
			//判断订单是否超时未支付
			if(!empty($now_order['order_info']['business_type']) && $now_order['order_info']['business_type'] == 'service' && $now_order['order_info']['order_info']['publish_id'] > 0){
				$now_time = $fetch_time = 0;
				$service_publish = D('Service_user_publish')->where(['publish_id' => $now_order['order_info']['order_info']['publish_id']])->find();
				if($service_publish['catgory_type'] == 2){
					$service_user_publish_buy = M('Service_user_publish_buy')->field('arrival_time_info')->where('publish_id = '. $now_order['order_info']['order_info']['publish_id'])->find();
					$now_time = time();
					$fetch_time = strtotime($service_user_publish_buy['arrival_time_info']);
					if ($service_user_publish_buy['arrival_time']  && $now_time > $fetch_time) {
						$this->returnCode('20045010', [], '订单支付超时！');
					} 
				}else if($service_publish['catgory_type'] == 3){
					$service_user_publish_give = M('Service_user_publish_give')->field('fetch_time')->where('publish_id = '. $now_order['order_info']['order_info']['publish_id'])->find();
					$now_time = time();
					$fetch_time = strtotime($service_user_publish_give['fetch_time']);
					if($now_time > $fetch_time){
						$this->returnCode('20045010', [], '订单支付超时！');
					}  
				}
			}
		} else if ($order_type == 'balance-appoint') {
			$now_order = D('Appoint_order')->get_pay_balace_order($this->user_session['uid'], intval($_GET['order_id']));
		} else if ($order_type == 'integral_mall'){
			$now_order = D('Merchant_integral_mall_goods_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
		} else if($order_type == 'ticket'){
            $now_order = D('Scenic_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        } else if($order_type == 'guide'){
            $now_order = D('Scenic_aguide')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        } else if($order_type == 'spread'){
            $now_order = D('Merchant_spread_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        } else if($order_type == 'mcard'){
            $now_order = D('Membership_card_order')->get_pay_order($this->user_session['uid'],intval($_GET['order_id']));
        }else {
			$this->returnCode('20045010');
		}

		if($now_order['error'] == 1){
			//小程序需要  20049999 来判断订单支付情况，做特殊页面操作
			if(stripos($now_order['msg'],L_('已经支付')) !== false){
				if($this->app_version >= 1295 && $now_order['url']){
					$now_order['url'] = str_replace('/appapi.php','/wap.php',$now_order['url']);
					if(stripos($now_order['url'],'/') === 0){
						$now_order['url'] = $this->config['site_url'].$now_order['url'];
					}
					$this->returnCode('20049998',array('url'=>$now_order['url'],'msg'=>$now_order['msg']));
				}else{
					$this->returnCode('20049999',array(),$now_order['msg']);
				}
			}
			if($now_order['url']){
				$this->returnCode('1001',array(),$now_order['msg']);
			}else{
				$this->returnCode('1001',array(),$now_order['msg']);
			}
		}


        //快店商品判断订单商品和规格是否被删除
        $stockSetting  = D('Merchant_store_shop')->where(['store_id'=>$now_order['order_info']['store_id']])->field('stock_type,reduce_stock_type')->find();
        $stockType = $stockSetting['stock_type'];
        $reduceStockType = $stockSetting['reduce_stock_type'];
		$goodsAll = [];
        if ($order_type == 'shop' || $order_type == 'mall') {
			$goodsIds = [];
            foreach ($now_order['order_info']['order_content'] as $g) {
                $goods_info = M('Shop_goods')->where(['goods_id' => $g['goods_id']])->find();
                if (empty($goods_info) || $goods_info['status'] != 1) {
                    $this->returnCode('1001', array(), L_('您订购商品X1已下架',['X1'=>$goods_info['name']]));
                }
                if ($g['spec_id']) {
                    $spec_values = D('Shop_goods')->format_spec_value($goods_info['spec_value'], $g['goods_id']);
                    if (empty($spec_values) || !isset($spec_values['list'][$g['spec_id']])) {
                        $this->returnCode('1001', array(), L_('您订购商品X1已下架',['X1'=>$g['name']]));
                    }
                }

				$gindex = $g['goods_id'] . '_' . $g['spec_id'];
				if ($goodsAll[$gindex]) {
					$goodsAll[$gindex]['num'] += $g['num'];
				} else {
					$goodsAll[$gindex] = ['goods_id' => $g['goods_id'], 'spec_id' => $g['spec_id'], 'num' => $g['num'], 'store_id' => $g['store_id']];
				}

                $discountDetails = unserialize($now_order['order_info']['discount_detail']);
                if ($discountDetails) {
                    $orderCount = D('Shop_order')->where(array('uid' => $this->user_session['uid'], 'paid' => 1, 'status' => array('not in', '4,5')))->count();
                    $storeOrderCount = D('Shop_order')->where(array('uid' => $this->user_session['uid'], 'store_id' => $now_order['order_info']['store_id'], 'paid' => 1, 'status' => array('not in', '4,5')))->count();
                    foreach ($discountDetails as $d) {
                        if ($d['discount_type'] == 1) {
                            //平台首单、首几单
                            $checkCount = $orderCount;
                        } else if ($d['discount_type'] == 3) {
                            //店铺首单、首几单
                            $checkCount = $storeOrderCount;
                        }
                        if ($d['order_count'] > 0 && $checkCount >= $d['order_count']) {
                            $this->returnCode('1001', array(), $d['name'] . '优惠已失效');
                        }
                    }
                }

				$goodsIds[] = $g['goods_id'];
            }

			$checkRs = D('Shop_goods')->checkBeforePay($goodsIds);
			if ($checkRs['error_no']) {
				$this->returnCode('1001', array(), $checkRs['error_msg']);
			}
        }


		if ($reduceStockType == 0) {
            $_POST['pay_from'] = 'pay';
			foreach ($goodsAll as $ga) {
				$t_return = D('Shop_goods')->check_stock($ga['goods_id'], $ga['num'], $ga['spec_id'], $stockType, $ga['store_id'], false, $this->user_session['uid']);
				if ($t_return['status'] == 0 || $t_return['status'] == 2) {
					$this->returnCode('1001', array(), $t_return['msg']);
				}
			}
		}

        // 景区门票、导游订单未支付，过期时间30分钟，默认关闭状态，订单状态改为5
        if (in_array($order_type, ['ticket', 'guide'])) {
            if ($order_type == 'ticket') {
                $scenic_order = D('Scenic_order')->get_one_order(['order_id' => intval($_GET['order_id'])]);
            } else {
                $scenic_order = D('Scenic_aguide')->get_one_order(['order_id' => intval($_GET['order_id'])]);
            }
            if ($scenic_order['paid'] == 1) {
                $branch = 1800 - ($_SERVER['REQUEST_TIME'] - $scenic_order['add_time']);
                if ($branch < 0) {
                    $scenic_order['order_status'] = 5;
                    if ($order_type == 'ticket') {
                        D('Scenic_order')->save_order(array('order_id' => $scenic_order['order_id']), array('order_status' => 5));
                    } else {
                        M('Scenic_aguide_order')->where(['order_id' => intval($_GET['order_id'])])->save(['rela_status' => 5]);
                    }
                    $this->returnCode('40000049');
                }
            } else if ($scenic_order['paid'] == 2) {
                $this->returnCode('40000050');
            }
        }


		if(!$now_order['order_info']['order_txt_type']){
			$now_order['order_info']['order_txt_type']  =   '';
		}
		if(!$now_order['order_info']['order_num']){
			if($order_type == 'appoint'){
				$now_order['order_info']['order_num']   =   1;
			}else{
				$now_order['order_info']['order_num']   =   0;
			}
		}
		if(!$now_order['order_info']['order_price']){
			$now_order['order_info']['order_price']   =   0;
		}
		if(!$now_order['order_info']['delivery_comment']){
			$now_order['order_info']['delivery_comment']   =   0;
		}
		$order_info['order_name'] = html_entity_decode($now_order['order_info']['order_name']);
		$arr['order_info'] = $order_info = isset($now_order) ? $now_order['order_info'] : array();
		$arr['pay_config']['is_recharge'] = 0 ;
		if($order_info['business_type']=='card_new_recharge'||$order_type=='recharge'){
			$arr['pay_config']['is_recharge']  =1;
		}

		//增加企业预存款返回
        //使用企业预存款支付
        if ($this->config['open_company_advance_deposit'] == 1 && $order_info['business_type'] != 'merchant_user_recharge') {
	        $qiye = D('Qiye')->getQiyeStaffByUid($this->user_session['uid']);
	        $now_user['qiye_money'] = $qiye['qiye_money'] ?? 0;
        }

		//来自商城的活动（拼团、砍价）不允许使用平台/商家优惠券抵扣
        if(($order_info['order_type']=='shop' ||$order_info['order_type']=='mall') && $order_info['order_from']==1 && ($order_info['group_id']>0 || $order_info['bar_id']>0)){
            $order_info['discount_status']=false;
        }
        //商家积分商城 不允许使用平台/商家优惠券抵扣
        if ($order_type == 'integral_mall') {
            $order_info['discount_status']=false;
        }
		if($order_info['mer_id']){
			$now_merchant = D('Merchant')->get_info($order_info['mer_id']);
			$use_discount = true;
			if($order_info['discount_status']===false){
				$use_discount = false;
			}

			//定制只能使用桌台或会员卡折扣
            if(1 == $this->config['is_open_merchant_foodshop_discount'] && $order_info['is_foodshop_new']!=1) {
				if ($order_info['order_info']['mer_table_discount'] && $order_info['order_info']['table_or_vipcard_discount'] == 0) {
					$use_discount = false;
				}
			}

			$use_discount && $arr['user_discount_type'] = $now_merchant['user_discount_type'];

			if($this->config['open_score_discount'] && $use_discount){
				switch($now_merchant['user_discount_type']){
					case 1 :   //优惠
						break;
					case 2 :   //积分
						$use_discount =false;
						break;
					case 3 :   //2选1
						$use_discount =  $_POST['discount_type'] == 'score'?false:true;
						break;
				}
			}else{
				$arr['user_discount_type'] = 0;
			}
		}else{
			$arr['user_discount_type'] = 0;
		}
		//商家小程序时，只允许对该商家自己的业务进行支付
		if($_POST['wxapp_own_mer_id'] && $_POST['wxapp_own_mer_id'] != $order_info['mer_id']){
			$this->returnCodeError(L_('您当前的环境下，不能跨商家支付'));
		}

		//判断商户是否开启了线上支付(包括控制能否使用积分和平台余额)
		$pay_online = 1;
		if($order_type=='group'||$order_type=='shop'||$order_type=='mall'){
			$pay_online = D('Percent_rate')->pay_online($order_info['mer_id']);
		}
		
		//商家自有支付授权
		if(($this->_uid || $order_info['openid']) && $order_info['mer_id'] > 0 && strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false && $_POST['app_type'] == 'packapp' && ($merchant_bind = D('Weixin_bind')->field(true)->where(array('mer_id' => $order_info['mer_id'], 'type' => 0))->find()) && $merchant_bind['service_type_info'] == '2' && $merchant_bind['verify_type_info'] != '-1' && $_POST['page_from'] == 'pay'){

			// 未登录的情况下使用openid
			$openid = $order_info['openid'] ? $order_info['openid'] : '';

			// 查询是否授权过
			$weixin_bind_user_condition = [];
			$weixin_bind_user_condition['appid'] = $merchant_bind['authorizer_appid'];
			$weixin_bind_user_condition['mer_id'] = $merchant_bind['mer_id'];
			if($openid){
				// 未登录
				$weixin_bind_user_condition['plat_openid'] = $openid;
			}else{
				$weixin_bind_user_condition['uid'] = $this->_uid;
			}
			$bindUser = M('Weixin_bind_user')->field(true)->where($weixin_bind_user_condition)->find();

			if (!$bindUser || !$bindUser['openid']) {
				$oauthUrl = $this->config['site_url'] . '/wap.php?c=Packapp&a=merchant_authorizer&authorizer_appid='.$merchant_bind['authorizer_appid'].'&mer_id='.$order_info['mer_id'].'&plat_openid='.$openid.'&refer=' . urlencode($this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $_POST['order_id'] . '&type=' . $_POST['type']); 
        		$this->returnCode(0, ['auth_url' => $oauthUrl]);
			}
		}

		//得到微信优惠金额,判断用户能否购买此团购
		$cheap_info = array('can_buy'=>true,'can_cheap'=>false,'wx_cheap'=>0);
        if($order_type == 'group'  && ($this->is_weixin_browser || $this->is_wxapp_browser || $this->is_app_browser)){
			$cheap_info['wx_cheap'] = D('Group')->get_group_cheap($order_info['group_id']);
			$cheap_info['wx_cheap'] = $cheap_info['wx_cheap']*$order_info['order_num'];
			if($cheap_info['wx_cheap']){
				$cheap_info['can_cheap'] = true;
			}
		}
		$arr['cheap_info']    =   isset($cheap_info) ? $cheap_info : array();

		//用户信息
		if(($this->config['cash_nobind_phone_use_balance']==0 && empty($now_user['phone'])) || $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id']>0 && $now_merchant['sub_mch_sys_pay']!=1){
			$now_user['now_money'] = 0;
		}
		//商户未开启线上支付，不能使用平台余额
		if(!$pay_online){
			$now_user['now_money'] = 0;
		}

		$arr['now_user']    =   isset($now_user) ? $now_user : array();
		$order_info['total_money'] = $order_info['order_total_money'];
        $tmp_order = $order_info;
		if($order_info['order_type']=='store'){
			$tmp_order['total_money']-=$tmp_order['no_discount_money'];
		}else  if($order_info['business_type']=='foodshop' && $order_info['order_info']['can_discount_money']>0){
			$tmp_order['total_money']=$order_info['order_info']['can_discount_money'];
		}
		$pay_infact = 0;
		//不同版本使用不同优惠券规则
		if(($this->config['cash_nobind_phone_use_balance'] || !empty($now_user['phone'])) && $order_type != 'recharge' && $order_info['business_type'] != 'merchant_user_recharge' &&$order_type != 'integral_mall' && $order_type != 'weidian' && (empty($order_info['business_type'])||$order_info['business_type']!='card_new_recharge')) {
			//商家优惠券
			if ($this->DEVICE_ID == 'wxapp') { 
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

			$merchant_balance = 0 ;

			 // 获取联盟卡
            if ($now_order['order_info']['can_use_mer_union_card']) {
                // 使用联盟会员卡
                if ($_GET['union_mer_id']&&$union_mer_id!=$order_info['mer_id']) {
                    // 获取联盟关系
                    $union_mer = D('Card_new')->get_union_merchant($_GET['union_mer_id'], $order_info['mer_id']);
                    if ($union_mer) {
                        $union_card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $_GET['union_mer_id']);
                    }
                }
            }

			$card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->_uid, $order_info['mer_id']);

  			// 使用联盟会员卡(会员卡余额和折扣使用选择的会员卡，优惠券使用本商家的优惠券)
            if ($union_card_info) {
                $card_info['discount'] = $union_card_info['union_card_info'];
                $card_info['card_money'] = $union_card_info['card_money'];
                $card_info['card_money_give'] = $union_card_info['card_money_give'];
            }

			$merchant_balance = $card_info['card_money'] + $card_info['card_money_give'];

			if (!$use_discount ||isset($order_info['discount_status']) && !$order_info['discount_status'] || empty($card_info)||empty($card_info['discount']) || $card_info['discount'] == '0.00') {
				$card_info['discount'] = 10;
			}
			$tmp_order['uid'] = $this->user_session['uid'];
			if($order_type=='shop'||$order_type=='mall'){
				$tmp_order['total_money'] = ($tmp_order['total_money']-$tmp_order['freight_charge']) * $card_info['discount'] / 10 - $cheap_info['wx_cheap']+$tmp_order['freight_charge'];
			}else{
				if ($now_order['order_info']['is_foodshop_new'] == 1) {
					// 新餐饮
					$tmp_order['total_money'] = ($tmp_order['total_money'] - $cheap_info['wx_cheap']);
				}else{
					if ($now_order['order_info']['is_foodshop_new'] == 1) {
						// 新餐饮
						$tmp_order['total_money'] = ($tmp_order['total_money'] - $cheap_info['wx_cheap']);
					}else{
					    // 先折后减
						$tmp_order['total_money'] = ($tmp_order['total_money']) * $card_info['discount'] / 10 - $cheap_info['wx_cheap'];
					}
				}
			}

			//快店部分商品不参加商家优惠券
			if( $order_info['order_type']=='shop' && $tmp_order['can_discount_money']>0){
				$tmp_order['can_discount_money'] = $tmp_order['can_discount_money'] * $card_info['discount'] / 10 ;
			}

			// 优惠券业务类型
			$business_type_coupon = !empty($order_info['business_type']) ? $order_info['business_type'] : $order_type;

			$arr['has_merchant_coupon'] = '1';
			$arr['merchant_coupon_conut'] = '0';//商家优惠券可使用数量
			if ((!isset($order_info['discount_status']) || $order_info['discount_status'])&&$use_mer_coupon && $use_discount ) {
				if($order_type!='mall' && ($order_info['order_type']=='shop'||$order_info['order_type']=='meal')) {
					if($tmp_order['can_discount_money']>0) {
						if (!empty($merchant_coupon_id)) {
							$tmp_coupon = D('Card_new_coupon')->get_coupon_info($merchant_coupon_id);
						} else {
							if (!empty($order_info['business_type'])) {
								$card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, $order_info['business_type']);
							} else {
								$card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform);
							}
							$tmp_coupon = reset($card_coupon_list);

							// 可使用优惠券数量
							$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $card_coupon_list, $business_type_coupon, $platform, 'mer');
							$arr['merchant_coupon_conut'] = $coupon_list_formart['can_use_total'];
						}
						if (!empty($tmp_coupon)) {
							$mer_coupon['had_id'] = $tmp_coupon['id'];
							//$mer_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
							$mer_coupon['order_money'] = $tmp_coupon['order_money'];
							$mer_coupon['discount'] = $tmp_coupon['discount'];
						} else {
							$arr['has_merchant_coupon'] = '0';
							$mer_coupon = array();
						}
					}
				}else{
					
					if (!empty($merchant_coupon_id)) {
	
						$tmp_coupon = D('Card_new_coupon')->get_coupon_info($merchant_coupon_id);
					} else {
						if (!empty($order_info['business_type'])) {
							$card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, $order_info['business_type']);
						} else {
							$card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform);
						}
						$tmp_coupon = reset($card_coupon_list);

						// 可使用优惠券数量
						$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $card_coupon_list, $business_type_coupon, $platform, 'mer');
						$arr['merchant_coupon_conut'] = $coupon_list_formart['can_use_total'];
					}

					if (!empty($tmp_coupon)) {
						$mer_coupon['had_id'] = $tmp_coupon['id'];
						//$mer_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
						$mer_coupon['order_money'] = $tmp_coupon['order_money'];
						$mer_coupon['discount'] = $tmp_coupon['discount'];
					} else {
						$arr['has_merchant_coupon'] = '0';
						$mer_coupon = array();
					}
				}
			}elseif(!$use_mer_coupon){//不使用不代表没有

				if($tmp_order['can_discount_money']>0) {
					if (!empty($order_info['business_type'])) {
						$card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, $order_info['business_type']);
					} else {
						$card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform);
					}
					$arr['has_merchant_coupon'] = !empty($card_coupon_list) ? '1' : '0';

					// 可使用优惠券数量
					$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $card_coupon_list, $business_type_coupon, $platform, 'mer');
					$arr['merchant_coupon_conut'] = $coupon_list_formart['can_use_total'];
				}
			}
			else{
				$arr['has_merchant_coupon'] = '0';
				$mer_coupon = array();
			}

			$tmp_order['total_money'] -= empty($mer_coupon['discount']) ? 0 : $mer_coupon['discount'];
			if($_GET['type'] == 'yuedan'){
				$use_discount = true;
			}
			//平台优惠券
			$arr['has_system_coupon'] = '1';
			$arr['system_coupon_conut'] = '0';//可使用平台优惠券数量
			if ($use_discount && ($tmp_order['total_money'] > 0 || empty($mer_coupon))&&$use_sys_coupon&&(!isset($order_info['discount_status']) || $order_info['discount_status'])) {
				if($_POST['app_version']>=500){
					//$extra_where['is_discount']=0;
				}
                //判断平台满减是否与平台优惠券共享
                $temp_detail = unserialize($tmp_order['discount_detail']);
                $is_system_full_discount_order = D('Shop_order')->is_system_full_discount_order($temp_detail);
				if(!empty($system_coupon_id)){
					$tmp_coupon = D('System_coupon')->get_coupon_info($system_coupon_id);
                    if($tmp_coupon['is_discount']==1){
                        if($tmp_coupon['discount_type']==1){//减免运费
                            $tmp_coupon['discount_money'] =round( $tmp_order['freight_charge'] * (100-$tmp_coupon['discount_value']*10)/100,2);
                        }else{
                            $tmp_coupon['discount_money'] =round( $tmp_order['total_money'] * (100-$tmp_coupon['discount_value']*10)/100,2);
                        }
                    }else{
                        $tmp_coupon['discount_money'] = $tmp_coupon['discount'];
                    }
                    if ($is_system_full_discount_order && $tmp_coupon['is_share_with_discount'] != 1) {
                        $tmp_coupon = [];
                    }
				}else{
					if (!empty($order_info['business_type'])) {
						$now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $this->user_session['phone'], $this->user_session['uid'], $platform, $order_info['business_type'],$extra_where,true,$isOpenMembershipCard);
					} else {
						$now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $this->user_session['phone'], $this->user_session['uid'], $platform,'',$extra_where,true,$isOpenMembershipCard);
					}
                    // 可使用优惠券数量
                    $coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $now_coupon, $business_type_coupon, $platform, 'system');
                    $arr['system_coupon_conut'] = $coupon_list_formart['can_use_total'];
                    if($coupon_list_formart['can_use_coupon_list']){
                        $canUseCouponHadIds = array_column($coupon_list_formart['can_use_coupon_list'], 'had_id');
                        foreach ($now_coupon as $k=>$c){
                            if(!in_array($c['id'], $canUseCouponHadIds)){
                                unset($now_coupon[$k]);
                            }
                        }
                    }else{
                        $now_coupon = [];
                    }
                    //平台优惠券是否和满减共享
                    if ($is_system_full_discount_order) {
                        foreach ($now_coupon as $c) {
                            if ($c['is_share_with_discount'] == 1) {
                                $tmp_coupon = $c;
                                break;
                            }
                        }
                    } else {
                        $tmp_coupon = reset($now_coupon);
                    }
				}

				if($tmp_coupon){
					$system_coupon['had_id'] = $tmp_coupon['id'];
					//	$system_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
					$system_coupon['order_money'] = $tmp_coupon['order_money'];

					$system_coupon['discount'] = $tmp_coupon['discount_money'];
					$system_coupon['is_discount'] = $tmp_coupon['is_discount'];
					$system_coupon['discount_value'] = $tmp_coupon['discount_value'];

					$system_coupon['discount_type'] = $tmp_coupon['discount_type'];
					if($tmp_coupon['discount_type']=='1'){//如果是减免运费,减免的金额不能还大于运费金额
						$system_coupon['discount'] = $order_info['freight_charge']>$system_coupon['discount']?$system_coupon['discount']:$order_info['freight_charge'];
					}
				}else{
					$arr['has_system_coupon'] = '0';
					$system_coupon =array();
				}
			}elseif(!$use_sys_coupon){//不使用不代表没有
				if (!empty($order_info['business_type'])) {
					$now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $this->user_session['phone'], $this->user_session['uid'], $platform, $order_info['business_type'],$extra_where);
				} else {
					$now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $this->user_session['phone'], $this->user_session['uid'], $platform,'',$extra_where);
				}
				$arr['has_system_coupon'] = !empty($now_coupon) ? '1' : '0';

				// 可使用优惠券数量
				$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $now_coupon, $business_type_coupon, $platform, 'system');
				$arr['system_coupon_conut'] = $coupon_list_formart['can_use_total'];
			}
			else{
				$arr['has_system_coupon'] = '0';
				$system_coupon = array();
			}

			if (isset($order_info['discount_status']) && !$order_info['discount_status']) {
				$card_info['discount'] = 10;
			}
			$tmp_order['total_money'] -=$system_coupon['discount'];
		}else{
			$tmp_order['total_money'] = $tmp_order['total_money'] - $cheap_info['wx_cheap'];
			//$score_can_use_count=0;
			//使用积分
		}

        //升级会员价格
        if ($order_info['upgrade_level_price'] > 0) {
            $tmp_order['total_money'] += $order_info['upgrade_level_price'];
        }
        //开通平台会员卡
        if ($order_info['is_open_membership_card']) {
            $tmp_order['total_money'] += $order_info['membership_card_price'];
        }

		if($tmp_order['total_money']<0){
			$tmp_order['total_money']= 0;
		}
		if($order_info['order_type']=='store'){
			$tmp_order['total_money']+=$tmp_order['no_discount_money'];
		}
		$score_can_use_count=0;
		$score_deducte=0;
		if ($order_type == 'group' || $order_type == 'store' || $order_type == 'meal' ||$order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad' || $order_type == 'appoint' || $order_type == 'mall' || $order_type == 'shop' || $order_type == 'balance-appoint' || ($order_info['order_type'] == 'plat' && $order_info['pay_system_score'])) {
			$type_ =$order_type;
			if($order_info['business_type']=='foodshop'){
				$type_ = 'meal';
			}
			if ($type_ == 'balance-appoint') {
				$type_ = 'appoint';
			}

			$user_score_use_condition=$this->config['user_score_use_condition'];
			$user_score_max_use=D('Percent_rate')->get_max_core_use($order_info['mer_id'],$type_,$tmp_order['total_money'],$tmp_order);
            if($order_type=='group'){
				$group_info = D('Group')->where(array('group_id'=>$order_info['group_id']))->find();
				if($group_info['score_use']){
					if($group_info['group_max_score_use']!=0){
						$user_score_max_use = $group_info['group_max_score_use'];
					}
				}else{
					$user_score_max_use = 0;
				}
			}
			$user_score_use_percent=$this->config['user_score_use_percent'];
			$score_max_deducte=$user_score_use_percent ? sprintf('%.2f',$user_score_max_use/$user_score_use_percent) : 0;

			if($user_score_use_percent>0&&$score_max_deducte>0&&$user_score_use_condition>0&&$now_user['score_count']>0){   //如果设置没有错误
				$total_ = $tmp_order['total_money'];

				if ($total_>=$user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
					if($total_>$score_max_deducte){                    //判断积分最大抵扣金额是否比这个订单的总额大
						if(C('config.score_round_two')==1){
							$score_can_use_count = getFormatNumber($now_user['score_count']>$user_score_max_use?$user_score_max_use:$now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
						}else{
							$score_can_use_count = (int)($now_user['score_count']>$user_score_max_use?$user_score_max_use:$now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
						}
						$score_deducte =bcdiv($score_can_use_count,$user_score_use_percent,2);
						$score_deducte = $score_deducte>$total_?$total_:$score_deducte;
					}else{                                                                      //最大可抵扣的金额比总单金额大 只扣掉总单范围内的积分 扣除积分=总单*积分抵扣比例
						if(C('config.score_round_two')==1){
							$score_can_use_count = floatval($total_*$user_score_use_percent)>(float)$now_user['score_count']?(float)$now_user['score_count']:floatval($total_*$user_score_use_percent);
						}else{
							$score_can_use_count = ceil($total_*$user_score_use_percent)>(int)$now_user['score_count']?(int)$now_user['score_count']:ceil($total_*$user_score_use_percent);
						}
						$score_deducte = bcdiv($score_can_use_count,$user_score_use_percent,2);
						$score_deducte = $score_deducte>$total_?$total_:$score_deducte;
					}
				}
			}
		}
        fdump_api([
            'uid' => $now_user['uid'],
            'user_score' => $now_user['score_count'],
            'score_can_use_count' => $score_can_use_count,
            'score_deducte' => $score_deducte,
            'user_score_use_condition' => $user_score_use_condition,
            'user_score_max_use' => $user_score_max_use,
            'user_score_use_percent' => $user_score_use_percent,
            'order_info' => $order_info
        ],'pay/score_use_check',1);
        //物业费积分判断 现在改成所有的缴费都可使用获得积分
        if($_POST['type'] == 'plat'&& in_array($order_info['order_info']['order_type'], array('property','custom','park','gas','electric','water','custom_payment','all'))){
            $score_can_use_count = $order_info['order_info']['score_can_use'];
            $score_deducte = $order_info['order_info']['score_can_pay'];
            $score_percent = $order_info['order_info']['score_percent'];
            if($now_user['score_count']<$score_can_use_count){
                $score_can_use_count = $now_user['score_count'];
                $score_deducte = floor($score_can_use_count/$score_percent);
            }
        }

        // 社区收银台缴费
        if($_POST['type'] == 'plat'&& $order_info['business_type']=='house_village_pay_cashier'){
            $score_info = D(ucfirst($order_info['business_type']).'_order')->get_score($order_info['order_info']['cashier_id'],$now_user);
            $score_can_use_count = $score_info['score_can_use'];
            $score_deducte = $score_info['score_can_pay'];
            $score_percent = $score_info['score_percent'];
            if($now_user['score_count']<$score_can_use_count){
                $score_can_use_count = $now_user['score_count'];
                $score_deducte = floor($score_can_use_count/$score_percent);
            }
        }

		//子商户设置不允许使用平台优惠抵扣
		if($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id']>0 && $now_merchant['sub_mch_discount']!=1||($this->config['cash_nobind_phone_use_balance']==0 && empty($now_user['phone'])) || $now_user['forzen_score'] == 1){
			$score_can_use_count = 0;
			$score_deducte = 0;
		}
		if(!$pay_online){//未开启商户线上支付不能使用积分
			$score_can_use_count = 0;
			$score_deducte = 0;
		}
        $arr['score_no_use_tips']=0;
        $arr['score_no_use_tips_msg']='';
        $getIntegralPool=D('Config_customization')->getIntegralPool();
        if($getIntegralPool && in_array($order_type,array('group','shop','mall','appoint','store','balance-appoint'))){
            $strBindWhere='uid='.$this->user_session['uid'].' AND village_id>0 AND type in(0,1,2,3) AND vacancy_id>0 AND status<2';
            $user_bind_rooms=D('House_village_user_bind')->where($strBindWhere)->field('pigcms_id,village_id,uid,vacancy_id')->select();
            $room_ids=array();
            if($user_bind_rooms){
                foreach ($user_bind_rooms as $bvv){
                    $room_ids[]=$bvv['vacancy_id'];
                }
                $room_ids=array_unique($room_ids);
            }
            $date_tmp_time=strtotime(date('Y-m-d')) +1;
            if($room_ids){
                $room_ids_str=implode(',',$room_ids);
                $strWhere=' order_type="property" AND is_paid=2 AND is_discard=1 AND room_id in ('.$room_ids_str.') AND village_id>0  AND ( ( service_start_time<='.$date_tmp_time.' AND service_end_time>'.$date_tmp_time.' ) OR service_end_time<='.$date_tmp_time.' )' ;
                $new_pay_order=D('House_new_pay_order')->where($strWhere)->field('*')->order('order_id desc')->find();
                if($new_pay_order && isset($new_pay_order['order_id'])){
                    $villageRoomBindInfo=$this->getVillageRoomBindInfo($new_pay_order);
                    $arr['score_no_use_tips']=1;
                    $arr['property_order_id']=$new_pay_order['order_id'];
                    $arr['property_room_id']=$new_pay_order['room_id'];
                    $arr['score_no_use_tips_msg']=$villageRoomBindInfo['tips'];
                }
            }
        }
		//调出支付方式
		$notOnline = intval($_GET['notOnline']);
		if($order_type!= 'recharge' && $order_type != 'appoint' && $order_type != 'store' && !($order_type == 'plat' && $order_info['business_type']=='card_new_recharge')){
			$notOffline = intval($_GET['notOffline']);
		}else{
			$notOffline = 1;
		}

		//********************预定金不允许线下支付*************************//
		if (intval($_GET['isdeposit'])) $notOffline = 1;
		if($order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad'){
			$t_order = D('Meal_order')->get_order_by_id($this->user_session['uid'], intval($_GET['order_id']));
			$true_price = $t_order['total_price'] - $t_order['minus_price'];
			if ($t_order['price'] < $true_price) $notOffline = 1;
		}
		//********************预定金不允许线下支付*************************//

		$pay_offline = D('Percent_rate')->pay_offline($order_info['mer_id'],$order_type);
		$merchant = D('Merchant')->get_info($order_info['mer_id']);
		if ($merchant) {
			$notOffline = $pay_offline && $merchant['is_offline'] == 1 ? 0 : 1;
		}

		if (isset($_GET['online']) && $order_type == 'foodPad') {
			$online = isset($_GET['online']) ? intval($_GET['online']) : 1;
			$notOnline = $online ? 0 : 1;
			$notOffline = $online ? 1 : $notOffline;
		}
		$notOnline = $pay_online?$notOnline:1;//如果商户没有开启线上支付方式，$notOnline=1
        $is_app = false;
        if (strpos($_SERVER['HTTP_USER_AGENT'],'pigcmso2oreallifeapp') !== false || in_array($app_type,[1,2])) {
            $is_app = true;
        }
		$pay_method = D('Config')->get_pay_method($notOnline,$notOffline,true,$is_app);
		if($order_type == 'appoint'){
			unset($pay_method['offline']);
		}
		if($pay_method['weixinapp']){
			$pay_method['weixinapp']['config'] = array_merge($pay_method['weixin']['config'],$pay_method['weixinapp']['config']);
			$pay_method['weixin'] = $pay_method['weixinapp'];
			$pay_method['weixin']['name'] = L_('微信支付');
			unset($pay_method['weixinapp']);
		}
		if(strpos($_SERVER['HTTP_USER_AGENT'],'UnionPay') !== false){
	      unset($pay_method['alipay']);
			}
			if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
	      unset($pay_method['unionpayapp']);
			}
        if($this->is_alipay_browser || $this->is_alipayapp_browser){
            unset($pay_method['weixin']);
        }
		//H5微信支付不展示（可以展示咯， 因为现在客户提了需求，让H5的微信支付也可以用）
		if(!$this->config['pay_weixinh5_open'] && !$this->config['open_wftpay'] && (($_POST['app_type'] == 'packapp' && strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') === false) || $_POST['app_type'] == 'alipayapp')){
            if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'xkyapp') === false){
                unset($pay_method['weixin']);
            }
		}
		if($this->config['pay_weixinh5_open']){
			unset($pay_method['weixinh5']);//如果这里不unset，那就会造成前端显示两个微信支付了， 去掉这个
		}
		
		$arr['pay_config']['balance_disable'] = 0;
		if($this->config['open_allinyun']==1){
			$can_pay_allinyun = true;
			$allinyun = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();

			if($order_info['mer_id'] && $order_type!='recharge'){
				$allinyun_mer =  D('Deposit')->get_merchant_info($order_info['mer_id'],1);
				if( $allinyun_mer['status']==1){
					$can_pay_allinyun = true;
				}else{
					$can_pay_allinyun = false;
				}
			}
			if($allinyun['bizUserId'] && $can_pay_allinyun){
				$pay_method = array();
				$pay_method['allinyun_alipay']['name'] =L_('云商通网关');
				$order_type!='recharge' && $pay_method['allinyun_balance']['name'] =L_('云商通余额');
				$arr['pay_config']['balance_disable'] = 1;
			}
		}
		if(!$pay_offline||!$group_pay_offline){
			unset($pay_method['offline']);
		}
		
		// 堂扫，新餐饮走自己支付方式
		if ($now_order['order_info']['is_foodshop_new']) {
			$pay_method = D('Merchant_store')->get_pay_method($now_order['order_info']['store_id']);
		}
		
		//删除翼支付线下
		unset($pay_method['yzfpay_offline']);
		if($pay_method['yzfpay']){
			$pay_method['yzfpay']['name'] = '翼支付';
		}

		$display_tips = $arr['order_info']['order_name'];
		$isDouyinMini = ($_POST['app_type'] == 'dyapp');
        if (!empty($pay_method)) {
            foreach ($pay_method as $k => $v) {
                if($isDouyinMini){
                    if($k != 'douyin'){
                        continue;
                    }
                }else{
                    if($k == 'douyin'){
                        continue;
                    }
                }

				if($this->DEVICE_ID == 'wxapp'){
                    if (!in_array($k, ['weixin', 'merchantwarrior', 'offline', 'baodi_card', 'scrcu_cash', 'hqpay_wx', 'farmersbankpay']) && strpos($k,'xiantao') === false) {
                        continue;
                    }
				}
				if($this->is_alipayapp_browser){
					if(in_array($k,['weixin','wirecard'])){
						continue;
					}
				}
				if($k == 'scrcu_cash'){// 四川农信
					if(!in_array($_POST['app_type'],['packapp','weixin','wxapp',1,2])){// 四川农信通道 暂不支持app				
						continue;
					}
				}
				if($k == 'farmersbankpay'){// 仪征农商行
					if(!in_array($_POST['app_type'],['wxapp'])){// 仪征农商行 只支持小程序			
						continue;
					}
				}
				if($display_tips == '' && isset($v['config']['service_charge'])){
					$display_tips = L_("以下选项会对最终支付金额产生变化");
				}
				if($k == 'weixinh5'){
					$v['name'] = '微信支付';
				}
				$tt = array(
					'name' => $v['name'],
					'type' => $k,
					'pic' => $this->config['site_url'] . '/static/images/pay_ico/' . $k . '.png',
					'service_charge' => $v['config']['service_charge_open'] && $v['config']['service_charge']  ? $v['config']['service_charge'] : '0',
					'discount_tips' => isset($v['config']['discount_tips']) ? $v['config']['discount_tips'] : '',
					'rates' => isset($v['config']['rates']) ? $v['config']['rates'] : 0,
				);

				$tt['submit_to'] = "other";

				if(C("config.pay_scrcu_cash_open") == '1' && (in_array($_POST['app_type'],['packapp','weixin','wxapp']) && $k == 'weixin')){
					$tt['submit_to'] = "wap";
				}
                if ($tt['type'] == 'ebankpay') {
                    $tt['payBankID'] = '810';
                    $tt['SDKEnv'] = 'ST'; //IT ST SIT SIT2 UAT PROD
                }
				$pay_method_array[] = $tt;
			}
		}
		if ($order_type == 'group') {
			$arr['order_info']['order_price'] = rtrim(rtrim(number_format($arr['order_info']['order_price'], 2, '.', ''), '0'), '.');
		} else {
			$arr['order_info']['order_price'] = rtrim(rtrim(number_format($arr['order_info']['order_total_money'], 2, '.', ''), '0'), '.');
		}

		if (empty($arr['order_info']['img']) && $this->config['wechat_share_img']) {
			$arr['order_info']['img'] = $this->config['wechat_share_img'];
		}

        if ($order_type == 'store' || $order_type == 'recharge' || $order_info['business_type'] == 'card_new_recharge' || $order_type == 'appoint' || $order_type == 'balance-appoint') {
            $arr['order_info']['order_num'] = 1;
            $arr['order_info']['num'] = 1;
        }
        $arr['order_info']['order_total_money'] = rtrim(rtrim(number_format($arr['order_info']['order_total_money'], 2, '.', ''), '0'), '.');
		//订单号
		if(empty($arr['order_info']['real_orderid'])){
			$arr['order_info']['real_orderid'] = $arr['order_info']['order_id'];
			if ($arr['order_info']['business_type']=='foodshop') {
				$arr['order_info']['real_orderid'] = $arr['order_info']['order_info']['real_orderid'];
			}
		}
        if(empty($pay_method_array)){
			$this->returnCode('1001',array(),L_('当前未开启任何支付方式，请联系系统管理员开启'));
		}
        $arr['pay_method'] = isset($pay_method_array) ? $pay_method_array : array();

        //微信代付只显示支付宝和微信
        if ($arr['only_pay'] == 1) {
            foreach ($arr['pay_method'] as $pk => $pv) {
                if (!in_array($pv['type'], ['alipay', 'weixin'])) {
                    unset($arr['pay_method'][$pk]);
                }
            }
            $arr['pay_method'] = array_values($arr['pay_method']);
        }

		//$arr['merchant_balance'] = $merchant_balance;
		if(empty($mer_coupon)){
			$mer_coupon['had_id'] = "";
			//	$mer_coupon['coupon_id'] = "";
			$mer_coupon['order_money'] = "";
			$mer_coupon['discount'] = "";
			$arr['mer_coupon'] = $mer_coupon;
		}else{
			$arr['mer_coupon'] = $mer_coupon;
		}

		if (empty($system_coupon)) {
			$system_coupon['had_id'] = "";
			//	$system_coupon['coupon_id'] = "";
			$system_coupon['order_money'] = "";
			$system_coupon['discount'] = "";
			$arr['system_coupon'] = $system_coupon;
		} else {
			if ($system_coupon['is_discount']) {
				if($system_coupon['order_money'] >0 ){
					$system_coupon['coupon_des'] = L_('满X1享X2折', array('X1' => floatval($system_coupon['order_money']), 'X2' => floatval($system_coupon['discount_value'])));
				}else{
					$system_coupon['coupon_des'] = L_('享X1折', array('X1' => floatval($system_coupon['discount_value'])));
				}
				if($system_coupon['discount_type']=='1'){
					if($system_coupon['order_money'] >0 ){
						$system_coupon['coupon_des'] = L_('满X1配送享X2折', array('X1' => floatval($system_coupon['order_money']), 'X2' => floatval($system_coupon['discount_value'])));
					}else{
						$system_coupon['coupon_des'] = L_('配送享X1折', array('X1' => floatval($system_coupon['discount_value'])));
					}
				}

			} else {
				$des_str = '满X1减X2';
				if($system_coupon['discount_type']==1){
					$des_str = '满X1减运费X2';
				}
				if($system_coupon['discount_type']==2){
					$des_str = '满X1减餐费X2';
				}
				if($system_coupon['order_money'] > 0 ){
					$system_coupon['coupon_des'] =  L_($des_str, array('X1' => C('config.Currency_symbol') . floatval($system_coupon['order_money']), 'X2' => C('config.Currency_symbol') . floatval($system_coupon['discount'])));
				}
				else{
					$system_coupon['coupon_des'] = L_("减X1", array('X1'=>C('config.Currency_symbol') . floatval($system_coupon['discount'])));
				}

			}
			if ($system_coupon['discount']) {
				$system_coupon['discount'] = getFormatNumber($system_coupon['discount']);
			}
			$arr['system_coupon'] = $system_coupon;
		}
//        $sub_mch_sys_pay = isset($now_merchant['sub_mch_sys_pay']) ? $now_merchant['sub_mch_sys_pay'] : 0;//商家设定是否使用平台余额
//        $is_mer_balance_percent = isset($now_merchant['is_balance_percent']) ? $now_merchant['is_balance_percent'] : 0;//商家设定是否在店铺使用余额百分比
//        $mer_balance_percent = isset($now_merchant['balance_percent']) ? $now_merchant['balance_percent'] : 100;//商家设定是否使用余额百分比
        /*if(!empty($now_user['now_money']) && !empty($now_merchant) && !is_null($now_merchant['is_balance_percent'])){
            if($now_merchant['open_sub_mchid']){//开启子商户
                if ($sub_mch_sys_pay && $is_mer_balance_percent && $mer_balance_percent>0 && $mer_balance_percent < 100) {//商家设定使用余额百分比
                    $balance_pay_infact=getFormatNumber($tmp_order['total_money']*$mer_balance_percent/100);
                    if($now_user['now_money']>$balance_pay_infact){
                        $now_user['now_money']=$balance_pay_infact;
                    }
                }else{
                    $now_user['now_money']=0;
                }
            }else{
                if ($is_mer_balance_percent && $mer_balance_percent>0 && $mer_balance_percent < 100) {//商家设定使用余额百分比
                    $balance_pay_infact=getFormatNumber($tmp_order['total_money']*$mer_balance_percent/100);
                    if($now_user['now_money']>$balance_pay_infact){
                        $now_user['now_money']=$balance_pay_infact;
                    }
                }else{
                    $now_user['now_money']=0;
                }
            }
        }*/
		$arr['pay_config']['balance_money'] = empty($now_user['now_money'])?"0":strval($now_user['now_money']);//余额
        if ($order_type == 'group') {
            // 是否使用商家会员余额
            if(isset($now_order['order_info']['is_group_combine']) && $now_order['order_info']['is_group_combine']){//优惠组合订单不能使用商家会员卡支付
                $merchant_balance=0;
            }
        }
        if ($order_type != 'integral_mall') {
			$arr['pay_config']['merchant_money'] = empty($merchant_balance)?"0":strval($merchant_balance); //商家余额
		}

		$arr['pay_config']['total_money'] = strval(floatval($order_info['order_total_money']));
		$arr['pay_config']['order_id'] = $tmp_order['order_id'];
		if(!empty($card_info['discount'])){
			$arr['pay_config']['card_discount'] = $card_info['discount']==10?0:strval(floatval($card_info['discount']));
		}else{
			$arr['pay_config']['card_discount'] = 0;
		}

		$arr['pay_config']['wx_cheap'] = strval($cheap_info['wx_cheap']);
		$arr['pay_config']['merchant_coupon_discount'] = strval($mer_coupon['discount']);
		$arr['pay_config']['system_coupon_discount'] = strval($system_coupon['discount']);
		$arr['pay_config']['score_money'] = strval($score_deducte);
		$arr['pay_config']['score_count'] = strval($score_can_use_count);
		if($order_type == 'plat' && $order_info['business_type'] == 'village_group'){
			$arr['pay_config']['score_count'] = 0;//社区拼团小程序端口不需要使用积分，因为社区拼团前面已经使用过积分了
		}

        //企业预付款
        $arr['pay_config']['qiye_balance_show'] = 0;
        $arr['pay_config']['qiye_balance_money'] = 0;
        $qiyeBalanceMoney = 0;
        if ($this->config['open_company_advance_deposit'] && D('Qiye')->checkTheSameQiye($now_user['uid'], $order_info['mer_id']) &&$order_info['business_type'] != 'merchant_user_recharge') {
            $qiyeBalanceMoney = M('Qiye_staff')->where(['job_number'=>$now_user['uid']])->getField('money');
            $arr['pay_config']['qiye_balance_show'] = 1;
            $arr['pay_config']['qiye_balance_money'] = strval(floatval($qiyeBalanceMoney));
        }

		if($tmp_order['total_money']>0&&$use_score){
			$tmp_order['total_money'] -= $score_deducte;
			if($tmp_order['total_money']>=0){
				$pay_infact += $score_deducte;
			}else{
				$pay_infact += $score_deducte+$tmp_order['total_money'];
			}
		}
		if($tmp_order['total_money']>0&&$use_merchant_money){
			$tmp_order['total_money'] -= $merchant_balance;
			if($tmp_order['total_money']>=0){
				$pay_infact += $merchant_balance;
			}else{
				$pay_infact += $merchant_balance+$tmp_order['total_money'];
			}
		}

		if($tmp_order['total_money']>0&&$use_balance_money){
			$tmp_order['total_money'] -= $now_user['now_money'];
			if($tmp_order['total_money']>=0){
				$pay_infact += $now_user['now_money'];
			}else{
				$pay_infact += $now_user['now_money']+$tmp_order['total_money'];
			}
		}
        if ($tmp_order['total_money'] > 0 && $use_qiye_money) {
            $tmp_order['total_money'] -= $qiyeBalanceMoney;
            if ($tmp_order['total_money'] >= 0) {
                $pay_infact += $qiyeBalanceMoney;
            } else {
                $pay_infact += $qiyeBalanceMoney + $tmp_order['total_money'];
            }
        }

		$employee_balance = 0;
		$employee_score = 0;
		$arr['pay_config']['employee_balance_show'] = 0;
        $arr['pay_config']['employee_balance_money'] = 0;
		$arr['pay_config']['employee_score_show'] = 0;
        $arr['pay_config']['employee_score_money'] = 0;
		if(C('customization.life_tools') && $order_info['mer_id'] > 0  && in_array($order_type, ['mall', 'shop'])){
			$condition = [];
			$condition['mer_id'] = $order_info['mer_id'];
			$condition['status'] = 1;
			$employeeCard = M('Employee_card')->where($condition)->find();
			if($employeeCard){
				$condition = [];
				$condition['uid'] = $now_user['uid'];
				$condition['card_id'] = $employeeCard['card_id'];
				$condition['status'] = 1;
				$employeeCardUser = M('Employee_card_user')->where($condition)->find();
				if($employeeCardUser){

					if($employeeCard['is_balance_pay'] == 1 && isset($employeeCardUser['card_money']) && $employeeCardUser['card_money'] > 0){
						$employee_balance = $employeeCardUser['card_money'];
						$arr['pay_config']['employee_balance_money'] = strval(floatval($employee_balance));
						$arr['pay_config']['employee_balance_show'] = 1;
					}
					
					if($employeeCard['is_score_pay'] == 1 && isset($employeeCardUser['card_score']) && $employeeCardUser['card_score'] > 0){
						$employee_score = $employeeCardUser['card_score'];
						$arr['pay_config']['employee_score_money'] = strval(floatval($employee_score));
						$arr['pay_config']['employee_score_show'] = 1;
					}

					//使用商家员工卡积分支付
					if($use_employee_score && $employee_score > 0 && $tmp_order['total_money'] > 0){
						$tmp_order['total_money'] -= $employee_score;
						if ($tmp_order['total_money'] >= 0) {
							$pay_infact += $employee_score;
						} else {
							$pay_infact += $employee_score + $tmp_order['total_money'];
						}
						
					}

					//使用商家员工卡余额支付
					if($use_employee_balance && $employee_balance > 0 && $tmp_order['total_money'] > 0){
						$tmp_order['total_money'] -= $employee_balance;
						if ($tmp_order['total_money'] >= 0) {
							$pay_infact += $employee_balance;
						} else {
							$pay_infact += $employee_balance + $tmp_order['total_money'];
						}
					}
					
				}
			}
 
		}

		$tmp_order['total_money'] = $tmp_order['total_money'] < 0 ? 0 : floatval($tmp_order['total_money']);
		//定制桌台折扣优惠
		if ($order_type == 'plat') {
			if ($order_info['order_info']['table_or_vipcard_discount'] == 0) {
				$now_ordert = D('Foodshop_order')->get_order_detail(array('order_id' => $order_info['order_info']['order_id']));
				$tablesDiscount = D('Merchant_store_foodshop')->getTableDiscount($order_info['mer_id'], $order_info['store_id'], $order_info['order_info']['order_id'], $now_ordert);
				if ($tablesDiscount != false && $tablesDiscount['mer_discount'] != 0) {
					$arr['pay_config']['tablesDiscount'] = $tablesDiscount['mer_discount'];
					if ($order_info['order_info']['is_book_pay'] == 1) {
						$discount = $order_info['order_info']['mer_table_discount'] / 100;
						$reduce_money = round($order_info['order_info']['can_discount_table_money'] - $order_info['order_info']['can_discount_table_money'] * $discount, 2);
						$tmp_order['total_money'] = sprintf("%.2f", floatval($tmp_order['total_money'] - $reduce_money));
					}
				}
			}
		}
		//================================使用一卡通支付金额================================
		$card_pay_money= 0;
        $internalCardIds = $_POST['internal_card_ids'];
		if($tmp_order['total_money']>0 && !empty($internalCardIds)){
			$card = M('internal_membership_card');
            if(is_string($internalCardIds)){//兼容下字符串
                $internalCardIds = explode(',', $internalCardIds);
            }
			foreach($internalCardIds as $card_id){
				$card_info = $card->where(['card_id'=>$card_id, 'status'=>1, 'uid'=>$this->_uid])->find();
				if (!$card_info) {
					$this->returnCode('1001',array(),L_('会员卡'. $card_id .'不存在'));
				}
				$card_info['balance_money'] = round($card_info['balance_money'] * 100) / 100;
				if ($tmp_order['total_money'] > 0){
					$tmp_order['total_money'] -= $card_info['balance_money'];
					if($tmp_order['total_money']>=0){
						$pay_infact += $card_info['balance_money'];
						$card_pay_money += $card_info['balance_money'];
					}else{
						$pay_infact += $card_info['balance_money']+$tmp_order['total_money'];
						$card_pay_money += $card_info['balance_money']+$tmp_order['total_money'];
					}
				}
			}
		}

		if ($tmp_order['total_money'] < 0){
			$tmp_order['total_money'] = 0;
		}

		$arr['pay_config']['online_pay'] = strval(sprintf("%.2f", $tmp_order['total_money'])) < 0.01 ? 0 : strval(round(floatval($tmp_order['total_money']) * 100) / 100);
		$arr['pay_config']['pay_infact'] = getFormatNumber($pay_infact + $tmp_order['total_money']) < 0.01 ? 0 : getFormatNumber($pay_infact + $tmp_order['total_money']);

		// 堂扫税率
		if ($now_order['order_info']['is_foodshop_new']&&$now_order['order_info']['order_info']['tax_rate_price']>0) {
	        $arr['pay_config']['online_pay'] = getFormatNumber($arr['pay_config']['online_pay'] + $now_order['order_info']['order_info']['tax_rate_price']);
	        $arr['pay_config']['pay_infact'] = getFormatNumber($arr['pay_config']['pay_infact'] + $now_order['order_info']['order_info']['tax_rate_price']);
	    }
		
		/* 默认选中第一个 */
		if(((($this->DEVICE_ID=='wxapp' || $_POST['app_type']=='packapp') && $this->app_version >= 1500) || $this->app_version >= 1510) && empty($_POST['pay_type']) && $pay_method && $_POST['page_from'] == 'pay'){
			$_POST['pay_type'] = $arr['pay_method'][0]['type'];
		}

		$arr['pay_config']['online_pay'] = strval(sprintf("%.2f", $tmp_order['total_money'])) < 0.01 ? 0 : strval(round(floatval($tmp_order['total_money']) * 100) / 100);
		//$arr['pay_config']['pay_infact'] = strval(sprintf("%.2f",floatval($pay_infact+$tmp_order['total_money'])))<0.01?0:strval(sprintf("%.2f",floatval($pay_infact+$tmp_order['total_money'])));
		$arr['pay_config']['pay_infact'] = getFormatNumber($pay_infact + $tmp_order['total_money']) < 0.01 ? 0 : getFormatNumber($pay_infact + $tmp_order['total_money']);



		// 堂扫税率
		if ($now_order['order_info']['is_foodshop_new']&&$now_order['order_info']['order_info']['tax_rate_price']>0) {
	        $arr['pay_config']['online_pay'] = getFormatNumber($arr['pay_config']['online_pay'] + $now_order['order_info']['order_info']['tax_rate_price']);
	        $arr['pay_config']['pay_infact'] = getFormatNumber($arr['pay_config']['pay_infact'] + $now_order['order_info']['order_info']['tax_rate_price']);
	    }

		$_service = 0;//支付手续费
		if(isset($_POST['pay_type']) && $pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1' && $pay_method[$_POST['pay_type']]['config']['service_charge'] > 0){
			$service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
			$_service = $arr['pay_config']['online_pay']*($service_charge/100);
		}

		$arr['pay_config']['pay_infact_old'] = getFormatNumber($arr['pay_config']['pay_infact']);
		$arr['pay_config']['pay_infact'] = getFormatNumber(($arr['pay_config']['pay_infact']+$_service)*100/100);
		$arr['pay_config']['online_pay'] = getFormatNumber(($arr['pay_config']['online_pay']+$_service)*100/100);
		$arr['pay_config']['display_tips'] = $display_tips;
		$arr['pay_config']['pay_infact'] = (string)$arr['pay_config']['pay_infact'];
		$arr['pay_config']['card_pay_money'] = getFormatNumber($card_pay_money*100/100);
        /*预售标志*/
        $arr['book_status']=0;
        if($arr['order_info']['book_id']>0){
            $arr['book_status']=1;
            $arr['pay_config']['pay_infact']=strval(floatval($order_info['order_total_money']));
            $arr['pay_config']['pay_infact_old']=strval(floatval($order_info['order_total_money']));
            /*不要折扣*/
            $arr['pay_config']['card_discount']=0;
            $arr['pay_config']['merchant_coupon_discount']="";
            $arr['pay_config']['system_coupon_discount']="";

            if($arr['pay_config']['online_pay'] > 0){
                /*如果选中时选择支付方式一栏时，给的是预售的线上支付价格*/
                $arr['pay_config']['online_pay']=strval(floatval($order_info['order_total_money']));
            }else{
                /*如果选择商家或者平台卡支付时预售的价格覆盖上*/
                $arr['pay_config']['offline_pay']=strval(floatval($order_info['order_total_money']));
            }
        }
        if ($order_type == 'mcard') {
            $arr['book_status'] = 1;
        }

		foreach ($arr['pay_method'] as $_k => $method) {
			if(isset($method['rates']) && $method['rates'] > 0){
				$arr['pay_method'][$_k]['discount_tips'] = '（'.$this->config['Currency_symbol'].$arr['pay_config']['online_pay'].'*汇率'.$method['rates'].'=￥'.getFormatNumber($arr['pay_config']['online_pay']*$method['rates']).'）';
			}
		}

		foreach ($arr['pay_method'] as $_k => $method) {
			if(isset($method['rates']) && $method['rates'] > 0){
				$arr['pay_method'][$_k]['discount_tips'] = '（$'.$arr['pay_config']['online_pay'].'*汇率'.$method['rates'].'=￥'.getFormatNumber($arr['pay_config']['online_pay']*$method['rates']).'）';
			}
		}

		//为了达到快店后续在详情页也能发起付款，使用优惠券，在这里先同步保存优惠券
		if($order_type == 'shop'){
			$data_shop_order = [];
			if($arr['mer_coupon']['had_id']){
				$data_shop_order['card_id'] = $arr['mer_coupon']['had_id'];
				$data_shop_order['card_price'] = $arr['mer_coupon']['discount'];
			}else{
				$data_shop_order['card_id'] = 0;
				$data_shop_order['card_price'] = 0;
			}
			if($arr['system_coupon']['had_id']){
				$data_shop_order['coupon_id'] = $arr['system_coupon']['had_id'];
				$data_shop_order['coupon_price'] = $arr['system_coupon']['discount'];
			}else{
				$data_shop_order['coupon_id'] = 0;
				$data_shop_order['coupon_price'] = 0;
			}
			M('Shop_order')->where(['order_id'=>$arr['order_info']['order_id']])->data($data_shop_order)->save();
		}

        $arr['pay_config']['offline_pay'] = 0;//非在线支付时，需要展示的金额
        if($arr['pay_config']['online_pay'] == 0){
            if (!$use_score) {
                $arr['pay_config']['offline_pay'] = getFormatNumber($arr['pay_config']['pay_infact']);
            } else {
                $arr['pay_config']['offline_pay'] = getFormatNumber($arr['pay_config']['pay_infact'] - $arr['pay_config']['score_money']);//非在线支付时，需要在支付按钮上显示支付金额
            }

        }

        /*预售标志*/
        $arr['book_status']=0;
         if($arr['order_info']['book_id']>0){
             $arr['book_status']=1;
             $arr['pay_config']['pay_infact']=strval(floatval($order_info['order_total_money']));
             $arr['pay_config']['pay_infact_old']=strval(floatval($order_info['order_total_money']));
             /*不要折扣*/
             $arr['pay_config']['card_discount']=0;
             $arr['pay_config']['merchant_coupon_discount']="";
             $arr['pay_config']['system_coupon_discount']="";

             if($arr['pay_config']['online_pay'] > 0){
                 /*如果选中时选择支付方式一栏时，给的是预售的线上支付价格*/
                 $arr['pay_config']['online_pay']=strval(floatval($order_info['order_total_money']));
             }else{
                 /*如果选择商家或者平台卡支付时预售的价格覆盖上*/
                 $arr['pay_config']['offline_pay']=strval(floatval($order_info['order_total_money']));
             }
         }

        if ($order_type == 'spread') {
            $arr['book_status'] = 1;
        }

        if ($order_type == 'mcard') {
            $arr['book_status'] = 1;
        }

		if(C('customization.pay_off_coupon')){
			$arr['order_info']['pay_merchant_coupon'] = false;
			$arr['order_info']['pay_system_coupon'] = false;
		}

		//充值一卡通使用此接口, 如果是充值一卡通, 关闭所有抵扣方式
		if ($order_type == 'store'){
			$store_order = M('store_order')->where(array('order_id' => intval($_GET['order_id'])))->find();
			if($store_order['from_plat'] == 4){
				$arr['pay_config']['is_recharge'] = 1;
			}
		}
		$this->returnCode(0, $arr);
	}

	//微信同步回调页面
	public function app_weixin_back(){

		if (C('config.pay_ebankpay_open')) {
			file_get_contents(C('config.site_url') . '/source/wap_ebank.php'); //日照银行支付
		}
		
		$ticket = I('ticket', false);
		$sPayStatus =   I('pay_status',1);
		if ($ticket) {
			$info = ticket::get($ticket, $this->DEVICE_ID, true);
			$this->user_session['uid'] = $info['uid'];
		}
		$_GET['order_id']   =   I('order_id');
		$_GET['order_type']   =   I('order_type');
		if($_GET['order_type'] == 'mall'){
			$_GET['order_type'] = 'shop';
		}

		switch($_GET['order_type']){
			case 'group':
				// $now_order = D('Group_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Group_order',$_GET['order_id']);
				break;
			case 'meal':
				// $now_order = D('Meal_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Meal_order',$_GET['order_id']);
				break;
			case 'takeout':
			case 'food':
			case 'foodPad':
				// $now_order = D('Meal_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Meal_order',$_GET['order_id']);
				break;
			case 'weidian':
				// $now_order = D('Weidian_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('Weidian_order',$_GET['order_id']);
				break;
			case 'recharge':
				// $now_order = D('User_recharge_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('User_recharge_order',$_GET['order_id']);
				break;
			case 'appoint':
				// $now_order = D('Appoint_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('Appoint_order',$_GET['order_id']);
				break;
			case 'waimai':
				// $now_order = D('Waimai_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('Waimai_order',$_GET['order_id']);
				break;
			case 'wxapp':
				// $now_order = D('Wxapp_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('Wxapp_order',$_GET['order_id']);
				break;
			case 'shop':
				// $now_order = D('Shop_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Shop_order',$_GET['order_id']);
				break;
			case 'store':
				$now_order =$this->get_orderid('Store_order',$_GET['order_id']);
				break;
			case 'plat':
				$now_order =$this->get_orderid('Plat_order',$_GET['order_id']);
				break;
			case 'balance-appoint':
				$now_order = $this->get_orderid('Appoint_order', $_GET['order_id']);
				break;
			case 'integral_mall':
				$now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $_GET['order_id']);
				break;
            case 'ticket':
                $now_order = $this->get_orderid('Scenic_order', $_GET['order_id']);
                break;
            case 'guide':
                $now_order = $this->get_orderid('Scenic_aguide_order', $_GET['order_id']);
                break;
            case 'spread':
                $now_order = $this->get_orderid('Merchant_spread_order', $_GET['order_id']);
            case 'mcard':
                $now_order = $this->get_orderid('Membership_card_order', $_GET['order_id']);
                break;
			default:
				$this->returnCode('20045010');
		}

		if(empty($now_order)){
			$this->returnCode('20046002');
		}
		$now_order['order_type'] = $_SERVER['original_get']['order_type']?$_SERVER['original_get']['order_type']:$_GET['order_type'];
		fdump($now_order,"dengyuanhui---now_order");
        fdump($_GET['order_type'],"dengyuanhui---GETorder_type");
        if((($now_order['paid'] && !in_array($_GET['order_type'],['ticket', 'guide'])) || ($now_order['paid'] == 2 && in_array($_GET['order_type'],['ticket', 'guide'])))&&$now_order['order_type']!='balance-appoint'){
			switch($_GET['order_type']){
				case 'group':
                    if (isset($now_order['is_head']) && $now_order['is_head'] > 0) {
                        $redirctUrl = C('config.site_url').'/wap.php?c=Groupnew&a=invitePin&order_id='.$now_order['order_id'];
                    } else {
                        $redirctUrl = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
                    }
					break;
				case 'meal':
					$redirctUrl = C('config.site_url').'/wap.php?c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'takeout':
					$redirctUrl = C('config.site_url').'/wap.php?c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'food':
				case 'foodPad':
					$redirctUrl = C('config.site_url').'/wap.php?c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'weidian':
					$redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']));
					$this->NoticeWDAsyn($now_order['orderid']);
					break;
				case 'appoint':
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
					break;
				case 'waimai':
					$redirctUrl = C('config.site_url').'/index.php?g=Waimaiwap&c=Index&a=order&order_id='.$now_order['order_id'];
					break;
				case 'recharge':
					$redirctUrl = C('config.site_url').str_replace('/appapi.php?g=Appapi&','/wap.php?',D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']));
					// $redirctUrl = C('config.site_url').'/wap.php?c=My&a=index';
					break;
				case 'wxapp':
					$redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
				case 'shop':
					if($now_order['order_from']!=1){
						$redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='.$now_order['order_id'];
					}else{
						$redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=status&order_id='.$now_order['order_id'];
                        if($now_order['group_id']*1>0 && $now_order['team_id']*1>0){
                            $redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=group_team_detail&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
                        }
					}
					break;
				case 'store':
					if(C('config.pay_success_jump_to_homepage')){
                        $redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';;
                    }else{
						$redirctUrl = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                    }
					break;
				case 'plat':
					$redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
					break;
				case 'balance-appoint':
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
					break;
				case 'integral_mall':
					$redirctUrl = C('config.site_url').'/wap.php?c=Integral_mall&a=order_detail_new&order_id='.$now_order['order_id'];
					break;
                case 'ticket':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=ticket_order_details&order_id='.$now_order['order_id'];
                    break;
                case 'guide':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=aguide_order_list';
                    break;
                case 'spread':
                    $redirctUrl = C('config.site_url') . '/wap.php?c=My&a=my_spread';
		      break;
                case 'mcard':
                    $redirctUrl = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMemberList';
                    break;
			}
			$this->returnCode(0,$redirctUrl);
			exit;
		}
		$now_order['order_id']  =   $now_order['orderid'];
		$import_result = import('@.ORG.pay.Weixin');
		$pay_method = D('Config')->get_pay_method();
		if(empty($pay_method)){
			$this->returnCode('20045012');
		}
		$is_mobile  = 2;
		if($_POST['app_type']=='packapp'){
			$this->config['pay_weixinapp_appid'] = $this->config['pay_weixin_appid'];
			$this->config['pay_weixinapp_mchid'] = $this->config['pay_weixin_mchid'];
			$this->config['pay_weixinapp_key'] = $this->config['pay_weixin_key'];
			$this->config['pay_weixinapp_appsecret'] = $this->config['pay_weixin_appsecret'];
			//自有支付
			if($now_order['mer_id'] > 0){
				$merchant_bind = D('Weixin_bind')->field(true)->where(array('mer_id' => $now_order['mer_id'], 'type' => 0))->find();
				if($merchant_bind){
					if (in_array($_GET['order_type'], array('mall','group', 'meal', 'weidian', 'takeout', 'food', 'foodPad', 'recharge', 'appoint', 'waimai', 'wxapp', 'store', 'shop', 'plat', 'balance-appoint'))) {
			            $mer_id = $now_order['mer_id'];
			        } else {
			            $this->config['merchant_ownpay'] = 0;
			        }
			        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
			        if($this->config['merchant_ownpay'] > 0){
		                if($this->config['city_ownpay']){
			        		$now_merchant = M("Merchant")->where(['mer_id'=> $now_order['mer_id']])->find();
		                    $merchant_ownpay = D('City_ownpay')->field('city_id',true)->where(array('city_id'=>$now_merchant['city_id']))->find();
		                    foreach($merchant_ownpay as $ownKey=>$ownValue){
		                        if($ownKey == 'weixin'){
			                        $ownValueArr = unserialize($ownValue);
			                        if($ownValueArr['open']){
			                            $this->config['pay_weixinapp_appid'] = $ownValueArr['pay_weixin_appid'];
										$this->config['pay_weixinapp_mchid'] = $ownValueArr['pay_weixin_mchid'];
										$this->config['pay_weixinapp_key'] = $ownValueArr['pay_weixin_key'];
										$this->config['pay_weixinapp_appsecret'] = $ownValueArr['pay_weixin_appsecret'];
										$is_own = 6;
			                        }
			                    }
		                    }
		                }
		                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
		                foreach ($merchant_ownpay as $ownKey => $ownValue) {
		                    if($ownKey == 'weixin'){
		                        $ownValueArr = unserialize($ownValue);
		                        if($ownValueArr['open']){
		                            $this->config['pay_weixinapp_appid'] = $ownValueArr['pay_weixin_appid'];
									$this->config['pay_weixinapp_mchid'] = $ownValueArr['pay_weixin_mchid'];
									$this->config['pay_weixinapp_key'] = $ownValueArr['pay_weixin_key'];
									$this->config['pay_weixinapp_appsecret'] = $ownValueArr['pay_weixin_appsecret'];
									$is_own = 6;
		                        }
		                    }
		                }
			        }
				}
			}
			$is_mobile  = 1;
		}else if($this->DEVICE_ID=='wxapp'){
			$this->config['pay_weixinapp_appid']=   $this->config['pay_wxapp_appid'];
			$this->config['pay_weixinapp_mchid']=$this->config['pay_wxapp_mchid'];
			$this->config['pay_weixinapp_key']=$this->config['pay_wxapp_key'];
			$this->config['pay_weixinapp_appsecret']=$this->config['pay_wxapp_appsecret'];
			$is_mobile  = 3;
			
			//商家自有小程序
			if($_POST['wxapp_own_mer_id']>0){
				$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id'=>$_POST['wxapp_own_mer_id'],'bind_type'=>0))->find();
				$bind_user = M('Weixin_bind_user')->where(array('uid'=>$this->user_session['uid'], 'mer_id'=>$_POST['wxapp_own_mer_id']))->find();
				$_SESSION['open_authorize_openid'] = $bind_user['wxapp_openid'];
				$pay_method = array(
					'pay_weixin_appid'=>$wxapp_own_pay['appid'],
					'pay_weixin_key'=>$wxapp_own_pay['wxpay_key'],
					'pay_weixin_mchid'=>$wxapp_own_pay['wxpay_merid'],
					'pay_weixin_appsecret'=>$wxapp_own_pay['appsecret'],
					'is_own'=>1,
				);
			}
			//物业自有小程序
			if($_POST['wxapp_own_village_id']>0){
				$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id'=>$_POST['wxapp_own_village_id'],'bind_type'=>1))->find();
				$bind_user = M('Weixin_bind_user')->where(array('uid'=>$this->user_session['uid'], 'property_id'=>$_POST['wxapp_own_village_id']))->find();
				$_SESSION['open_authorize_openid'] = $bind_user['wxapp_openid'];
				$pay_method = array(
					'pay_weixin_appid'=>$wxapp_own_pay['appid'],
					'pay_weixin_key'=>$wxapp_own_pay['wxpay_key'],
					'pay_weixin_mchid'=>$wxapp_own_pay['wxpay_merid'],
					'pay_weixin_appsecret'=>$wxapp_own_pay['appsecret'],
					'is_own'=>1,
				);
			}
		}else if($this->DEVICE_ID=='wxcapp'){
			$is_mobile = 3;

			$this->config['pay_weixinapp_appid']=   $this->config['pay_wxapp_group_appid'];
			$this->config['pay_weixinapp_mchid']=$this->config['pay_wxapp_group_mchid'];
			$this->config['pay_weixinapp_key']=$this->config['pay_wxapp_group_key'];
			$this->config['pay_weixinapp_appsecret']=$this->config['pay_wxapp_group_appsecret'];
		}else if($this->DEVICE_ID=='wxapp_paotui'){
			$is_mobile = 3;

			$this->config['pay_weixinapp_appid']	=   $this->config['pay_wxapp_paotui_appid'];
			$this->config['pay_weixinapp_mchid']	=	$this->config['pay_wxapp_paotui_mchid'];
			$this->config['pay_weixinapp_key']		=	$this->config['pay_wxapp_paotui_key'];
			$this->config['pay_weixinapp_appsecret']=	$this->config['pay_wxapp_paotui_appsecret'];
		}
		
		if($now_order['order_from']==1 && $now_order['order_type']=='shop'){
			$now_order['order_type']='mall';
		}
        $payOrder = M('Payment_money_order')
            ->where([
                'order_type' => $now_order['order_type'],
                'orderid' => $now_order['orderid']
            ])->find();
		if($payOrder['pay_type'] == 'farmersbankpay'){
            import('@.ORG.pay.Farmersbankpay');
            $now_order['pay_time'] = $payOrder['addtime'];
            $pay_class = new Farmersbankpay($now_order,0,'farmersbankpay', $pay_method[$payOrder['pay_type']]['config'],$this->user_session,1);
            $go_query_param = $pay_class->query();
        }else{
            $payConfig = [
                'pay_weixin_appid'=>$this->config['pay_weixinapp_appid'],
                'pay_weixin_mchid'=>$this->config['pay_weixinapp_mchid'],
                'pay_weixin_key'=>$this->config['pay_weixinapp_key'],
                'pay_weixin_appsecret'=>$this->config['pay_weixinapp_appsecret'],
                'is_own'=>$is_own ? : 0,
            ];
            if($payOrder['pay_config']){
                $payConfig = json_decode($payOrder['pay_config'], true);
            }
            $pay_class = new Weixin($now_order,0,'weixin',$payConfig,$this->user_session,$is_mobile);
            $go_query_param = $pay_class->query_order();
        }
		if($now_order['order_from']==1 && $now_order['order_type']=='mall'){
			$now_order['order_type']='shop';
		}
		if($go_query_param['error'] === 0){
			$payment_where = ['orderid'=>$now_order['order_id'], 'order_type'=>$now_order['order_type']];
            if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
				$payment_where['order_type'] = ['in',['shop','mall']];
			}
			//判断回调金额
			if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
				if($payment_money_order['pay_rate'] > 0){
					$go_query_param['order_param']['pay_money'] = getFormatNumber($go_query_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
					M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
				}
				else{
					M('Payment_money_order')->where($payment_where)->setField('paid_money',$go_query_param['order_param']['pay_money']);
					
					if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
						fdump_sql(array('go_query_param'=>$go_query_param,'payment_money_order'=>$payment_money_order),'weixin_payment_error_back');
						$this->returnCode('10070007');
					}
				}
			}else{
				$this->returnCode('10070007');
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
					$go_query_param['order_param']['orderid'] = $go_query_param['order_param']['order_id'];
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
				case 'wxapp':
					D('Wxapp_order')->after_pay($go_query_param['order_param']);
					break;
				case 'shop':
					$pay_info = D('Shop_order')->after_pay($go_query_param['order_param']);
					fdump_sql(array('pay_info'=>$pay_info,'get_pay_param'=>$go_query_param),'weixin_return_pay_error');
					break;
				case 'store':
					D('Store_order')->after_pay($go_query_param['order_param']);
					break;
				case 'plat':
					D('Plat_order')->after_pay($go_query_param['order_param']);
					break;
				case 'balance-appoint':
					D('Appoint_order')->balance_after_pay($go_query_param['order_param']);
					break;
				case 'integral_mall':
					D('Merchant_integral_mall_goods_order')->after_pay($go_query_param['order_param']);
					break;
                case 'ticket':
                    D('Scenic_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'guide':
                    D('Scenic_aguide')->after_pay($go_query_param['order_param']);
                    break;
                case 'spread':
                    D('Merchant_spread_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'mcard':
                    $pay_info = D('Membership_card_order')->after_pay($go_query_param['order_param']);
                    fdump_sql(array('pay_info'=>$pay_info,'get_pay_param'=>$go_query_param),'weixin_return_pay_error');
                    break;
			}
		}
		switch($_GET['order_type']){
			case 'group':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=My&a=group_order&order_id='.$_GET['order_id'];
				break;
			case 'meal':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Meal&a=detail&orderid='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'takeout':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Takeout&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'food':
			case 'foodPad':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Food&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'weidian':
				$redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
				$this->NoticeWDAsyn($now_order['orderid']);
				break;
			case 'appoint':
			case 'balance-appoint':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=My&a=appoint_order&order_id='.$_GET['order_id'];
				break;
			case 'waimai':
				$redirctUrl = C('config.site_url').'/index.php?g=Waimaiwap&c=Index&a=order&order_id='.$_GET['order_id'];
				break;
			case 'recharge':
				$redirctUrl = C('config.site_url').str_replace('/appapi.php?g=Appapi&','/wap.php?',D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']));
				break;
			case 'wxapp':
				$redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$_GET['order_id'];
				break;
			case 'shop':
				if($now_order['order_from']!=1){
					$redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='.$_GET['order_id'];
				}else{
                    $redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=status&order_id='.$_GET['order_id'];
				}
				break;
			case 'store':
				if(C('config.pay_success_jump_to_homepage')){
					$redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';;
				}else{
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$_GET['order_id'];
				}
				break;
			case 'plat':
				$redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
				break;
			case 'integral_mall':
				$redirctUrl = C('config.site_url').'/wap.php?c=Integral_mall&a=order_detail_new&order_id='.$now_order['order_id'];
				break;
            case 'ticket':
                $redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=ticket_order_details&order_id='.$now_order['order_id'];
                break;
            case 'guide':
                $redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=aguide_order_list';
                break;
            case 'spread':
                $redirctUrl = C('config.site_url') . '/wap.php?c=My&a=my_spread';
                break;
            case 'mcard':
                $redirctUrl = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMemberList';
                break;
		}
		$this->returnCode(0,$redirctUrl);
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

	public function go_pay(){
        $_POST['order_type'] = I('order_type');
        $_POST['order_id'] = I('order_id');
        $_POST['pay_type'] = I('pay_type');
        $_POST['union_mer_id'] = I('union_mer_id');

        $_POST['card_id'] = I('card_id');
        if ($_POST['order_type'] == 'mall') {
            $_POST['order_type'] = 'shop';
        }
        fdump_api($_POST,'pay/appapi_go_pay', 1);
        $_POST['is_pay_for_others'] = I('is_pay_for_others');
        if ($_POST['order_id'] && $this->_uid && in_array($_POST['order_type'], ['shop', 'mall'])) {
            D('Shop_order')->where(['order_id' => intval($_POST['order_id'])])->save(['pay_uid' => $this->_uid]);
        }
        $internalCardIds = $_POST['internal_card_ids'];
        if (!empty($internalCardIds) && is_string($internalCardIds)) {//兼容下字符串
            $_POST['internal_card_ids'] = explode(',', $internalCardIds);
        }
        if ($_POST['is_pay_for_others'] && in_array($_POST['order_type'], ['shop', 'mall'])) {
            if (empty($this->_uid)) {
                $this->returnCode('20044010');
            }
            $payUser = D('User')->where(['uid' => $this->_uid])->field('openid,wxapp_openid')->find();
            $_SESSION['openid'] = $payUser['openid'];
            $_SESSION['wxapp_openid'] = $payUser['wxapp_openid'];
            $_SESSION['pay_uid_for_others'] = $this->_uid;
            $_POST['pay_uid_for_others'] = $this->_uid;
            
            //防止多人同时调起支付
            $cachePayUser =  S('shop_order'.$_POST['order_id']);
            if(empty($cachePayUser)){
                S('shop_order'.$_POST['order_id'],  $this->_uid, 60*2);
            }else if($cachePayUser != $this->_uid){
                $this->returnCode('10100303');
            }
            
            $this->_uid = D('Shop_order')->where(['order_id' => intval($_POST['order_id'])])->getField('uid');
        } else if (in_array($_POST['order_type'], ['shop', 'mall'])) {
			//如果别人存在代付进行中，下单人再去支付判断下别重复支付
			$cachePayUser =  S('shop_order' . $_POST['order_id']);
			if ($cachePayUser !== false && $cachePayUser != $this->_uid) {
				$this->returnCode('10100303');
			}
			S('shop_order' . $_POST['order_id'],  $this->_uid, 60 * 2);
		}


		// 判断是否需要登录才能支付
		$need_user = true;
		if($_POST['order_type'] == 'plat'){
            $now_order = D('Plat_order')->get_pay_order(0, intval($_POST['order_id']));
			
			// 扫码点餐不需登录
            if($now_order['order_info']['business_type']=='foodshop' && $now_order['order_info']['order_info']['order_from'] =='1'){
                $need_user = false;
            }
			
			// 停车缴费不需要登录
            if($now_order['order_info']['business_type']=='house_village_pay' && $now_order['order_info']['order_info']['order_type'] =='park'){
                $need_user = false;
            }

			//充值商家会员卡不能走银联渠道支付
			if ($now_order['order_info']['business_type'] == 'card_new_recharge') {
				C('config.open_chinaums', 0);
				$this->config['open_chinaums'] = 0;
			}
        }   
		
		// 快速买单不需登录
        if($_POST['order_type'] == 'store'){
            $need_user = false;
        }
		
		$now_user = [];
		if ($need_user || $this->_uid) {
			if(empty($this->_uid)){
				$this->returnCode('20044010');
			}
			$now_user = D('User')->get_user($this->_uid);
			if(!$now_user['status']){
				$this->returnCode('20000007');
			}
		}else if($_POST['tmp_ticket']){
			$info = ticket::get($_POST['tmp_ticket'], $this->DEVICE_ID, true);
			$now_third = M('User_tmp_third')->where(['third_id' => $info['uid']])->find();
			$_SESSION['openid'] = $now_third['openid'];
            $_SESSION['wxapp_openid'] = $now_third['wxapp_openid'];
		}

		//wxapp 自有支付

        $app_version = I('app_version');
        $merchant_coupon_id = $_POST['card_id'];
        $system_coupon_id = I('coupon_id');
        $use_score = I('use_score');
        $use_merchant_money = I('use_merchant_money');
        $use_balance_money = I('use_balance_money');
        $use_sys_coupon = I('use_sys_coupon') ? I('use_sys_coupon') : 0;
        $use_mer_coupon = I('use_mer_coupon') ? I('use_mer_coupon') : 0;
        $use_qiye_money = I('use_qiye_money');

		if(!in_array($_POST['order_type'],array('group','meal','weidian','takeout','food','foodPad','recharge','appoint','waimai','wxapp','store','shop','plat','balance-appoint', 'integral_mall','yuedan','ticket','guide','mcard'))){
			$this->returnCode('20045005');
		}
		switch($_POST['order_type']){
			case 'group':
				$now_order = D('Group_order')->get_pay_order($this->_uid,intval($_POST['order_id']));
				break;
			case 'meal':
			case 'takeout':
			case 'food':
			case 'foodPad':
				$now_order = D('Meal_order')->get_pay_order($this->_uid, intval($_POST['order_id']), false,  $_POST['order_type']);
				break;
			case 'weidian':
				$now_order = D('Weidian_order')->get_pay_order($this->_uid,intval($_POST['order_id']));
				break;
			case 'recharge':
				$now_order = D('User_recharge_order')->get_pay_order($this->_uid,intval($_POST['order_id']));
				break;
			case 'appoint':
				$now_order = D('Appoint_order')->get_pay_order($this->_uid,intval($_POST['order_id']));
				break;
			case 'waimai':
				$now_order = D('Waimai_order')->get_pay_order($this->_uid,intval($_POST['order_id']),false,$_POST['order_type']);
				if ($now_order['order_info']['pay_type'] !== $_POST['pay_type']) {
					$this->returnCode('20045010');
				}
				break;
			case 'wxapp':
				$now_order = D('Wxapp_order')->get_pay_order(0,intval($_POST['order_id']));
				break;
			case 'store':
				$now_order = D('Store_order')->get_pay_order($this->_uid, intval($_POST['order_id']));
				$now_order['order_info']['openid'] && $_SESSION['openid'] = $now_order['order_info']['openid'];
				break;
			case 'shop':
				$now_order = D('Shop_order')->get_pay_order($this->_uid,intval($_POST['order_id']));
				break;
			case 'plat':
				$now_order = D('Plat_order')->get_pay_order($this->_uid, intval($_POST['order_id']));
				break;
			case 'balance-appoint':
				$now_order = D('Appoint_order')->get_pay_balace_order($this->_uid,intval($_POST['order_id']));
				break;
			case 'yuedan':
				$now_order = D('Yuedan_service_order')->get_pay_order($this->_uid,intval($_POST['order_id']));
				break;
			case 'integral_mall':
				$now_order = D('Merchant_integral_mall_goods_order')->get_pay_order($this->_uid,intval($_POST['order_id']));
				break;
            case 'ticket':
                //景区门票订单
                $now_order = D('Scenic_order')->get_pay_order($this->_uid,intval($_POST['order_id']));
                break;
            case 'guide':
                //导游
                $now_order = D('Scenic_aguide')->get_pay_order($this->_uid, intval($_POST['order_id']));
                break;
            case 'spread':
                $now_order = D('Merchant_spread_order')->get_pay_order($this->_uid, intval($_POST['order_id']));
                break;
            case 'mcard':
                //平台会员卡
                $now_order = D('Membership_card_order')->get_pay_order($this->_uid, intval($_POST['order_id']));
                break;
			default:
				$this->returnCode('20045010');
		}
        if($now_order['error'] == 1){
            $this->returnCode('20020014');
        }
        if (!in_array($_POST['order_type'], ['ticket'])) {
            if ($now_order['order_info']['paid'] == 1) {
                $this->returnCode('20130037');
            }
        }

        $order_info = $now_order['order_info'];
        if (in_array($_POST['order_type'], ['ticket', 'guide'])) {
            if ($_POST['order_type'] == 'ticket') {
                $scenic_order = D('Scenic_order')->get_one_order(['order_id' => intval($_GET['order_id'])]);
            } else {
                $scenic_order = D('Scenic_aguide')->get_one_order(['order_id' => intval($_GET['order_id'])]);
            }
            if ($scenic_order['paid'] == 1) {
                $branch = 1800 - ($_SERVER['REQUEST_TIME'] - $scenic_order['add_time']);
                if ($branch < 0) {
                    $scenic_order['order_status'] = 5;
                    if ($_POST['order_type'] == 'ticket') {
                        D('Scenic_order')->save_order(array('order_id' => $scenic_order['order_id']), array('order_status' => 5));
                    } else {
                        M('Scenic_aguide_order')->where(['order_id' => intval($_GET['order_id'])])->save(['rela_status' => 5]);
                    }
                    $this->returnCode('40000049');
                }
            } else if ($scenic_order['paid'] == 2) {
                $this->returnCode('40000050');
            }
        }

		$order_info['total_money'] = $order_info['order_total_money'];
		if($order_info['mer_id']){
			$now_merchant = D('Merchant')->get_info($order_info['mer_id']);
			if($now_merchant['status'] != 1){
				$this->returnCode('10070012');
			}
		}
		$use_discount = true;
		
		//商家小程序时，只允许对该商家自己的业务进行支付
		if($_POST['wxapp_own_mer_id'] && $_POST['wxapp_own_mer_id'] != $order_info['mer_id']){
			$this->returnCodeError(L_('您当前的环境下，不能跨商家支付'));
		}
		if($this->config['open_score_discount'] ){
			switch($now_merchant['user_discount_type']){
				case 1 :   //优惠
					$order_info['score_discount_type'] = 2;
					break;
				case 2 :   //积分
					$use_discount =false;
					$order_info['score_discount_type'] = 1;
					break;
				case 3 :   //2选1
					$use_discount =  $_POST['discount_type'] == 'score'?false:true;
					break;
			}
			if($_POST['discount_type']=="score"){
				$order_info['score_discount_type'] = 1;
			}else if($_POST['discount_type']=="coupon"){
				$order_info['score_discount_type'] = 2;
			}
		}
		
		//定制只能使用桌台或会员卡折扣
        if(1 == $this->config['is_open_merchant_foodshop_discount'] && $order_info['is_foodshop_new']!=1) {
			if ($order_info['order_info']['mer_table_discount'] && $order_info['order_info']['table_or_vipcard_discount'] == 0) {
				$use_discount = false;
			}
		}

		if($_POST['order_type'] != 'recharge' && $_POST['order_type'] != 'weidian' && (empty($order_info['business_type'])||$order_info['business_type']!='card_new_recharge')) {
			if($app_version<80||empty($app_version)){
				if(!empty($_POST['card_id'])){
					$now_coupon = D('Member_card_coupon')->get_coupon_by_recordid($_POST['card_id'],$this->_uid);
					if(empty($now_coupon)){
						$this->returnCode('20031001');
					}
					$now_coupon['type']='mer';
				}

				if(!empty($_POST['coupon_id'])){
					$now_coupon = D('System_coupon')->get_coupon_by_id($_POST['coupon_id']);
					if(empty($now_coupon)){
						$this->returnCode('20031001');
					}
					$now_coupon['type']='system';
				}
				//商家会员卡余额
				$merchant_balance = D('Member_card')->get_balance($this->_uid,$order_info['mer_id']);
			}else {
				if ($use_discount && !isset($order_info['discount_status']) || $order_info['discount_status']) {
					if (!empty($merchant_coupon_id)&&$use_mer_coupon) {
						$mer_coupon = D('Card_new_coupon')->get_coupon_info($merchant_coupon_id);
						if (empty($mer_coupon)) {
							$this->returnCode('20031001');
						}
						$now_coupon['card_price'] = $mer_coupon['discount'];
						$now_coupon['merc_id'] = $mer_coupon['id'];
					}
					if (!empty($system_coupon_id) && $use_sys_coupon ) {
						$system_coupon = D('System_coupon')->get_coupon_info($system_coupon_id);
						if (empty($system_coupon)) {
							$this->returnCode('20031001');
						}

						if($system_coupon['is_discount']==1){
							if($system_coupon['discount_type']==1){//减免运费
								$tmp_total_money = $order_info['freight_charge'];
							}elseif($system_coupon['discount_type']==2){//减免餐费
								$tmp_total_money = $order_info['goods_price'];
							}else{
								$tmp_total_money = $order_info['order_total_money'];
							}
							//$system_coupon['discount'] =round( $order_info['total_money'] * (100-$system_coupon['discount_value']*10)/100,2);
							$system_coupon['discount'] =round( $tmp_total_money * (100-$system_coupon['discount_value']*10)/100,2);
						}else{
                            if ($system_coupon['discount_type'] == 1) {
                                $tmp_total_money = $order_info['freight_charge'];
                            } elseif ($system_coupon['discount_type'] == 2) {
                                $tmp_total_money = $order_info['goods_price'];
                            } else {
                                $tmp_total_money = $order_info['order_total_money'];
                            }
                            $system_coupon['discount'] = min($tmp_total_money, $system_coupon['discount']);
                        }

						$now_coupon['coupon_price'] = $system_coupon['discount'] < 0 ? 0 : $system_coupon['discount'];
						$now_coupon['sysc_id'] = $system_coupon['id'];
						$now_coupon['is_discount'] = $system_coupon['is_discount'];
					}
				}

 				// 获取商家联盟会员卡卡
                if ($now_order['order_info']['can_use_mer_union_card']&&$_POST['union_mer_id']&&$_POST['union_mer_id']!=$order_info['mer_id']) {
                    // 获取联盟关系
                    $union_mer = D('Card_new')->get_union_merchant($_POST['union_mer_id'], $order_info['mer_id']);
                    if ($union_mer) {
                        $union_card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->_uid, $_POST['union_mer_id']);
                    }
                }

				$card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->_uid, $order_info['mer_id']);

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
				}elseif($order_info['order_from']==1 && ($order_info['group_id']>0 || $order_info['bar_id']>0)){
					$merchant_balance['card_money'] = 0;
					$merchant_balance['card_give_money'] = $card_info['card_money_give'];
					$merchant_balance['card_discount'] = 10;
				} else {
					$merchant_balance['card_money'] = $card_info['card_money'];
					$merchant_balance['card_give_money'] = $card_info['card_money_give'];

					if(!$use_discount || empty($card_info['discount'])||(isset($order_info['discount_status'])&&!$order_info['discount_status'])){
						$merchant_balance['card_discount'] = 10;
					}else{
						$merchant_balance['card_discount'] = $card_info['discount'];
					}
				}

				$cheap_info = array('can_buy'=>true,'can_cheap'=>false,'wx_cheap'=>0);
                if(($this->is_weixin_browser || $this->is_wxapp_browser || $this->is_app_browser || $this->is_alipay_browser || $this->is_alipayapp_browser)){
                    $cheap_info['wx_cheap'] = D('Group')->get_group_cheap($order_info['group_id']);
                    $cheap_info['wx_cheap'] = $cheap_info['wx_cheap']*$order_info['order_num'];
                    if($cheap_info['wx_cheap']){
                        $cheap_info['can_cheap'] = true;
                    }
                }
				$order_info['total_money'] = $order_info['order_total_money'];
				$tmp_order = $order_info;
				$tmp_order['total_money'] = $tmp_order['total_money'] * $merchant_balance['card_discount'] / 10 - $cheap_info['wx_cheap'];
				$order_info['use_score'] = $use_score?1:0;

				$score_deducte=0;
				if($use_score) {
					$type_ = $_POST['order_type'];
					if ($order_info['business_type'] == 'foodshop') {
						$type_ = 'meal';
					}

					$user_score_use_condition = $this->config['user_score_use_condition'];
					$user_score_max_use = D('Percent_rate')->get_max_core_use($order_info['mer_id'], $type_,$tmp_order['total_money'],$tmp_order);
					if ($_POST['order_type'] == 'group') {
						$group_info = D('Group')->where(array('group_id' => $order_info['group_id']))->find();
						if ($group_info['score_use']) {
							if ($group_info['group_max_score_use'] != 0) {
								$user_score_max_use = $group_info['group_max_score_use'];
							}
						} else {
							$user_score_max_use = 0;
						}
					}
					$user_score_use_percent = $this->config['user_score_use_percent'];
					$score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);
					if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
						$total_ = $tmp_order['total_money'];

						if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
							if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
								$score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
								$score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
								$score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
							} else {                                                                      //最大可抵扣的金额比总单金额大 只扣掉总单范围内的积分 扣除积分=总单*积分抵扣比例
								$score_can_use_count = ceil($total_ * $user_score_use_percent) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : ceil($total_ * $user_score_use_percent);
								$score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
								$score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
							}
						}
					}
				}
				//物业费积分判断 现在改成所有的缴费都可使用获得积分
		        if($_POST['order_type'] == 'plat'&& in_array($order_info['order_info']['order_type'], array('property','custom','park','gas','electric','water','custom_payment'))){
		            $score_can_use_count = $order_info['order_info']['score_can_use'];
		            $score_deducte = $order_info['order_info']['score_can_pay'];
		            $score_percent = $order_info['order_info']['score_percent'];
		            if($now_user['score_count']<$score_can_use_count){
		                $score_can_use_count = $now_user['score_count'];
		                $score_deducte = floor($score_can_use_count/$score_percent);
		            }
		        }
				//积分冻结
				if($now_user['forzen_score'] == 1){
					$score_can_use_count = 0;
					$score_deducte = 0;
				}
				$order_info['score_deducte'] = $score_deducte;
				$order_info['use_merchant_balance'] = $use_merchant_money?1:0;
				$order_info['use_balance'] = $use_balance_money?1:0;
				$order_info['score_used_count'] = $score_can_use_count ?? 0;
			}
		}else{
			$merchant_balance['card_money'] = 0;
			$merchant_balance['card_give_money'] = 0;
			$merchant_balance['card_discount'] = 10;
		}

		//用户信息
		if($app_version<80||empty($app_version)) {
			$_POST['use_score'] = I('use_score');
			$_POST['score_deducte'] = I('score_deducte');
			$_POST['score_used_count'] = I('score_used_count');
			if ($_POST['use_score']) {
				if ($now_user['score_count'] < $_POST['score_used_count']) {
					$this->returnCode('20031003');
				}
				$order_info['score_used_count'] = $_POST['score_used_count'];
				$order_info['score_deducte'] = $_POST['score_deducte'];
			} else {
				$order_info['score_used_count'] = 0;
				$order_info['score_deducte'] = 0;
			}

			$order_info['use_balance'] = 1;
			$order_info['use_merchant_balance'] = 1;
			$merchant_balance = array();
			$merchant_balance['card_discount'] = 10;
		}
        fdump_api([
            'user_score' => $now_user['score_count'],
            'score_can_use_count' => $score_can_use_count ?? 0,
            'score_deducte' => $score_deducte ?? 0,
            'user_score_use_condition' => $user_score_use_condition ?? 0,
            'user_score_max_use' => $user_score_max_use ?? 0,
            'user_score_use_percent' => $user_score_use_percent ?? 0,
            'uid' => $now_user['uid'],
            'order_info' => $order_info,
        ], 'pay/go_pay', 1);
        fdump_api([$now_order, $order_info], 'shop_order/go_pay', 1);
        $sub_mch_sys_pay = isset($now_merchant['sub_mch_sys_pay']) ? $now_merchant['sub_mch_sys_pay'] : 0;//商家设定是否使用平台余额
        $is_mer_balance_percent = isset($now_merchant['is_balance_percent']) ? $now_merchant['is_balance_percent'] : 0;//商家设定是否在店铺使用余额百分比
        $mer_balance_percent = isset($now_merchant['balance_percent']) ? $now_merchant['balance_percent'] : 100;//商家设定是否使用余额百分比
//        if(!empty($now_user['now_money']) && !empty($now_merchant)){
//            if($now_merchant['open_sub_mchid']){//开启子商户
//                if ($sub_mch_sys_pay && $is_mer_balance_percent && $mer_balance_percent>0 && $mer_balance_percent < 100) {//商家设定使用余额百分比
//                    //$now_user['now_money']=getFormatNumber($now_user['now_money']*$mer_balance_percent/100);
//                    $balance_pay_infact=getFormatNumber($order_info['total_money']*$mer_balance_percent/100);
//                    if($now_user['now_money']>$balance_pay_infact){
//                        $now_user['now_money']=$balance_pay_infact;
//                    }
//                }else{
//                    $now_user['now_money']=0;
//                }
//            }else{
//                if ($is_mer_balance_percent && $mer_balance_percent>0 && $mer_balance_percent < 100) {//商家设定使用余额百分比
//                    //$now_user['now_money']=getFormatNumber($now_user['now_money']*$mer_balance_percent/100);
//                    $balance_pay_infact=getFormatNumber($order_info['total_money']*$mer_balance_percent/100);
//                    if($now_user['now_money']>$balance_pay_infact){
//                        $now_user['now_money']=$balance_pay_infact;
//                    }
//                }else{
//                    $now_user['now_money']=0;
//                }
//            }
//        }
		//如果用户存在余额或使用了优惠券，则保存至订单信息。如果金额满足订单总价，则实时扣除并返回支付成功！若不够则不实时扣除，防止用户在付款过程中取消支付
		$wx_cheap = 0;
		if($order_info['order_type'] == 'group'){
            if(($this->is_weixin_browser || $this->is_wxapp_browser || $this->is_app_browser)) {
                $now_group = M('Group')->field('`group_id`,`wx_cheap`')->where(array('group_id' => $order_info['group_id']))->find();
                $wx_cheap = $order_info['order_num'] * $now_group['wx_cheap'];
            }
			$save_result = D('Group_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$wx_cheap);
		}else if($order_info['order_type'] == 'meal' || $order_info['order_type'] == 'takeout' || $order_info['order_type'] == 'food' || $order_info['order_type'] == 'foodPad'){
			$save_result = D('Meal_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user, $order_info['order_type']);
		}else if($order_info['order_type'] == 'weidian'){
			$save_result = D('Weidian_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		}else if($order_info['order_type'] == 'recharge'){
			$save_result = D('User_recharge_order')->wap_befor_pay($order_info,$now_user);
		}else if($order_info['order_type'] == 'appoint'){
			$save_result = D('Appoint_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		} else if ($order_info['order_type'] == 'yuedan') {
			$save_result = D('Yuedan_service_order')->wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user,$ecard_info);
		} else if($order_info['order_type'] == 'waimai'){
			$save_result = D('Waimai_order')->wap_befor_pay($order_info,$merchant_balance,$now_user);
		}else if($order_info['order_type'] == 'wxapp'){
			$save_result = D('Wxapp_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		}else if($order_info['order_type'] == 'store'){
			$save_result = D('Store_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		}else if($order_info['order_type'] == 'shop'){
            $order_info['qiye_balance'] = $use_qiye_money;
			$save_result = D('Shop_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		}else if($order_info['order_type'] == 'mall'){
            $order_info['qiye_balance'] = $use_qiye_money;
			$save_result = D('Shop_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		}else if($order_info['order_type'] == 'plat'){
			//定制商家桌次折扣
			if($order_info['order_info']['table_or_vipcard_discount']==0){
				if($order_info['order_info']['mer_table_discount']>0 && $order_info['order_info']['mer_table_discount']<100 ) {
					$discount = $order_info['order_info']['mer_table_discount'] / 100 ;
					$reduce_money = round($order_info['order_info']['can_discount_table_money'] - $order_info['order_info']['can_discount_table_money'] * $discount,2);
					$pay_money = $order_info['order_total_money'];
					$pay_money = round($pay_money - $reduce_money,2);
					$order_info['order_total_money'] = $pay_money;
				}
			}
			$save_result = D('Plat_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		}else if($order_info['order_type'] == 'balance-appoint'){
			$save_result = D('Appoint_order')->wap_balace_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		} else if ($_GET['type'] == 'integral_mall'){
			$save_result = D('Merchant_integral_mall_goods_order')->wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user);
		} else if ($order_info['order_type'] == 'ticket'){
            $save_result = D('Scenic_order')->wap_befor_pay($order_info,$now_user);
        } else if ($order_info['order_type'] == 'guide') {
            $save_result = D('Scenic_aguide')->wap_befor_pay($order_info, $now_user);

        } else if ($order_info['order_type'] == 'spread') {
            $save_result = D('Merchant_spread_order')->wap_befor_pay($order_info, $now_user);

        } else if ($order_info['order_type'] == 'mcard') {
            $save_result = D('Membership_card_order')->wap_befor_pay($order_info, $now_user);
        }

		if($save_result['error_code']){
			$this->returnCode(1,array(),$save_result['msg']);
		}else if($save_result['url']){
            $this->returnCode(1, array('url' => $save_result['url']), $save_result['msg']);
		}

		$order_id = $order_info['order_id'];
		if ($_POST['order_type'] == 'takeout' || $_POST['order_type'] == 'food' || $_POST['order_type'] == 'foodPad') {
			$order_table = 'Meal_order';
		}else if($_POST['order_type']=='recharge'){
			$order_table = 'User_recharge_order';
		}else if($_POST['order_type']=='balance-appoint'){
			$order_table = 'Appoint_order';
		}else if($_POST['order_type']=='yuedan'){
			$order_table = 'Yuedan_service_order';
		}else if($_POST['order_type']=='ticket'){
		    $order_table = 'Scenic_order';
        }else if($_POST['order_type']=='guide'){
            $order_table = 'Scenic_aguide_order';
        }else if ($order_info['order_type'] == 'spread') {
            $order_table = 'Merchant_spread_order';

        }else if ($order_info['order_type'] == 'mcard') {
            $order_table = 'Membership_card_order';

        }else{
			$order_table = ucfirst($_POST['order_type']).'_order';
		}

		$nowtime = date("ymdHis");
		if($_POST['order_type']=='balance-appoint'){
			$nowtime = date("mdHis");
			$orderid = $nowtime.sprintf("%04d",$this->_uid);
		}
        elseif($_POST['pay_type'] == 'farmersbankpay'){
            $nowtime = date("dHis");
            $orderid = $nowtime . str_pad(substr(sprintf("%04d", $this->_uid),-4),4,'0',STR_PAD_LEFT);
        }else{
			/* if($_POST['pay_type'] == 'merchantwarrior'){
				$nowtime = str_shuffle(str_replace(array('4','5'),'0',$nowtime));
			} */
			$orderid = $nowtime.rand(10,99).sprintf("%06d",$this->_uid);
		}
		$data_tmp['pay_type'] = $_POST['pay_type'];
		$data_tmp['order_type'] = $_POST['order_type'];
		$data_tmp['order_id'] = $order_id;
		$data_tmp['orderid'] = $orderid;
		$data_tmp['addtime'] = time();
		if(!D('Tmp_orderid')->add($data_tmp)){
			$this->returnCode('20046008');
		}
		if($_POST['order_type'] == 'appoint'){
			$save_pay_id = D($order_table)->where(array("order_id" => $order_id))->setField('orderids', $orderid);
		}else{
			$save_pay_id = D($order_table)->where(array("order_id" => $order_id))->setField('orderid', $orderid);
		}


		if(!$save_pay_id){
			$this->returnCode('20046008');
		}else{
			$order_info['order_id']=$orderid;
		}

		// if($_POST['app_version']>=600 && $_POST['stillPay']!=$save_result['payment_money']){
		// 	$this->returnCode(1,'',L_('支付金额有误'));
		// }

		$pay_money = round($save_result['pay_money']*100)/100;
		if ($order_info['is_foodshop_new']) {
			// 新餐饮 支付手续费
			$all_pay_method = D('Merchant_store')->get_pay_method($order_info['store_id']);
		}else{
			$all_pay_method = D('Config')->get_pay_method();//获取所有支付方式
		}
		
		// 堂扫税率
		if ($order_info['is_foodshop_new']&&$order_info['tax_rate_price']>0) {
	        $pay_money= getFormatNumber($pay_money + $order_info['tax_rate_price']);
	    }

		$_service = 0;//支付手续费

        if($all_pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1'){
            $service_charge = $all_pay_method[$_POST['pay_type']]['config']['service_charge'];
            $_service = $pay_money*($service_charge/100);
        }
        $pay_rate = 0;
        if($all_pay_method[$_POST['pay_type']]['config']['rates'] >0 && !$order_info['is_foodshop_new']){
            $pay_rate = $all_pay_method[$_POST['pay_type']]['config']['rates'];
        }

		$pay_money = round(($pay_money+$_service)*100)/100;//保留两位小数，四舍五入
		$payment_money_date['payment_money'] = $pay_money;
		$payment_money_date['order_type'] =  $_POST['order_type'];
		$payment_money_date['pay_type'] =  $_POST['pay_type'];
		$payment_money_date['order_id'] =  $_POST['order_id'];
		$payment_money_date['orderid'] =  $orderid;
		$payment_money_date['addtime'] = time();
		$payment_money_date['service_charge'] = round($_service*100)/100;//支付手续费
        $payment_money_date['is_mobile_pay'] = $_POST['app_type'] == 'packapp' ? '1' : ($this->DEVICE_ID=='wxapp' ? '3' : '2');
		$payment_money_date['pay_rate'] = round($pay_rate,2);
        if (!$payment_money_order_id = M('Payment_money_order')->add($payment_money_date)) {
            fdump_sql([$_POST, $payment_money_date], 'payment_money_order_error');
        }
	
		//支付前,加上费率处理
        if($all_pay_method[$_POST['pay_type']]['config']['rates'] >0 && !$order_info['is_foodshop_new']){
            $pay_rate = $all_pay_method[$_POST['pay_type']]['config']['rates'];
            $pay_money = getFormatNumber($pay_money * $pay_rate);
        }elseif($this->config['pay_weixin_rates']  > 0 && !$order_info['is_foodshop_new']){

			$rates = explode('_','pay_'.$_POST['pay_type'].'_rates');
			if($rates[1] == $_POST['pay_type'] ){
				$pay_money = $pay_money*$this->config['pay_weixin_rates'];
			}
		}
		if(strpos($_POST['pay_type'],'allinyun')!==false || ( ($_POST['pay_type']=='allinyun_alipay' || $_POST['pay_type']=='alipay' ) && $this->config['open_allinyun']==1)){

			import('@.ORG.AccountDeposit.AccountDeposit');
			$deposit = new AccountDeposit('Allinyun');
			$allyun = $deposit->getDeposit();

			$allinyun_user = D('Deposit')->allinyun_user($this->_uid);

			if($allinyun_user){
				if($allinyun_user['phone']==''){
					$this->returnCode(1,'',L_('您的云商通账号还未绑定手机无法完成支付，请先绑定手机号码'));
				}
				$allyun->setUser($allinyun_user);
				$pay_type = explode('_',$_POST['pay_type']);
				$order_info['payerId'] = $allinyun_user['bizUserId'];
				//$order_info['openid'] = $this->user_session['openid'] ;
				if($_POST['pay_type']=='weixin'){
					$order_info['pay_type'] = 'wechatpay_app';
				}else if($_POST['pay_type']=='allinyun_alipay'){
					$order_info['pay_type'] = 'scan_alipay';
				}else{

					$order_info['pay_type'] = $pay_type[1];
					$_POST['bank_code'] && $order_info['bank_code'] = $_POST['bank_code'];
				}
				$order_info['source'] = 1;
				$order_info['platform'] = 'APP';
				$order_info['uid'] = $allinyun_user['uid'];
				if($order_info['mer_id']){
					$allinyun_mer =  D('Deposit')->get_merchant_info($order_info['mer_id'],1);
					$order_info['recieverId'] =  $allinyun_mer['bizUserId'];
				}

				$res = $allyun->Pay($_POST['order_type'],$orderid,$pay_money,$order_info);

				if($res['status']=='OK' && $res['signedResult']['payInfo']!=''){
					$pay_arr['alipay_url']=$res['signedResult']['payInfo'];
					$pay_arr['backUrl']=$res['signedResult']['backUrl'];
					$this->returnCode(0,$pay_arr);
				}else{
					// $this->error_tips($res['info'],'window.pigcmspackapp.closewebview(2);');
					$this->returnCode(0,'',$res['message']);
				}

				die;
			}

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
                $this->returnCode('10001', '','后台利楚参数未配置，无法支付');
            }
            $lichu_config['pay_money'] = $pay_money;
            $lichu_config['openid'] = $_SESSION['openid'] ? : $now_user['openid'];
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
                $go_pay_param['pay_money'] = $pay_money;
                $go_pay_param['weixin_param'] = $res['result'];
//                    $go_pay_param['backUrl']=$this->config['site_url'].'/index.php?c=LowFeePayNotice&a=order_back_notice&order_type='.$order_info['order_type']."&source=2&order_id={$orderid}&uid={$this->_uid}";
                $go_pay_param['error'] = 0;
                $go_pay_param['orderid'] = $orderid;
                $this->returnCode(0,$go_pay_param);
            } else {
                $this->returnCode('10001', '',$res['msg']);
            }
        }

        //抖音支付
        if ($this->config['pay_douyin_open'] == 1 && $_POST['pay_type'] == 'douyin') {
            try {
                import('@.ORG.pay.Douyin');
                $pay = new Douyin($order_info, $pay_money,'douyin',[]);
                $extra = [
                    'subject' => $order_info['order_name'],
                    'body' => $order_info['order_txt_type'] ?: $order_info['order_name'],
                ];
                $go_pay_param = $pay->miniPay($_POST['order_type'] . '_' . $order_info['order_id'], $pay_money, $extra);
                $pay_arr['pay_money'] = $pay_money;
                $pay_arr['info'] = $go_pay_param['info'];
                $pay_arr['error'] = 0;
                $pay_arr['orderid'] = $orderid;
                $this->returnCode(0, $pay_arr);
            } catch (Exception $e) {
                $this->returnCode(1222, '', $e->getMessage());
            }
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
                fdump_api([
                    'msg' => 'mallbook支付',
                    'params' => $_POST
                ],'mallbookOldPay/pay', true);
                import('@.ORG.pay.Mallbookpay.MallbookPayService');
                $agent = $this->getPayAgent();
                $pay = new MallbookPayService($agent);
                $pay->config['pay_type'] = $_POST['pay_type'];
                if($this->DEVICE_ID == 'wxapp'){
                    $pay->config['openid'] = $_SESSION['wxapp_openid'] ?: ($now_user['wxapp_openid'] ?: $now_user['openid']);
                }else if($_POST['app_type'] == 'packapp'){
                    $pay->config['openid'] = $_SESSION['openid'] ?: $now_user['openid'];
                }else if ($_POST['app_type'] == '1' || $_POST['app_type'] == '2') {
					//app微信支付，跳转到微信小程序支付
					$pay_arr = [
						'redirect_mini' => 1,
						'userName' => $this->config['pay_wxapp_username'],
						'path' => '/pages/shop_new/confirmOrder/pay?order_id=' . $_POST['order_id'] . '&type=' . $_POST['order_type']
					];
					$this->returnCode(0, $pay_arr);
				}

                $extra = array_merge($order_info, $_POST);
                $wxPayParams = $pay->pay($orderid, $pay_money, $extra);
                fdump_api([
                    'msg' => 'mallbook支付',
                    'wxPayParams' => $wxPayParams
                ],'mallbookOldPay/pay', true);
                
                $pay_arr['pay_money'] = $pay_money;
                $pay_arr['weixin_param'] = $wxPayParams['info'];
                $pay_arr['error'] = 0;
                $pay_arr['orderid'] = $orderid;
                $this->returnCode(0,$pay_arr);
            } catch(\Exception $e){
                fdump_api([
                    'error_msg' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],'mallbookOldPay/pay_error', true);
                $this->returnCode(1222,'',$e->getMessage());
            }
        }
		//银联云闪付通道
		if($this->config['open_chinaums'] == '1' && $_POST['pay_type'] == 'unionpayapp' && (($_POST['app_version'] >= 30166 && $_POST['app_type'] == '2') || ($_POST['app_version'] >= 30201 && $_POST['app_type'] == '1')) && !empty(M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->getField("chinaums_merno"))){
			try{

				import('@.ORG.pay.Chinaums');
				$pay = new Chinaums();
				$chinaums_merno = '';
				if($order_info['mer_id']){
					$now_merchant = M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->find();
					if(!empty($now_merchant['chinaums_merno'])){
						$chinaums_merno = $now_merchant['chinaums_merno'];
						$percent = D("Percent_rate")->get_percent($order_info['mer_id'],$_POST['order_type'],$pay_money, '', $order_info['store_id'],true);
						$pay->platform_get = ceil($pay_money*$percent);
						$pay->merchant_get = getFormatNumber($pay_money*100 - $pay->platform_get);
					}
				}
				$go_pay_param = $pay->UnionpayApp($_POST['order_type'].'_'.$order_info['order_id'], $pay_money, $chinaums_merno);
				$pay_arr['pay_money'] = $pay_money;
				$pay_arr['unionpay_param'] = json_decode($go_pay_param['unionpay_param']);
				$pay_arr['error'] = 0;
				$pay_arr['orderid'] = $orderid;
				$this->returnCode(0,$pay_arr);
			}
			catch(Exception $e){
				$this->returnCode(1222,'',$e->getMessage());
			}
		}

		//拉卡拉（钱账通）微信小程序支付
		if ($this->config['lakala_pay_switch'] == 1 && $_POST['pay_type'] == 'weixin' && $this->DEVICE_ID == 'wxapp') {
			try {
				import('@.ORG.pay.Lakala');
				$lakalaConfig  = Lakala::getParams($order_info['mer_id'], 0);
				if ($lakalaConfig) {
					$pay = new Lakala($order_info, $lakalaConfig);
					$pay->setConfig('wxapp_openid', $_SESSION['wxapp_openid']?:$now_user['wxapp_openid']);

					M('Payment_money_order')->where(['id' => $payment_money_order_id])->data(['pay_method' => 'lakala', 'pay_config' => json_encode($lakalaConfig), 'is_mobile_pay' => 3])->save();
					$go_pay_param = $pay->WechatMiniPay($pay_money);

					$pay_arr['pay_money'] = $pay_money;
					$pay_arr['weixin_param'] = $go_pay_param['weixin_param'];
					$pay_arr['error'] = 0;
					$pay_arr['orderid'] = $orderid;
					$this->returnCode(0, $pay_arr);
				}
			} catch (Exception $e) {
				$this->returnCode(1222, '', $e->getMessage());
			}
		}
		//拉卡拉（钱账通）微信公众号支付
		if ($this->config['lakala_pay_switch'] == '1' && $_POST['pay_type'] == 'weixin' && $_POST['app_type'] == 'packapp') {
			try {
				import('@.ORG.pay.Lakala');
				$lakalaConfig  = Lakala::getParams($order_info['mer_id'], 0);
				if ($lakalaConfig) {
					$pay = new Lakala($order_info, $lakalaConfig);
					$pay->setConfig('openid', $_SESSION['openid']?:$now_user['openid']);

					M('Payment_money_order')->where(['id' => $payment_money_order_id])->data(['pay_method' => 'lakala', 'pay_config' => json_encode($lakalaConfig), 'is_mobile_pay' => 3])->save();

					$go_pay_param = $pay->WechatH5Pay($pay_money);

					$pay_arr['pay_money'] = $pay_money;
					$pay_arr['weixin_param'] = $go_pay_param['weixin_param'];
					$pay_arr['error'] = 0;
					$pay_arr['orderid'] = $orderid;
					$this->returnCode(0, $pay_arr);
				}
			} catch (Exception $e) {
				$this->returnCode(1222, '', $e->getMessage());
			}
		}

		if($this->config['open_chinaums'] == '1' && $_POST['pay_type'] == 'weixin' && $this->DEVICE_ID=='wxapp'){
			try{

				import('@.ORG.pay.Chinaums');
				$pay = new Chinaums();
				$pay->config['openid'] = $_SESSION['wxapp_openid'] ?: ($now_user['wxapp_openid'] ?: $now_user['openid']);
				$chinaums_merno = '';
				if($order_info['mer_id']){
					$now_merchant = M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->find();
					if(!empty($now_merchant['chinaums_merno'])){
						$chinaums_merno = $now_merchant['chinaums_merno'];
						if (in_array($_POST['order_type'], ['shop', 'mall'])) {
							$settlementRs = D('Merchant_money_list')->compute_merchant_settlement_money(intval($_POST['order_id']),true);
							$pay->merchant_get = intval($settlementRs['money'] * 100);
							$pay->platform_get = getFormatNumber($pay_money * 100 - $pay->merchant_get);
							if ($pay->platform_get < 0) {
								$pay->platform_get = 0;
								$pay->merchant_get = getFormatNumber($pay_money * 100);
							}
						} else {
							$percent = D("Percent_rate")->get_percent($order_info['mer_id'], $_POST['order_type'], $pay_money, '', $order_info['store_id'], true);
							$pay->platform_get = intval($pay_money * $percent);
							$pay->merchant_get = getFormatNumber($pay_money * 100 - $pay->platform_get);
						}
						
					}
				}
				if($order_info['business_type'] == 'house_village_pay' || $order_info['business_type'] == 'house_village_express' || $order_info['business_type'] == 'house_village_pay_cashier'){
					$percent = D("Chinaums")->getConfig($order_info['order_info']['village_id']);
					$village_id = $order_info['order_info']['village_id'];
					if(isset($percent['mid']) && isset($percent['percent'])){
						$chinaums_merno = $percent['mid'];
						$pay->platform_get = ceil($pay_money*$percent['percent']);
						$pay->merchant_get = getFormatNumber($pay_money*100 - $pay->platform_get);
					}
				}

				if($chinaums_merno){
					$pay->order_name = $order_info['order_name'];
					$go_pay_param = $pay->Wechatmini($_POST['order_type'].'_'.$order_info['order_id'], $pay_money, $chinaums_merno,isset($village_id)?$village_id:0);
					$pay_arr['pay_money'] = $pay_money;
					$pay_arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
					$pay_arr['error'] = 0;
					$pay_arr['orderid'] = $orderid;
					$this->returnCode(0,$pay_arr);
				}
			}
			catch(Exception $e){
				$this->returnCode(1222,'',$e->getMessage());
			}
		}

		if($this->config['open_chinaums'] == '1' && $_POST['pay_type'] == 'weixin' && $_POST['app_type'] == 'packapp'){
			try{
				import('@.ORG.pay.Chinaums');
				$pay = new Chinaums();
				$pay->config['openid'] = $_SESSION['openid'] ? : $now_user['openid'];
				$chinaums_merno = '';
				if($order_info['mer_id']){
					$now_merchant = M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->find();
					if(!empty($now_merchant['chinaums_merno'])){
						$chinaums_merno = $now_merchant['chinaums_merno'];
						if (in_array($_POST['order_type'], ['shop', 'mall'])) {
							$settlementRs = D('Merchant_money_list')->compute_merchant_settlement_money(intval($_POST['order_id']),true);
							$pay->merchant_get = ceil($settlementRs['money'] * 100);
							$pay->platform_get = getFormatNumber($pay_money * 100 - $pay->merchant_get);
							if ($pay->platform_get < 0) {
								$pay->platform_get = 0;
								$pay->merchant_get = getFormatNumber($pay_money * 100);
							}
						} else {
							$percent = D("Percent_rate")->get_percent($order_info['mer_id'],$_POST['order_type'],$pay_money, '', $order_info['store_id'],true);
							$pay->platform_get = ceil($pay_money*$percent);
							$pay->merchant_get = getFormatNumber($pay_money*100 - $pay->platform_get);
						}
					}
				}
				if($order_info['business_type'] == 'house_village_pay' || $order_info['business_type'] == 'house_village_express' || $order_info['business_type'] == 'house_village_pay_cashier'){
					$percent = D("Chinaums")->getConfig($order_info['order_info']['village_id']);
					$village_id = $order_info['order_info']['village_id'];
					if(isset($percent['mid']) && isset($percent['percent'])){
						$chinaums_merno = $percent['mid'];
						$pay->platform_get = ceil($pay_money*$percent['percent']);
						$pay->merchant_get = getFormatNumber($pay_money*100 - $pay->platform_get);
					}
				}

				if($chinaums_merno){
					$pay->order_name = $order_info['order_name'];
					$go_pay_param = $pay->Wechatpay($_POST['order_type'].'_'.$order_info['order_id'], $pay_money, $chinaums_merno,isset($village_id)?$village_id:0);
					$pay_arr['pay_money'] = $pay_money;
					$pay_arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
					$pay_arr['error'] = 0;
					$pay_arr['orderid'] = $orderid;
					$this->returnCode(0,$pay_arr);
				}
			}
			catch(Exception $e){
				$this->returnCode(1222,'',$e->getMessage());
			}
		}

		if($this->config['open_chinaums'] == '1' && $_POST['is_ysfmini'] == '1'){
			try{
				import('@.ORG.pay.ChinaumsYsf');
				$pay = new ChinaumsYsf();
				$chinaums_merno = '';
				if($order_info['mer_id']){
					$now_merchant = M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->find();
					if(!empty($now_merchant['chinaums_merno'])){
						$chinaums_merno = $now_merchant['chinaums_merno'];
					}
				}
				$order_info['orderNo'] = $_POST['order_type'].'_'.$order_info['order_id'];
				$order_info['payment_money'] = $pay_money;
				$go_pay_param = $pay->pay($order_info, $chinaums_merno);
				$pay_arr['pay_money'] = $pay_money;
				$pay_arr['ysfmini_param'] = json_decode($go_pay_param['ysfmini_param']);
				$pay_arr['error'] = 0;
				$pay_arr['orderid'] = $orderid;
				$pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
				$this->returnCode(0,$pay_arr);
			}
			catch(Exception $e){
				$this->returnCode(1222,'',$e->getMessage());
			}
		}

		if($this->config['open_chinaums'] == '1' && ($_POST['pay_type'] == 'weixin' || $_POST['pay_type'] == 'alipay') && $_POST['app_type']!='packapp' && (($_POST['app_version'] >= 30166 && $_POST['app_type'] == '2') || ($_POST['app_version'] >= 30201 && $_POST['app_type'] == '1')) && !empty(M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->getField("chinaums_merno"))){
			try{

				import('@.ORG.pay.Chinaums');
				$pay = new Chinaums();
				$chinaums_merno = '';
				if($order_info['mer_id']){
					$now_merchant = M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->find();
					if(!empty($now_merchant['chinaums_merno'])){
						$chinaums_merno = $now_merchant['chinaums_merno'];
						if (in_array($_POST['order_type'], ['shop', 'mall'])) {
							$settlementRs = D('Merchant_money_list')->compute_merchant_settlement_money(intval($_POST['order_id']),true);
							$pay->merchant_get = ceil($settlementRs['money'] * 100);
							$pay->platform_get = getFormatNumber($pay_money * 100 - $pay->merchant_get);
							if ($pay->platform_get < 0) {
								$pay->platform_get = 0;
								$pay->merchant_get = getFormatNumber($pay_money * 100);
							}
						} else {
							$percent = D("Percent_rate")->get_percent($order_info['mer_id'],$_POST['order_type'],$pay_money, '', $order_info['store_id'],true);
							$pay->platform_get = ceil($pay_money*$percent);
							$pay->merchant_get = getFormatNumber($pay_money*100 - $pay->platform_get);
						}
					}
				}
				if($order_info['business_type'] == 'house_village_pay' || $order_info['business_type'] == 'house_village_express' || $order_info['business_type'] == 'house_village_pay_cashier'){
					$percent = D("Chinaums")->getConfig($order_info['order_info']['village_id']);
					$village_id = $order_info['order_info']['village_id'];
					if(isset($percent['mid']) && isset($percent['percent'])){
						$chinaums_merno = $percent['mid'];
						$pay->platform_get = ceil($pay_money*$percent['percent']);
						$pay->merchant_get = getFormatNumber($pay_money*100 - $pay->platform_get);
					}
				}
				if($chinaums_merno){
					if($_POST['pay_type'] == 'alipay'){
						$go_pay_param = $pay->Alipayapp($_POST['order_type'].'_'.$order_info['order_id'], $pay_money, $chinaums_merno,isset($village_id)?$village_id:0);
						$pay_arr['ali_param'] = $go_pay_param['ali_param'];
					}
					else{
						$go_pay_param = $pay->Wechatapp($_POST['order_type'].'_'.$order_info['order_id'], $pay_money, $chinaums_merno,isset($village_id)?$village_id:0);
						$pay_arr['weixin_param'] = $go_pay_param['weixin_param'];					
					}
					$pay_arr['pay_money'] = $pay_money;
					$pay_arr['error'] = 0;
					$pay_arr['orderid'] = $orderid;
					$pay_arr['payMethod'] = "chinaums";
					
					$this->returnCode(0,$pay_arr);
				}
			}
			catch(Exception $e){
				$this->returnCode(1222,'',$e->getMessage());
			}
		}

		//银联分账（非资管平台）
		if ($this->config['open_chinaums_without_asset'] == '1' && $_POST['pay_type'] == 'weixin' && $this->DEVICE_ID == 'wxapp') {
			try {
				import('@.ORG.pay.Chinaums');
				$pay = new Chinaums();
				$pay->config['openid'] = $_SESSION['wxapp_openid'] ?: ($now_user['wxapp_openid'] ?: $now_user['openid']);
				$chinaums_merno = '';
				if ($order_info['mer_id']) {
					$now_merchant = M("Merchant")->where(['mer_id' => $order_info['mer_id']])->find();
					if (!empty($now_merchant['chinaums_merno'])) {
						$chinaums_merno = $now_merchant['chinaums_merno'];
						if (in_array($_POST['order_type'], ['shop', 'mall'])) {
							$settlementRs = D('Merchant_money_list')->compute_merchant_settlement_money(intval($_POST['order_id']), true);
							$pay->merchant_get = ceil($settlementRs['money'] * 100);
							$pay->platform_get = getFormatNumber($pay_money * 100 - $pay->merchant_get);
							if ($pay->platform_get < 0) {
								$pay->platform_get = 0;
								$pay->merchant_get = getFormatNumber($pay_money * 100);
							}
						} else {
							$percent = D("Percent_rate")->get_percent($order_info['mer_id'], $_POST['order_type'], $pay_money, '', $order_info['store_id'], true);
							$pay->platform_get = ceil($pay_money * $percent);
							$pay->merchant_get = getFormatNumber($pay_money * 100 - $pay->platform_get);
						}
					}
				}
				if ($chinaums_merno) {
					$pay->order_name = $order_info['order_name'];
					$go_pay_param = $pay->Wechatmini($_POST['order_type'] . '_' . $order_info['order_id'], $pay_money, $chinaums_merno, isset($village_id) ? $village_id : 0,false);
					$pay_arr['pay_money'] = $pay_money;
					$pay_arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
					$pay_arr['error'] = 0;
					$pay_arr['orderid'] = $orderid;
					$this->returnCode(0, $pay_arr);
				}
			} catch (Exception $e) {
				$this->returnCode(1222, '', $e->getMessage());
			}
		}

		if(($_POST['order_type']!='recharge'||$this->config['juhe_uid']!='') && $this->config['open_juhepay']==1 && $_POST['pay_type']=='alipay'){
			import('@.ORG.LowFeePay');
			$order_info['openid'] = $_SESSION['openid'] ? : $now_user['openid'];
			$order_info['pay_type'] = 'alipay_scan';
			$order_info['order_id'] =   $_POST['order_type'].'_'.$order_info['order_id'];
			$order_info['order_total_money'] =  $pay_money;
			$lowfeepay = new LowFeePay('juhepay');
			if($order_info['mer_id']){
				$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$order_info['mer_id']))->find();
				if(!empty($mer_juhe)){
					$lowfeepay->userId =$mer_juhe['userid'];
					$order_info['is_own'] =1 ;
					$order_info['order_id'] =  $order_info['order_id'].'_1';
				}
			}
			$go_pay_param= $lowfeepay->pay($order_info);

			if(!empty($go_pay_param['info'])){
				$pay_arr['alipay_url']=$go_pay_param['info'];
				$pay_arr['backUrl']=$this->config['site_url'].'/index.php?c=LowFeePayNotice&a=order_back_notice&order_type='.$order_info['order_type']."&source=2&order_id={$orderid}&uid={$this->_uid}";
				$this->returnCode(0,$pay_arr);
			}else{
				$this->returnCode(1222,'',$go_pay_param['msg']);
			}
		}

		if($_POST['app_version'] >= 29000 && $this->config['pay_weixin_nong'] == '1' && $_POST['pay_type'] == 'weixin' && $_POST['app_type'] != 'packapp' && $this->DEVICE_ID!='wxapp' && $this->config['pay_wxapp_username']){
			//app微信支付，跳转到微信小程序支付
			$pay_arr = [
				'redirect_mini' => 1,
				'userName' => $this->config['pay_wxapp_username'],
				'path' => '/pages/shop_new/confirmOrder/pay?order_id='.$_POST['order_id'].'&type='.$_POST['order_type']
			];
			$this->returnCode(0,$pay_arr);
		}

		if($this->config['open_yanglao_and_sande'] == '1' && $_POST['app_type'] == 'packapp' && $_POST['pay_type'] == 'alipay'){
			import('@.ORG.pay.Sande');
			$order_info['pay_type'] = 'alipay';
			$order_info['alipay_uid'] = $now_user['alipay_uid'] ? : $_SESSION['alipay_uid'];
			
			$order_info['orderNo'] =   $_POST['order_type'].'_'.$order_info['order_id'];
			$order_info['payment_money'] =  $pay_money;
			$pay = new Sande();
			$go_pay_param = $pay->pay($order_info);
			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}
			$pay_arr['tradeNO'] = $go_pay_param['tradeNO'];
			$pay_arr['error'] = 0;
			// $pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
			$this->returnCode(0,$pay_arr);
		}

		if($this->config['open_yanglao_and_sande'] == '1' && ($this->DEVICE_ID=='wxapp' || $_POST['app_type'] == 'packapp')&& $_POST['pay_type'] == 'weixin'){
			import('@.ORG.pay.Sande');
			if($this->DEVICE_ID=='wxapp'){//微信小程序
				$order_info['pay_type'] = 'wxapp';
				$order_info['openid'] = $now_user['wxapp_openid'];
			}
			elseif($_POST['app_type'] == 'packapp'){//微信公众号
				$order_info['pay_type'] = 'wechat';
				$order_info['openid'] = $_SESSION['openid'] ? : $now_user['openid'];
			}
			$order_info['orderNo'] =   $_POST['order_type'].'_'.$order_info['order_id'];
			$order_info['payment_money'] =  $pay_money;
			$pay = new Sande();
			$go_pay_param = $pay->pay($order_info);
			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}
			$pay_arr['pay_money'] = $pay_money;
			$pay_arr['weixin_param'] = $go_pay_param['weixin_param'];
			$pay_arr['error'] = 0;
			$pay_arr['orderid'] = $orderid;
			// $pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
			$this->returnCode(0,$pay_arr);
		}

		if($this->config['pay_weixin_open'] == '1' && $this->config['pay_weixin_nong'] == '1' && $_POST['pay_type'] == 'weixin' && ($this->DEVICE_ID=='wxapp' || $_POST['app_type'] == 'packapp')){
			import('@.ORG.LowFeePay');
			$order_info['openid'] = $_SESSION['openid'] ? : $now_user['openid'];
			$order_info['orderNo'] =   $_POST['order_type'].'_'.$order_info['order_id'];
			$order_info['payment_money'] =  $pay_money;
			if($this->DEVICE_ID=='wxapp'){
				$order_info['openid'] =  $_SESSION['wxapp_openid'] ? : $now_user['wxapp_openid'];
				C("config.pay_weixin_appid", $this->config['pay_wxapp_appid']);
			}
			$lowfeepay = new LowFeePay('nong');
			$go_pay_param = $lowfeepay->pay($order_info);
			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}
			$pay_arr['pay_money'] = $pay_money;
			$pay_arr['weixin_param'] = $go_pay_param['weixin_param'];
			$pay_arr['error'] = 0;
			$pay_arr['orderid'] = $orderid;
			// $pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
			$this->returnCode(0,$pay_arr);
		}

		if($this->config['xiaoma_open'] == 1 && $_POST['pay_type'] == 'weixin' && $this->DEVICE_ID!='wxapp' && $_POST['app_type'] != 'packapp'){
			$order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
			$order_info['order_total_money'] = $pay_money;
			$lowfeepay = new LowFeePay('xiaoma');
			$go_pay_param = $lowfeepay->pay($order_info);
			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}
			$pay_arr['pay_money'] = $pay_money;
			$pay_arr['weixin_param'] = $go_pay_param['weixin_param'];
			$pay_arr['error'] = 0;
			$pay_arr['orderid'] = $orderid;
			// $pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
			$this->returnCode(0,$pay_arr);
		}

		//paylinx 支付 ，澳洲的一家支付平台  可对接 weixin   微信小程序
		if($this->config['pay_paylinx_open'] == 1){
			$_POST['pay_type'] = strtolower($_POST['pay_type']);
			if($_POST['pay_type'] == 'weixin'){
				if($this->DEVICE_ID == 'wxapp'){
					$_POST['pay_type'] = 'weixin_wxapp';	//小程序环境微信支付
					$order_info['pay_type'] = $_POST['pay_type'];
					$order_info['openid'] = $_SESSION['wxapp_openid'] ? : $now_user['wxapp_openid'];
				}
				else{
					$_POST['pay_type'] = 'app_weixin_wechat';	//app环境微信支付
					$order_info['pay_type'] = $_POST['pay_type'];
				}
				$order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
				$order_info['order_total_money'] = $pay_money;
				$lowfeepay = new LowFeePay('paylinx');
				$go_pay_param = $lowfeepay->pay($order_info);

				if ($go_pay_param['error']) {
					$this->returnCode('10001', array(), $go_pay_param['msg']);
				}
				$pay_arr['pay_money'] = $pay_money;
				$pay_arr['weixin_param'] = $go_pay_param['weixin_param'];
				$pay_arr['error'] = 0;
				$pay_arr['orderid'] = $orderid;
				$pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
				$this->returnCode(0,$pay_arr);
			}
		}

		if ($this->config['open_wftpay'] == 1  
            && ($this->DEVICE_ID=='wxapp' || $_POST['app_type'] == 'packapp') 
            && !$_POST['wxapp_own_mer_id'] 
            && !$_POST['wxapp_own_village_id']
            && $_POST['pay_type'] != 'farmersbankpay'
        ) {
			if ($_POST['app_type'] == 'packapp') {
				$now_user['openid'] && $order_info['openid'] = $_SESSION['openid'] ? : $now_user['openid'];
				$now_user['alipay_uid'] && $order_info['alipay_uid'] = $now_user['alipay_uid'];
			}else{
				$order_info['openid'] =  $_SESSION['wxapp_openid'] ? : $now_user['wxapp_openid'];
			}
			
			$_POST['pay_type'] = strtolower($_POST['pay_type']);
			
			if($_POST['app_type'] == 'packapp' && $_POST['pay_type'] == 'weixin'){
				
				$_POST['pay_type'] = 'weixin_wechat';	//公众号环境微信支付
				
			} else if($this->DEVICE_ID=='wxapp' && $_POST['pay_type'] == 'weixin'){
				
				$_POST['pay_type'] = 'weixin_wxapp';	//小程序环境微信支付
				
			}else if($_POST['pay_type'] == 'weixin'){
				
				$_POST['pay_type'] = 'weixinapp';	//APP环境微信支付
				
			}else if($_POST['app_type'] == 'packapp' && $_POST['pay_type'] == 'alipay'){
				
				$_POST['pay_type'] = 'alipayh5';	//H5环境支付宝支付
				
			}else if($_POST['pay_type'] == 'alipay'){
				
				$_POST['pay_type'] = 'alipayapp';	//APP 环境支付宝支付
				
			}
			
			$order_info['pay_type'] = $_POST['pay_type'];
			$order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];

			$order_info['order_total_money'] = $pay_money;

			$lowfeepay = new LowFeePay('wftpay');
			if($order_info['mer_id']){
				$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$order_info['mer_id']))->find();
				if(!empty($mer_juhe)){
					$lowfeepay->userId =$mer_juhe['userid'];
					$order_info['is_own'] =1 ;
					$order_info['order_id'] =  $order_info['order_id'].'_1';
				}
			}
			$go_pay_param = $lowfeepay->pay($order_info);

			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}
			if($_POST['pay_type'] == 'weixin_wxapp' || $_POST['pay_type'] == 'weixin_wechat'){
				$pay_arr['pay_money'] = $pay_money;
				$pay_arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
				$pay_arr['error'] = 0;
				$pay_arr['orderid'] = $orderid;
				$pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
			}else{
				if($_POST['pay_type'] == 'alipayh5'){	//支付宝H5，返回字段
					$tmp_json_arr = json_decode($go_pay_param['alipay_param'], true);
					$pay_arr['tradeNO'] = $tmp_json_arr['tradeNO'];
					$pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
				}else{
					$pay_arr['alipay_url'] = $go_pay_param['info'];
					$pay_arr['backUrl'] = $this->config['site_url'].'/index.php?c=LowFeePayNotice&a=order_back_notice&order_type='.$order_info['order_type']."&source=2&order_id={$orderid}&uid={$now_user['uid']}";
				}
			}
			$this->returnCode(0,$pay_arr);
		}else if($this->config['open_weikebaba'] == 1 && $this->config['weikebaba_system_user_id']){
			$order_info['openid'] = $_SESSION['wxapp_openid'] ? : $now_user['wxapp_openid'];
			$_POST['pay_type'] = strtolower($_POST['pay_type']);
			if($this->DEVICE_ID=='wxapp' && $_POST['pay_type'] == 'weixin'){
				$_POST['pay_type'] = 'weixin_wxapp';	//小程序环境微信支付
			}
			$order_info['pay_type'] = $_POST['pay_type'];
			$order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];

			$order_info['order_total_money'] = $pay_money;

			$lowfeepay = new LowFeePay('weikebaba');
			if($order_info['mer_id']){
				$mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order_info['mer_id']))->find();
				if(!empty($mer_juhe) && !empty($mer_juhe['user_id']) && !empty($mer_juhe['key'])){
					$lowfeepay->user_id = $mer_juhe['user_id'];
					$lowfeepay->key = $mer_juhe['key'];
					$lowfeepay->wxappid = $mer_juhe['wxappid'];
					$lowfeepay->wxappid_mini = $mer_juhe['wxappid_mini'];
					$order_info['is_own'] =1 ;
					$order_info['order_id'] =  $order_info['order_id'].'_1';
				}
			}
			$go_pay_param = $lowfeepay->pay($order_info);

			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}
			if($_POST['pay_type'] == 'weixin_wxapp'){
				$pay_arr['pay_money'] = $pay_money;
				$pay_arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
				$pay_arr['error'] = 0;
				$pay_arr['orderid'] = $orderid;
				$pay_arr['redirctUrl'] = $go_pay_param['redirctUrl'];
			}
			$this->returnCode(0,$pay_arr);
		}else if($this->config['open_juhepay']==1 && $_POST['pay_type']=='weixin'){
			import('@.ORG.LowFeePay');
			// 游客openid处理，取订单中openid
			$order_info['openid'] = $now_user['openid'] ? $now_user['openid'] : $order_info['openid'];
			$order_info['pay_type'] = 'wxApp';
			$order_info['order_id'] =   $_POST['order_type'].'_'.$order_info['order_id'];
			$order_info['order_total_money'] =  $pay_money;
			$lowfeepay = new LowFeePay('juhepay');
			if($order_info['mer_id']){
				$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$order_info['mer_id']))->find();
				if(!empty($mer_juhe)){
					$lowfeepay->userId =$mer_juhe['app_userid'];
					$order_info['is_own'] =1 ;
					$order_info['order_id'] =  $order_info['order_id'].'_1';
				}
			}
			$go_pay_param= $lowfeepay->pay($order_info);

			if(!empty($go_pay_param['payInfo']) && $go_pay_param['head']['resCode']=='000000'){
				$go_pay_param['weixin_param'] = json_decode($go_pay_param['payInfo'],true);
				$go_pay_param['backUrl']=$this->config['site_url'].'/index.php?c=LowFeePayNotice&a=order_back_notice&order_type='.$order_info['order_type']."&source=2&order_id={$orderid}&uid={$this->_uid}";
				$this->returnCode(0,$go_pay_param['weixin_param']);
			}else{
				$this->returnCode(1222,'',$go_pay_param['msg']);
			}
		}

		if($_POST['pay_type'] == 'weixin' && $this->DEVICE_ID=='wxapp'){

			$_SESSION['openid'] = $_SESSION['wxapp_openid'] ? : $now_user['wxapp_openid'];
			if($this->config['open_juhepay']==1){
				import('@.ORG.LowFeePay');
				$order_info['openid'] = $_SESSION['openid'];
				$order_info['pay_type'] = 'wx_app';
				$order_info['order_id'] =   $_POST['order_type'].'_'.$order_info['order_id'];

				$order_info['order_total_money'] =  $pay_money;
				$order_info['is_wxapp'] = 1;

				$lowfeepay = new LowFeePay('juhepay');
				if($order_info['mer_id']){
					$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$order_info['mer_id']))->find();
					if(!empty($mer_juhe)){
						$lowfeepay->userId =$mer_juhe['userid'];
						$order_info['is_own'] =1 ;
						$order_info['order_id'] =  $order_info['order_id'].'_1';
					}
				}
				$order_info['pay_type_platform'] = 'wxapp';
				$go_pay_param= $lowfeepay->pay($order_info);
				if($go_pay_param['error']){
					$this->returnCode(1222,'',$go_pay_param['msg']);
				}else {
					$arr['pay_money'] = $pay_money;
					$arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
					$arr['error'] = 0;
					$arr['orderid'] = $orderid;
				}
			}else {
				$pay_method = array(
					'pay_weixin_appid' => $this->config['pay_wxapp_appid'],
					'pay_weixin_key' => $this->config['pay_wxapp_key'],
					'pay_weixin_mchid' => $this->config['pay_wxapp_mchid'],
					'pay_weixin_appsecret' => $this->config['pay_wxapp_appsecret'],
					'pay_weixin_client_cert' => $this->config['pay_wxapp_cert'],
					'pay_weixin_client_key' => $this->config['pay_wxapp_cert_key'],
				);

				//商家自有小程序
				if ($_POST['wxapp_own_mer_id'] > 0) {
					$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['wxapp_own_mer_id'], 'bind_type' => 0))->find();
					$bind_user = M('Weixin_bind_user')->where(array('uid' => $this->_uid, 'mer_id' => $_POST['wxapp_own_mer_id'], 'wxapp_openid' => array('neq', '')))->find();

					$_SESSION['open_authorize_openid'] = $bind_user['wxapp_openid'];
					$pay_method = array(
						'pay_weixin_appid' => $wxapp_own_pay['appid'],
						'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
						'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
						'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
						'is_own' => 1,
					);
				}
				elseif ($_POST['wxapp_own_village_id'] > 0) {	//物业自有小程序
					$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['wxapp_own_village_id'], 'bind_type' => 1))->find();
					$bind_user = M('Weixin_bind_user')->where(array('uid' => $this->_uid, 'property_id' => $_POST['wxapp_own_village_id'], 'wxapp_openid' => array('neq', '')))->find();

					$_SESSION['open_authorize_openid'] = $bind_user['wxapp_openid'];
					$pay_method = array(
						'pay_weixin_appid' => $wxapp_own_pay['appid'],
						'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
						'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
						'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
						'is_own' => 1,
					);
				}
				elseif ($this->config['open_sub_mchid']) {
		            $is_sub_mchid = false;
		            if($pay_method['is_own'] === 6){
		                $is_sub_mchid = true;
		            }
		            else{
		                $now_store = D('Merchant_store')->get_store_by_storeId($order_info['store_id']);
		                if ($order_info['store_id'] != 0 && $now_store['open_sub_mchid'] && $now_store['sub_mch_id'] > 0) {
		                    $pay_method['sub_mch_id'] = $now_store['sub_mch_id'];

		                    $pay_method['open_weixin_profit_sharing'] = C('config.open_weixin_profit_sharing') && $now_store['open_weixin_profit_sharing'] ? 1 : 0;// 是否开启分账功能
		                    $pay_method['is_own'] = 3;
		                    $is_sub_mchid = true;
		                } else if ($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
		                    $pay_method['sub_mch_id'] = $now_merchant['sub_mch_id'];

		                    $pay_method['open_weixin_profit_sharing'] = C('config.open_weixin_profit_sharing') && $now_merchant['open_weixin_profit_sharing'] ? 1 : 0;// 是否开启分账功能
		                    $pay_method['is_own'] = 2;
		                    $is_sub_mchid = true;
		                } else if ($order_info['business_type'] == 'house_village_pay' || $order_info['business_type'] == 'house_village_express' || $order_info['business_type'] == 'house_village_pay_cashier') {
		                    $now_village = D('House_village')->get_one($order_info['order_info']['village_id']);
		                    if ($now_village['sub_mch_id'] > 0) {
		                        $pay_method['is_own'] = 5;
		                        $pay_method['sub_mch_id'] = $now_village['sub_mch_id'];
		                        $is_sub_mchid = true;
		                    }
		                } else if ($pay_method['sub_mch_id'] && $pay_method['is_own']==0){
		                    $is_sub_mchid = true;
		                }
		            }

		            if ($is_sub_mchid) {
		                $pay_method['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
	                	$pay_method['pay_weixin_mch_name'] = $this->config['pay_weixin_sp_mch_name'];
		                $pay_method['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
		                $pay_method['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
		                $pay_method['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
		            }
		        }elseif(C('config.pay_weixin_profit_sharing')){// 非自有支付的分账
		            $pay_method['open_weixin_profit_sharing'] = $this->config['pay_weixin_profit_sharing'];
		        }
		        
	        	// 保存支付信息
		        M('Payment_money_order')->where(['id'=>$payment_money_order_id])->data(['pay_config'=>json_encode($pay_method),'is_mobile_pay' => 3])->save();

				$import_result = import('@.ORG.pay.Weixin');

				$pay_class = new Weixin($order_info, $pay_money, 'weixin', $pay_method, $now_user, 3);
				$go_pay_param = $pay_class->pay();

				if ($go_pay_param['error']) {
					$this->returnCode('10001', array(), $go_pay_param['msg']);
				}

				$arr['pay_money'] = $pay_money;
				$arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
				$arr['error'] = 0;
				$arr['orderid'] = $orderid;
			}
			$this->returnCode(0,$arr);
		}else if($_POST['pay_type'] == 'weixin' && $_POST['app_type'] == 'packapp'){
			// 游客openid处理，取订单中openid
            if(empty($_SESSION['openid'])){
                $_SESSION['openid'] = $now_user['openid'] ? $now_user['openid'] : $order_info['openid'];
            }

			$pay_method = array(
				'pay_weixin_appid'=>$this->config['pay_weixin_appid'],
				'pay_weixin_key'=>$this->config['pay_weixin_key'],
				'pay_weixin_mchid'=>$this->config['pay_weixin_mchid'],
				'pay_weixin_appsecret'=>$this->config['pay_weixin_appsecret'],
				'pay_weixin_client_cert' => $this->config['pay_weixin_client_cert'],
				'pay_weixin_client_key' => $this->config['pay_weixin_client_key'],
			);
	        
			if (in_array($order_info['order_type'], array('mall','group', 'meal', 'weidian', 'takeout', 'food', 'foodPad', 'recharge', 'appoint', 'waimai', 'wxapp', 'store', 'shop', 'plat', 'balance-appoint')) && $order_info['mer_id']) {
	            $mer_id = $order_info['mer_id'];
	        } else {
	            $this->config['merchant_ownpay'] = 0;
	        }
	        if ($order_info['pay_merchant_ownpay'] === false) {
	            $this->config['merchant_ownpay'] = 0;
	        }
	        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
	        if($this->config['merchant_ownpay'] > 0){
	        	if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false && $_POST['app_type'] == 'packapp'){
	                if($this->config['city_ownpay']){
	                    $merchant_ownpay = D('City_ownpay')->field('city_id',true)->where(array('city_id'=>$now_merchant['city_id']))->find();
	                    foreach($merchant_ownpay as $ownKey=>$ownValue){
	                        if($ownKey == 'weixin'){
		                        $ownValueArr = unserialize($ownValue);
		                        if($ownValueArr['open']){
		                            $ownValueArr['is_own'] = 6;
		                            $pay_method = $ownValueArr;
		                        }
		                    }
	                    }
	                }
	                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
	                foreach ($merchant_ownpay as $ownKey => $ownValue) {
	                    if($ownKey == 'weixin'){
	                        $ownValueArr = unserialize($ownValue);
	                        if($ownValueArr['open']){
								$merchant_bind = D('Weixin_bind')->field(true)->where(array('mer_id' => $mer_id, 'type' => 0))->find();

								$weixin_bind_user_condition = array('appid' => $merchant_bind['authorizer_appid'], 'mer_id' => $mer_id);
                                if($_POST['is_pay_for_others'] && $_POST['pay_uid_for_others']){
                                    //代付
                                    $weixin_bind_user_condition['uid'] = $_POST['pay_uid_for_others'];
                                }else if ($order_info['openid']) {
									// 未登录
									$weixin_bind_user_condition['plat_openid'] = $order_info['openid'];
								}else{
									$weixin_bind_user_condition['uid'] = $this->_uid;
								}

								$bindUser = M('Weixin_bind_user')->field(true)->where($weixin_bind_user_condition)->find();
								if($bindUser['openid']){
									$ownValueArr['is_own'] = true;
									$pay_method = $ownValueArr;
								}
	                        }
	                    }
	                }
	            }
	        }
	        if($pay_method['is_own'] > 0){
	        	session_start();
	        	$merchant_bind = D('Weixin_bind')->field(true)->where(array('mer_id' => $mer_id, 'type' => 0))->find();

	        	$weixin_bind_user_condition = array('appid' => $merchant_bind['authorizer_appid'], 'mer_id' => $mer_id);
				if($_POST['is_pay_for_others'] && $_POST['pay_uid_for_others']){
				    //代付
                    $weixin_bind_user_condition['uid'] = $_POST['pay_uid_for_others'];
                }else if ($order_info['openid']) {
					// 未登录
					$weixin_bind_user_condition['plat_openid'] = $order_info['openid'];
				}else{
					$weixin_bind_user_condition['uid'] = $this->_uid;
				}
		        $bindUser = M('Weixin_bind_user')->field(true)->where($weixin_bind_user_condition)->find();

		        $_SESSION['open_authorize_openid'] = $bindUser['openid'];
	      	}

	      	//配置服务商子商户支付
	        if ($this->config['open_sub_mchid']) {
	            $is_sub_mchid = false;
	            if($pay_method['is_own'] === 6){
	                $is_sub_mchid = true;
	            }
	            else{
	                $now_store = D('Merchant_store')->get_store_by_storeId($order_info['store_id']);
	                if ($order_info['store_id'] != 0 && $now_store['open_sub_mchid'] && $now_store['sub_mch_id'] > 0) {
	                    $pay_method['sub_mch_id'] = $now_store['sub_mch_id'];

		                $pay_method['open_weixin_profit_sharing'] = C('config.open_weixin_profit_sharing') && $now_store['open_weixin_profit_sharing'] ? 1 : 0;// 是否开启分账功能

	                    $pay_method['is_own'] = 3;
	                    $is_sub_mchid = true;
	                } else if ($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
	                    $pay_method['sub_mch_id'] = $now_merchant['sub_mch_id'];

		                $pay_method['open_weixin_profit_sharing'] = C('config.open_weixin_profit_sharing') && $now_merchant['open_weixin_profit_sharing'] ? 1 : 0;// 是否开启分账功能
	                    $pay_method['is_own'] = 2;
	                    $is_sub_mchid = true;
	                } else if ($order_info['business_type'] == 'house_village_pay' || $order_info['business_type'] == 'house_village_express' || $order_info['business_type'] == 'house_village_pay_cashier') {
	                    $now_village = D('House_village')->get_one($order_info['order_info']['village_id']);
	                    if ($now_village['sub_mch_id'] > 0) {
	                        $pay_method['is_own'] = 5;
	                        $pay_method['sub_mch_id'] = $now_village['sub_mch_id'];
	                        $is_sub_mchid = true;
	                    }
	                } else if ($pay_method['sub_mch_id'] && $pay_method['is_own']==0){
	                    $is_sub_mchid = true;
	                }
	            }

	            if ($is_sub_mchid) {
	                $pay_method['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
	                $pay_method['pay_weixin_mch_name'] = $this->config['pay_weixin_sp_mch_name'];
	                $pay_method['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
	                $pay_method['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
	                $pay_method['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
	                if(empty($pay_method['pay_weixin_appid'])){
	            		$pay_method['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
	            		$pay_method['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
	            	}
	            }
	        }

	        if(!$is_sub_mchid && C('config.pay_weixin_profit_sharing')){// 官方通道设置分账参数
	            $pay_method['open_weixin_profit_sharing'] = $this->config['pay_weixin_profit_sharing'];
	        }

	        // 保存支付信息
		    M('Payment_money_order')->where(['id'=>$payment_money_order_id])->data(['pay_config'=>json_encode($pay_method),'is_mobile_pay' => 1])->save();

			$import_result = import('@.ORG.pay.Weixin');
			$order_info['DEVICE_ID'] = 'packapp';
			$pay_class = new Weixin($order_info, $pay_money, 'weixin', $pay_method, $now_user, 1);
			$go_pay_param = $pay_class->pay();

			if($go_pay_param['error']){
				$this->returnCode('10001',array(),$go_pay_param['msg']);
			}

			$arr['pay_money'] = $pay_money;
			$arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
			$arr['error'] = 0;
			$arr['orderid'] = $orderid;

			$this->returnCode(0,$arr);
		}else if($_POST['pay_type'] == 'weixin' && $this->DEVICE_ID=='wxcapp'){
			$_SESSION['openid'] = $now_user['group_openid'];

			$pay_method = array(
				'pay_weixin_appid'=>$this->config['pay_wxapp_group_appid'],
				'pay_weixin_key'=>$this->config['pay_wxapp_group_key'],
				'pay_weixin_mchid'=>$this->config['pay_wxapp_group_mchid'],
				'pay_weixin_appsecret'=>$this->config['pay_wxapp_group_appsecret'],
			);


			$import_result = import('@.ORG.pay.Weixin');
			$order_info['DEVICE_ID'] ='wxcapp';
			$pay_class = new Weixin($order_info,$pay_money,'weixin',$pay_method,$now_user,3);
			$go_pay_param = $pay_class->pay();

			if($go_pay_param['error']){
				$this->returnCode('10001',array(),$go_pay_param['msg']);
			}

			$arr['pay_money'] = $pay_money;
			$arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
			$arr['error'] = 0;
			$arr['orderid'] = $orderid;

			$this->returnCode(0,$arr);
		}else if($_POST['pay_type'] == 'weixin' && $this->DEVICE_ID == 'wxapp_paotui') {
			$_SESSION['openid'] = $now_user['paotui_openid'];

			$pay_method = array(
				'pay_weixin_appid' => $this->config['pay_wxapp_paotui_appid'],
				'pay_weixin_key' => $this->config['pay_wxapp_paotui_key'],
				'pay_weixin_mchid' => $this->config['pay_wxapp_paotui_mchid'],
				'pay_weixin_appsecret' => $this->config['pay_wxapp_paotui_appsecret'],
			);

			$import_result = import('@.ORG.pay.Weixin');
			$order_info['DEVICE_ID'] = 'wxapp_paotui';
			$pay_class = new Weixin($order_info, $pay_money, 'weixin', $pay_method, $now_user, 3);
			$go_pay_param = $pay_class->pay();

			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}

			$arr['pay_money'] = $pay_money;
			$arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
			$arr['error'] = 0;
			$arr['orderid'] = $orderid;

			$this->returnCode(0, $arr);
		}else if($_POST['pay_type']=='yzfpay'){
			$mer_id = $order_info['mer_id'];
			$this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
			switch ($this->config['merchant_ownpay']) {
				case '0':
					$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					break;
				case '1':
					$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
						}
					}

					break;
				case '2':
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
						}
					}

					if (empty($pay_method)) {
						$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					}
					break;
			}

			$import_result = import('@.ORG.pay.Yzfpay');
			if($this->DEVICE_ID=='wxapp'){
				$pay_class = new Yzfpay($order_info,$pay_money,'yzfpay',$pay_method[$_POST['pay_type']]['config'],$now_user,0);

			}else{
				$pay_class = new Yzfpay($order_info,$pay_money,'yzfpay',$pay_method[$_POST['pay_type']]['config'],$now_user,2);

			}
			$go_pay_param = $pay_class->pay();
			$this->returnCode(0,$go_pay_param);
		}
		else if ($_POST['pay_type'] == 'ebankpay') {
            $pay_class_name = 'Ebankpay';
            $env = D('Config')->get_this_env($this->DEVICE_ID, $_POST['app_type'] ?? 0);
            if($_POST['app_version'] < 1500){
                $this->returnCode(0,$orderid);//先让前端吊起吧，后端调试的时候签名总是不对。。难受
            }
            // 游客 openid处理
            if (!$now_user && $order_info['openid']) {
                $now_user['openid'] = $order_info['openid'];
            }
            $import_result = import('@.ORG.pay.'.$pay_class_name);

			$merEbankSmchid = D('Merchant')->where(['mer_id'=>$order_info['mer_id']])->getField('ebankpay_smchid');
            $pay_method[$_POST['pay_type']]['config'] = array(
                'pay_ebankpay_pubkey' => $this->config['pay_ebankpay_pubkey'],
                'pay_ebankpay_prikey' => $this->config['pay_ebankpay_prikey'],
                'pay_ebankpay_appid'  => $this->config['pay_ebankpay_appid'],
                'pay_ebankpay_mchid'  => $this->config['pay_ebankpay_mchid'],
                'pay_ebankpay_smchid' => $merEbankSmchid?:'',
                'pay_ebankpay_prikey_pass' => $this->config['pay_ebankpay_prikey_pass'],
                'appPackName' => I('appPackName') ?? '', //android必传
                'appPackSign' => I('appPackSign') ?? '', //android必传
                'appBundleId' => I('appBundleId') ?? '' //ios必传
            );
            $pay_class = new $pay_class_name($order_info, $pay_money,$_POST['pay_type'] , $pay_method[$_POST['pay_type']]['config'], $now_user, 2);
            $go_pay_param = $pay_class->pay($env);

            if ($go_pay_param['error']) {
                $this->returnCode('10001', array(), $go_pay_param['msg']);
            }

            $arr['pay_money'] = $pay_money;
            $arr['redirectH5Pay'] = $go_pay_param['redirectH5Pay'] ?? '';
            $arr['redirct_rizhao'] = $go_pay_param['redirectH5Pay'] ?? '';
            $arr['weixin_param'] = $go_pay_param['weixin_param'];
            $arr['error'] = 0;
            $arr['orderid'] = $orderid;
            $this->returnCode(0,$arr);
        }
		else if($_POST['app_version']>600){
			//alipay支付

			if(strtolower($_POST['pay_type']) == 'alipay'){
				$this->deal_alipay($order_info, $pay_money);
			}
			if($_POST['pay_type']=='weixin' && !empty($this->config['pay_weixinapp_appid'])){
				$pay_class_name = 'Weixin';
				$pay_method['weixin']['config'] = array(
					'pay_weixin_appid' => $this->config['pay_weixinapp_appid'],
					'pay_weixin_key' => $this->config['pay_weixinapp_key'],
					'pay_weixin_mchid' => $this->config['pay_weixinapp_mchid'],
					'pay_weixin_appsecret' => $this->config['pay_weixinapp_appsecret'],
					'pay_weixin_client_cert' => $this->config['pay_weixinapp_cert'],
					'pay_weixin_client_key' => $this->config['pay_weixinapp_cert_key'],
				);
			}
			if($this->DEVICE_ID == 'alipayapp' && $_POST['pay_type']=='alipay'){
				$pay_class_name = 'AlipayApp';
				$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['alipayapp_app_id'];
				$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['alipayapp_app_prikey'];
				$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['alipayapp_app_pubkey'];
			}
			elseif ($_POST['pay_type']=='alipay' && $this->config['new_pay_alipay_app_public_key'] != '' && $this->config['new_pay_alipay_app_appid'] != '' && $this->config['new_pay_alipay_app_private_key'] != '') {
				$pay_class_name = 'AlipayApp';
				$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
				$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
				$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
			}
			elseif($_POST['pay_type'] == 'alipay' && $_POST['app_type'] == 'packapp'){
				$pay_class_name = 'AlipayApp';
				$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['pay_alipayh5_appid'];
				$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['pay_alipayh5_merchant_private_key'];
				$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['pay_alipayh5_public_key'];
			}
            else if ($_POST['pay_type'] == 'ebankpay') {
                $pay_class_name = 'Ebankpay';
                $env = D('Config')->get_this_env($this->DEVICE_ID, $_POST['app_type'] ?? 0);
			}else if ($_POST['pay_type'] == 'hqpay_wx' || $_POST['pay_type'] == 'hqpay_al') {
                $pay_class_name = 'Hqpay';
            }
			elseif ($_POST['pay_type'] == 'farmersbankpay'){
                $pay_class_name = 'Farmersbankpay';
            }
               
			if($_POST['app_version'] < 1500){
				$this->returnCode(0,$orderid);//先让前端吊起吧，后端调试的时候签名总是不对。。难受
			}
			if(empty($pay_class_name)){
				$this->returnCode(0,$orderid);
			}

			//配置服务商子商户支付
	        if ($this->config['open_sub_mchid']  && (!isset($_POST['app_package_name']) || !trim($_POST['app_package_name']))) {
	            $is_sub_mchid = false;
                $now_store = D('Merchant_store')->get_store_by_storeId($order_info['store_id']);
                if ($order_info['store_id'] != 0 && $now_store['open_sub_mchid'] && $now_store['sub_mch_id'] > 0) {
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_store['sub_mch_id'];
                    $pay_method['weixin']['config']['sub_appid'] = $now_store['sub_appid'];

	                $pay_method['weixin']['config']['open_weixin_profit_sharing'] = C('config.open_weixin_profit_sharing') && $now_store['open_weixin_profit_sharing'] ? 1 : 0;// 是否开启分账功能
                    $pay_method['weixin']['config']['is_own'] = 3;
                    $is_sub_mchid = true;
                } else if ($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                    $pay_method['weixin']['config']['sub_appid'] = $now_merchant['sub_appid'];	                
                    $pay_method['weixin']['config']['open_weixin_profit_sharing'] =  C('config.open_weixin_profit_sharing') && $now_merchant['open_weixin_profit_sharing'] ? 1 : 0;// 是否开启分账功能

                    $pay_method['weixin']['config']['is_own'] = 2;
                    $is_sub_mchid = true;
                } else if ($order_info['business_type'] == 'house_village_pay' || $order_info['business_type'] == 'house_village_express' || $order_info['business_type'] == 'house_village_pay_cashier') {
                    $now_village = D('House_village')->get_one($order_info['order_info']['village_id']);
                    if ($now_village['sub_mch_id'] > 0) {
                        $pay_method['weixin']['config']['is_own'] = 5;
                        $pay_method['weixin']['config']['sub_mch_id'] = $now_village['sub_mch_id'];
                        $is_sub_mchid = true;
                    }
                } else if ($pay_method['weixin']['config']['sub_mch_id'] && $pay_method['weixin']['config']['is_own']==0){
                    $is_sub_mchid = true;
                }

	            if ($is_sub_mchid) {
	                $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
	                $pay_method['weixin']['config']['pay_weixin_mch_name'] = $this->config['pay_weixin_sp_mch_name'];
	                $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
	                $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
	                $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
	            }
	        }	        

	        if(!$is_sub_mchid && C('config.pay_weixin_profit_sharing')){// 官方通道设置分账参数
	            $pay_method['weixin']['config']['open_weixin_profit_sharing'] = $this->config['pay_weixin_profit_sharing'];
	        }

			// 游客 openid处理
			if (!$now_user&&$order_info['openid']) {
				$now_user['openid'] = $order_info['openid'];
			}

			$import_result = import('@.ORG.pay.'.$pay_class_name);

			if($this->DEVICE_ID == 'alipayapp' && $_POST['pay_type']=='alipay'){

				$pay_class = new $pay_class_name($order_info, $pay_money,$_POST['pay_type'] , $pay_method[$_POST['pay_type']]['config'], $now_user, 3);
			}
			else{
                // 单独处理APP包名信息
                if ($_POST['pay_type']=='weixin' && isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
                    $app_package_name = trim($_POST['app_package_name']);
                    $db_list = D('Appapi_pack_list');
                    if (isset($_POST['app_type']) && $_POST['app_type'] && 1 == $_POST['app_type']) {
                        // ios
                        $where_pack_list = [
                            'package_name' => $app_package_name
                        ];
                    } elseif (isset($_POST['app_type']) && $_POST['app_type'] && 2 == $_POST['app_type']) {
                        // 安卓
                        $where_pack_list = [
                            'package_name2' => $app_package_name
                        ];
                    } else {
                        // 或查询
                        $where_pack_list = [
                            'package_name|package_name2' => $app_package_name
                        ];
                    }
                    $pack_info = $db_list->where($where_pack_list)->find();
                    if ($pack_info) {
                        $appid = $pack_info['weixinapp_appid']?$pack_info['weixinapp_appid']:'';
                        $weixinapp_appsecret = $pack_info['weixinapp_appsecret']?$pack_info['weixinapp_appsecret']:'';
                        $pay_method['weixin']['config']['pay_weixin_appid'] = $appid;
                        $pay_method['weixin']['config']['pay_weixin_appsecret'] = $weixinapp_appsecret;
                        if (!$appid || !$weixinapp_appsecret) {
                            $this->returnCodeError("应用【{$pack_info['name']}】缺少微信参数");
                        }
                    }
                }
				$pay_class = new $pay_class_name($order_info, $pay_money,$_POST['pay_type'] , $pay_method[$_POST['pay_type']]['config'], $now_user, 2);
			}

            if ($_POST['pay_type'] == 'ebankpay') {
                $go_pay_param = $pay_class->pay($env ?? '');
            } else if ($_POST['pay_type'] == 'hqpay_wx' || $_POST['pay_type'] == 'hqpay_al') {
                $pay_class_name = 'Hqpay';
                $import_result = import('@.ORG.pay.' . $pay_class_name);
                if (empty($import_result)) {
                    $this->returnCode(0,$orderid);
                }
                if ($_POST['pay_type'] == 'hqpay_wx') {
                    if ($this->DEVICE_ID == 'wxapp' || $this->is_wxapp_browser) {
                        $env = 'wxMini';
                    } else if ($this->is_weixin_browser) {
                        $env = 'wxH5';
                    } else {
                        $env = 'wxApp';
                    }
                } else if ($_POST['pay_type'] == 'hqpay_al') {
                    if ($this->is_alipayapp_browser) {
                        $env = 'alMini';
                    } else if ($this->is_alipay_browser) {
                        $env = 'alH5';
                    } else {
                        $env = 'alApp';
                    }
                }
                $go_pay_param = $pay_class->pay($env);
            }else if($_POST['pay_type'] == 'farmersbankpay'){
                $importResult = import('@.ORG.pay.Farmersbankpay');
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                if (empty($importResult)) {
                    $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
                }
                $order_info['orderid'] = $orderid;
                $pay_class = new Farmersbankpay($order_info, $pay_money, $_POST['pay_type'], $pay_method[$_POST['pay_type']]['config'], $now_user, 1);
                fdump(['farmersBankPay' => $pay_class],'go_pay',1);
                $go_pay_param = $pay_class->pay();
            }else {
                $go_pay_param = $pay_class->pay();
            }
	        // 保存支付信息
		    M('Payment_money_order')->where(['id'=>$payment_money_order_id])->data(['pay_config'=>json_encode($pay_method[$_POST['pay_type']]['config'])])->save();

			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}

            if ($_POST['pay_type'] == 'ebankpay' && in_array($env, ['wxMini', 'wxH5'])) {
                $arr['pay_money']     = $pay_money;
                $arr['redirectH5Pay'] = $go_pay_param['redirectH5Pay'] ?? '';
                $arr['weixin_param']  = $go_pay_param['weixin_param'] ?? '';
                $arr['error']   = 0;
                $arr['orderid'] = $orderid;
                $this->returnCode(0, $arr);
            }
			if($_POST['pay_type']=='weixin'){
				$arr['pay_money'] = $pay_money;
				$arr['weixin_param'] = $go_pay_param['sign'];
				$arr['error'] = 0;
				$arr['orderid'] = $orderid;
				$this->returnCode(0,$arr);
			}
            if ($_POST['pay_type'] == 'farmersbankpay') {
                $arr['pay_money'] = $pay_money;
                $arr['weixin_param'] = $go_pay_param['weixin_param'];
                $arr['error'] = 0;
                $arr['orderid'] = $orderid;
                $this->returnCode(0,$arr);
            }
            if ($_POST['pay_type'] == 'hqpay_wx') {
                $arr['pay_money'] = $pay_money;
                $arr['redirectH5Pay'] = $go_pay_param['redirectH5Pay'] ?? '';
                $arr['weixin_param'] = $go_pay_param['weixin_param'];
                $arr['error'] = 0;
                $arr['orderid'] = $orderid;
                $this->returnCode(0,$arr);
            }
            if ($_POST['pay_type'] == 'hqpay_al') {
                $arr['orderStr'] = $go_pay_param['alipay_param'];
                $this->returnCode(0,$arr);
            }
			if($_POST['pay_type']=='alipay'){
			    if($go_pay_param['tradeNO']){
			        $arr['tradeNO'] = $go_pay_param['tradeNO'];
			    }else{
			        $arr['orderStr'] = $go_pay_param['orderStr'];
			    }
				$this->returnCode(0,$arr);
			}
		}else{
			$this->returnCode(0,$orderid);
		}
	}

	//支付宝支付（后面各种渠道支付宝都可以走这里）
	protected function deal_alipay($order_info, $pay_money){
		if(empty($order_info) || empty($pay_money)) return ;
		if($this->config['pay_wepayez_open'] == 1){//哈客定制的渠道支付宝支付
			$order_info['pay_type'] = $_POST['pay_type'];
			$order_info['order_id'] = $_POST['order_type'] . '_' . $order_info['order_id'];
			$order_info['order_total_money'] = $pay_money;
			$lowfeepay = new LowFeePay('wepayez');//哈客定制支付宝渠道
			$go_pay_param = $lowfeepay->pay($order_info);

			if ($go_pay_param['error']) {
				$this->returnCode('10001', array(), $go_pay_param['msg']);
			}
			$pay_arr = array(
				'orderStr' => $go_pay_param['param'],
				'redirctUrl' => $go_pay_param['redirctUrl']
			);
			$this->returnCode(0,$pay_arr);
		}
		return ;
	}

	public function get_orderid($table,$order_id,$offline=0){
		if(strlen($order_id) < 12){
			$orderStr = 'order_id';
		}else{
			$orderStr = 'orderid';
		}
		$order =  D($table);
		$tmp_orderid = D('Tmp_orderid');
		if($offline){
			$now_order = $order->where(array($orderStr=>$order_id))->find();
		}else{
			$now_order = $order->where(array($orderStr=>$order_id))->find();
			if(empty($now_order)){
				$res = $tmp_orderid->where(array('orderid'=>$order_id))->find();
				$now_order = $order->where(array('order_id'=>$res['order_id']))->find();
				$order->where(array('order_id'=>$res['order_id']))->setField('orderid',$order_id);
				$now_order['orderid']=$order_id;
			}
		}
		if(empty($now_order)){
			$this->returnCode('20045010');
		}

		return $now_order;
	}
	//跳转通知
	public function return_url(){
        fdump_api([
            '请求参数' => [$_GET, $_POST],
            'data' => file_get_contents("php://input"),
        ], 'pay/weixin_pay_notice', 1);
		$pay_type = I('get.pay_type');
		$pay_method = D('Config')->get_pay_method();
		if(empty($pay_method)){
			$this->returnCode('20070003');
		}
		if(empty($pay_method[$pay_type])){
			$this->returnCode('20070004');
		}
		$pay_class_name = ucfirst($pay_type);
		$import_result = import('@.ORG.pay.'.$pay_class_name);
		if(empty($import_result)){
			$this->returnCode('20070005');
		}
		$is_mobile = 2;
		if($pay_type == 'weixin'){
			$pay_method[$pay_type]['config'] = array(
				'pay_weixin_appid'=>$this->config['pay_weixinapp_appid'],
				'pay_weixin_mchid'=>$this->config['pay_weixinapp_mchid'],
				'pay_weixin_key'=>$this->config['pay_weixinapp_key'],
				'pay_weixin_appsecret'=>$this->config['pay_weixinapp_appsecret']
			);
		}
		$is_merchant_wechat_mini_pay = false;
		if($_GET['Device-Id'] == 'wxapp'){
			$pay_method[$pay_type]['config'] = array(
				'pay_weixin_appid'=>$this->config['pay_wxapp_appid'],
				'pay_weixin_mchid'=>$this->config['pay_wxapp_mchid'],
				'pay_weixin_key'=>$this->config['pay_wxapp_key'],
				'pay_weixin_appsecret'=>$this->config['pay_wxapp_appsecret']
			);
			$is_mobile = 3;
			$xml = file_get_contents("php://input");
			$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            fdump_api(['xml' => $xml, 'array_data' => $array_data], 'pay/weixin_pay_notice', 1);
            
			if($array_data['attach']!='weixin'&&$array_data['attach']>0 ){
                $out_trade_no = $array_data['out_trade_no'];
                $split_trade = explode('_',$out_trade_no);
                $is_own = $split_trade[2] ?? 0;
                $orderid = $split_trade[1]??'';
                $order_type = $split_trade[0]??'';

                if ($order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad') {
                    $order_table = 'Meal_order';
                } else if ($order_type == 'recharge') {
                    $order_table = 'User_recharge_order';
                } else if ($order_type == 'ticket') {
                    $order_table = 'Scenic_order';
                } else if ($order_type == 'guide') {
                    $order_table = 'Scenic_aguide_order';
                } else if ($order_type == 'mcard') {
                    $order_table = 'Membership_card_order';
                } else if ($order_type == 'shop' || $order_type == 'mall') {
                    $order_table = 'Shop_order';
                } else if ($order_type == 'settlein') {
                    $order_table = 'Merchant_settle_in_pay';
                } else {
                    $order_table = ucfirst($order_type) . '_order';
                }
                $now_order = $this->get_orderid($order_table,$orderid);

                $is_sub_mchid = false;
                if ($is_own == 3 && $now_order['store_id']) {
                    //店铺子商户
                    $now_store = D('Merchant_store')->get_store_by_storeId($now_order['store_id']);
                    if ($now_store['open_sub_mchid'] && $now_store['sub_mch_id'] > 0) {
                        $pay_method[$pay_type]['config']['sub_mch_id'] = $now_store['sub_mch_id'];
                        $pay_method[$pay_type]['config']['is_own'] = 3;
                        $is_sub_mchid = true;
                    }
                } else if ($is_own == 2) {
                    //商家子商户
                    $now_merchant = D('Merchant')->get_info($array_data['attach']);
                    if ($now_merchant['sub_mch_id'] > 0) {
                        $pay_method[$pay_type]['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                        $pay_method[$pay_type]['config']['is_own'] = 2;
                        $is_sub_mchid = true;
                    }
                } else if ($is_own == 5) {
                    //社区子商户
                    if ($array_data['attach']>0) {
                        $now_order['village_id'] = $array_data['attach'];
                    } elseif (!isset($now_order['village_id']) && isset($now_order['business_type']) && isset($now_order['business_id'])) {
                        $order_param = D(ucfirst($now_order['business_type']).'_order')->get_pay_order($now_order['business_id']);
                        $order_param_info = $order_param['order_info'];
                        $now_order['village_id'] = $order_param_info['village_id'];
                    }
                    $now_village = D('House_village')->get_one($now_order['village_id']);
                    if ($now_village['sub_mch_id'] > 0) {
                        $pay_method[$pay_type]['config']['sub_mch_id'] = $now_village['sub_mch_id'];
                        $pay_method[$pay_type]['config']['is_own'] = 5;
                        $is_sub_mchid = true;
                    }
                }else{
                    $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id'=>$array_data['attach'],'bind_type'=>0))->find();
                    if($wxapp_own_pay){
                        $is_merchant_wechat_mini_pay = true;


                        $pay_method[$pay_type]['config'] = array(
                            'pay_weixin_appid'=>$wxapp_own_pay['appid'],
                            'pay_weixin_key'=>$wxapp_own_pay['wxpay_key'],
                            'pay_weixin_mchid'=>$wxapp_own_pay['wxpay_merid'],
                            'pay_weixin_appsecret'=>$wxapp_own_pay['appsecret'],
                            'is_own'=>1,
                        );
                    }
                }

                if ($is_sub_mchid) {
                    $pay_method[$pay_type]['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
                    $pay_method[$pay_type]['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
                    $pay_method[$pay_type]['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
                    $pay_method[$pay_type]['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
                }
			}
		}else if($_GET['Device-Id'] =='wxcapp'){
			$is_mobile = 3;
			$pay_method[$pay_type]['config'] = array(
				'pay_weixin_appid'=>$this->config['pay_wxapp_group_appid'],
				'pay_weixin_key'=>$this->config['pay_wxapp_group_key'],
				'pay_weixin_mchid'=>$this->config['pay_wxapp_group_mchid'],
				'pay_weixin_appsecret'=>$this->config['pay_wxapp_group_appsecret'],
			);
		}else if($_GET['Device-Id']=='wxapp_paotui'){
			$is_mobile = 3;
			$pay_method[$pay_type]['config'] = array(
				'pay_weixin_appid'=>$this->config['pay_wxapp_paotui_appid'],
				'pay_weixin_key'=>$this->config['pay_wxapp_paotui_key'],
				'pay_weixin_mchid'=>$this->config['pay_wxapp_paotui_mchid'],
				'pay_weixin_appsecret'=>$this->config['pay_wxapp_paotui_appsecret'],
			);
		}

		if ($this->config['open_sub_mchid'] && !$is_merchant_wechat_mini_pay && $array_data['attach'] > 0) {
		    if (!isset($now_order['village_id']) || !$now_order['village_id']) {
                $now_merchant = D('Merchant')->get_info($array_data['attach']);
                if($now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0){
                    $pay_method['weixin']['config']['pay_weixin_appid'] = $_GET['Device-Id'] == 'wxapp' ? $this->config['pay_wxapp_appid'] : $this->config['pay_weixin_appid'];
                    $pay_method['weixin']['config']['pay_weixin_appsecret'] = $_GET['Device-Id'] == 'wxapp' ? $this->config['pay_wxapp_appsecret'] : $this->config['pay_weixin_appsecret'];
                    $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
                    $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                    $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
                    $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
                }
            } else {
		        if (isset($now_village['sub_mch_id'])&&$now_village['sub_mch_id']) {
                    $pay_method['weixin']['config']['pay_weixin_appid'] = $_GET['Device-Id'] == 'wxapp' ? $this->config['pay_wxapp_appid'] : $this->config['pay_weixin_appid'];
                    $pay_method['weixin']['config']['pay_weixin_appsecret'] = $_GET['Device-Id'] == 'wxapp' ? $this->config['pay_wxapp_appsecret'] : $this->config['pay_weixin_appsecret'];
                    $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
                    $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_village['sub_mch_id'];
                    $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
                    $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
                }
            }
		}

		$pay_class = new $pay_class_name('','',$pay_type,$pay_method[$pay_type]['config'],$this->user_session,$is_mobile);
		$get_pay_param = $pay_class->return_url();
		
		fdump_sql(['get' => $_GET,'now_order' => $now_order,'get_pay_param' => $get_pay_param, 'array_data' => file_get_contents("php://input"), 'pay_type' => $pay_type, 'pay_method' => $pay_method], 'appapi_get_pay_param');

		// file_put_contents('./weixinpay_test/'.$_SERVER['HTTP_HOST'].'_'.date('Y-m-d').'.php',var_export($get_pay_param,true).PHP_EOL.PHP_EOL.file_get_contents('./weixinpay_test/'.$_SERVER['HTTP_HOST'].'_'.date('Y-m-d').'.php'));
		if ($get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad') {
			$order_table = 'Meal_order';
		}else if($get_pay_param['order_param']['order_type']=='recharge'){
			$order_table = 'User_recharge_order';
		}else if($get_pay_param['order_param']['order_type']=='ticket'){
            $order_table = 'Scenic_order';
        }else if($get_pay_param['order_param']['order_type']=='guide'){
            $order_table = 'Scenic_aguide_order';
        }else if($get_pay_param['order_param']['order_type']=='mcard'){
            $order_table = 'Membership_card_order';
        }else if($get_pay_param['order_param']['order_type']=='settlein'){
            $order_table = 'merchant_settle_in_pay';
        }else{
			$order_table = ucfirst($get_pay_param['order_param']['order_type']).'_order';
		}

		$now_order = $this->get_orderid($order_table,$get_pay_param['order_param']['order_id']);
		$get_pay_param['order_param']['order_id'] = $now_order['orderid'];

		$payment_where = ['orderid'=>$get_pay_param['order_param']['order_id'], 'order_type'=>$get_pay_param['order_param']['order_type']];
        if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
            $payment_where['order_type'] = ['in',['shop','mall']];
        }

		if(empty($get_pay_param['error'])){
			//在线支付回调判断是否与支付前金额相等
			if($pay_type!='offline'){
				if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
					if($payment_money_order['pay_rate']>0){
						$get_pay_param['order_param']['pay_money'] = getFormatNumber($get_pay_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
						M('Payment_money_order')->where($payment_where)->setField('paid_money',$get_pay_param['order_param']['pay_money']);
					}
					else{
						M('Payment_money_order')->where($payment_where)->setField('paid_money',$get_pay_param['order_param']['pay_money']);
						if($payment_money_order['payment_money'] > $get_pay_param['order_param']['pay_money'] + 0.1){
							fdump_sql(array('get_pay_param'=>$get_pay_param,'payment_money_order'=>$payment_money_order),'weixin_payment_error_return');
							$this->returnCode('10070007');
						}
					}
				}else{
					$this->returnCode('10070007');
				}
			}
			if($get_pay_param['order_param']['order_type'] == 'group'){
				$pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout'){
				$pay_info = D('Meal_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
				$pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'recharge'){
				$pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'store'){
				$pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'weidian'){
				$pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
				if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
					$this->NoticeWDAsyn($now_order['orderid']);
				}
			}else if($get_pay_param['order_param']['order_type'] == 'plat'){
				$pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'appoint'){
				if($now_order['is_initiative']==2){
					$pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
				}else{
					$pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
				}
			}else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
				$pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
			}else if ($get_pay_param['order_param']['order_type'] == 'integral_mall'){
				$pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
			}else if ($get_pay_param['order_param']['order_type'] == 'ticket'){
                $pay_info = D('Scenic_order')->after_pay($get_pay_param['order_param']);
            }else if ($get_pay_param['order_param']['order_type'] == 'guide'){
                $pay_info = D('Scenic_aguide')->after_pay($get_pay_param['order_param']);
            }else if ($get_pay_param['order_param']['order_type'] == 'spread'){
                $pay_info = D('Merchant_spread_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'mcard'){
                $pay_info = D('Membership_card_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'settlein'){
                $pay_info = D('Merchant_settle_in_pay')->after_pay($get_pay_param['order_param']);
            }
			if(empty($pay_info['error'])){
				if($get_pay_param['order_param']['pay_type'] == 'weixin'){
					exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
				}
				$pay_info['msg'] = L_('订单付款成功！');
				$this->returnCode(0);
			}
			fdump_sql(array('pay_info'=>$pay_info,'get_pay_param'=>$get_pay_param),'weixin_return_pay_error');
			$this->returnCode('20070007');
		} else {
			$this->returnCode(0, $get_pay_param['msg']);
		}
	}

    public function weixinWithdrawNotify()
    {
        fdump_api(['withdrawV3Notify' => file_get_contents('php://input')], 'pay/withdrawV3Notify', 1);
        import('@.ORG.withdraw.WeixinPayV3');
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        $resource = $params['resource'];
        $associatedData = $resource['original_type'];
        $nonceStr = $resource['nonce'];
        $ciphertext = $resource['ciphertext'];

        $withdraw = new WeixinPayV3();
        $notifyData = $withdraw->decryptToString($associatedData, $nonceStr, $ciphertext);

        if (empty($notifyData)) {
            exit(json_encode([
                "code" => "FAIL",
                "message" => "解密失败"
            ]));
        }
        fdump_api(['withdrawV3NotifyDecrypt' => $notifyData], 'pay/withdrawV3Notify', 1);
        $notifyData = json_decode($notifyData, true);
        M('Withdraw_request_info')->where(['out_bill_no' => $notifyData['out_bill_no']])->save([
            'paid_state' => $notifyData['state'],
            'notify_data' => json_encode($notifyData),
        ]);
        try {
            $withdrawInfo = M('Withdraw_request_info')->where(['out_bill_no' => $notifyData['out_bill_no']])->find();
            if(
                $withdrawInfo &&
                $withdrawInfo['withdraw_id'] &&
                in_array($notifyData['state'], ['FAIL', 'CANCELLED'])
            ) {//提现失败和取消
                if($withdrawInfo['withdraw_type'] == 'company_pay') {
                    D('Companypay')->refuseWithdraw($withdrawInfo['withdraw_id'], $notifyData['fail_reason']);
                }else
                if($withdrawInfo['withdraw_type'] == 'merchant_withdraw') {
                    D('Merchant_withdraw')->refuseWithdraw($withdrawInfo['withdraw_id'], $notifyData['fail_reason']);
                }
            }
        }catch (\Exception $e){
            fdump_api([
                'weixinWithdrawNotifyError' => $e->getMessage(), 
                $withdrawInfo['withdraw_id'], 
                $notifyData['fail_reason']
            ], 'withdraw/weixinWithdrawNotify', 1);
        }

        exit(json_encode([
            "code" => "SUCCESS",
            "message" => ""
        ]));
    }
	//支付异步通知
	public function alipay_notice(){
		fdump_sql($_POST,'alipay_notice');
        fdump_sql($_SERVER,'alipay_notice_server');
		$_POST['subject'] = html_entity_decode(html_entity_decode($_POST['subject']));
		$import_result = import('@.ORG.pay.Alipay');
		//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
		//合作身份者id，以2088开头的16位纯数字
		$alipay_config['partner']		= $this->config['pay_alipay_app_pid'];

		//商户的私钥（后缀是.pen）文件相对路径
		if($_POST['app_id']!=''){
			$this->alipay_notice_new();
		}else if($_POST['body']==2){
			$alipay_config['private_key_path']	= $this->config['pay_alipay_app_private_key_android'];
		}else{
			$alipay_config['private_key_path']	= $this->config['pay_alipay_app_private_key_ios'];
		}

		//支付宝公钥（后缀是.pen）文件相对路径
		$alipay_config['ali_public_key_path']=$this->config['pay_alipay_app_public_key'];

//		D('Pay_async_record')->alipay_record($_POST);
		$pay_class = new Alipay();
		$verify_result = $pay_class->app_notice($alipay_config);
		if($verify_result) {//验证成功
			$out_trade_no = $_POST['out_trade_no'];
			$trade_no = $_POST['trade_no'];
			$trade_status = $_POST['trade_status'];
			$trade_no = explode('_',$out_trade_no);
			//订单类型
			$order_type = $trade_no[0];
			//长id
			$orderid= $trade_no[1];
			$order_param = array(
				'order_id' => $orderid,
				'pay_type' => 'alipay',
				'third_id' => $_POST['trade_no'],
				'pay_money' => $_POST['total_fee'],
				'is_mobile' => 2,
				'is_own'=>0,
			);

			$payment_where = ['orderid'=>$orderid,'order_type'=>$order_type];
			if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
				$payment_where['order_type'] = ['in',['shop','mall']];
			}
		
			//判断回调金额
			if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
				if($payment_money_order['pay_rate']>0){
					$order_param['pay_money'] = getFormatNumber($order_param['pay_money']/$payment_money_order['pay_rate']);
					M('Payment_money_order')->where($payment_where)->setField('paid_money',$order_param['pay_money']);
				}
				else{
					M('Payment_money_order')->where($payment_where)->setField('paid_money',$order_param['pay_money']);

					if($payment_money_order['payment_money'] > $order_param['pay_money'] + 0.1){
						fdump_sql(array('order_param'=>$order_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_return');
						$this->returnCode('10070007');
					}
				}
			}else{
				$this->returnCode('10070007');
			}

			if($_POST['trade_status'] == 'TRADE_FINISHED') {
				$this->returnCode('10070008');
			}
			else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
				// 支付成功

				if ($order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad') {
					$order_table = 'Meal_order';
				}else if($order_type=='recharge'){
					$order_table = 'User_recharge_order';
				}else{
					$order_table = ucfirst($order_type).'_order';
				}

				$now_order = $this->get_orderid($order_table,$orderid);
				$order_param['order_id'] = $now_order['orderid'];
				if($order_type == 'group'){
					$pay_info = D('Group_order')->after_pay($order_param);
				}else if($order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad'){
					$pay_info = D('Meal_order')->after_pay($order_param);
				}else if($order_type == 'shop' || $order_type == 'mall'){
					$pay_info = D('Shop_order')->after_pay($order_param);
				}else if($order_type == 'store'){
					$pay_info = D('Store_order')->after_pay($order_param);
				}else if($order_type == 'recharge'){
					$pay_info = D('User_recharge_order')->after_pay($order_param);
				}else if($order_type == 'weidian'){
					$pay_info = D('Weidian_order')->after_pay($order_param);
					if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
						$this->NoticeWDAsyn($now_order['orderid']);
					}
				}else if($order_type == 'plat'){
					$pay_info = D('Plat_order')->after_pay($order_param);
				}else if($order_type == 'appoint'){
					$pay_info = D('Appoint_order')->after_pay($order_param);
				}else if($order_type  == 'balance-appoint'){
					$pay_info = D('Appoint_order')->balance_after_pay($order_param);
				}else if ($order_type == 'integral_mall'){
					$pay_info = D('Merchant_integral_mall_goods_order')->after_pay($order_param);
				}else if ($order_type == 'spread'){
                    $pay_info = D('Merchant_spread_order')->after_pay($order_param);
				}else if($order_type == 'mcard'){
                    $pay_info = D('Membership_card_order')->after_pay($order_param);
                }
				if(empty($pay_info['error'])){
					$arr['url']= $pay_info['url'];
					$this->returnCode(0,$arr);
				}
				$this->returnCode('10070007');

			}

		}else {
			$this->returnCode('10070009');
		}


	}

	//根据 orderid 获取now_order
	public function get_order_by_orderid($orderid,$order_type){

		switch($order_type){
			case 'group':
				$now_order =$this->get_orderid('Group_order',$orderid);
				break;
			case 'meal':
				$now_order =$this->get_orderid('Meal_order',$orderid);
				break;
			case 'takeout':
			case 'food':
			case 'foodPad':
				$now_order =$this->get_orderid('Meal_order',$orderid);
				break;
			case 'weidian':
				$now_order =$this->get_orderid('Weidian_order',$orderid);
				break;
			case 'recharge':
				$now_order =$this->get_orderid('User_recharge_order',$orderid);
				break;
			case 'appoint':
			case 'balance-appoint':
				$now_order =$this->get_orderid('Appoint_order',$orderid);
				break;
			case 'waimai':
				$now_order =$this->get_orderid('Waimai_order',$orderid);
				break;
			case 'wxapp':
				$now_order =$this->get_orderid('Wxapp_order',$orderid);
				break;
			case 'shop':
				$now_order =$this->get_orderid('Shop_order',$orderid);
				break;
			case 'plat':
				$now_order =$this->get_orderid('Plat_order',$orderid);
				break;
			case 'store':
				$now_order =$this->get_orderid('Store_order',$orderid);
				break;
			case 'integral_mall':
				$now_order =$this->get_orderid('Merchant_integral_mall_goods_order',$orderid);
				break;
			default:
				$now_order = array();
		}
		return $now_order;

	}

	public function app_alipay_back_alipayapp($alipay_return=array(),$now_order=array()){

		$import_result = import('@.ORG.pay.Aop.AopClient');
		$import_result = import('@.ORG.pay.Aop.AlipayTradeQueryRequest');
		$import_result = import('@.ORG.pay.Aop.SignData');
		$aop = new AopClient();
		$this->signtype = 'RSA2';
		$this->alipay_public_key = $this->config['alipayapp_app_pubkey'];
		$aop->alipayrsaPublicKey = $this->alipay_public_key;


		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $this->config['alipayapp_app_id'];
		$aop->rsaPrivateKey = $this->config['alipayapp_app_prikey'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='utf-8';
		$aop->format='json';
		$request = new AlipayTradeQueryRequest ();
		$request->setBizContent("{" .
			"\"out_trade_no\":\"{$alipay_return['out_trade_no']}\"," .
			"\"trade_no\":\"{$alipay_return['trade_no']}\"" .
			"  }");

		$result = $aop->execute ( $request);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){

		} else {
			$this->returnCode('10070007');
		}
		$_GET['order_id'] = $now_order['order_id'];
		$order_param = array(
			'order_id' => strlen($alipay_return['order_id']) < 12 ? $now_order['orderid'] : $alipay_return['order_id'],
			'pay_type' => 'alipay',
			'third_id' => $alipay_return['trade_no'],
			'pay_money' => $alipay_return['total_amount'],
			'is_mobile' => 2,
			'is_own'=>0,
		);
		if($now_order['order_type']=='mall'){
			$now_order['order_type']='shop';
		}
		if($payment_money_order = M('Payment_money_order')->where(array('orderid'=>$order_param['order_id'],'order_type'=>$now_order['order_type']))->find()){
			M('Payment_money_order')->where(array('orderid'=>$order_param['order_id'],'order_type'=>$now_order['order_type']))->setField('paid_money',$order_param['pay_money']);
			if($payment_money_order['payment_money'] > $order_param['pay_money'] + 0.1){
				fdump_sql(array('order_param'=>$order_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_back');
				$this->returnCode('10070007');
			}
		}else{
			$this->returnCode('10070007');
		}
		if($result){
			switch($_GET['order_type']){
				case 'group':
					D('Group_order')->after_pay($order_param);
					break;
				case 'meal':
				case 'takeout':
				case 'food':
				case 'foodPad':
					$go_query_param['order_param']['orderid'] = $order_param['order_id'];
					//unset($go_query_param['order_param']['order_id']);
					D('Meal_order')->after_pay($order_param, $_GET['order_type']);
					break;
				case 'weidian':
					$pay_info = D('Weidian_order')->after_pay($order_param);
					if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
						$this->NoticeWDAsyn($now_order['orderid']);
					}
					break;
				case 'recharge':
					D('User_recharge_order')->after_pay($order_param);
					break;
				case 'waimai':
					D('Waimai_order')->after_pay($order_param);
					break;
				case 'appoint':
					D('Appoint_order')->after_pay($order_param);
					break;
				case 'wxapp':
					D('Wxapp_order')->after_pay($order_param);
					break;
				case 'shop':
					D('Shop_order')->after_pay($order_param);
					break;
				case 'store':

					$res = D('Store_order')->after_pay($order_param);
				case 'plat':
					D('Plat_order')->after_pay($order_param);
					break;
				case 'balance-appoint':
					D('Appoint_order')->balance_after_pay($order_param);
					break;
				case 'integral_mall':
					D('Merchant_integral_mall_goods_order')->after_pay($order_param);
					break;
                case 'ticket':
                    D('Scenic_order')->after_pay($order_param);
                    break;
                case 'guide':
                    D('Scenic_aguide')->after_pay($order_param);
                    break;

                case 'spread':
                    D('Merchant_spread_order')->after_pay($order_param);
                    break;
                case 'mcard':
                    D('Membership_card_order')->after_pay($order_param);

                    break;
			}
		}else{
			$this->returnCode('10070007');
		}



		switch($_GET['order_type']){
			case 'group':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=My&a=group_order&order_id='.$_GET['order_id'];
				break;
			case 'meal':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Meal&a=detail&orderid='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'takeout':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Takeout&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'food':
			case 'foodPad':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Food&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'weidian':
				$redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
				break;
			case 'appoint':
			case 'balance-appoint':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=My&a=appoint_order&order_id='.$_GET['order_id'];
				break;
			case 'waimai':
				$redirctUrl = C('config.site_url').'/index.php?g=Waimaiwap&c=Index&a=order&order_id='.$_GET['order_id'];
				break;
			case 'recharge':
				$redirctUrl = C('config.site_url').'/wap.php?c=My&a=index';
				break;
			case 'wxapp':
				$redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$_GET['order_id'];
				break;
			case 'shop':
				$redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='.$_GET['order_id'];
				break;
			case 'store':
				if(C('config.pay_success_jump_to_homepage')){
					$redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';;
				}else{
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$_GET['order_id'];
				}
				break;
			case 'plat':
				$redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
				break;
			case 'integral_mall':
				$redirctUrl = C('config.site_url').'/wap.php?c=Integral_mall&a=order_detail_new&order_id='.$now_order['order_id'];
				break;
            case 'ticket':
                $redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=ticket_order_details&order_id='.$now_order['order_id'];
                break;
            case 'guide':
                $redirctUrl = C('config.site_url') . '/wap.php?c=Scenic_user&a=aguide_order_list';
                break;

            case 'spread':
                $redirctUrl = C('config.site_url') . '/wap.php?c=My&a=my_spread';
                break;
            case 'mcard':
                $redirctUrl = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMemberList';
                break;
		}

		// $arr['url']=$redirctUrl;
		// $this->returnCode(0,$redirctUrl);
		$arr['url']=$redirctUrl;
		$this->returnCode(0,$arr);
	}

	public function app_alipay_back_new($alipay_return=array(),$now_order=array()){

		$alipay_return_msg = $alipay_return;
		$import_result = import('@.ORG.pay.Aop.AopClient');
		$import_result = import('@.ORG.pay.Aop.AlipayTradeQueryRequest');
		$import_result = import('@.ORG.pay.Aop.SignData');
		$aop = new AopClient();
		$this->signtype = $alipay_return['sign_type'];
		$this->alipay_public_key = $this->config['new_pay_alipay_app_public_key'];
		$aop->alipayrsaPublicKey = $this->alipay_public_key;


		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $alipay_return_msg['app_id'];
		$aop->rsaPrivateKey = $this->config['new_pay_alipay_app_private_key'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset=$alipay_return_msg['charset'];
		$aop->format='json';
		$request = new AlipayTradeQueryRequest ();
		$request->setBizContent("{" .
			"\"out_trade_no\":\"{$alipay_return_msg['out_trade_no']}\"," .
			"\"trade_no\":\"{$alipay_return_msg['trade_no']}\"" .
			"  }");

		$result = $aop->execute ( $request);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){

		} else {
			$this->returnCode('10070007');
		}
		$_GET['order_id'] = $now_order['order_id'];
		$order_param = array(
			'order_id' => strlen($alipay_return_msg['order_id']) < 12 ? $now_order['orderid'] : $alipay_return_msg['order_id'],
			'pay_type' => 'alipay',
			'third_id' => $alipay_return_msg['trade_no'],
			'pay_money' => $alipay_return_msg['total_amount'],
			'is_mobile' => 2,
			'is_own'=>0,
		);
        $payment_where = ['orderid'=>$order_param['order_id'],'order_type'=>$now_order['order_type']];
        if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
            $payment_where['order_type'] = ['in',['shop','mall']];
        }
		
        if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
			if($payment_money_order['pay_rate']>0){
				$order_param['pay_money'] = getFormatNumber($order_param['pay_money']/$payment_money_order['pay_rate']);
                M('Payment_money_order')->where($payment_where)->setField('paid_money',$order_param['pay_money']);
			}
			else{
                M('Payment_money_order')->where($payment_where)->setField('paid_money',$order_param['pay_money']);
				if($payment_money_order['payment_money'] > $order_param['pay_money'] + 0.1){
					fdump_sql(array('order_param'=>$order_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_back');
					$this->returnCode('10070007');
				}
			}
		}else{
			$this->returnCode('10070007');
		}
		if($result){
			switch($_GET['order_type']){
				case 'group':
					D('Group_order')->after_pay($order_param);
					break;
				case 'meal':
				case 'takeout':
				case 'food':
				case 'foodPad':
					$go_query_param['order_param']['orderid'] = $order_param['order_id'];
					//unset($go_query_param['order_param']['order_id']);
					D('Meal_order')->after_pay($order_param, $_GET['order_type']);
					break;
				case 'weidian':
					$pay_info = D('Weidian_order')->after_pay($order_param);
					if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
						$this->NoticeWDAsyn($now_order['orderid']);
					}
					break;
				case 'recharge':
					D('User_recharge_order')->after_pay($order_param);
					break;
				case 'waimai':
					D('Waimai_order')->after_pay($order_param);
					break;
				case 'appoint':
					D('Appoint_order')->after_pay($order_param);
					break;
				case 'wxapp':
					D('Wxapp_order')->after_pay($order_param);
					break;
				case 'shop':
				case 'mall':
					D('Shop_order')->after_pay($order_param);
					break;
				case 'store':

					$res = D('Store_order')->after_pay($order_param);
				case 'plat':
					D('Plat_order')->after_pay($order_param);
					break;
				case 'balance-appoint':
					D('Appoint_order')->balance_after_pay($order_param);
					break;
				case 'integral_mall':
					D('Merchant_integral_mall_goods_order')->after_pay($order_param);
					break;
                case 'ticket':
                    D('Scenic_order')->after_pay($order_param);
                    break;
                case 'guide':
                    D('Scenic_aguide')->after_pay($order_param);
                    break;

                case 'spread':
                    D('Merchant_spread_order')->after_pay($order_param);
                    break;
                case 'mcard':
                    D('Membership_card_order')->after_pay($order_param);
                    break;
			}
		}else{
			$this->returnCode('10070007');
		}



		switch($_GET['order_type']){
			case 'group':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=My&a=group_order&order_id='.$_GET['order_id'];
				break;
			case 'meal':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Meal&a=detail&orderid='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'takeout':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Takeout&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'food':
			case 'foodPad':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Food&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'weidian':
				$redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
				break;
			case 'appoint':
			case 'balance-appoint':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=My&a=appoint_order&order_id='.$_GET['order_id'];
				break;
			case 'waimai':
				$redirctUrl = C('config.site_url').'/index.php?g=Waimaiwap&c=Index&a=order&order_id='.$_GET['order_id'];
				break;
			case 'recharge':
				$redirctUrl =  C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
				break;
			case 'wxapp':
				$redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$_GET['order_id'];
				break;
			case 'shop':
				$redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='.$now_order['order_id'];
				break;
			case 'store':
				if(C('config.pay_success_jump_to_homepage')){
					$redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';;
				}else{
					$redirctUrl = C('config.site_url').'/wap.php?c=My&&a=store_order_detail&order_id='.$_GET['order_id'];
				}
				break;
			case 'plat':
				$redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
				break;
			case 'integral_mall':
				$redirctUrl = C('config.site_url').'/wap.php?c=Integral_mall&a=order_detail_new&order_id='.$now_order['order_id'];
				break;
            case 'ticket':
                $redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=ticket_order_details&order_id='.$now_order['order_id'];
                break;
            case 'guide':
                $redirctUrl = C('config.site_url') . '/wap.php?c=Scenic_user&a=aguide_order_list';
                break;

            case 'spread':
                $redirctUrl = C('config.site_url') . '/wap.php?c=My&a=my_spread';
                break;
            case 'mcard':
                $redirctUrl = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMemberList';
                break;
		}

		// $arr['url']=$redirctUrl;
		// $this->returnCode(0,$redirctUrl);
		$arr['url']=$redirctUrl;
		$this->returnCode(0,$arr);
	}

	public function alipay_notice_new(){
		$import_result = import('@.ORG.pay.Aop.AopClient');
		$aop = new AopClient();
		$this->signtype = 'RSA2';
		if($_GET['h5pay'] == '1'){
			$this->alipay_public_key = $this->config['pay_alipayh5_public_key'];
		}
		else{
			$this->alipay_public_key = $this->config['alipayapp_app_pubkey'] ? : $this->config['new_pay_alipay_app_public_key'];
		}
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$_POST['fund_bill_list'] = htmlspecialchars_decode($_POST['fund_bill_list']);
        $_POST['now_lang'] = null;
		$result = $aop->rsaCheckV1($_POST,null, $this->signtype);
		if($result) {//验证成功
			$out_trade_no = $_POST['out_trade_no'];
			$trade_no = $_POST['trade_no'];
			$trade_status = $_POST['trade_status'];
			$trade_no = explode('_',$out_trade_no);
			//订单类型
			$order_type = $trade_no[0];
			//长id
			$orderid= $trade_no[1];
			$order_param = array(
				'order_id' => $orderid,
				'pay_type' => 'alipay',
				'third_id' => $_POST['trade_no'],
				'pay_money' => $_POST['total_amount'],
				'is_mobile' => 2,
				'is_own'=>0,
			);
			
            $payment_where = ['orderid'=>$orderid,'order_type'=>$order_type];
            if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
                $payment_where['order_type'] = ['in',['shop','mall']];
			}

            if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
				if($payment_money_order['pay_rate']>0){
					$order_param['pay_money'] = getFormatNumber($order_param['pay_money']/$payment_money_order['pay_rate']);
                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$order_param['pay_money']);
				}
				else{
                    M('Payment_money_order')->where($payment_where)->setField('paid_money',$order_param['pay_money']);
					if($payment_money_order['payment_money'] > $order_param['pay_money'] + 0.1){
						fdump_sql(array('order_param'=>$order_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_return');
						$this->returnCode('10070007');
					}
				}
			}else{
				$this->returnCode('10070007');
			}


			if($_POST['trade_status'] == 'TRADE_FINISHED') {
				$this->returnCode('10070008');
			}else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
				// 支付成功

				if ($order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad') {
					$order_table = 'Meal_order';
				}else if($order_type=='recharge'){
					$order_table = 'User_recharge_order';
				}else{
					$order_table = ucfirst($order_type).'_order';
				}

				$now_order = $this->get_orderid($order_table,$orderid);
				$order_param['order_id'] = $now_order['orderid'];
				if($order_type == 'group'){
					$pay_info = D('Group_order')->after_pay($order_param);
				}else if($order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad'){
					$pay_info = D('Meal_order')->after_pay($order_param);
				}else if($order_type == 'shop' || $order_type == 'mall'){
					$pay_info = D('Shop_order')->after_pay($order_param);
				}else if($order_type == 'store'){
					$pay_info = D('Store_order')->after_pay($order_param);
				}else if($order_type == 'recharge'){
					$pay_info = D('User_recharge_order')->after_pay($order_param);
				}else if($order_type == 'weidian'){
					$pay_info = D('Weidian_order')->after_pay($order_param);
					if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
						$this->NoticeWDAsyn($now_order['orderid']);
					}
				}else if($order_type == 'plat'){
					$pay_info = D('Plat_order')->after_pay($order_param);
				}else if($order_type == 'appoint'){
					$pay_info = D('Appoint_order')->after_pay($order_param);
				}else if($order_type  == 'balance-appoint'){
					$pay_info = D('Appoint_order')->balance_after_pay($order_param);
				}else if ($order_type == 'integral_mall'){
					$pay_info = D('Merchant_integral_mall_goods_order')->after_pay($order_param);

				}else if ($order_type == 'spread'){
                    $pay_info = D('Merchant_spread_order')->after_pay($order_param);
				}else if($order_type == 'mcard'){
                    $pay_info = D('Membership_card_order')->after_pay($order_param);
                }
				if(empty($pay_info['error'])){
					$arr['url']= $pay_info['url'];
					$this->returnCode(0,$arr);
				}
                fdump_sql(['post' => $_POST, 'pay_info' => $pay_info], 'after_pay');
				$this->returnCode('10070007');

			}

		}else {
            fdump_sql([$_POST, openssl_error_string(), PHP_VERSION], 'rsaCheckV1');
			$this->returnCode('10070009');
		}

	}

	//支付宝同步回调页面
	public function app_alipay_back(){
		fdump_sql($_POST,'alipay_back');
		$ticket = I('ticket', false);
		$sPayStatus =   I('pay_status',1);
		// if ($ticket) {
		// 	$info = ticket::get($ticket, $this->DEVICE_ID, true);
		// 	$this->user_session['uid'] = $info['uid'];
		// }
		// if(empty($this->user_session)){
		// 	$this->returnCode('20044010');
		// }
		$_GET['order_id']   =   $_POST['order_id'];
		$_GET['order_type']   =  $_POST['order_type'];
		if((empty($_GET['order_id']) || empty($_GET['order_type'])) && $_POST['alipay_return']){
			//防止客户端没有传入这两个参数
			$alipay_return = json_decode(html_entity_decode($_POST['alipay_return']),true);
			$_out_trade_no = $alipay_return['alipay_trade_app_pay_response']['out_trade_no'];
			if($_out_trade_no){
				$_tmp = explode('_', $_out_trade_no);
				$_GET['order_id'] = $_tmp[1];
				$_GET['order_type'] = $_tmp[0];
			}
		}


		switch($_GET['order_type']){
			case 'group':
				// $now_order = D('Group_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Group_order',$_GET['order_id']);
				break;
			case 'meal':
				// $now_order = D('Meal_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Meal_order',$_GET['order_id']);
				break;
			case 'takeout':
			case 'food':
			case 'foodPad':
				// $now_order = D('Meal_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Meal_order',$_GET['order_id']);
				break;
			case 'weidian':
				// $now_order = D('Weidian_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('Weidian_order',$_GET['order_id']);
				break;
			case 'recharge':
				// $now_order = D('User_recharge_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('User_recharge_order',$_GET['order_id']);
				break;
			case 'appoint':
			case 'balance-appoint':
				// $now_order = D('Appoint_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('Appoint_order',$_GET['order_id']);
				break;
			case 'waimai':
				// $now_order = D('Waimai_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('Waimai_order',$_GET['order_id']);
				break;
			case 'wxapp':
				// $now_order = D('Wxapp_order')->get_order_by_id(intval($_GET['order_id']));
				$now_order =$this->get_orderid('Wxapp_order',$_GET['order_id']);
				break;
			case 'shop':
				// $now_order = D('Shop_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Shop_order',$_GET['order_id']);
				break;
			case 'mall':
				// $now_order = D('Shop_order')->where(array('order_id'=>$_GET['order_id']))->find();
				$now_order =$this->get_orderid('Shop_order',$_GET['order_id']);
				break;
			case 'plat':
				$now_order =$this->get_orderid('Plat_order',$_GET['order_id']);
				break;
			case 'store':
				$now_order =$this->get_orderid('Store_order',$_GET['order_id']);
				break;
			case 'integral_mall':
				$now_order =$this->get_orderid('Merchant_integral_mall_goods_order',$_GET['order_id']);
				break;
            case 'ticket':
                $now_order = $this->get_orderid('Scenic_order', $_GET['order_id']);
                break;
            case 'guide':
                $now_order = $this->get_orderid('Scenic_aguide_order', $_GET['order_id']);
                break;
			default:
				$this->returnCode('20045010');
		}
		if(empty($now_order)){
			$this->returnCode('20046002');
		}
		$now_order['order_type'] = $_GET['order_type'];
		if((($now_order['paid'] && !in_array($_GET['order_type'],['ticket', 'guide'])) || ($now_order['paid'] == 2 && in_array($_GET['order_type'],['ticket', 'guide'])))&&$now_order['order_type']!='balance-appoint'){
			switch($_GET['order_type']){
				case 'group':
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
					break;
				case 'meal':
					$redirctUrl = C('config.site_url').'/wap.php?c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'takeout':
					$redirctUrl = C('config.site_url').'/wap.php?c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'food':
				case 'foodPad':
					$redirctUrl = C('config.site_url').'/wap.php?c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'weidian':
					$redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']));
					$this->NoticeWDAsyn($now_order['orderid']);
					break;
				case 'appoint':
				case 'balance-appoint':
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
					break;
				case 'waimai':
					$redirctUrl = C('config.site_url').'/index.php?g=Waimaiwap&c=Index&a=order&order_id='.$now_order['order_id'];
					break;
				case 'recharge':
					//$redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
					$redirctUrl =  C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
					break;
				case 'wxapp':
					$redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
					break;
				case 'shop':
					if($now_order['group_id']&&$now_order['team_id']){
						$redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=group_team_detail&order_id='.$now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
					}else{
						$redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='.$now_order['order_id'];
					}
					break;
				case 'mall':
					if($now_order['group_id']&&$now_order['team_id']){
						$redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=group_team_detail&order_id='.$now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
					}else{
						$redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=status&order_id='.$now_order['order_id'];
					}
					break;
				case 'store':
					if(C('config.pay_success_jump_to_homepage')){
						$redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';;
					}else{
						$redirctUrl = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$_GET['order_id'];
					}
					break;
				case 'plat':
					$redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
					break;
				case 'integral_mall':
					$redirctUrl = C('config.site_url').'/wap.php?c=Integral_mall&a=order_detail_new&order_id='.$now_order['order_id'];
					break;
                case 'ticket':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=ticket_order_details&order_id='.$now_order['order_id'];
                    break;
                case 'guide':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=aguide_order_list';
                    break;
                case 'spread':
                    $redirctUrl = C('config.site_url') . '/wap.php?c=My&a=my_spread';
                    break;
                case 'mcard':
                    $redirctUrl = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMemberList';
                    break;
			}
			$arr['url']=$redirctUrl;
			$this->returnCode(0,$arr);
			exit;
		}

		$alipay_return  = json_decode(str_replace('&quot;','"',$_POST['alipay_return']),ture);

		if($alipay_return['alipay_trade_app_pay_response']['app_id']!=''){
			$alipay_return = array_merge($alipay_return['alipay_trade_app_pay_response'],$_GET);
			$this->app_alipay_back_new($alipay_return,$now_order);die;
		}
		if($this->DEVICE_ID == 'alipayapp'){
			$this->app_alipay_back_alipayapp(['trade_no'=>$_POST['trade_no'],'order_id'=>$_POST['order_id'],'order_type'=>$_POST['order_type'],'out_trade_no'=>$_POST['order_type']."_".$_POST['order_id']],$now_order);die;
		}


		$post_arr = explode('&amp;',$_POST['alipay_return']);

		$_POST=array();
		foreach($post_arr as $v){
			$tmp = explode('&quot;',$v);
			$_POST[str_replace('=','',$tmp[0])] = '"'.str_replace('&quot;','',$tmp[1]).'"';
		}

		$now_order['order_id']  =   $now_order['orderid'];
		$alipay_config['partner']		= $this->config['pay_alipay_app_pid'];

		//商户的私钥（后缀是.pen）文件相对路径
		if($_POST['body']==2){
			$alipay_config['private_key_path']	= $this->config['pay_alipay_app_private_key_android'];
		}else{
			$alipay_config['private_key_path']	= $this->config['pay_alipay_app_private_key_ios'];
		}
		//支付宝公钥（后缀是.pen）文件相对路径
		$alipay_config['ali_public_key_path']=$this->config['pay_alipay_app_public_key'];


		//$_POST['trade_no'] = $_GET['order_type'].'_'.$_GET['order_id'];
		$import_result = import('@.ORG.pay.Alipay');
		$pay_class = new Alipay();
		$result = $pay_class->app_return_url($alipay_config);

		$order_param = array(
			'order_id' => $now_order['orderid'],
			'pay_type' => 'alipay',
			'third_id' => str_replace('"','',$_POST['trade_no']),
			'pay_money' => floatval(str_replace('"','',$_POST['total_fee'])),
			'is_mobile' => 2,
			'is_own'=>0,
		);
		
		$payment_where = ['orderid'=>$order_param['order_id'],'order_type'=>$now_order['order_type']];
        if($payment_where['order_type'] == 'mall' || $payment_where['order_type'] == 'shop'){
            $payment_where['order_type'] = ['in',['shop','mall']];
        }
		
		//判断回调金额
		if($payment_money_order = M('Payment_money_order')->where($payment_where)->find()){
			if($payment_money_order['pay_rate']>0){
				$order_param['pay_money'] = getFormatNumber($order_param['pay_money']/$payment_money_order['pay_rate']);
				M('Payment_money_order')->where($payment_where)->setField('paid_money',$order_param['pay_money']);
			}else{
				M('Payment_money_order')->where($payment_where)->setField('paid_money',$order_param['pay_money']);
				if($payment_money_order['payment_money'] > $order_param['pay_money'] + 0.1){
					fdump_sql(array('order_param'=>$order_param,'payment_money_order'=>$payment_money_order),'alipay_payment_error_back');
					$this->returnCode('10070007');
				}
			}
		}else{
			$this->returnCode('10070007');
		}
		if($result){
			switch($_GET['order_type']){
				case 'group':
					D('Group_order')->after_pay($order_param);
					break;
				case 'meal':
				case 'takeout':
				case 'food':
				case 'foodPad':
					$go_query_param['order_param']['orderid'] = $order_param['order_id'];
					//unset($go_query_param['order_param']['order_id']);
					D('Meal_order')->after_pay($order_param, $_GET['order_type']);
					break;
				case 'weidian':
					$pay_info = D('Weidian_order')->after_pay($order_param);
					if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
						$this->NoticeWDAsyn($now_order['orderid']);
					}
					break;
				case 'recharge':
					D('User_recharge_order')->after_pay($order_param);
					break;
				case 'waimai':
					D('Waimai_order')->after_pay($order_param);
					break;
				case 'appoint':
					D('Appoint_order')->after_pay($order_param);
					break;
				case 'wxapp':
					D('Wxapp_order')->after_pay($order_param);
					break;
				case 'shop':
					D('Shop_order')->after_pay($order_param);
					break;
				case 'store':
					D('Store_order')->after_pay($order_param);
					break;
				case 'plat':
					D('Plat_order')->after_pay($order_param);
					break;
				case 'balance-appoint':
					D('Appoint_order')->balance_after_pay($order_param);
					break;
				case 'integral_mall':
					D('Merchant_integral_mall_goods_order')->after_pay($order_param);
					break;
                case 'spread':
                    D('Merchant_spread_order')->after_pay($order_param);
                    break;
                case 'mcard':
                    D('Membership_card_order')->after_pay($order_param);
                    break;
			}
		}else{
			$this->returnCode('10070007');
		}
		switch($_GET['order_type']){
			case 'group':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=My&a=group_order&order_id='.$_GET['order_id'];
				break;
			case 'meal':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Meal&a=detail&orderid='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'takeout':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Takeout&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'food':
			case 'foodPad':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=Food&a=order_detail&order_id='.$_GET['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
				break;
			case 'weidian':
				$redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$go_query_param['order_param']['third_id'],'payment_method'=>$go_query_param['order_param']['pay_type']));
				break;
			case 'appoint':
			case 'balance-appoint':
				$redirctUrl = C('config.site_url').'/wap.php?g=Wap&c=My&a=appoint_order&order_id='.$_GET['order_id'];
				break;
			case 'waimai':
				$redirctUrl = C('config.site_url').'/index.php?g=Waimaiwap&c=Index&a=order&order_id='.$_GET['order_id'];
				break;
			case 'recharge':
				$redirctUrl = C('config.site_url').'/wap.php?c=My&a=index';
				break;
			case 'wxapp':
				$redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$_GET['order_id'];
				break;
			case 'shop':
				if($now_order['group_id']&&$now_order['team_id']){
					$redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=group_team_detail&order_id='.$now_order['order_id'].'&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'];
				}else{
					$redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='.$now_order['order_id'];
				}
				break;
			case 'store':
				if(C('config.pay_success_jump_to_homepage')){
					$redirctUrl = C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index';;
				}else{
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$_GET['order_id'];
				}
				break;
			case 'plat':
				$redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
				break;
			case 'integral_mall':
				$redirctUrl = C('config.site_url').'/wap.php?c=Integral_mall&a=order_detail_new&order_id='.$now_order['order_id'];
				break;
            case 'spread':
                $redirctUrl = C('config.site_url') . '/wap.php?c=My&a=my_spread';
                break;
            case 'mcard':
                $redirctUrl = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMemberList';
                break;
		}
		$arr['url']=$redirctUrl;
		$this->returnCode(0,$arr);
	}

	// 获得商家联盟会员卡
	public function get_union_mer_card(){
		$mer_id = $_POST['mer_id'];
        // 联盟会员卡列表
        $card_list = D('Card_new')->get_union_card_by_uid_and_mer_id($this->_uid, $mer_id);
        $return = array();
        if (count($card_list)>0) {
        	foreach ($card_list as $key => $value) {
	        	$_tmp['card_id'] = $value['card_id'];
	        	$_tmp['mer_id'] = $value['mer_id'];
	        	$_tmp['uid'] = $value['uid'];
	        	$_tmp['card_money'] = $value['card_money'];
	        	$_tmp['card_money_give'] = $value['card_money_give'];
	        	$_tmp['name'] = $value['name'];
	        	$return[] = $_tmp;
	        }
        }
		$this->returnCode(0,$return);
	}

	//小马哥支付系统的异步通知
	public function xiaoma_return_url(){
		$pay_type = $_GET['pay_type'];
		import('@.ORG.LowFeePay');
		$lowfeepay = new LowFeePay('xiaoma');
		$get_pay_param = $lowfeepay->notice($_POST);
		fdump($get_pay_param, "lumin_xm", 1);
		
		// file_put_contents('./weixinpay_test/'.$_SERVER['HTTP_HOST'].'_'.date('Y-m-d').'.php',var_export($get_pay_param,true).PHP_EOL.PHP_EOL.file_get_contents('./weixinpay_test/'.$_SERVER['HTTP_HOST'].'_'.date('Y-m-d').'.php'));
		if ($get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad') {
			$order_table = 'Meal_order';
		}else if($get_pay_param['order_param']['order_type']=='recharge'){
			$order_table = 'User_recharge_order';
		}else if($get_pay_param['order_param']['order_type']=='ticket'){
            $order_table = 'Scenic_order';
        }else if($get_pay_param['order_param']['order_type']=='guide'){
            $order_table = 'Scenic_aguide_order';
        }else{
			$order_table = ucfirst($get_pay_param['order_param']['order_type']).'_order';
		}

		$now_order = $this->get_orderid($order_table,$get_pay_param['order_param']['order_id']);
		$get_pay_param['order_param']['order_id'] = $now_order['orderid'];



		if(empty($get_pay_param['error'])){
			//在线支付回调判断是否与支付前金额相等
			if($pay_type!='offline'){
				if($payment_money_order = M('Payment_money_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id'],'order_type'=>$get_pay_param['order_param']['order_type']))->find()){
					if($payment_money_order['pay_rate']>0){
						$get_pay_param['order_param']['pay_money'] = getFormatNumber($get_pay_param['order_param']['pay_money']/$payment_money_order['pay_rate']);
						M('Payment_money_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id'],'order_type'=>$get_pay_param['order_param']['order_type']))->setField('paid_money',$get_pay_param['order_param']['pay_money']);
					}
					else{
						M('Payment_money_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id'],'order_type'=>$get_pay_param['order_param']['order_type']))->setField('paid_money',$get_pay_param['order_param']['pay_money']);
						if($payment_money_order['payment_money'] > $get_pay_param['order_param']['pay_money'] + 0.1){
							fdump_sql(array('get_pay_param'=>$get_pay_param,'payment_money_order'=>$payment_money_order),'weixin_payment_error_return');
							$this->returnCode('10070007');
						}
					}
				}else{
					$this->returnCode('10070007');
				}
			}
			if($get_pay_param['order_param']['order_type'] == 'group'){
				$pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout'){
				$pay_info = D('Meal_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'shop'){
				$pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'recharge'){
				$pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'store'){
				$pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'weidian'){
				$pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
				if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
					$this->NoticeWDAsyn($now_order['orderid']);
				}
			}else if($get_pay_param['order_param']['order_type'] == 'plat'){
				$pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
			}else if($get_pay_param['order_param']['order_type'] == 'appoint'){
				if($now_order['is_initiative']==2){
					$pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
				}else{
					$pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
				}
			}else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
				$pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
			}else if ($get_pay_param['order_param']['order_type'] == 'integral_mall'){
				$pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
			}else if ($get_pay_param['order_param']['order_type'] == 'ticket'){
                $pay_info = D('Scenic_order')->after_pay($get_pay_param['order_param']);
            }else if ($get_pay_param['order_param']['order_type'] == 'guide'){
                $pay_info = D('Scenic_aguide')->after_pay($get_pay_param['order_param']);
            }else if ($get_pay_param['order_param']['order_type'] == 'spread'){
                $pay_info = D('Merchant_spread_order')->after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'mcard'){
                $pay_info = D('Membership_card_order')->after_pay($get_pay_param['order_param']);
            }
			if(empty($pay_info['error'])){
				if($get_pay_param['order_param']['pay_type'] == 'weixin'){
					exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
				}
				$pay_info['msg'] = L_('订单付款成功！');
				$this->returnCode(0);
			}
			fdump_sql(array('pay_info'=>$pay_info,'get_pay_param'=>$get_pay_param),'weixin_return_pay_error');
			$this->returnCode('20070007');
		} else {
			$this->returnCode(0, $get_pay_param['msg']);
		}
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
    
    public function mallbookPayNotify()
    {
        $data = $_POST;
        $data['result'] = htmlspecialchars_decode(html_entity_decode($data['result']));
        fdump_api([
            'callback_data' => $data,
            'msg'=>'格式化前的'
        ], "mallBookOldPay/mallbookPayCallback", true);
        try {
            import('@.ORG.pay.Mallbookpay.Mallbook');
            $veritySign = $data['sign'] ?? '';
            if (!Mallbook::verify($data, $veritySign)) {
                throw_exception('返回值验签失败');
            }
            
            import('@.ORG.pay.Mallbookpay.MallbookPayService');
            $pay_service = new MallbookPayService();
            $data['result'] = json_decode($data['result'], true);
            
            fdump_api([
                'callback_data' => $data,
                'msg'=>'格式化后的'
            ], "mallBookOldPay/mallbookPayCallback", true);
            
            $params = $pay_service->notice($data);
            fdump_api([
                'params' => $params,
                'msg'=>'验签后的'
            ], "mallBookOldPay/mallbookPayCallback", true);
            //调用业务方service  after_pay
            D('Pay')->after_pay($params['order_type'], ['order_param' => $params], false);
        } catch (\Exception $e) {
            fdump_api([
                'callback_data' => $data,
                'msg'=>$e->getMessage()
            ], "mallBookOldPay/mallbookPayCallbackError", true);
            $this->ajaxReturn([
                'msg' => $e->getMessage(),
            ]);
        }
        $this->ajaxReturn([
            'msg' 	 => '请求成功',
        ]);
    }
}
?>