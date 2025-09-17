<?php
/*
 *2019年08月30日14:10
 *营销活动
 */
class MarketingActivityAction extends BaseAction {
	public function activity_detail(){
		$activity_id = intval($_POST['activity_id']);
		if (!$activity_id) {
			$this->returnCode('73000001');
		}
		$database_marketing_activity = D('Marketing_activity');
		$database_marketing_activity_detail = D('Marketing_activity_detail');
		$activity = $database_marketing_activity->where(array('activity_id'=>$activity_id))->find();

		if (!$activity) {
			$this->returnCode('76000006');
		}

		if ($activity['status'] == 0) {
            $return_arr['info'] = $activity;
			$this->returnCode('76000002',$return_arr);
		}

		// 获取正在进行中的城市活动详情
		$where['activity_id'] = $activity_id;
		$where['is_del'] = 0;
		$where['end_time'] =array('gt',time());
		$where['start_time'] =array('lt',time());
		$detail = $database_marketing_activity_detail->where($where)->select();

		$city_ids = array_column($detail, 'city_id');
		$area_list = M('Area')->where(array('area_id'=>array('in',$city_ids)))->select();
		$area_list_tem = array();
		foreach ($area_list as $key => $value) {
			$area_list_tem[$value['area_id']] = $value['area_name'];
		}

		foreach ($detail as $key => $value) {
			$detail[$key]['city_name'] = $area_list_tem[$value['city_id']];
			// 发券
			$condition = array('coupon_id'=>array('in', $value['coupon_ids']));  
		 	$condition['end_time'] = array('gt',time());
	        $condition['status'] = array('in','1');  //状态正常
            $condition['_string'] = 'num > had_pull';  //状态正常
			$coupon_list = D('System_coupon')->field(true)->where($condition)->count();
			if (!$coupon_list) {
				unset($detail[$key]);
			}
			
		}

        if (!$detail) {
            $mim_start_time = $database_marketing_activity_detail->where(array('activity_id'=>$activity_id,'is_del'=>0, 'start_time'=>array('gt',time())))->find();
            if ($mim_start_time>time()) {
                // 活动未开始
                $return_arr['info'] = $activity;
                $this->returnCode('76000001',$return_arr);
            }else{
                // 活动已结束
                $return_arr['info'] = $activity;
                $this->returnCode('76000002',$return_arr);
            }
        }

		$return_arr['city_arr'] = $detail ? $detail : array();
		$return_arr['info'] = $activity;
		$return_arr['share_info'] = array(
			'title' => $activity['title'],
			'desc' => '邀您参与活动，领取优惠大礼包',
			'image' => $this->config['wechat_share_img'] ? $this->config['wechat_share_img'] : $this->config['site_logo'],
            'url' => $this->config['site_url'] . '/packapp/westpac/index.html#/?activity_id=' . $activity_id,
		);
		$this->returnCodeOk($return_arr);
	}

    public function get_share_info(){
        $activity_id = intval($_POST['activity_id']);
        if (!$activity_id) {
            $this->returnCode('73000001');
        }
        $database_marketing_activity = D('Marketing_activity');
        $database_marketing_activity_detail = D('Marketing_activity_detail');
        $activity = $database_marketing_activity->where(array('activity_id'=>$activity_id))->find();

        if (!$activity) {
            $this->returnCode('76000006');
        }
        
        $return_arr['share_info'] = array(
            'title' => $activity['title'],
            'desc' => '邀您参与活动，领取优惠大礼包',
            'image' => $this->config['wechat_share_img'] ? $this->config['wechat_share_img'] : $this->config['site_logo'],
            'url' => $this->config['site_url'] . '/packapp/westpac/index.html#/?activity_id=' . $activity_id,
        );
        $this->returnCodeOk($return_arr);
    }

	// 领取优惠券
	public function get_coupon(){
		$phone = $_POST['phone'];
		$code = $_POST['code'];
		$city_id = $_POST['city_id'];
		$activity_id = $_POST['activity_id'];
        $is_new = $_POST['is_new'];

        $phone_country_type = strval($_POST['phone_country_type']);
        $phone = phone_format($phone_country_type, $phone);
 		if (!$activity_id) {
            $this->returnCode('73000001');
        }

 		if (!$city_id) {
            $this->returnCode('76000003');
        }

 		if (!$phone) {
            $this->returnCode('76000004');
        }

 		if (!$code) {
            $this->returnCode('20044008');
        }
        $where = array();
        $where['phone'] = $phone;
        $where['extra'] = $code;
        $where['status'] = 0;
        $where['expire_time'] = array('gt', time());
        $result = D("App_sms_record")->where($where)->find();
        if (! $result) {
           $this->returnCode('20044009');
        }

        // 验证手机号是否注册
        $where = array();
        $where['phone'] = $phone;
        $now_user = D("User")->field('`uid`')->where($where)->find();
        if(!$now_user){
            $this->returnCode('76000005');
        }

        // 已参与过活动
		$joined =M('Marketing_activity_join')->where(array('activity_id'=>$activity_id,'phone'=>$phone))->find();
		if ($joined) {
            $this->returnCode('76000007');
		}
        // 验证活动是否可参与
		$activity = M('Marketing_activity')->where(array('activity_id'=>$activity_id,'is_del'=>0))->find();
		$activity_detail = M('Marketing_activity_detail')->where(array('activity_id'=>$activity_id,'city_id'=>$city_id,'is_del'=>0))->find();

		if (!$activity || $activity['status'] == 0) {
			$this->returnCode('76000002');
		}

		// 未开始
		if ($activity_detail['start_time']>time()) {
			$this->returnCode('76000001');
		}

		// 已结束
		if ($activity_detail['end_time']<time()) {
			$this->returnCode('76000002');
		}

		$add_data = array(
			'activity_id' => $activity_id,
			'detail_id' => $activity_detail['detail_id'],
			'uid' => $now_user['uid'],
			'phone' => $phone,
            'is_new' => $is_new,
			'coupon_ids' => $activity_detail['coupon_ids'],
			'add_time' => time(),
		);

		$add_sql = M('Marketing_activity_join')->add($add_data);
		if (!$add_sql) {
			$this->returnCode('76000008');
		}

		// 发券
		$condition = array('coupon_id'=>array('in', $activity_detail['coupon_ids']));  
	 	$condition['end_time'] = array('gt',time());
        $condition['status'] = array('in','1');  //状态正常
		$order_string = '`coupon_id` DESC';
		$coupon_list = D('System_coupon')->field(true)->where($condition)->order($order_string)->select();
		foreach ($coupon_list as $key => &$coupon) {
			$res = D('System_coupon')->had_pull($coupon['coupon_id'],$now_user['uid'],'',1);
		}
		

		$this->returnCode('0');
	}

	 //注册
    public function register() {
        $code = I('code', 0);
        if (! $code) {
            $this->returnCode('20044008');
        }
        $phone = I("phone", false);
        if (! $phone) {
            $this->returnCode('20042001');
        }
        $passwd = I("passwd", false);
        if (! $passwd) {
            $this->returnCode('20042002');
        }
        $client = I('client', 0);

        $phone_country_type = strval($_POST['phone_country_type']);
        $phone = phone_format($phone_country_type, $phone);

        $where = array();
        $where['phone'] = $phone;
        $where['extra'] = $code;
        $where['status'] = 0;
        $where['expire_time'] = array('gt', time());
        $result = D("App_sms_record")->where($where)->find();
        if (! $result) {
           $this->returnCode('20044009');
        }
        //注册账号
        $result = D('User')->where(array('phone'=>$phone))->find();
        if ($result) {
            $this->returnCode('20044005');
        }
		
        $result = D('User')->checkreg($phone, $passwd, $phone_country_type);
        if ($result['error_code']) {
            $this->returnCode('20044003');
        }

        if(!empty($result['user']) && $this->config['open_score_fenrun'] && $_POST['spread_code']){
            if($spread_user = M('User')->where(array('spread_code'=>$_POST['spread_code']))->find()){
                $now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();
                D('User_spread')->data(array('spread_openid'=>$spread_user['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>'','uid'=>$result['user']['uid']))->add();
                if ($this->config['spread_user_give_type'] == 1 || $this->config['spread_user_give_type'] == 2) {
                    $spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']:$this->config['spread_give_money'];
                    //D('User')->add_money($spread_user['uid'],  $spread_give_money, '推荐新用户注册平台赠送余额','','',$uid);
                    $Res = D('Fenrun')->add_recommend_award($spread_user['uid'],$result['user']['uid'],1,$spread_give_money,L_('推荐新用户注册平台奖励佣金'));

                }

            }
        }

        $database_house_village_user_bind = D('House_village_user_bind');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $find_village = D('House_village_user_bind')->where(array('phone'=>trim($phone)))->select();
        if($find_village){
            foreach($find_village as $k=>$v){
                $data_vacancy = array();
                $dataInfo['uid'] = $result['user']['uid'];
                $database_house_village_user_bind->where(array('pigcms_id'=>$v['pigcms_id']))->data($dataInfo)->save();
                if($v['type']==0 && $v['vacancy_id']>0){
                    $database_house_village_user_vacancy->where(array('pigcms_id'=>$v['vacancy_id']))->data($dataInfo)->save();
                }
            }
        }

        unset($result['user']['pwd']);
        $uid = $result['user']['uid'];
        $nickname = $result['user']['nickname'];

        //商盈通对接
        // $shangyingtong = D("UserShangyingtong");
        // $shangyingtong->reg($phone,$uid);

        // $ticket = ticket::create($uid, $this->DEVICE_ID, true);
        // //统一数据结构
        // $result['user']['coupon_count'] = 0;
        // $return = array(
        //     'ticket' => $ticket["ticket"],
        //     'user' => $result['user'],
        // );
        $this->addLog($result['user'],$client);
        // $this->upDevice($client,$result['user']['uid']);
        $this->returnCode(null, $return);
    }

    //  记录手机登录，Appapi_app_login_log
    public function addLog($user = array(),$client){
        $log = array(
            'client' => $client,
            'device_id' => $this->DEVICE_ID,
            'uid' => $user['uid'],
            'phone' => $user['phone'],
            'create_time' => time(),
        );
        M("Appapi_app_login_log")->add($log);
    }

	//发送验证码
    public function sendCode() {
		//type值  1注册帐号，2忘记密码，3修改手机，4绑定手机号 
        $phone = I('phone', 0);
		$type = I('type', 1);

		// if(strpos($phone,'+') !== false){
		// 	$now_user = D('User')->get_user($this->_uid);
		// 	$phone = $now_user['phone'];
		// }

		$phone_country_type = I('phone_country_type', 1);
		$phone = phone_format($phone_country_type, $phone);

        if (!$phone) {
            $this->returnCode('20042001');
        }
        if(strlen($this->DEVICE_ID)>40 || empty($this->DEVICE_ID)){
            $this->returnCode('20045014');
        }
        //防止1分钟内多次发送短信
        $laste_sms = M('App_sms_record')->where(array('device_id'=>$this->DEVICE_ID,'type'=>$type))->order('pigcms_id DESC')->find();
        if($this->check_sms($this->DEVICE_ID,$phone)>10){
            $this->returnCode('20046037');
        }
        if(time()-$laste_sms['send_time']<60 && $type!=3){
            $this->returnCode('20046036');
        }

        
        $code = mt_rand(1000, 9999);
        $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',array('X1'=>$code));
       

        $columns = array();
        $columns['phone'] = $phone;
        $columns['text'] = $text;
        $columns['extra'] = $code;
        $columns['device_id'] = $this->DEVICE_ID;
        $columns['type'] = $type;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = D("App_sms_record")->add($columns);
        fdump(D("App_sms_record")->_sql(),'sendCode');
        if (! $result) {
            $this->returnCode('20044007');
        }
		
		$sms_data = array('mer_id' => 0, 'store_id' => 0, 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'app_register');
		$phone_country_type && $sms_data['nationCode']  = $phone_country_type;
        $return = Sms::sendSms($sms_data);
		
        if ($result != 0) {
            $this->returnCode(self::ConverSmsCode($return));
        }
        $return['code'] = $code;
        $this->returnCode(null, $return);
    }

	//检查短信一天条数
    public function check_sms($device_id,$phone){
        $today_zero = strtotime(date('Ymd',time()));
		if($device_id == 'packapp'){
			$where['_string'] = "phone ='".$phone."' AND send_time >".$today_zero.' AND send_time<'.($today_zero+86399);
		}else{
			$where['_string'] = "(device_id = '".$device_id."' OR phone ='".$phone."') AND send_time >".$today_zero.' AND send_time<'.($today_zero+86399);
		}
        return M('App_sms_record')->where($where)->count();
    }

    //错误代码
    static private function ConverSmsCode($smscode) {
        $errCode = array(
            '2'    => '20060001',
            '400'  => '20060002',
            '401'  => '20060003',
            '402'  => '20060004',
            '403'  => '20060005',
            '4030' => '20060006',
            '404'  => '20060007',
            '405'  => '20060008',
            '4050' => '20060009',
            '4051' => '20060010',
            '4052' => '20060011',
            '406'  => '20060012',
            '407'  => '20060013',
            '4070' => '20060014',
            '4071' => '20060015',
            '4072' => '20060016',
            '4073' => '20060017',
            '408'  => '20060018',
            '4085' => '20060019',
            '4084' => '20060020',
        );
        return $errCode[$smscode];
    }
}
?>