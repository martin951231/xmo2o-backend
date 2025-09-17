<?php
class MyAction extends BaseAction{

	public $now_user;

	public function __construct(){
		parent::__construct();
		$not_check_login = I('not_check_login',0);
        if(ACTION_NAME != 'im_order_list' && ACTION_NAME != 'pay' && ACTION_NAME != 'store_order_detail' && !(ACTION_NAME=='adres_map'&&$not_check_login==1)){
			if(empty($this->user_session)){
				if($this->is_app_browser){
					$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
					$this->error_tips('请先进行登录！',U('Login/index',$location_param));
				}else{
					$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
					redirect(U('Login/index',$location_param));
				}
			}
			
			if(C('config.mdd_api_url')){
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->select($this->user_session['uid']);
			}

		$now_user = D('User')->get_user($this->user_session['uid']);
		if(empty($now_user)){
			session('user',null);
			setcookie('user_device_id', '', -1, '/');
			setcookie('user_ticket', '', -1, '/');
			setcookie('login_status', 'logout', 0, '/');
		
			$this->error_tips('未获取到您的帐号信息，请重新登录！',U('Login/index'));
		}
		if(!$now_user['status']){
			session('user',null);
			setcookie('user_device_id', '', -1, '/');
			setcookie('user_ticket', '', -1, '/');
			setcookie('login_status', 'logout', 0, '/');
		
			$this->error_tips('您的账号被禁止登录',U('Login/index'));
		}
		$now_user['now_money'] = floatval($now_user['now_money']);
		$now_user['now_money_two'] = number_format(floatval($now_user['now_money']),2);
		$level = M(_view('User_level'))->getField('level,lname,icon');
		$now_user['lname'] = $level[$now_user['level']]['lname'];
		$now_user['icon'] = $level[$now_user['level']]['icon'];
		if( $this->config['zbw_key']){
			$zbw_user = D('ZbwErp')->sync_data($this->user_session['uid']);
		}
		if($this->config['open_allinyun']==1){
			$allinyun = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();
			if($allinyun['bizUserId']){
				import('@.ORG.AccountDeposit.AccountDeposit');
				$deposit = new AccountDeposit('Allinyun');
				$allyun = $deposit->getDeposit();
				// $allinyun = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();
				$allyun->setUser($allinyun);
				$allinyun_user_info = $allyun->queryBalance();
				$now_user['now_money'] = $allinyun_user_info['signedResult']['allAmount']/100;

				}
			}
			$this->now_user = $now_user;
			$this->assign('now_user',$now_user);
			//$this->assign('level',$level);

			//二次验证时间
			if($times = D('Verify_limit')->field('end_time')->where(array('uid'=>$this->user_session['uid'],'times'=>array('lt',2)))->find()){
				$_SESSION['user']['verify_end_time']=$times['end_time'];
			}

			//全部跳转到新版页面
            if (in_array(ACTION_NAME, ['group_order_list', 'shop_order_list', 'appoint_order_list'])) {
                redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order?state=0');
                return false;
            }
        }

	}

	//	新的个人中心页面
	public function index(){
		if($this->config['now_scenic'] == 2){
			redirect(U('Scenic_user/index'));
		}
		if($this->config['single_system_type'] == 'village_group'){
			redirect(U('Village_group/index'));
		}
		
		redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/plat_menu/my');
		
		/*获取来源*/
		parse_str($_SERVER['HTTP_REFERER']);
		if(isset($_GET['from'])){
			$from=$_GET['from'];
		}
		
		$uid	=	$this->user_session['uid'];
		$is_manager = M('Classify_manager')->field(true)->where(array('uid'=>$uid))->find();
		if($is_manager){
			$this->assign('is_manager',$is_manager);
		}else{
			$is_manager = false;
			$this->assign('is_manager',$is_manager);
		}
		//	商家优惠券
//		$mer_list = D('Member_card_coupon')->get_all_coupon($uid);
		$mer_list = D('Card_new_coupon')->get_user_all_coupon_list($uid,1,$from);
		if($mer_list){
			$mer_number	=	count($mer_list);
		}else{
			$mer_number = 0;
		}
		$this->assign('mer_number',$mer_number);
		//	平台优惠券
		$coupon_list = D('System_coupon')->get_user_coupon_list($uid,$this->user_session['phone'],1,$from);
		$coupon_number	=	count($coupon_list);
		$this->assign('coupon_number',$coupon_number);
		//	统计我参与的活动
		$sql	=	"SELECT COUNT(*) AS tp_count FROM `pigcms_extension_activity_record` `ear`,`pigcms_extension_activity_list` `eal`,`pigcms_merchant` `m` WHERE(`ear`.`activity_list_id` = `eal`.`pigcms_id` AND `eal`.`mer_id` = `m`.`mer_id`AND `ear`.`uid` = '$uid') GROUP BY `eal`.`pigcms_id` ";
		$mod = new Model();
		$result = $mod->query($sql);
		$activity_number	=	count($result);
		$this->assign('activity_number',$activity_number);
		//	统计我的会员卡
		//$card_number = D('Member_card_set')->get_all_card_count($uid);
		$sql = 'SELECT c.card_id,c.status,cl.status as clstatus,c.bg,c.diybg,m.name,c.discount,cl.id as cardid,cl.card_money,cl.card_money_give,m.pic_info,m.mer_id FROM '
				.C('DB_PREFIX').'card_userlist `cl`  left join '
				.C('DB_PREFIX').'card_new `c` on cl.mer_id = c.mer_id left join '
				.C('DB_PREFIX').'merchant m on c.mer_id  = m.mer_id WHERE ( cl.uid = '.$uid.' AND c.status=1 AND cl.status=1 AND m.status=1) ';
		$res =  M('')->query($sql);

		foreach ($res as $v) {
			$tmp[$v['card_id']]['id'] = $v['cardid'];
		}
		$card_number = count($tmp);
		$this->assign('card_number', $card_number);
		$share = new WechatShare($this->config,$_SESSION['openid']);
		$this->BioAuthticMethod = $share->getBioAuthticMethod($this->static_path);
		$this->assign('BioAuthticMethod', $this->BioAuthticMethod);

		//底部导航
		$home_menu_list = D('Home_menu')->getMenuList('app_footer');
		if(empty($home_menu_list) && $this->config['single_system_type'] == 'shop'){
			$home_menu_list = array(
				array(
					'name'		=>	$this->config['shop_alias_name'],
					'url'		=> 	U('Shop/index'),
					'pic_path'	=> 	'shop/footer_store.png',
					'hover_pic_path'	=> 	'shop/footer_store_active.png',
				),
				array(
					'name'		=>	'订单',
					'url'		=> 	U('My/shop_order_list'),
					'pic_path'	=> 	'shop/footer_order.png',
					'hover_pic_path'	=> 	'shop/footer_order_active.png',
				),
				array(
					'name'		=>	'我的',
					'url'		=> 	U('My/index'),
					'pic_path'	=> 	'shop/footer_my.png',
					'hover_pic_path'	=> 	'shop/footer_my_active.png',
				),
			);
		}
		if($home_menu_list){
			$this->assign('home_menu_list',$home_menu_list);
		}

		$database_house_worker = D('House_worker');
		$house_worker_condition['status'] = 1;
		$house_worker_condition['openid'] = $this->user_session['openid'];
		if($now_house_worker = $database_house_worker->where($house_worker_condition)->find()){
			$this->assign('now_house_worker' , $now_house_worker);
		}
		if($this->config['show_scroll_msg']){
			$scroll_msg = D('Scroll_msg')->get_msg();
			$this->assign('scroll_msg',$scroll_msg);
		}

		$recently_sign = M('User_sign')->where(array('uid'=>$uid))->order('id DESC')->find();
		$today_sing = false;
		if($recently_sign && strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))==strtotime(date('Ymd',$recently_sign['sign_time']))){
			$today_sing = true;
		}

		if($this->config['open_distributor']==1){
			$distributor['distributor'] = M('Distributor_agent')->where(array('uid'=>$uid,'type'=>1))->find();
			$distributor['agent'] = M('Distributor_agent')->where(array('uid'=>$uid,'type'=>2))->find();
			$this->assign('distributor',$distributor);
		}
		$this->assign('today_sing',$today_sing);

		// 关注商家
		$merchant_count = M('Merchant_user_relation')->where(array('openid'=>$this->user_session['openid'],'mer_id' => array('gt',0)))->count();
		$this->assign('merchant_count',$merchant_count);
		// 团购收藏
		$group_count = M('Merchant_user_relation')->where(array('uid'=>$this->user_session['uid'], 'type'=>'group'))->count();
		$this->assign('group_count',$group_count);
		// 美食收藏
        $foodshop_count = D('')->table(array(C('DB_PREFIX').'merchant_store'=>'ms', C('DB_PREFIX').'merchant_user_relation'=>'msc',C('DB_PREFIX').'merchant_store_foodshop'=>'msf'))->where("msc.uid= '".$this->user_session['uid']."' AND ms.store_id = msc.store_id AND msf.store_id = msc.store_id AND msc.type = 'foodshop'")->count();
//        $foodshop_count = M('Merchant_store_collection')->where(array('uid'=>$this->user_session['uid']))->count();
		$this->assign('foodshop_count',$foodshop_count);
		// 美食收藏
		$appoint_count = M('Merchant_user_relation')->where(array('uid'=>$this->user_session['uid'],'type'=>'appoint'))->count();
		$this->assign('appoint_count',$appoint_count);

		$site_phone = empty($this->config['site_phone'])?array():explode(' ',$this->config['site_phone']);
		$kefu['phone'] =$site_phone;

        if (C('IM_JMESSAGE_OPEN')) {
            $whiteUid = C('IM_WHITE_UID');
            $kf = M('Merchant_store_kefu')->where(['store_id' => 0])->find();
            if ($kf && $uid > 0 && (empty($whiteUid) || in_array($uid, $whiteUid))) {
                $kefu['url'] = build_im_chat_url('user_' . $uid, $kf['username'], 'user2plat', ['come_from' => 'my']);
            } else {
                if ($this->is_weixin_browser && $this->config['open_kefu_url'] && $this->now_user['openid'] && $services = D('Customer_service')->where(array('mer_id' => $this->config['kefu_mer_id']))->select()) {
                    $key = $this->get_encrypt_key(array('app_id' => $this->config['im_appid'], 'openid' => $this->now_user['openid']), $this->config['im_appkey']);
                    $kefu['url'] = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com') . '/?app_id=' . $this->config['im_appid'] . '&openid=' . $this->now_user['openid'] . '&key=' . $key . '#serviceList_' . $this->config['kefu_mer_id'];
                }
            }
        } else {
            if ($this->is_weixin_browser && $this->config['open_kefu_url'] && $this->now_user['openid'] && $services = D('Customer_service')->where(array('mer_id' => $this->config['kefu_mer_id']))->select()) {
                $key = $this->get_encrypt_key(array('app_id' => $this->config['im_appid'], 'openid' => $this->now_user['openid']), $this->config['im_appkey']);
                $kefu['url'] = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com') . '/?app_id=' . $this->config['im_appid'] . '&openid=' . $this->now_user['openid'] . '&key=' . $key . '#serviceList_' . $this->config['kefu_mer_id'];
            }
        }

		$this->assign('kefu',$kefu);

        // 是否绑定企业 企业员工预存款
        $qiye_bind = M('Qiye_staff')->where(array('job_number'=>$this->user_session['uid'],'status'=>1))->find();
        if ($qiye_bind) {
        	$qiye_info = M('Qiye')->where(array('qiye_id'=>$qiye_bind['qiye_id']))->find();
			$this->assign('qiye_info',$qiye_info);
        }
		$this->assign('qiye_bind',$qiye_bind);

		if($from=='mall'){
			$this->display('Mall/myCenter');
		}else{
		    // 处理下从业者信息
            if ($this->config['employee_switch']) {
                $data['uid'] = $uid;
                $m_employee_list = M('Employee_list');
                $info = $m_employee_list->where(array('uid' => $uid))->find();
                if ($info && $info['examine_status'] == 1) {
                    $this->assign('is_employee',true);
                } else {
                    $this->assign('is_employee',false);
                }
            } else {
                $this->assign('is_employee',false);
            }

			$this->display();	
		}
	}

	//	老的个人中心页面
	public function index__old(){
		if($this->config['im_appid'] && $_SESSION['openid'] && $this->config['user_center_redirect_friend']){
			redirect(U('Api/go_im',array('hash'=>'myList','title'=>urlencode('会员中心'))));exit;
		}
		$this->display();
	}

	//	个人信息页面
	public function myinfo(){
		
		redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/my/myinfo');
		
		$find	=	M('User_authentication')->field('authentication_id')->where(array('uid'=>$this->user_session['uid']))->order('authentication_time DESC')->find();
		$this->assign('find',$find);
		$find_car	=	M('User_authentication_car')->field('car_id')->where(array('uid'=>$this->user_session['uid']))->order('add_time DESC')->find();
		$this->assign('find_car', $find_car);


		$merchant_url = $this->config['site_url'] . '/packapp/merchant/index.html';

		$this->assign('merchant_url', $merchant_url);
		$this->display();
	}

	//	我的钱包页面
	public function my_money(){
		if($_GET['source'] == 1){
			$_SESSION['source']	=	1;
		}else{
			$_SESSION['source']	=	2;
		}
		$this->display();
	}

	//	完善资料页面
	public function inputinfo(){
		$this->display();
	}

	//	交易记录
	public function transaction(){
		if($this->config['open_allinyun']==1){
			$allinyun = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();
			if($allinyun['bizUserId']){
				redirect(U('setAccountDeposit/money_list'));
			}
		}
		$this->display();
	}

	public function money_list(){
		$this->display();
	}

	public function redpack_list(){
		$res = M('User_redpack_history')->where(array('uid'=>$this->now_user['uid']))->sum('money');
		$this->assign('redpack_money',$res['money']);
		$this->display();
	}

	//	交易记录json
	public function transaction_json(){
		$page	=	$_POST['page'];
		$transaction	=	D('User_money_list')->get_list($this->now_user['uid'],$page,20);
		$transaction['count'] = count($transaction['money_list']);
		foreach($transaction['money_list'] as $k=>$v){
			$transaction['money_list'][$k]['time_s']	=	date('Y/m/d H:i',$v['time']);
			$transaction['money_list'][$k]['money']	=	floatval($transaction['money_list'][$k]['money']	);
		}
		echo json_encode($transaction);
	}

	//红包记录
	public function redpack_json(){
		$page	=	$_POST['page'];
		$transaction	=	D('User_money_list')->get_redpack_list($this->now_user['uid'],$page,20);
		$transaction['count'] = count($transaction['money_list']);
		foreach($transaction['money_list'] as $k=>$v){
			$transaction['money_list'][$k]['time_s']	=	date('Y/m/d H:i',$v['add_time']);
		}
		echo json_encode($transaction);
	}

	//	企业员工预存款余额操作记录
	public function qiye_staff_money_list(){
		$this->display();
	}

	//	企业员工预存款余额操作记录
	public function qiye_staff_money_list_json(){
		$page = $_POST['page'] ? $_POST['page'] : 1;
        $start = ($page - 1) * 20;
        $keyword = $_POST['keyword'];
        if (!$this->config['open_company_advance_deposit']) {
            exit(json_encode(array('page' => $page, 'list' => array())));
        }
        $where = array();
        $where['uid'] = $this->user_session['uid'];
        $list = M('Qiye_staff_money_list')->where($where)->limit($start, 20)->order('time DESC')->select();
        $list = $list ? $list : array();
        $list['list'] = $list;
        $list['count'] = count($list['list']);

		foreach($list['list'] as $k=>$v){
			$list['list'][$k]['time_s']	=	date('Y/m/d H:i',$v['time']);
		}
		echo json_encode($list);
	}

	//	积分记录
	public function integral(){
		$this->display();
	}

	//新积分记录
	public function score_list(){
		$this->display();
	}

	//	积分记录json
	public function integral_json(){
		$page	=	$_POST['page'];
		$integral	=	D('User_score_list')->get_list($this->user_session['uid'],$page,20);
		$integral['count'] = count($integral['score_list']);
		foreach($integral['score_list'] as $k=>&$v){
			$integral['score_list'][$k]['time_s']	=	date('Y/m/d H:i',$v['time']);
			$integral['score_list'][$k]['score']	=	floatval($integral['score_list'][$k]['score']	);
			if(
			    !empty($v['order_id']) &&
                !empty($v['order_type']) &&
                $v['order_type'] == 'shop' &&
                strpos('编号',$v['order_type']) < 1
            ){
			    $shopOrder = D('Shop_order')
                    ->where(['order_id' => $v['order_id']])
                    ->field('real_orderid')
                    ->find();
			    if(!empty($shopOrder)){
			        $v['desc'] .= '，订单编号'.$shopOrder['real_orderid'];
                }
            }
		}
		echo json_encode($integral);
	}

	//	关于我们
	public function about(){
		$this->display();
	}

	//	关于我们json
	public function about_json(){
		$activity_arr = array();
		$intro = D(_view('Appintro'));
		$count	=	$intro->count();
		if($count){
			$intro_info = $intro->select();
			foreach($intro_info as $v){
				$activity_arr[] = array(
						'title'=>$v['title'],
						'url'=>$this->config['site_url'] .'/wap.php?g=Wap&c=Appintro&a=intro&id='.$v['id']
				);
			}
		}else{
			$activity_arr  = array();
		}
		echo json_encode($activity_arr);
	}

	public function savemyinfo(){
		$_POST['truename']=trim($_POST['truename']);
		if(empty($_POST['truename'])) $this->dexit(array('error'=>1,'msg'=>'您的姓名必须要填写！'));
		if(empty($_POST['youaddress'])) $this->dexit(array('error'=>1,'msg'=>'地址必须要填写！'));
        if (isset($_POST['add_time'])) {
            unset($_POST['add_time']);
        }
		if(M('User')->where(array('uid'=>$this->now_user['uid']))->data($_POST)->save()){
			$this->dexit(array('error'=>0,'msg'=>'保存成功！'));
		}
		$this->dexit(array('error'=>1,'msg'=>'保存失败！'));
	}

	public function username(){
		if($_POST['nickname']){
			if(empty($_POST['nickname'])){
				$this->assign('error','请输入新用户名！');
			}else if($_POST['nickname'] == $this->now_user['nickname']){
				$this->assign('error','您还没有修改用户名！');
			}else if($_POST['nickname'] == $this->config['site_name']){
				$this->assign('error','用户名不能和平台名称一样！');
			}else{
				$result = D('User')->save_user($this->now_user['uid'],'nickname',$_POST['nickname']);
				if($result['error']){
					$this->assign('error',$result['msg']);
				}else{
					redirect(U('My/myinfo',array('OkMsg'=>urlencode('昵称修改成功'))));
				}
			}
		}
		$this->display();
	}

	public function password(){
		if(IS_POST){
			if(!empty($this->now_user['pwd']) && md5($_POST['currentpassword']) != $this->now_user['pwd']){
				$this->assign('error','当前密码输入错误！');
			}else if($_POST['currentpassword'] == $_POST['password']){
				$this->assign('error','新密码不能和当前密码相同！');
			}else if($_POST['password2'] != $_POST['password']){
				$this->assign('error','两次新密码输入不一致！');
			}else{
				$result = D('User')->save_user($this->now_user['uid'],'pwd',md5($_POST['password']));
				if($result['error']){
					$this->assign('error',$result['msg']);
				}else{
					unset($_SESSION['veriry_token']);
					redirect(U('My/myinfo',array('OkMsg'=>urlencode('密码修改成功'))));
				}
			}
		}
		$this->display();
	}

	//发送验证码
	public function SmsCodeverify() {
		$user_modifypwdDb = M('User_modifypwd');
		if(isset($_POST['phone']) && !empty($_POST['phone'])){
			$chars = '0123456789';
			mt_srand((double)microtime() * 1000000 * getmypid());
			$vcode = "";

			while (strlen($vcode) < 6)
				$vcode .= substr($chars, (mt_rand() % strlen($chars)), 1);
			$content = '您的验证码是：'. $vcode . '。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！';
			Sms::sendSms(array('mer_id' => 0, 'store_id' => 0, 'content' => $content, 'mobile' => $_POST['phone'], 'uid' => $this->now_user['uid'], 'type' => 'bindphone'));
			$addtime = time();
			$expiry = $addtime + 20 * 60; /*             * **二十分钟有效期*** */
			$data = array('telphone' => $_POST['phone'], 'vfcode' => $vcode, 'expiry' => $expiry, 'addtime' => $addtime);
			$insert_id = $user_modifypwdDb->add($data);
			$this->ajaxReturn(array('error' => false));
			exit();

		}
	}

	public function bind_user(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$referer = !empty($_GET['referer']) ? htmlspecialchars_decode($_GET['referer']) : U('My/index');
		
		redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/my/newnumber?referer=' . urlencode($referer));
		
		if(preg_match('/\d+\|/',$_POST['phone'])){
			$tmp_phone = explode('|',$_POST['phone']);
			$_POST['phone'] = $tmp_phone[1];
			$_POST['phone_country_type'] = $tmp_phone[0];
			$_POST['phone'] = phone_format($_POST['phone_country_type'], $_POST['phone']);
		}

		if($this->config['open_allinyun']==1){
			redirect(U('setAccountDeposit/bindphone',array('referer'=>$referer)));
		}
		if(IS_POST){
			$database_user = D('User');
			if(empty($_POST['phone'])){
				$this->error('请输入手机号码！');
			}

			if(!empty($this->now_user['phone'])){
				$condition_user['phone'] = $_POST['phone'];
				if($database_user->field(true)->where($condition_user)->find()){
					$this->error('该手机号已注册！');
				}
			}

			//验证短信验证码

			if ($this->config['bind_phone_verify_sms']&&$this->config['sms_key']&&substr($_POST['phone'],0,10)!='1321234567') {
				$sms_verify_result = D('Smscodeverify')->verify($_POST['sms_code'], $_POST['phone']);
				if ($sms_verify_result['error_code']) {
					$this->error($sms_verify_result['msg']);
				} else {
					$modifypwd = $sms_verify_result['modifypwd'];
				}
			}

			//if (!empty($modifypwd)||$this->config['bind_phone_verify_sms']=='0'||empty($this->config['sms_key'])) {
			//$nowtime = time();
			//if ($modifypwd['expiry'] > $nowtime||$this->config['bind_phone_verify_sms']=='0'||empty($this->config['sms_key'])) {

			$condition_user['phone'] = $_POST['phone'];
			$res = $database_user->where($condition_user)->find();
			if($res && empty($this->now_user['phone']) && (!empty($this->now_user['openid']) || !empty($this->now_user['alipay_uid']))){
				if($_POST['bind_exist']){
					//$openid = $database_user->field('`openid`')->where(array('uid'=>$res['uid']))->find();
					if((!empty($res['openid']) && !empty($this->now_user['openid']) ) || (!empty($res['alipay_uid']) &&  !empty($this->now_user['alipay_uid']))){
						$this->error("该手机已被其他用户绑定，请登录该账号解除绑定！");
					}
					if($this->config['reg_verify_sms'] && $this->config['bind_phone_verify_sms'] && !empty($this->config['sms_key'])){
						$sms_verify_result = D('Smscodeverify')->verify($_POST['sms_code'], $_POST['phone']);
						if ($sms_verify_result['error_code']) {
							$this->error($sms_verify_result['msg']);
						}else{
							$modifypwd = $sms_verify_result['modifypwd'];
							$login_result['user'] = D('User')->get_user($_POST['phone'],'phone');

						}
					}else{
						$check_pwd = true;
						if ($this->config['bind_phone_verify_sms']==0 && ($this->now_user['pwd'] == '' OR $this->now_user['phone'] == '') ) {
							$check_pwd = false;
						}
						$login_result = D('User')->checkin($_POST['phone'],$_POST['password'],fasle,false,'',$check_pwd);
					}
					if($login_result['error_code']){
						$this->error($login_result['msg']);
					}else{
						$data_user['openid'] =$this->now_user['openid'];
						$this->now_user['union_id'] && $data_user['union_id'] =$this->now_user['union_id'];
						$this->now_user['wxapp_openid'] && $data_user['wxapp_openid'] =$this->now_user['wxapp_openid'];
						$this->now_user['avatar'] && $data_user['avatar'] = $this->now_user['avatar'];
						$this->now_user['pay_pwd'] && $data_user['pay_pwd'] = $this->now_user['pay_pwd'];
						if($this->now_user['openid'] && $database_user->where(array('uid'=>$res['uid']))->save($data_user)){
							if($this->config['cash_bind_phone_merge_score']){
								D('User')->merge_user_from_new($this->now_user['uid'],$res['uid']);//合并积分
							}

							$data_use['openid'] = $this->now_user['openid'].'~no_use';
							$data_use['union_id'] = $this->now_user['union_id'].'~no_use';
							$data_use['wxapp_openid'] = $this->now_user['wxapp_openid'].'~no_use';
							$data_use['app_openid'] = $this->now_user['app_openid'].'~no_use';
							$data_use['paotui_openid'] = $this->now_user['paotui_openid'].'~no_use';
							$data_use['group_openid'] = $this->now_user['group_openid'].'~no_use';
							$data_use['phone'] = '';
							$data_use['status'] = 4;
							$_POST['phone_country_type'] && $data_use['phone_country_type'] = $_POST['phone_country_type'];
							$database_user->where(array('uid'=>$this->now_user['uid']))->save($data_use);
							setcookie('user_device_id', '', -1, '/');
		                    setcookie('user_ticket', '', -1, '/');
		                    setcookie('login_status', '', -1, '/');
							// 转移资产
							D("User")->change_property($this->now_user['uid'],$res['uid']);
							
							session_destroy();
							unset($_SESSION);
							$this->success("绑定成功，请重新登录",$_GET['referer']);
						}elseif($this->now_user['alipay_uid'] && $database_user->where(array('uid'=>$res['uid']))->setField('alipay_uid',$this->now_user['alipay_uid'])) {
							if($this->config['cash_bind_phone_merge_score']) {
								D('User')->merge_user_from_new($this->now_user['uid'], $res['uid']);
							}
							$data_use['alipay_uid'] = $this->now_user['alipay_uid'].'~no_use';
							$data_use['phone'] = '';
							$data_use['status'] = 4;
							$_POST['phone_country_type'] && $data_use['phone_country_type'] = $_POST['phone_country_type'];
							$database_user->where(array('uid'=>$this->now_user['uid']))->save($data_use);
							// 转移资产
							D("User")->change_property($this->now_user['uid'],$res['uid']);
							session_destroy();
							unset($_SESSION);
							$this->success("绑定成功，请重新登录",$_GET['referer']);
						}else {
							$this->error('绑定失败!');
						}
					}
				}else {
					$this->error('phone_exist');
				}
			}

			$condition_save_user['uid'] = $this->now_user['uid'];
			$data_save_user['phone'] = $_POST['phone'];
			if(!empty($_POST['password'])){
				if(!empty($this->now_user['phone'])){
					$condition_save_user['pwd'] = md5($_POST['password']);
				}else{
					$data_save_user['pwd'] = md5($_POST['password']);
				}
			}

			$_POST['phone_country_type'] && $data_save_user['phone_country_type'] = $_POST['phone_country_type'];
			if($database_user->where($condition_save_user)->data($data_save_user)->save()){
				$_SESSION['user']['phone'] = $_POST['phone'];
//				session_destroy();
//				unset($_SESSION);


				$database_house_village_user_bind = D('House_village_user_bind');
				$bind_where['uid'] = $this->now_user['uid'];
				$data = array('phone'=>$_POST['phone']);

				$database_house_village_user_bind->where($bind_where)->save($data);

            	// 绑定手机号获得积分
				$score = D('User_score_list')->get_one_score(array('score_type'=>1,'uid'=>$bind_where['uid']));
				if (!$score&&C('config.score_bind_phone')>0) { // 绑定手机只能获一次积分
					D('User')->add_score($bind_where['uid'], C('config.score_bind_phone'),'绑定手机号获得'.C('config.score_name'),0,array('score_type'=>1));
				}

				$this->success('手机号码绑定成功！');
			}else{
				$this->error('手机号码绑定失败！请重试。');
			}
			exit();
			//}
			//	}
		}
		$now_user = D('User')->where(array('uid'=>$this->user_session['uid']))->find();
		$this->assign('referer',$referer);
		$this->assign('now_user',$now_user);
		$this->display();

	}

	//验证原手机
	public function verify_original_phone(){
		redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/my/number');
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		if(IS_POST) {
			$database_user = D('User');
			if (empty($_POST['phone'])) {
				$this->error_tips('请输入手机号码！');
			}

			if (empty($_POST['vcode'])) {
				$this->error_tips('请输入验证码！');
			}
			//print_r($_POST);
			//验证短信验证码
			if (substr($_POST['phone'], 0, 10) != '1321234567') {
				$sms_verify_result = D('Smscodeverify')->verify($_POST['vcode'], $_POST['phone']);
				if ($sms_verify_result['error_code']) {
					$this->error_tips($sms_verify_result['msg']);
				} else {
					$modifypwd = $sms_verify_result['modifypwd'];
				}
			}
			$_SESSION['veriry_token'] =1 ;
			$url = $_POST['go']=='password'?U('My/password'):U('My/bind_user',array('bind'=>1));
			$this->success_tips('手机号码验证成功！',$url);

		}else{
			$referer = !empty($_GET['referer']) ? $_GET['referer'] : $_SERVER['HTTP_REFERER'];
			$this->assign('referer',$referer);
			$this->display();
		}
	}

	/*优惠券操作*/
	public function card(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}

		$coupon_list = D('Member_card_coupon')->get_all_coupon($this->user_session['uid']);
		$this->assign('coupon_list',$coupon_list);

		$this->display();
	}

	/*选择优惠券*/
	public function select_card(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		//以下代码是为了得到商户的mer_id ，并且判断此订单是否存在！
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
		}else if($_GET['type'] == 'wxapp'){
			$_GET['notOffline'] = true;
			$now_order = D('Wxapp_order')->get_pay_order($_GET['uid'],intval($_GET['order_id']));
			$this->assign('notCard',true);
		}else if($_GET['type'] == 'store'){
			$_GET['notOffline'] = true;
			$now_order = D('Store_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
			//$this->assign('notCard',true);
		}else if($_GET['type'] == 'shop'||$_GET['type'] == 'mall'){
			$now_order = D('Shop_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
		}else if($_GET['type'] == 'plat'){
			$now_order = D('Plat_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
		}else if($_GET['type'] == 'balance-appoint'){
            $now_order = D('Appoint_order')->get_pay_balace_order($this->user_session['uid'],intval($_GET['order_id']));
        }else if($_GET['type'] == 'village_group'){
            $now_order = D('Village_group_order')->get_pay_order(intval($_GET['order_id']));
        }else if($_GET['type'] == 'integral_mall'){
            $param = $_GET;
            $this->assign('unselect',U('Pay/check',$param));
            $this->assign('coupon_list',array());
            $this->display();
        }else if($_GET['type'] == 'mcard'){
            $now_order = D('Membership_card_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']));
        }else{
			$this->error_tips('非法的订单');
		}
		$now_order = $now_order['order_info'];
		$now_order['total_money'] = $now_order['order_total_money'];

		if(empty($now_order)){
			$this->error_tips('当前订单不存在！');
		}

		if($_SESSION['discount']>0){
			$now_order['total_money']=$now_order['total_money']*$_SESSION['discount']/10;
			unset($_SESSION['discount']);
		}

		if($_SESSION['wx_cheap']>0){
			$now_order['total_money'] -=$_SESSION['wx_cheap'];
			unset($_SESSION['wx_cheap']);
		}
		$now_order['uid'] = $this->user_session['uid'];
		$this->assign('back_url',U('Pay/check',$_GET));
		if ($this->is_wxapp_browser) { 
            $platform = 'wxapp';
        } else if($this->is_app_browser){
			$platform = 'app';
		}else if($this->is_weixin_browser){
			$platform = 'weixin';
		}else{
			$platform = 'wap';
		}
		if($_GET['coupon_type']=='mer') {
			//$card_list = D('Member_card_coupon')->get_coupon($now_order['mer_id'], $this->user_session['uid']);
			if(!empty($now_order['business_type'])){
				$coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($now_order,$_GET['type'],$platform,$now_order['business_type']);
			}else{
				$coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($now_order, $_GET['type'],$platform);
			}
		}else if($_GET['coupon_type']=='system') {

			if($_SESSION['card_discount']>0){
				$now_order['total_money'] -= $_SESSION['card_discount'];
				unset($_SESSION['card_discount']);
			}
			if(!empty($now_order['business_type'])) {
				$coupon_list = D('System_coupon')->get_noworder_coupon_list($now_order, $_GET['type'], $this->user_session['phone'], $this->user_session['uid'], $platform,$now_order['business_type']);
			}else{
				$coupon_list = D('System_coupon')->get_noworder_coupon_list($now_order, $_GET['type'], $this->user_session['phone'], $this->user_session['uid'], $platform);
			}
		}
		if(!empty($coupon_list)){
			$param = $_GET;
			foreach($coupon_list as &$value){
			    if ($_GET['type'] == 'village_group') {
                    if($_GET['coupon_type']=='mer'){
                        $param['merc_id'] =$value['id'];
                        unset($param['unmer_coupon']);
                    }else{
                        unset($param['unsys_coupon']);
                        $param['sysc_id'] =$value['id'];
                    }
                    $value['select_url'] = U('Village_group/confirmOrder',$param);
                } else {
                    if($_GET['coupon_type']=='mer'){
                        $param['merc_id'] =$value['id'];
                        unset($param['unmer_coupon']);
                    }else{
                        unset($param['unsys_coupon']);
                        $param['sysc_id'] =$value['id'];
                    }
                    $value['select_url'] = U('Pay/check',$param);
                }
			}
			$this->assign('coupon_list',$coupon_list);
		}

		$param = $_GET;

		if($_GET['coupon_type']=='mer'){
			unset($param['merc_id']);
			$param['unmer_coupon']=1;
		}else{
			unset($param['sysc_id']);
			$param['unsys_coupon']=1;
		}

        if ($_GET['type'] == 'village_group') {
            $this->assign('unselect',U('Village_group/confirmOrder',$param));
        } else {
            $this->assign('unselect',U('Pay/check',$param));
        }

		$this->display();
	}

        /* 地址操作 */
    public function adress()
    {
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！');
        }
        $adress_list = D('User_adress')->get_adress_list($this->user_session['uid']);
        
        if (empty($adress_list)) {
            redirect(U('My/edit_adress', $_GET));
        } else {
            
            $inList = array();
            $outList = array();
            $distance_array = array();
            
            if ($_GET['group_id'] && $_GET['buy_type'] !='mall') {
                $select_url = 'Group/buy';
            } elseif ($_GET['store_id']) {
                if ($_GET['buy_type'] == 'waimai') {
                    $select_url = 'Takeout/sureOrder';
                } elseif ($_GET['buy_type'] == 'shop') {
                    $store = M('Merchant_store')->field('long,lat')->where(array('store_id' => intval($_GET['store_id'])))->find();
                    $shop = M('Merchant_store_shop')->field('delivery_range_type,delivery_radius,delivery_range_polygon, deliver_type')->where(array('store_id' => intval($_GET['store_id'])))->find();
                    $store = array_merge($store, $shop);
                    if ($store['delivery_range_polygon']) {
                        $store['delivery_range_polygon'] = substr($store['delivery_range_polygon'], 9, strlen($store['delivery_range_polygon']) - 11);
                        $lngLatData = explode(',', $store['delivery_range_polygon']);
                        array_pop($lngLatData);
                        $lngLats = array();
                        foreach ($lngLatData as $lnglat) {
                            $lng_lat = explode(' ', $lnglat);
                            $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
                        }
                        $store['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
                    }
                    foreach ($adress_list as $address) {
                        $address['distance'] = getDistance($address['latitude'], $address['longitude'], $store['lat'], $store['long']);
                        if ($store['delivery_range_type'] == 0) {
                            $distance = $address['distance'] / 1000;
                            if ($store['delivery_radius'] >= $distance || $store['deliver_type'] == 5) {
                                $distance_array[] = $address['distance'];
                                $inList[] = $address;
                            } else {
                                $outList[] = $address;
                            }
                        } else {
                            if ($store['delivery_range_polygon']) {
                                if (!isPtInPoly($address['longitude'], $address['latitude'], $store['delivery_range_polygon']) && $store['deliver_type'] != 5) {
                                    $outList[] = $address;
                                } else {
                                    $distance_array[] = $address['distance'];
                                    $inList[] = $address;
                                }
                            } else {
                                $outList[] = $address;
                            }
                        }
                    }
                    if ($distance_array && $inList) {
                        array_multisort($distance_array, SORT_ASC, $inList);
                    }
                    $adress_list = $inList;
                    
                    $select_url = 'Shop/confirm_order';
                } elseif ($_GET['buy_type'] == 'LocalShopMall') {
                    $store = M('Merchant_store')->field('long,lat')->where(array('store_id' => intval($_GET['store_id'])))->find();
                    $shop = M('Merchant_store_shop')->field('delivery_range_type,delivery_radius,delivery_range_polygon, deliver_type')->where(array('store_id' => intval($_GET['store_id'])))->find();
                    $store = array_merge($store, $shop);
                    if ($store['delivery_range_polygon']) {
                        $store['delivery_range_polygon'] = substr($store['delivery_range_polygon'], 9, strlen($store['delivery_range_polygon']) - 11);
                        $lngLatData = explode(',', $store['delivery_range_polygon']);
                        array_pop($lngLatData);
                        $lngLats = array();
                        foreach ($lngLatData as $lnglat) {
                            $lng_lat = explode(' ', $lnglat);
                            $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
                        }
                        $store['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
                    }
                    foreach ($adress_list as $address) {
                        $address['distance'] = getDistance($address['latitude'], $address['longitude'], $store['lat'], $store['long']);
                        if ($store['delivery_range_type'] == 0) {
                            $distance = $address['distance'] / 1000;
                            if ($store['delivery_radius'] >= $distance || $store['deliver_type'] == 5) {
                                $distance_array[] = $address['distance'];
                                $inList[] = $address;
                            } else {
                                $outList[] = $address;
                            }
                        } else {
                            if ($store['delivery_range_polygon']) {
                                if (!isPtInPoly($address['longitude'], $address['latitude'], $store['delivery_range_polygon']) && $store['deliver_type'] != 5) {
                                    $outList[] = $address;
                                } else {
                                    $distance_array[] = $address['distance'];
                                    $inList[] = $address;
                                }
                            } else {
                                $outList[] = $address;
                            }
                        }
                    }
                    if ($distance_array && $inList) {
                        array_multisort($distance_array, SORT_ASC, $inList);
                    }
                    $adress_list = $inList;

                    $select_url = 'LocalShopMall/confirm_order';
                } elseif ($_GET['buy_type'] == 'mall') {
                    $select_url = 'Mall/confirm_order';
                } else {
                    $select_url = 'Meal/cart';
                }
            } elseif (! empty($_GET['gift_id'])) {
                $select_url = 'Gift/order';
                $plat_deliver = M('Gift_plat_deliver')->where(array('id'=>2))->find();
                $userLocationLat = $plat_deliver['lat'];
        		$userLocationLong = $plat_deliver['long'];
        		$plat_deliver['delivery_range_polygon'] = substr($plat_deliver['delivery_range_polygon'], 9, strlen($plat_deliver['delivery_range_polygon']) - 11);
        		$lngLatData = explode(',', $plat_deliver['delivery_range_polygon']);
		        array_pop($lngLatData);
		        $lngLats = array();
		        foreach ($lngLatData as $lnglat) {
		            $lng_lat = explode(' ', $lnglat);
		            $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
		        }
		        $plat_deliver['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
		        foreach ($adress_list as $address) {
			        if ($plat_deliver['delivery_range_type'] == 0) {
		                $distance = getDistance($address['latitude'], $address['longitude'], $plat_deliver['lat'], $plat_deliver['long']);
		                $distance = $distance / 1000;
		                
		                if ($plat_deliver['delivery_radius'] < $distance) {
		                    $outList[] = $address;
		                }
		                else{
		                	$inList[] = $address;	
		                }
		            } else {
		                if ($plat_deliver['delivery_range_polygon']) {
		                    if (!isPtInPoly($address['longitude'], $address['latitude'], $plat_deliver['delivery_range_polygon'])) {
		                        $outList[] = $address;
		                    }
		                    else{
		                    	$inList[] = $address;	
		                    }
		                } else {
                            $outList[] = $address;
		                }
		            }
		        }
	            $adress_list = $inList;
            } elseif (! empty($_GET['appoint_id'])) {
                $select_url = 'Appoint/order';
            } elseif (! empty($_GET['classify_userinput_id'])) {
                $select_url = 'Classify/order';
            } elseif (! empty($_GET['cid'])) {
                $select_url = 'Service/publish_detail';
            } elseif (! empty($_GET['token'])){
                $select_url = 'Lotterynew/index';
            } elseif ($_GET['buy_type'] == 'village_group' && $_GET['order_id']) {
                $order  = D('Village_group_order')->order_detail($_GET['order_id']);
                $header = D('Village_group')->get_header_info(0,$order['start_uid'],true);
                $header  =$header['msg'];
                foreach ($adress_list as $address) {
                    $address['distance'] = getDistance($address['latitude'], $address['longitude'], $header['pick_address']['lat'], $header['pick_address']['long']);
                    if ($header['pick_address']['delivery_range_type'] == 0) {
                        $distance = $address['distance'] / 1000;
                        if ($header['pick_address']['delivery_radius'] >= $distance) {
                            $distance_array[] = $address['distance'];
                            $inList[] = $address;
                        } else {
                            $outList[] = $address;
                        }
                    }
                }
                if ($distance_array && $inList) {
                    array_multisort($distance_array, SORT_ASC, $inList);
                }
                $adress_list = $inList;

                $select_url = 'Village_group/confirmOrder';
            } elseif ($_GET['buy_type'] == 'integral_mall'){
                $select_url = 'Integral_mall/confirm_order';
            } elseif ($_GET['type'] == 'receive_level_gift') {
                $select_url = 'My/receive_level_gift';
            }
            if ($select_url) {
                $this->assign('back_url', U($select_url, $_GET));
            } else {
                $this->assign('back_url', U('My/myinfo'));
            }
            $param = $_GET;
            
            foreach ($adress_list as $key => $value) {
                $param['adress_id'] = $value['adress_id'];
                if (! empty($select_url)) {
                    $adress_list[$key]['select_url'] = U($select_url, $param);
                }
                $adress_list[$key]['edit_url'] = U('My/edit_adress', $param);
                $adress_list[$key]['del_url'] = U('My/del_adress', $param);
            }
            
            foreach ($outList as &$out) {
                $param['adress_id'] = $out['adress_id'];
                if (! empty($select_url)) {
                    $out['select_url'] = U($select_url, $param);
                }
                $out['edit_url'] = U('My/edit_adress', $param);
                $out['del_url'] = U('My/del_adress', $param);
            }
            //售后说快递配送没有服务范围的
            if($_GET['now_delivery_type']==0 && !empty($outList)){
                if(empty($adress_list)){
                    $adress_list=$outList;
                }
                $outList[]=[];
            }
            $this->assign('adress_list', $adress_list);
            $this->assign('out_adress_list', $outList);

			if($select_url){
				$this->assign('back_url',U($select_url,$_GET));
			}else{
				$this->assign('back_url',U('My/myinfo'));
			}
			$param = $_GET;

			foreach($adress_list as $key=>$value){
				$param['adress_id'] = $value['adress_id'];
				if(!empty($select_url)){
					$adress_list[$key]['select_url'] = U($select_url,$param);
				}
				$adress_list[$key]['edit_url'] = U('My/edit_adress',$param);
				$adress_list[$key]['del_url'] = U('My/del_adress',$param);
			}

			$this->assign('adress_list',$adress_list);

			$database_area = D('Area');
			$now_city_area = $database_area->where(array('area_id'=>$this->config['now_city']))->find();
			$this->assign('now_city_area',$now_city_area);

			$province_list = $database_area->get_arealist_by_areaPid(0);
			$this->assign('province_list',$province_list);

			$city_list = $database_area->get_arealist_by_areaPid($now_city_area['area_pid']);
			$this->assign('city_list',$city_list);

			$area_list = $database_area->get_arealist_by_areaPid($now_city_area['area_id']);
			$this->assign('area_list',$area_list);

			$id = $_GET['adress_id'];
			if(cookie('user_address') === '0' || cookie("user_address") == "") {
				$now_adress = D('User_adress')->get_adress($this->user_session['uid'], $id);
				if ($now_adress) {
					$this->assign('now_adress', $now_adress);

					$province_list = $database_area->get_arealist_by_areaPid(0);
					$this->assign('province_list',$province_list);

					$city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
					$this->assign('city_list', $city_list);

					$area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
					$this->assign('area_list', $area_list);
				} else {
					$now_city_area = $database_area->where(array('area_id'=>$this->config['now_city']))->find();
					$this->assign('now_city_area',$now_city_area);

					$province_list = $database_area->get_arealist_by_areaPid(0);
					$this->assign('province_list',$province_list);

					$city_list = $database_area->get_arealist_by_areaPid($now_city_area['area_pid']);
					$this->assign('city_list',$city_list);

					$area_list = $database_area->get_arealist_by_areaPid($now_city_area['area_id']);
					$this->assign('area_list',$area_list);
				}
			} else {
				$cookie = json_decode($_COOKIE['user_address'], true);
				$now_adress = $cookie;
				$now_adress['default'] = $now_adress['defaul'];
				$now_adress['adress_id'] = $now_adress['id'];
				$this->assign('now_adress', $now_adress);
				$province_list = $database_area->get_arealist_by_areaPid(0);
				$this->assign('province_list',$province_list);

				$city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
				$this->assign('city_list', $city_list);

				$area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
				$this->assign('area_list', $area_list);
				$params = $_GET;
				unset($params['adress_id']);
				$this->assign('params',$params);
			}
			$this->display();
		}
	}

	public function pick_address(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$flag = $_GET['buy_type'] == 'shop' || $_GET['buy_type'] == 'mall' ? true : false;
		$adress_list = D('Pick_address')->get_pick_addr_by_merid($_GET['mer_id'], $flag, $_GET['store_id'],false,[],1);
		if(empty($adress_list)){
			$this->error_tips('地址信息错误请联系管理员！');
		}else{
			if ($_GET['group_id'] && $_GET['buy_type'] !='mall') {
				$select_url = 'Group/buy';
				$database_group_store = D('Group_store');
				$condition_group_store['group_id'] = $_GET['group_id'];
				$store_list = $database_group_store->field(true)->where($condition_group_store)->select();
				$store_arr = array();
				foreach ($store_list as $value) {
					$store_arr[] = $value['store_id'];
				}
			} elseif ($_GET['store_id']) {
				if ($_GET['buy_type'] == 'waimai') {
					$select_url = 'Takeout/sureOrder';
				} elseif ($_GET['buy_type'] == 'shop') {
					$select_url = 'Shop/confirm_order';
				} elseif ($_GET['buy_type'] == 'mall') {
					$select_url = 'Mall/confirm_order';
				} else {
					$select_url = 'Meal/cart';
				}
			}else if($_GET['token']){
                $select_url = 'Lotterynew/index';
            }
			if($select_url){
				$this->assign('back_url',U($select_url,$_GET));
			}else{
				$this->assign('back_url',U('My/myinfo'));
			}

			$param = $_GET;

			foreach($adress_list as $key=>$value){
				$param['pick_addr_id'] = $value['pick_addr_id'];
				if(strpos($value['pick_addr_id'],'s')!==false){
					$tmp_store_id = preg_match('/\d+/',$value['pick_addr_id'],$r);
					if(!in_array($r[0],$store_arr)){
						unset($adress_list[$key]);
						continue;
					}
				}
				if($value['distance']){
					$adress_list[$key]['distance'] = $this->wapFriendRange($value['distance']);
				}
				if(!empty($select_url)){
					$adress_list[$key]['select_url'] = U($select_url,$param);
				}
			}
			$this->assign('pick_list',$adress_list);
			$this->display();
		}

	}

	/*添加编辑地址*/
	public function edit_adress(){
		if(IS_POST){
			if(empty($_POST['adress'])){
				$this->error('您的位置没有选择！请点击选择位置进行完善！');
			}

			if(!$_POST['province'] || !$_POST['city'] || !$_POST['area']){
				$now_city = D('Area')->cityMatching($_POST['latitude'],$_POST['longitude']);
				$_POST['province'] = $now_city['area_info']['province_id'] ? $now_city['area_info']['province_id'] : '';
				$_POST['city'] = $now_city['area_info']['city_id'] ? $now_city['area_info']['city_id'] : '';
				$_POST['area'] = $now_city['area_info']['area_id'] ? $now_city['area_info']['area_id'] : '';
			}

			if(D('User_adress')->post_form_save($this->user_session['uid']) !== false){
				cookie('user_address', 0);
				$param['group_id'] =$_POST['group_id'];
				$param['store_id'] =$_POST['store_id'];
				$param['gift_id'] =$_POST['gift_id'];
				$param['buy_type'] =$_POST['buy_type'];
				$param['classify_userinput_id'] =$_POST['classify_userinput_id'];
				$param['current_id'] =$_POST['current_id'];

				$arr=(array)$_GET;
				$param=array_merge($arr,$param);
				
                // 填写地址赠送积分
                $score = D('User_score_list')->get_one_score(array('score_type'=>3,'uid'=>$this->user_session['uid']));
                if (!$score&&C('config.score_bind_adderss')>0) { // 填写地址只能获一次积分
                    D('User')->add_score($this->user_session['uid'], C('config.score_bind_adderss'),'首次填写地址获得'.C('config.score_name'),0,array('score_type'=>3));
                }
				$this->success('保存成功！',U('adress',$param));
			}else{
				$this->error('地址保存失败！请重试');
			}
		}else{
			$database_area = D('Area');
			$id = $_GET['adress_id'];
			if(cookie('user_address') === '0' || cookie("user_address") == "") {
				$now_adress = D('User_adress')->get_adress($this->user_session['uid'], $id);
				if ($now_adress) {
					$this->assign('now_adress', $now_adress);

					$province_list = $database_area->get_arealist_by_areaPid(0);
					$this->assign('province_list',$province_list);

					$city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
					$this->assign('city_list', $city_list);

					$area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
					$this->assign('area_list', $area_list);
				} else {
					$now_city_area = $database_area->where(array('area_id'=>$this->config['now_city']))->find();
					$this->assign('now_city_area',$now_city_area);

					$province_list = $database_area->get_arealist_by_areaPid(0);
					$this->assign('province_list',$province_list);

					$city_list = $database_area->get_arealist_by_areaPid($now_city_area['area_pid']);
					$this->assign('city_list',$city_list);

					$area_list = $database_area->get_arealist_by_areaPid($now_city_area['area_id']);
					$this->assign('area_list',$area_list);
				}
			} else {
				$cookie = json_decode($_COOKIE['user_address'], true);
				$now_adress = $cookie;
				$now_adress['default'] = $now_adress['defaul'];
				$now_adress['adress_id'] = $now_adress['id'];
				$this->assign('now_adress', $now_adress);
				$province_list = $database_area->get_arealist_by_areaPid(0);
				$this->assign('province_list',$province_list);

				$city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
				$this->assign('city_list', $city_list);

				$area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
				$this->assign('area_list', $area_list);
			}

			$params = $_GET;
			unset($params['adress_id']);
			foreach($params as $key => $value){
				$params[$key] = urlencode($value);
			}
			$this->assign('params',$params);
		}

		$this->display();
	}

	/* 地图 */
	public function adres_map(){
		$params = $_GET;
		unset($params['adress_id']);
		$this->assign('params',$params);

		$database_area = D('Area');

		//得到当前城市的经纬度，用于地图初始化定位
		$now_city = $database_area->get_area_by_areaId($this->config['now_city']);
		if($now_city && $now_city['area_lng'] == 0){
            $result = D('Area')->address_info($now_city['area_name']);
            $lng = $result['result']['location']['lng'];
            $lat = $result['result']['location']['lat'];
            M('Area')->where(array('area_id'=>$this->config['now_city']))->data(array('area_lng'=>$lng,'area_lat'=>$lat))->save();
            $now_city = $database_area->get_area_by_areaId($this->config['now_city']);
		}
		
		if($now_city['area_lng'] == 0){
			$now_city['area_lng'] = '116.403847';
		}
		if($now_city['area_lat'] == 0){
			$now_city['area_lat'] = '39.915526';
		}
		$this->assign('now_city',$now_city);

		//得到所有城市并以城市首拼排序
		$all_city = S('all_city_address');
		if(empty($all_city)){
			$database_field = '`area_id`,`area_name`,`area_url`,`first_pinyin`,`is_hot`,`area_lng`,`area_lat`';
			$condition_all_city['area_type'] = 2;
			$all_city_old = $database_area->field($database_field)->where($condition_all_city)->order('`first_pinyin` ASC,`area_id` ASC')->select();
			foreach($all_city_old as $key=>$value){
				//首拼转成大写
				if(!empty($value['first_pinyin'])){
					$first_pinyin = strtoupper($value['first_pinyin']);
					$all_city[$first_pinyin][] = $value;
				}
			}
			S('all_city_address',$all_city);
		}
		$this->assign('all_city',$all_city);
		
		if($_GET['from_uni'] && $_GET['address_name'] && $_GET['address_lng'] && $_GET['address_lat'] && $this->config['map_config'] == 'google' && $this->config['google_map_ak']){
			$address_arr = [
				'province' => '',
				'city' => '',
				'area' => '',
				'adress' => $_SERVER['original_get']['address_name'],
				'longitude' => $_GET['address_lng'],
				'latitude' => $_GET['address_lat'],
			];
			cookie('user_address', json_encode($address_arr));
		}
		
		$this->display();
	}

	/*删除地址*/
	public function del_adress(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$result = D('User_adress')->delete_adress($this->user_session['uid'],$_GET['adress_id']);
		if($result){
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！');
		}
	}

	/*删除地址*/
	public function ajax_del_adress(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$result = D('User_adress')->delete_adress($this->user_session['uid'],$_GET['adress_id']);
		if($result){
			exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
		}else{
			exit(json_encode(array('status'=>1,'msg'=>'删除失败！')));
		}
	}

	public function select_area(){
		$area_list = D('Area')->get_arealist_by_areaPid($_POST['pid']);
		if(!empty($area_list)){
			$return['error'] = 0;
			$return['list'] = $area_list;
		}else{
			$return['error'] = 1;
		}
		echo json_encode($return);
	}

	/*全部团购*/
	public function group_order_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
        redirect(U('Groupnew/order_list'));

		$order_list = D('Group')->wap_get_order_list($this->user_session['uid'],intval($_GET['status']));
		$this->assign('order_list',$order_list);

		$this->display();
	}

	public function classify_order_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$database_classify_userinput = D('Classify_userinput');
		$order_list = $database_classify_userinput->wap_get_order_list($this->user_session['uid'],intval($_GET['status']));
		$this->assign('order_list',$order_list);
		$this->display();
	}

	public function ajax_classify_order_list(){
		if(IS_AJAX){
			$database_classify_userinput = D('Classify_userinput');
			$order_list = $database_classify_userinput->wap_get_order_list($this->user_session['uid'],intval($_GET['status']));

			if(!empty($order_list)){
				exit(json_encode(array('status'=>1,'order_list'=>$order_list)));
			}else{
				exit(json_encode(array('status'=>0,'order_list'=>$order_list)));
			}
		}else{
			$this->error('访问页面不存在！~~');
		}
	}

	public function ajax_group_order_list(){
		if(IS_AJAX){
			$order_list = D('Group')->wap_get_order_list($this->user_session['uid'],intval($_GET['status']));
			if(!empty($order_list)){
				exit(json_encode(array('status'=>1,'order_list'=>$order_list)));
			}else{
				exit(json_encode(array('status'=>0,'order_list'=>$order_list)));
			}

		}else{
			$this->error('访问页面不存在！~~');
		}
	}

	/*全部预约*/
	public function appoint_order_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}

		$mer_id = $_GET['mer_id'] + 0;
		$status = $_GET['status'] ? $_GET['status'] + 0 : 0;

		$database_appoint = D('Appoint');

		if($status == 1){
			$where['service_status'] = 0;
		}elseif($status == 2){
			$where['service_status'] = 1;
		}

		if(!empty($mer_id)){
			$where['mer_id'] = $mer_id;
			$where['uid'] = $this->user_session['uid'];
            $where = [
                'is_del' => ['neq', 5]
            ];
			$order_list = $database_appoint->wap_order_list($where);
		}else{
            $where = [
                'is_del' => ['neq', 5]
            ];
			$order_list = $database_appoint->wap_get_order_list($this->user_session['uid'], $status, $where);
		}

		$this->assign('order_list', $order_list);
		$this->display();
	}

	# 删除预约
	public function ajax_appoint_order_del(){
		$database_appoint_order = D('Appoint_order');
		$now_order = $database_appoint_order->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']));
		if(empty($now_order)){
			$this->error_tips('当前订单不存在！');
		}else if($now_order['paid']){
			$this->error_tips('当前订单已付款，不能删除。');
		}
		$condition_group_order['order_id'] = $now_order['order_id'];
		$data_group_order['is_del'] = 5;
		if($database_appoint_order->where($condition_group_order)->data($data_group_order)->save()){
            /*-----记录系统订单信息 订单取消成功 改写状态已取消 2020/8/7----*/
            D("System_order")->save_order_status('appoint',intval($_GET['order_id']),5);
			exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'删除失败！')));
		}
	}

	public function ajax_appoint_order_list(){
		$status = $_GET['status'] ? $_GET['status'] + 0 : 0;
		$database_appoint = D('Appoint');
        $where = [
            'is_del' => ['neq', 5]
        ];
		$order_list = $database_appoint->wap_get_order_list($this->user_session['uid'], $status, $where);

		if(!empty($order_list)){
			exit(json_encode(array('status' => 1 , 'order_list'=>$order_list)));
		}else{
			exit(json_encode(array('status'=> 0 , 'order_list'=>$order_list)));
		}
	}

	public function gift_order_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}

		$database_gift_order = D('Gift_order');
		$order_list = $database_gift_order->get_order_list($this->user_session['uid'],intval($_GET['status']),true,99999);

		$this->assign('order_list',$order_list);

		$this->display();
	}

	public function ajax_gift_order_list(){
		if(IS_AJAX){
			if(empty($this->user_session)){
				$this->error_tips('请先进行登录！');
			}

			$database_gift_order = D('Gift_order');
			$order_list = $database_gift_order->get_order_list($this->user_session['uid'],intval($_GET['status']),true,99999);

			foreach($order_list['order_list'] as &$order){
				$order['order_url'] = U('My/gift_order',array('order_id'=>$order['order_id']));
			}

			if($order_list['order_list']){
				exit(json_encode(array('status'=>1,'order_list'=>$order_list['order_list'])));
			}else{
				exit(json_encode(array('status'=>0,'order_list'=>$order_list['order_list'])));
			}
		}else{
			$this->error_tips('访问页面有误！');
		}
	}

	public function gift_order(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}

		$order_id = $_GET['order_id'] + 0;
		if(!$order_id){
			$this->error_tips('传递参数有误！');
		}

		$database_gift_order = D('Gift_order');
		$now_order = $database_gift_order->get_order_detail_by_id($this->user_session['uid'],$order_id,true);

		$now_order['express_name'] = D('Express')->get_express($now_order['express_type']);

		$this->assign('now_order',$now_order);
		$this->display();
	}

	//积分商城订单退款
	public function gift_order_refund(){
		$id = intval($_GET['id']);
		$database_gift_order = D('Gift_order');
		$now_order = $database_gift_order->get_order_detail_by_id($this->user_session['uid'],$id);

		$now_order['express_name'] = D('Express')->get_express($now_order['express_type']);
		$this->assign('now_order',$now_order);
		//dump($now_order);
		$this->display();
	}

	public function gift_order_check_refund(){
		if (empty($this->user_session)) {
			$this->error_tips(L_('请先进行登录！'));
		}
		$order_id = intval($_GET['order_id']);
		$database_gift_order = D('Gift_order');
		$now_order = $database_gift_order->get_order_detail_by_id($this->user_session['uid'],$order_id);
		if(empty($now_order)){
			$this->error_tips(L_('当前订单不存在'));
		}
		if($now_order['paid']<>1){
			$this->error_tips(L_('抱歉当前订单未支付，无法退款'));
		}
		if($now_order['status']>0){
			$this->error_tips(L_('当前订单已发货无法退款！'));
		}
		$return = $database_gift_order->refund($now_order);
		if ($return['error_code']) {
			$this->error_tips($return['msg']);
		} else {
			D("System_order")->save_order_status('gift',$order_id,5);
			$this->success_tips($return['msg'],U('gift_order',array('order_id'=>$order_id)));
		}
	}


	/*团购收藏*/
	public function group_collect(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}

		$this->assign(D('Group')->wap_get_group_collect_list($this->user_session['uid']));

		$this->display();
	}

	public function ajax_group_collect(){
		if(empty($this->user_session)){
			$this->error('请先进行登录！');
		}

		$data_user_collect['type'] = 'group';
		$data_user_collect['group_id'] = $_POST['id'];
		$data_user_collect['uid'] = $this->user_session['uid'];

		if($_POST['action'] == 'add'){
			if(D('Merchant_user_relation')->get_group_collect($this->user_session['uid'],$_POST['id'])){
				$this->error('已收藏过');
			}
			if(M('Merchant_user_relation')->data($data_user_collect)->add()){
				if($_POST['type'] == 'group_detail'){
					$condition_group['group_id'] = $_POST['id'];
					D('Group')->where($condition_group)->setInc('collect_count');
				}
				$this->success('收藏成功');
			}else{
				$this->error('收藏失败！请重试');
			}
		}else if($_POST['action'] == 'del'){
			if(M('Merchant_user_relation')->where($data_user_collect)->delete()){
				if($_POST['type'] == 'group_detail'){
					$condition_group['group_id'] = $_POST['id'];
					D('Group')->where($condition_group)->setDec('collect_count');
				}
				$this->success('取消收藏成功');
			}else{
				$this->error('取消收藏失败！请重试');
			}
		}else{
			$this->error('您要做什么？');
		}

	}

	//预约收藏
	public function appoint_collect(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}

		$collection_list = D('')->table(array(C('DB_PREFIX').'appoint'=>'a', C('DB_PREFIX').'merchant_user_relation'=>'ac'))->where("ac.uid= '".$this->user_session['uid']."' AND ac.appoint_id = a.appoint_id")->select();

		foreach ($collection_list as $k => $v) {
	        $collection_list[$k]['pic'] = str_replace(',', '/', $v['pic']);
	    }

        $this->assign('collection_list',$collection_list);
		$this->display();
	}

	public function ajax_appoint_collect(){
		$listRows = 8;
		$firstRow = $_POST['page'] * $listRows;
		$collection_list = D('')->table(array(C('DB_PREFIX').'appoint'=>'a', C('DB_PREFIX').'merchant_user_relation'=>'ac'))->where("ac.uid= '".$this->user_session['uid']."' AND ac.appoint_id = a.appoint_id")->limit($firstRow,$listRows)->select();

		foreach ($collection_list as $k => $v) {
	        $collection_list[$k]['pic'] = str_replace(',', '/', $v['pic']);
	    }
		$html = '';
	    foreach ($collection_list as $key => $value) {
	    	$html.='<dd>
        				<a href="'.$value['url'].'" class="react">
							<div class="dealcard">
						        <div class="dealcard-img imgbox">
						        	<img src="'.file_domain().'/upload/appoint/'.$value['pic'].'" style="width:100%;height:100%;">
						        </div>
							    <div class="dealcard-block-right">
										<div class="dealcard-brand single-line">'.$value['appoint_name'].'</div>
										<div class="title text-block">'.$value['appoint_name'].'</div>
							        <div class="price">
							            <strong>'.$value['appoint_price'].'</strong>
							            <span class="strong-color">'.C('config.Currency_txt').'</span>
							            <span class="line-right">已售'.$value['appoint_sum'].'</span>
							        </div>
							    </div>
							</div>
       					</a>
       				</dd>';
	    }

	    exit(json_encode(array('error'=>1,'msg'=>'加载成功','html'=>$html)));
	}

	/*预约详情*/
	public function appoint_order(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$now_order = D('Appoint_order')->get_order_detail_by_id($this->user_session['uid'],$_GET['order_id'],true);

		$now_appoint = D('Appoint')->get_appoint_by_appointId($now_order['appoint_id'], 'hits-setInc');

        $appoint_base_url = D('Appoint')->appoint_base_url;
        $url = C('config.site_url').$appoint_base_url.'pages/appoint/goods/submitDetail?orderid='.$_GET['order_id'].'&appoint_id='.$now_order['appoint_id'];
        redirect($url);
        
		if(empty($now_order)){
			$this->error_tips('当前订单不存在');
//			$order = D('Appoint_order')->where(array('order_id'=>$_GET['order_id']))->find();
//			if(empty($order)){
//				$this->error_tips('当前订单不存在');
//			}else{
//				$now_order = D('Appoint_order')->get_order_detail_by_id($order['uid'],$_GET['order_id'],true);
//			}
		}
		$now_order['order_type'] = 'appoint';
		$laste_order_info = D('Tmp_orderid')->get_laste_order_info($now_order['order_type'],$now_order['order_id']);
		if(!$now_order['paid'] && !empty($laste_order_info)) {
			if ($laste_order_info['pay_type']=='weixin') {
				$redirctUrl = C('config.site_url') . '/wap.php?g=Wap&c=Pay&a=weixin_back&order_type='.$now_order['order_type'].'&order_id=' . $laste_order_info['orderid'];
				file_get_contents($redirctUrl);
				$now_order = D('Appoint_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']),true);
			}
		}
		if(empty($now_order)){
			$this->error_tips('当前订单不存在');
		}
        $this->assign('appoint_rule_type',$this->config['appoint_rule_type']);
		$now_supply = D('Appoint_supply')->where(array('order_id'=>intval($_GET['order_id']),'status'=>3))->find();
		$this->assign('now_appoint',$now_appoint);
		$this->assign('now_supply',$now_supply);
		$this->assign('now_order',$now_order);
		$appoint_cate = D('Appoint_category')->where(array('cat_id'=>$now_order['cat_id']))->find();
		$appoint_cate['list_pic'] = C('config.site_url').'/upload/system/'.$appoint_cate['cat_pic'];
		$this->assign('appoint_cate',$appoint_cate);
		$this->display();
	}

	/*团购详情*/
	public function group_order(){

        //跳转到新的团购订单列表 - 不做处理直接跳转
        redirect(U('Groupnew/order_list'));
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		
		$otherrm = isset($_GET['otherrm']) ? intval($_GET['otherrm']) : 0;
		$otherrm && $_SESSION['otherwc'] = null;
		$now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']),true);

		$now_order['order_type'] = 'group';
		$laste_order_info=D('Tmp_orderid')->get_laste_order_info($now_order['order_type'],$now_order['order_id']);
		if(!$now_order['paid'] && !empty($laste_order_info)) {
			if ($laste_order_info['pay_type']=='weixin') {
				$redirctUrl = C('config.site_url') . '/wap.php?g=Wap&c=Pay&a=weixin_back&order_type='.$now_order['order_type'].'&order_id=' . $laste_order_info['orderid'];
				file_get_contents($redirctUrl);
				$now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'], intval($_GET['order_id']), true);
			}
		}
		$now_group = D('Group')->where(array('group_id'=>$now_order['group_id']))->find();

		$database_merchant = D('Merchant');
		$now_merchant = $database_merchant->get_info($now_group['mer_id']);
		$now_group['merchant_name'] = $now_merchant['name'];

		if(empty($now_order)){
			$this->error_tips('当前订单不存在');
		}
		if(empty($now_order['paid'])){
			$now_order['status_txt'] = '未付款';
		}else if(empty($now_order['third_id']) && $now_order['pay_type'] == 'offline'){
			$now_order['status_txt'] = '线下未付款';
		}else if(empty($now_order['status'])){
			if($now_order['tuan_type'] != 2){
				$now_order['status_txt'] = '未消费';
			}else{
				$now_order['status_txt'] = '未发货';
			}
		}else if($now_order['status'] == '1'){
			$now_order['status_txt'] = '待评价';
		}else if($now_order['status'] == '2'){
			$now_order['status_txt'] = '已完成';
		}else if($now_order['status'] == '3'){
			$now_order['status_txt'] = '已退款';
			$now_order['group_pass_txt'] = '退款订单无法查看';
		}else if($now_order['status'] == '7'){
            $now_order['status_txt'] = '未消费';
        }


		$uid = $this->user_session['uid'];
		$group_share_num = D('Group_share_relation')->get_share_num($uid,$now_order['order_id']);
		$is_shared = D('Group_share_relation')->check_share($uid,$now_order['order_id']);
		$pic = explode(';',$now_group['pic']);
		foreach($pic as &$v){
			$v = preg_replace('/,/','/',$v);
		}
		$now_group['pic'] = $pic;
		if($now_group['pin_num']>0 && $now_order['single_buy']==0 && $now_order['status']<3 && $now_order['paid']){
			$my_group_join = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
			if(empty($my_group_join)&&$now_order['paid']==1){
				$this->error_tips('当前订单出错');
			}
			$buyer = D('Group_start')->get_buyerer_by_order_id($now_order['order_id']);
			$robot_list = M('Robot_list')->where(array('mer_id'=>$now_order['mer_id']))->getField('id,robot_name,avatar');

            if ($buyer) {
                $fid = reset($buyer)['fid'];
                $group_start_info = D('Group_start')->get_group_start_user_by_gid($fid);
            }
			foreach ($buyer as &$v) {
				if($v['type']==1||$v['uid']!=$now_order['uid']){
					if(empty($v['pay_time'])){
						$v['pay_time'] = $group_start_info['last_time'];
					}
					$tmp_name = $v['nickname'];
					if($v['type']==1){
						$tmp_name = $robot_list[$v['uid']]['robot_name'];
						$v['avatar'] = $robot_list[$v['uid']]['avatar'];
					}
					if(preg_match('/\d{3}\*{4}\d{4}/',$v['nickname'],$m)){
						$v['nickname'] = '游客';
					}
					$strlen     = mb_strlen($tmp_name, 'utf-8');
					$firstStr     = mb_substr($tmp_name, 0, 1, 'utf-8');
					$lastStr     = mb_substr($tmp_name, -1, 1, 'utf-8');
					$v['nickname'] =  $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($tmp_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 1) ;
					//$v['avatar'] = $robot_list[$v['uid']]['avatar'];
				}
			}

			//$group_share_info = D('Group_start')->get_group_start_user_by_gid($my_group_join['id']);
			$end_time = $my_group_join['start_time'] + $now_group['pin_effective_time'] * 3600;
			$effective_time = $end_time - $_SERVER['REQUEST_TIME'];
			$efftime['h'] = floor($effective_time / 3600);
			$efftime['m'] = floor(($effective_time - $efftime['h'] * 3600) / 60);
			$efftime['s'] = $effective_time - $efftime['h'] * 3600 - $efftime['m'] * 60;

			if ($effective_time > 0) {
				$this->assign('effective_time', $efftime);
			}
			$end_time = $my_group_join['start_time']+$now_group['pin_effective_time']*3600;
			$effective_time= $end_time-$_SERVER['REQUEST_TIME'];
			if($effective_time<=0 && $my_group_join['status']==0){
				D('Group_start')->timeout($now_order['order_id']);
				$my_group_join['status'] = 2;
			}
			//$this->assign('effective_time',$effective_time);
			$this->assign('buyer',$buyer);
			$this->assign('my_group_join',$my_group_join);
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
				$share_user = D('Group_share_relation')->get_share_user($this->user_session['uid'], $now_order['order_id']);
				$this->assign('share_user', $share_user);
			}
		}
		//$now_order['coupon_price'] = D('Group_order')->get_coupon_info($now_order['order_id']);
		//$has_pay = $now_order['wx_cheap']+$now_order['merchant_balance']+$now_order['balance_pay']+$now_order['score_deducte']+$now_order['coupon_price']+$now_order['payment_money'];

        $pass_num = $now_order['pass_num']>1?intval($now_order['pass_num']):1; // 一份团购 对应生成核销券 份数
        $total_pass_num = $now_order['num']*$pass_num; // 改订单产生的总 核销券 份数
		if($now_order['pass_array']){
			$pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
			$consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
            $unconsume_pass_num = $total_pass_num-$consume_num;
			$this->assign('pass_array',$pass_array);
		}elseif($now_order['tuan_type']==2){
            $unconsume_pass_num = $total_pass_num;
        }else{
            $unconsume_pass_num = 1;
        }
		if($now_order['status']==6 || $now_order['status']==3){
			if($total_pass_num!=$unconsume_pass_num){
				$refund_money = $now_order['refund_money'];
			}else{
				$refund_money = $now_order['merchant_balance']+$now_order['balance_pay']+$now_order['payment_money'];
			}
			$now_order['refund_total'] = $refund_money;
		}else{
			$now_order['refund_total'] = $now_order['balance_pay']+$now_order['payment_money']+$now_order['merchant_balance'];
		}
        $pass_info = array(
            'total_pass_num' => $total_pass_num,
            'unconsume_pass_num' => $unconsume_pass_num,
        );
        $this->assign('pass_info',$pass_info);

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
				$this->assign('trade_hotel_info',$trade_hotel_info);
			}
		}

		// 获取填写的用户自定义字段信息

        $write_info = unserialize($now_order['write_field']);
		if ($write_info) {
            $write_list = array();
            foreach($write_info as $key=>$val){
                if(!empty($write_info[$key]['value'])){
                    $write_list[$key]['name'] = $val['name'];
                    $write_list[$key]['value'] = $val['value'];
                    $write_list[$key]['type'] = $val['type'];
                    if($write_info[$key]['type'] == 2){
                        $write_list[$key]['long'] = $val['long'];
                        $write_list[$key]['lat'] = $val['lat'];
                        $write_list[$key]['address'] = $val['address'];
                        $write_list[$key]['value'] = $val['value'].' '.$val['value-desc'];
                    }
                }
            }
            $this->assign('write_list', $write_list);
        }
        // 如果存在 获取对应规格
        if ($now_order['specifications_id']) {
            $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->find();
            if ($specifications_info) {
                $now_order['specifications_info'] = $specifications_info;
                $now_order['s_name'] = $now_order['s_name'] . '('.$specifications_info['specifications_name'].')';
            } else {
                $now_order['specifications_info'] = array();
            }
        }
        // 退款详情
        if ($now_order['refund_detail']) {
            $refund_detail = unserialize($now_order['refund_detail']);
            $this->assign($refund_detail);
        }

		$lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
		$this->assign('lat',$lng_lat['lat']);
		$this->assign('lng',$lng_lat['long']);
		$this->assign('now_order',$now_order);
		$this->assign('now_group',$now_group);
		$this->assign('group_share_num',$group_share_num);
		$this->assign('is_shared',$is_shared);
		$this->assign('now_merchant',$now_merchant);
		
		if($now_group['pin_num']>0){
			$share = new WechatShare($this->config,$_SESSION['openid']);
			$this->sign_data = $share->get_wx_config();
			$this->assign('sign_data',$this->sign_data);
			$this->display('pin_group_order');
		}else{
			$this->display();
		}
	}

	/*团购详情*/
	public function meal_order_refund(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}

		$orderid = intval($_GET['orderid']);
		$store_id = intval($_GET['store_id']);
		$now_order = M("Meal_order")->where(array('order_id' => $orderid, 'mer_id' => $this->mer_id, 'store_id' => $store_id))->find();
		if (empty($now_order)) {
			$this->error_tips('当前订单不存在');
		}
		if ($now_order['is_confirm']) {
			$this->error_tips('当前订单店员正在处理中，不能退款或取消');
		}
		if(empty($now_order['paid'])){
			$this->error_tips('当前订单还未付款！');
		}
		if ($now_order['meal_type']) {
			if ($now_order['status'] > 0 && $now_order['status'] < 3) {
				$this->error_tips('订单必须是未消费状态才能取消！', U('Takeout/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
			} elseif ($now_order['status'] > 2) {
				$this->redirect(U('Takeout/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
			}
		} else {
			if ($now_order['status'] > 0 && $now_order['status'] < 3) {
				$this->error_tips('订单必须是未消费状态才能取消！', U('Food/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
			} elseif ($now_order['status'] > 2) {
				$this->redirect(U('Food/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
			}
		}

		$now_order['price'] = $now_order['pay_money'];
		$now_order['pay_type_txt'] = D('Pay')->get_pay_name($now_order['pay_type'],$now_order['is_mobile_pay']);
		$this->assign('now_order',$now_order);
		$this->display();
	}

	//取消订单
	public function meal_order_check_refund(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$orderid = intval($_GET['orderid']);
		$store_id = intval($_GET['store_id']);
		$now_order = M("Meal_order")->where(array('order_id' => $orderid, 'mer_id' => $this->mer_id, 'store_id' => $store_id))->find();
		if(empty($now_order)){
			$this->error_tips('当前订单不存在');
		}
		if ($now_order['is_confirm']) {
			$this->error_tips('当前订单店员正在处理中，不能退款或取消');
		}
		if(empty($now_order['paid'])){
			$this->error_tips('当前订单还未付款！');
		}
		if ($now_order['meal_type']) {
			if ($now_order['status'] > 0 && $now_order['status'] < 3) {
				$this->error_tips('订单必须是未消费状态才能取消！', U('Takeout/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
			} elseif ($now_order['status'] > 2) {
				$this->redirect(U('Takeout/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
			}
		} else {
			if ($now_order['status'] > 0 && $now_order['status'] < 3) {
				$this->error_tips('订单必须是未消费状态才能取消！', U('Food/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
			} elseif ($now_order['status'] > 2) {
				$this->redirect(U('Food/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
			}
		}

// 		$now_order['price'] = $now_order['pay_money'];
// 		$data_meal_order['pay_money'] = 0;
// 		$data_meal_order['paid'] = 0;
		$my_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
		//在线付款退款
		if($now_order['pay_type'] == 'offline'){
			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize(array('refund_time'=>time()));
			$data_meal_order['status'] = 3;
			if(D('Meal_order')->data($data_meal_order)->save()){
			    //退款打印
			    $printHaddle = new PrintHaddle();
			    $printHaddle->printit($now_order['order_id'], 'meal_order', 3);

				$mer_store = D('Merchant_store')->where(array('mer_id' => $this->mer_id, 'store_id' => $store_id))->find();
				$sms_data = array('mer_id' => $mer_store['mer_id'], 'store_id' => $mer_store['store_id'], 'type' => 'food');
				if ($this->config['sms_cancel_order'] == 1 || $this->config['sms_cancel_order'] == 3) {
					$sms_data['uid'] = $now_order['uid'];
					$sms_data['mobile'] = $now_order['phone'] ? $now_order['phone'] : $my_user['phone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = '您在 ' . $mer_store['name'] . '店中下的订单(订单号：' . $orderid . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
					Sms::sendSms($sms_data);
				}
				if ($this->config['sms_cancel_order'] == 2 || $this->config['sms_cancel_order'] == 3) {
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $mer_store['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = '顾客' . $now_order['name'] . '的预定订单(订单号：' . $orderid . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
					Sms::sendSms($sms_data);
				}
				//如果使用了优惠券
				if($now_order['card_id']){
					$result = D('Member_card_coupon')->add_card($now_order['card_id'],$now_order['mer_id'],$now_order['uid']);

					$param = array('refund_time' => time());
					if($result['error_code']){
						$param['err_msg'] = $result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}

					$data_meal_order['order_id'] = $now_order['order_id'];
					$data_meal_order['refund_detail'] = serialize($param);
					$result['error_code'] || $data_meal_order['status'] = 3;
					D('Meal_order')->data($data_meal_order)->save();
					if ($result['error_code']) {
						$this->error_tips($result['msg']);
					}
					$go_refund_param['msg'] = $result['msg'];
				}

				//如果使用了积分 2016-1-15
				if ($now_order['score_used_count']!=='0') {
					$order_info=unserialize($now_order['info']);
					$order_name=$order_info[0]['name']."*".$order_info[0]['num'];
					$result = D('User')->add_score($now_order['uid'],$now_order['score_used_count'],'退款 '.$order_name.' '.$this->config['score_name'].'回滚');
					$param = array('refund_time' => time());
					if($result['error_code']){
						$param['err_msg'] = $result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}
					$data_meal_order['order_id'] = $now_order['order_id'];
					$data_meal_order['refund_detail'] = serialize($param);
					$result['error_code'] || $data_meal_order['status'] = 3;
					D('Meal_order')->data($data_meal_order)->save();
					if ($result['error_code']) {
						$this->error_tips($result['msg']);
					}
					$go_refund_param['msg'] = $result['msg'];
				}

				//平台余额退款
				if($now_order['balance_pay'] != '0.00'){
					$add_result = D('User')->add_money($now_order['uid'], $now_order['balance_pay'], L_('退款 X1 增加余额',['X1' => $now_order['order_name']]));

					$param = array('refund_time' => time());
					if($result['error_code']){
						$param['err_msg'] = $result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}

					$data_meal_order['order_id'] = $now_order['order_id'];
					$data_meal_order['refund_detail'] = serialize($param);
					$result['error_code'] || $data_meal_order['status'] = 3;
					D('Meal_order')->data($data_meal_order)->save();
					if ($result['error_code']) {
						$this->error_tips($result['msg']);
					}
					$go_refund_param['msg'] = L_('平台余额退款成功');
				}
				//商家会员卡余额退款
				if($now_order['merchant_balance'] != '0.00'){
					//$result = D('Member_card')->add_card($now_order['uid'],$now_order['mer_id'],$now_order['merchant_balance'],'退款 '.$now_order['order_name'].' 增加余额');
					$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['merchant_balance'],$now_order['card_give_money'],0, L_('退款 X1 增加余额', ['X1' => $now_order['order_name']]), L_('退款 X1 增加赠送余额', ['X1' => $now_order['order_name']]));

					$param = array('refund_time' => time());
					if($result['error_code']){
						$param['err_msg'] = $result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}

					$data_meal_order['order_id'] = $now_order['order_id'];
					$data_meal_order['refund_detail'] = serialize($param);
					$result['error_code'] || $data_meal_order['status'] = 3;
					D('Meal_order')->data($data_meal_order)->save();
					if ($result['error_code']) {
						$this->error_tips($result['msg']);
					}
					$go_refund_param['msg'] = $result['msg'];
				}
				if ($now_order['meal_type']) {
					$this->success_tips(L_('您使用的是线下支付！订单状态已修改为已退款。'), U('Takeout/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
				} else {
					$this->success_tips(L_('您使用的是线下支付！订单状态已修改为已退款。'), U('Food/order_detail',array('order_id'=>$now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
				}
				exit;
			}else{
				$this->error_tips(L_('取消订单失败！请重试。'));
			}
		}
		if($now_order['payment_money'] != '0.00'){
			if($now_order['is_own']){
				$pay_method = array();
				$merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$now_order['mer_id']))->find();
				foreach($merchant_ownpay as $ownKey=>$ownValue){
					$ownValueArr = unserialize($ownValue);
					if($ownValueArr['open']){
						$ownValueArr['is_own'] = true;
						$pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
					}
				}
				$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
				if ($now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
					$pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
					$pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
					$pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
					$pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
					$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
					$pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
					$pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
					$pay_method['weixin']['config']['is_own'] = 2 ;
				}
			}else{
				$pay_method = D('Config')->get_pay_method(0,0,1);
			}

			if(empty($pay_method)){
				$this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
			}
			$pay_class_name = ucfirst($now_order['pay_type']);
            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                $pay_class_name = 'Hqpay';
                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
            }
			if(empty($pay_method[$now_order['pay_type']])){
				$this->error_tips(L_('您选择的支付方式不存在，请更新支付方式！'));
			}

			$import_result = import('@.ORG.pay.'.$pay_class_name);
			if(empty($import_result)){
				$this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
			}

			if ($now_order['meal_type'] == 1) {
				$now_order['order_type'] = 'takeout';
			} elseif ($now_order['meal_type'] == 2) {
				$now_order['order_type'] = 'foodPad';
			} else {
				$now_order['order_type'] = 'food';
			}
			$order_id = $now_order['order_id'];
			$now_order['order_id'] = $now_order['orderid'];

			if($now_order['is_mobile_pay']==3){
				$pay_method[$now_order['pay_type']]['config'] =array(
						'pay_weixin_appid'=>$this->config['pay_wxapp_appid'],
						'pay_weixin_key'=>$this->config['pay_wxapp_key'],
						'pay_weixin_mchid'=>$this->config['pay_wxapp_mchid'],
						'pay_weixin_appsecret'=>$this->config['pay_wxapp_appsecret'],
				);
				if($now_order['is_own']){
					$now_merchant_wxapp_ownpay = M('Weixin_app_bind')->where(array('other_id'=>$now_order['mer_id']))->find();
					$pay_method[$now_order['pay_type']]['config'] = array(
							'pay_weixin_appid' => $now_merchant_wxapp_ownpay['appid'],
							'pay_weixin_key' => $now_merchant_wxapp_ownpay['wxpay_key'],
							'pay_weixin_mchid' => $now_merchant_wxapp_ownpay['wxpay_merid'],
							'pay_weixin_appsecret' =>$now_merchant_wxapp_ownpay['appsecret'],
					);
					C('config.pay_weixin_client_cert', $now_merchant_wxapp_ownpay['public_key']);
					C('config.pay_weixin_client_key', $now_merchant_wxapp_ownpay['private_key']);
				}
			}

			$pay_class = new $pay_class_name($now_order,$now_order['payment_money'],$now_order['pay_type'],$pay_method[$now_order['pay_type']]['config'],$this->user_session,1);
			$go_refund_param = $pay_class->refund();

			$now_order['order_id'] = $orderid;
			$data_meal_order['order_id'] = $orderid;
			$data_meal_order['refund_detail'] = serialize($go_refund_param['refund_param']);
			if(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok'){
				$data_meal_order['status'] = 3;
			}

			D('Meal_order')->data($data_meal_order)->save();
			if($data_meal_order['status'] != 3){
				$this->error_tips($go_refund_param['msg']);
			}
		}
		//如果使用了优惠券
		if($now_order['card_id']){
			$result = D('Member_card_coupon')->add_card($now_order['card_id'],$now_order['mer_id'],$now_order['uid']);

			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			if ($result['error_code']) {
				$this->error_tips($result['msg']);
			}
			$go_refund_param['msg'] = $result['msg'];
		}


		//如果使用了积分 2016-1-15
		if ($now_order['score_used_count']!=='0') {
			$order_info=unserialize($now_order['info']);
			$order_name=$order_info[0]['name']."*".$order_info[0]['num'];
			$result = D('User')->add_score($now_order['uid'],$now_order['score_used_count'],'退款 '.$order_name.' '.$this->config['score_name'].'回滚');
			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			if ($result['error_code']) {
				$this->error_tips($result['msg']);
			}
			$go_refund_param['msg'] .= $result['msg'];
		}

		//平台余额退款
		if($now_order['balance_pay'] != '0.00'){
			$add_result = D('User')->add_money($now_order['uid'],$now_order['balance_pay'], L_('退款 X1 增加余额',['X1' => $now_order['order_name']]));

			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			if ($result['error_code']) {
				$this->error_tips($result['msg']);
			}
			$go_refund_param['msg'] = '平台余额退款成功';
		}
		//商家会员卡余额退款
		if($now_order['merchant_balance'] != '0.00'){
			$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['merchant_balance'],$now_order['card_give_money'],0, L_('退款 X1 增加余额',['X1' => $now_order['order_name']]), L_('退款 X1 增加赠送余额',['X1' => $now_order['order_name']]));
			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			if ($result['error_code']) {
				$this->error_tips($result['msg']);
			}
			$go_refund_param['msg'] = $result['msg'];
		}
		if(empty($now_order['pay_type'])){
			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			$go_refund_param['msg'] = L_('取消订单成功');
		}

		//退款时销量回滚
		if ($now_order['paid'] == 1 && date('m', $now_order['dateline']) == date('m')) {
			foreach (unserialize($now_order['info']) as $menu) {
				D('Meal')->where(array('meal_id' => $menu['id'], 'sell_count' => array('gt', $menu['num'])))->setDec('sell_count', $menu['num']);
			}
		}
		D("Merchant_store_meal")->where(array('store_id' => $now_order['store_id'], 'sale_count' => array('gt', 0)))->setDec('sale_count', 1);

		//退款打印
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($now_order['order_id'], 'meal_order', 3);

		$mer_store = D('Merchant_store')->where(array('mer_id' => $this->mer_id, 'store_id' => $store_id))->find();
		$sms_data = array('mer_id' => $mer_store['mer_id'], 'store_id' => $mer_store['store_id'], 'type' => 'food');
		if ($this->config['sms_cancel_order'] == 1 || $this->config['sms_cancel_order'] == 3) {
			$sms_data['uid'] = $now_order['uid'];
			$sms_data['mobile'] = $now_order['phone'] ? $now_order['phone'] : $my_user['phone'];
			$sms_data['sendto'] = 'user';
			$sms_data['content'] = '您在 ' . $mer_store['name'] . '店中下的订单(订单号：' . $orderid . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_cancel_order'] == 2 || $this->config['sms_cancel_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $mer_store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = '顾客' . $now_order['name'] . '的预定订单(订单号：' . $orderid . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
			Sms::sendSms($sms_data);
		}

		if ($now_order['meal_type'] == 1) {
			$this->success_tips($go_refund_param['msg'], U('Takeout/order_detail',array('order_id'=>$orderid, 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
		} else {
			$this->success_tips($go_refund_param['msg'], U('Food/order_detail',array('order_id'=>$orderid, 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
		}
	}

	/*团购详情*/
	public function group_order_refund(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']),true);
		$now_group = D('Group')->where(array('group_id'=>$now_order['group_id']))->find();

		if(empty($now_order)){
			$this->error_tips('当前订单不存在');
		}
		if($now_order['paid'] != 1){
			$this->error_tips('当前订单还未付款！');
		}
		if ($now_order['status'] > 0 && $now_order['status'] < 5) {
			$this->error_tips('订单必须是未消费状态才能取消！',U('My/group_order',array('order_id'=>$now_order['order_id'])));
		} elseif ($now_order['status'] > 2 && $now_order['status'] != 5) {
			$this->redirect(U('My/group_order',array('order_id'=>$now_order['order_id'])));
		}
        if ($now_group['no_refund'] == 1) {
            $this->error_tips('当前'.$this->config['group_alias_name'].'不支持退款');
        }
        // 该时限只针对 团购券
        if ($now_group['no_refund'] == 0 && $now_group['before_effective_time'] > 0 && $now_group['tuan_type'] == 0) {
            // 计算时间是否超出
            $now_time = time();
            if ($now_group['effective_time_type'] == 1) {
                $time = floatval($now_group['before_effective_time']) * 24 * 3600;
            } else {
                $time = floatval($now_group['before_effective_time']) * 3600;
            }
            // 计算 退款时效
            $time_diff = floatval($now_group['deadline_time']) - $time;
            if (intval($time_diff) < intval($now_time)) {
                $this->error_tips('超出'.$this->config['group_alias_name'].'退款有效期，当前不可取消订单！');
            }
        }

        // 新版团购退款期限验证
    	if($now_group['effective_type'] == 1){// 团购有效期 购买后多少天
            if($now_order['pay_time']){
                $now_group['deadline_time'] = $now_order['pay_time'] + $now_group['deadline_time']*86400;
            }else{
                $now_group['deadline_time'] = $now_order['add_time'] + $now_group['deadline_time']*86400;
            }
        }
        
        if($now_group['cancel_type'] == 1){// 到期前几小时取消

	        if($now_group['deadline_time'] - $now_group['cancel_hours']*3600 < time()){
                $this->error_tips('超出'.$this->config['group_alias_name'].'退款有效期，当前不可取消订单！');
	        }
        }elseif($now_group['cancel_type'] == 3){ // 开场前可取消

	        if($now_group['deadline_time']  < time()){
                $this->error_tips('超出'.$this->config['group_alias_name'].'退款有效期，当前不可取消订单！');
	        }
        }

        // 如果成团后退款手续费收取大于等于100%，返回错误
        if ($now_group['pin_num']>0 && $now_group['group_refund_fee']>0 && $now_group['group_refund_fee']>=100) {
            $this->error_tips('当前'.$this->config['group_alias_name'].'不支持退款');
        }
        // 拼团做判断
        if( $now_group['pin_num']>0&&!$now_order['single_buy']) {
            $now_group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            if (!empty($now_group_start) && $now_group_start['status']==2) {
                $this->error_tips('当前'.$this->config['group_alias_name'].'已超时，不支持退款');
            }
            if ($now_order['is_head'] && $now_group_start['status']==0) {
                $this->error_tips('当前'.$this->config['group_alias_name'].'未成团，不支持团长退款');
            }
            // 如果使用了积分且需退的金额大于0，计算退多少积分 优先退积分
            $group_refund_fee = $now_group['group_refund_fee']>0?$now_group['group_refund_fee']/100:0; // 退款所需手续费
        } else {
            $group_refund_fee = 0;
        }

        // 首先 计算出 已经使用的核销码数量和未使用的核销码数量  同步计算出需要抵扣的金额 线上支付+平台余额+商户余额+商户赠送余额+积分抵扣金额
        $pass_num = $now_order['pass_num']>1?intval($now_order['pass_num']):1; // 一份团购 对应生成核销券 份数
        $total_pass_num = $now_order['num']*$pass_num; // 改订单产生的总 核销券 份数
        $express_fee = $now_order['express_fee'] > 0 ? intval($now_order['express_fee']):0; // 运费
        $total_pay_money = $now_order['price'] * intval($now_order['num']) + $express_fee; // // 改订单实际支付的总金额 线上支付+平台余额+商户余额+商户赠送余额+积分抵扣金额+运费  直接单价*数量
        $total_pay_money = sprintf("%.2f",$total_pay_money);
        if ($now_order['card_discount']>0 && $now_order['card_discount']<10) {
            $card_discount_money = $total_pay_money - round($total_pay_money * $now_order['card_discount'] * 0.1, 2); // 优先会员卡折扣优惠金额
        } else {
            $card_discount_money = 0;
        }
		$total_pay_money = $total_pay_money - $now_order['wx_cheap']; // 去除微信端/app优惠
        $actual_total_pay_money = $total_pay_money - $now_order['card_price'] - $now_order['coupon_price'] - $card_discount_money; // 实际支付总金额   总金额-各种优惠金额（商家优惠券金额+平台优惠券金额+	微信优惠+会员卡折扣优惠金额）[用户等级优惠在单价上已经计算过了，不予考虑]

		//计算下订单记录的金额
		$total_order_pay = $now_order['merchant_balance'] + $now_order['balance_pay'] + $now_order['payment_money'] + $now_order['score_deducte'];
		if ($total_order_pay>$actual_total_pay_money) {
			// 兼容原来旧的 计算优惠顺序不同的金额
			$actual_total_pay_money = $total_order_pay;
		}
		$actual_total_pay_money = sprintf("%.2f",$actual_total_pay_money);
        // 计算没分核销码的单价
        $pass_price = sprintf("%.4f",$actual_total_pay_money/$total_pass_num);
        if($now_order['pass_array']){
            $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
            $unconsume_pass_num = $total_pass_num-$consume_num;
        }elseif($now_order['tuan_type']==2){
            $unconsume_pass_num = $total_pass_num;
        }else{
            $unconsume_pass_num = 1;
        }
        $this->assign('unconsume_pass_num',$unconsume_pass_num);
        $need_refund_money = $unconsume_pass_num * $pass_price; // 未使用的核销券 需要对应退款的总金额
        $need_refund_money = sprintf("%.2f",$need_refund_money);
        $refund_money_total = $need_refund_money; // 总需要退款金额


        // 团购退款手续总费用
        $refund_fee_total = $need_refund_money*$group_refund_fee; // 团购退款手续总费用 = 总需要退款金额*手续费比率
        $refund_fee_total = sprintf("%.2f",$refund_fee_total);
        $refund_score = 0; // 默认需要退款 积分 为 0
        $refund_score_fee = 0; // 积分退款手续费-积分数
        if ($now_order['score_used_count']>0 && $need_refund_money>0) {
            // 首先判断积分是否有剩余需要退的
            if ($need_refund_money >= $now_order['score_deducte']) {
                $need_refund_money = $need_refund_money - $now_order['score_deducte']; // 计算还需要退款金额 = 需退款金额 - 积分抵扣金额
                $refund_score = $now_order['score_deducte'] * (1 - $group_refund_fee) * ($now_order['score_used_count']/$now_order['score_deducte']); // 需要退的积分数量 = 积分抵扣金额 * (1-退款手续费) * （使用积分数/对应抵扣金额数）
                $refund_score = sprintf("%.2f",$refund_score); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_score_fee = $now_order['score_used_count'] - $refund_score; // 积分退款手续费-积分数 = 总的使用积分数-需要退的积分数
                    $refund_score_fee = sprintf("%.2f",$refund_score_fee); // 保留两位小数
                }
            } else {
                $refund_score = $need_refund_money * (1 - $group_refund_fee) * ($now_order['score_used_count']/$now_order['score_deducte']); // 计算需要退的积分 = 需退金额 * (1-退款手续费) * （使用积分数/对应抵扣金额数）
                $refund_score = sprintf("%.2f",$refund_score);
                if ($group_refund_fee>0) {
                    $refund_score_fee = sprintf("%.2f",$need_refund_money * ($now_order['score_used_count']/$now_order['score_deducte'])) - $refund_score; // 积分退款手续费-积分数 = 总的需要退的积分（未计算手续费的）-需要退的积分数
                    $refund_score_fee = sprintf("%.2f",$refund_score_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 积分就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了商户会员卡赠送金额且还需退的金额大于0 接着退 商户会员卡赠送金额
        $refund_card_give_money = 0; // 默认需要退款 商户会员卡赠送金额 为 0
        $refund_card_give_fee = 0; // 商户会员卡赠送金额 退款手续费
        if($now_order['card_give_money'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['card_give_money']) {
                $need_refund_money = $need_refund_money - $now_order['card_give_money']; // 计算还需要退款金额 = 还需要退款的金额 - 商户会员卡赠送余额
                $refund_card_give_money = $now_order['card_give_money'] * (1 - $group_refund_fee); // 需要退的商户会员卡赠送余额 = 商户会员卡赠送余额 * (1-退款手续费)
                $refund_card_give_money = sprintf("%.2f",$refund_card_give_money);// 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_card_give_fee = $now_order['card_give_money'] - $refund_card_give_money; // 商户会员卡赠送金额手续费 = 总的商户会员卡赠送金额-需要退的商户会员卡赠送金额
                    $refund_card_give_fee = sprintf("%.2f",$refund_card_give_fee); // 保留两位小数
                }
            } else {
                $refund_card_give_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的商户会员卡赠送余额 = 需退金额* (1-退款手续费)
                $refund_card_give_money = sprintf("%.2f",$refund_card_give_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_card_give_fee = $need_refund_money - $refund_card_give_money; // 商户会员卡赠送金额手续费 = 需要退的金额-需要退的商户会员卡赠送金额
                    $refund_card_give_fee = sprintf("%.2f",$refund_card_give_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 商户会员卡赠送余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了商户会员卡金额且还需退的金额大于0 接着退 商户会员卡金额
        $refund_merchant_balance = 0; // 默认需要退款 商户会员卡金额 为 0
        $refund_merchant_balance_fee = 0; // 商户会员卡赠送金额 退款手续费
        if($now_order['merchant_balance'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['merchant_balance']) {
                $need_refund_money = $need_refund_money - $now_order['merchant_balance']; // 计算还需要退款金额 = 还需要退款的金额 - 商户会员卡金额
                $refund_merchant_balance = $now_order['merchant_balance'] * (1 - $group_refund_fee); // 需要退的商户会员卡金额= 需要退的商户会员卡金额 * (1-退款手续费)
                $refund_merchant_balance = sprintf("%.2f",$refund_merchant_balance); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_merchant_balance_fee = $now_order['merchant_balance'] - $refund_merchant_balance; // 商户会员卡金额手续费 = 需要退的金额-需要退的商户会员卡金额
                    $refund_merchant_balance_fee = sprintf("%.2f",$refund_merchant_balance_fee); // 保留两位小数
                }
            } else {
                $refund_merchant_balance = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的商户会员卡金额 = 需退金额 * (1-退款手续费)
                $refund_merchant_balance = sprintf("%.2f",$refund_merchant_balance); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_merchant_balance_fee = $need_refund_money - $refund_merchant_balance; // 商户会员卡赠送金额手续费 = 需要退的金额-需要退的商户会员卡赠送金额
                    $refund_merchant_balance_fee = sprintf("%.2f",$refund_merchant_balance_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 商户会员卡金额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了平台余额 且还需退的金额大于0 接着退 平台余额
        $refund_balance_pay = 0; // 默认需要退款 平台余额 为 0
        $refund_balance_pay_fee = 0; // 平台余额 退款手续费
        if($now_order['balance_pay'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['balance_pay']) {
                $need_refund_money = $need_refund_money - $now_order['balance_pay']; // 计算还需要退款金额 = 还需要退款的金额 - 平台余额
                $refund_balance_pay = $now_order['balance_pay'] * (1 - $group_refund_fee); // 需要退的平台余额 = 需要退的平台余额 * (1-退款手续费)
                $refund_balance_pay = sprintf("%.2f",$refund_balance_pay); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_balance_pay_fee = $now_order['balance_pay'] - $refund_balance_pay; // 平台余额 = 需要退的金额-需要退的平台余额
                    $refund_balance_pay_fee = sprintf("%.2f",$refund_balance_pay_fee); // 保留两位小数
                }
            } else {
                $refund_balance_pay = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的平台余额 = 需退金额 * (1-退款手续费)
                $refund_balance_pay = sprintf("%.2f",$refund_balance_pay); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_balance_pay_fee = $need_refund_money - $refund_balance_pay; // 平台余额手续费 = 需要退的金额-需要退的平台余额
                    $refund_balance_pay_fee = sprintf("%.2f",$refund_balance_pay_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }

        // 如果使用了平台E卡支付 且还需退的金额大于0 接着退 平台E卡金额
		$refund_ecard_pay = 0; // 默认需要退款 平台E卡金额 为 0
		$refund_ecard_pay_fee = 0; // 平台E卡 退款手续费
		if($now_order['use_ecard_price'] > 0 && $need_refund_money>0){
		    $need_refund_money = sprintf("%.2f",$need_refund_money);
		    if ($need_refund_money >= $now_order['use_ecard_price']) {
		        $need_refund_money = $need_refund_money - $now_order['use_ecard_price']; // 计算还需要退款金额 = 还需要退款的金额 - 平台E卡余额
		        $refund_ecard_pay = $now_order['use_ecard_price'] * (1 - $group_refund_fee); // 需要退的平台E卡余额 = 需要退的平台E卡余额 * (1-退款手续费)
		        $refund_ecard_pay = sprintf("%.2f",$refund_ecard_pay); // 保留两位小数
		        if ($group_refund_fee>0) {
		            $refund_ecard_pay_fee = $now_order['use_ecard_price'] - $refund_ecard_pay; // 平台E卡余额 = 需要退的金额-需要退的平台余额
		            $refund_ecard_pay_fee = sprintf("%.2f",$refund_ecard_pay_fee); // 保留两位小数
		        }
		    } else {
		        $refund_ecard_pay = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的平台余额 = 需退金额 * (1-退款手续费)
		        $refund_ecard_pay = sprintf("%.2f",$refund_ecard_pay); // 保留两位小数
		        if ($group_refund_fee>0) {
		            $refund_ecard_pay_fee = $need_refund_money - $refund_ecard_pay; // 平台余额手续费 = 需要退的金额-需要退的平台余额
		            $refund_ecard_pay_fee = sprintf("%.2f",$refund_ecard_pay_fee); // 保留两位小数
		        }
		        $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
		    }
		}

        //  如果使用了线上支付 且还需退的金额大于0 接着退 线上支付
        $refund_payment_money = 0; // 默认需要退款 线上支付 为 0
        $refund_payment_money_fee = 0; // 线上支付 退款手续费
        if($now_order['payment_money'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['payment_money']) {
                $need_refund_money = $need_refund_money - $now_order['payment_money']; // 计算还需要退款金额 = 还需要退款的金额 - 线上支付金额
                $refund_payment_money = $now_order['payment_money'] * (1 - $group_refund_fee); // 需要退的线上支付 = 线上支付金额 * (1-退款手续费)
                $refund_payment_money = sprintf("%.2f",$refund_payment_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_payment_money_fee = $now_order['payment_money'] - $refund_payment_money; // 线上支付金额 = 需要退的金额-需要退的线上支付金额
                    $refund_payment_money_fee = sprintf("%.2f",$refund_payment_money_fee); // 保留两位小数
                }
            } else {
                $refund_payment_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的线上支付金额 = 需退金额 * (1-退款手续费)
                $refund_payment_money = sprintf("%.2f",$refund_payment_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_payment_money_fee = $need_refund_money - $refund_payment_money; // 线上支付金额手续费 = 需要退的金额-需要退的线上支付金额
                    $refund_payment_money_fee = sprintf("%.2f",$refund_payment_money_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了线下退款 且还需退的金额大于0  接着计算 线下退款金额
        $refund_offline_money = 0; // 默认需要退款 线下支付 为 0
        $refund_offline_money_fee = 0; // 线下支付 退款手续费
        if($now_order['pay_type'] == 'offline' && $need_refund_money>0){
            $offline_money = $total_pay_money-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['card_price']-$now_order['coupon_price']-$card_discount_money; // 计算线下支付金额

            if ($need_refund_money >= $offline_money) {
                $need_refund_money = $need_refund_money - $offline_money; // 计算还需要退款金额 = 还需要退款的金额 - 线下支付金额
                $refund_offline_money = $offline_money * (1 - $group_refund_fee); // 需要退的线下支付金额 = 线下支付金额 * (1-退款手续费)
                $refund_offline_money = sprintf("%.2f",$refund_offline_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_offline_money_fee = $offline_money - $refund_offline_money; // 线下支付金额 = 需要退的金额-需要退的线下支付金额
                    $refund_offline_money_fee = sprintf("%.2f",$refund_offline_money_fee); // 保留两位小数
                }
            } else {
                $need_refund_money = sprintf("%.2f",$need_refund_money);
                $refund_offline_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的线下支付 = 需退金额 * (1-退款手续费)
                $refund_offline_money = sprintf("%.2f",$refund_offline_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_offline_money_fee = $need_refund_money - $refund_offline_money; // 线下支付金额手续费 = 需要退的金额-需要退的线下支付金额
                    $refund_offline_money_fee = sprintf("%.2f",$refund_offline_money_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 所有的钱均在线下支付退完
            }


        }

		$now_order['coupon_price'] = D('Group_order')->get_coupon_info($now_order['order_id']);


        //退款金额相关信息
        $refund_detail = array(
            'refund_score' => $refund_score,
            'refund_score_fee' => $refund_score_fee,
            'refund_card_give_money' => $refund_card_give_money,
            'refund_card_give_fee' => $refund_card_give_fee,
            'refund_merchant_balance' => $refund_merchant_balance,
            'refund_merchant_balance_fee' => $refund_merchant_balance_fee,
            'refund_balance_pay' => $refund_balance_pay,
            'refund_balance_pay_fee' => $refund_balance_pay_fee,
            'refund_ecard_pay' => $refund_ecard_pay,
            'refund_ecard_pay_fee' => $refund_ecard_pay_fee,
            'refund_payment_money' => $refund_payment_money,
            'refund_payment_money_fee' => $refund_payment_money_fee,
            'refund_offline_money' => $refund_offline_money,
            'refund_offline_money_fee' => $refund_offline_money_fee,
        );
        $this->assign($refund_detail);


        // 如果存在 获取对应规格
        if ($now_order['specifications_id']) {
            $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->find();
            if ($specifications_info) {
                $now_order['specifications_info'] = $specifications_info;
                $now_order['s_name'] = $now_order['s_name'] . '('.$specifications_info['specifications_name'].')';
            } else {
                $now_order['specifications_info'] = array();
            }
        }
		$this->assign('refund_money',$refund_money_total);
		$this->assign('refund_fee',$refund_fee_total);
		$this->assign('now_order',$now_order);
		$this->display();
	}

    //取消订单
    public function group_order_check_refund(){
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录！');
        }
        $now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']),true);
        if(empty($now_order)){
            $this->error_tips('当前订单不存在');
        }
        if($now_order['paid'] != 1){
            $this->error_tips('当前订单还未付款！');
        }
        if ($now_order['status'] > 0 && $now_order['status'] < 5) {
            $this->error_tips('订单必须是未消费状态才能取消！',U('My/group_order',array('order_id'=>$now_order['order_id'])));
        } elseif ($now_order['status'] > 2 && $now_order['status'] != 5) {
            $this->redirect(U('My/group_order',array('order_id'=>$now_order['order_id'])));
        }
        $now_group = M('Group')->where(array('group_id'=>$now_order['group_id']))->find();
        if ($now_group['no_refund'] == 1) {
            $this->error_tips('当前'.$this->config['group_alias_name'].'不支持退款');
        }
        // 该时限只针对 团购券
        if ($now_group['no_refund'] == 0 && $now_group['before_effective_time'] > 0 && $now_group['tuan_type'] == 0) {
            // 计算时间是否超出
            $now_time = time();
            if ($now_group['effective_time_type'] == 1) {
                $time = floatval($now_group['before_effective_time']) * 24 * 3600;
            } else {
                $time = floatval($now_group['before_effective_time']) * 3600;
            }
            // 计算 退款时效
            $time_diff = floatval($now_group['deadline_time']) - $time;
            if (intval($time_diff) < intval($now_time)) {
                $this->error_tips('超出'.$this->config['group_alias_name'].'退款有效期，当前不可取消订单！');
            }
        }
        // 如果成团后退款手续费收取大于等于100%，返回错误
        if ($now_group['pin_num']>0 && $now_group['group_refund_fee']>0 && $now_group['group_refund_fee']>=100) {
            $this->error_tips('当前'.$this->config['group_alias_name'].'不支持退款');
        }
        // 拼团做判断
        if( $now_group['pin_num']>0&&!$now_order['single_buy']) {
            $now_group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            if (!empty($now_group_start) && $now_group_start['status']==2) {
                $this->error_tips('当前'.$this->config['group_alias_name'].'已超时，不支持退款');
            }
            if ($now_order['is_head'] && $now_group_start['status']==0) {
                $this->error_tips('当前'.$this->config['group_alias_name'].'未成团，不支持团长退款');
            }
            // 如果使用了积分且需退的金额大于0，计算退多少积分 优先退积分
            $group_refund_fee = $now_group['group_refund_fee']>0?$now_group['group_refund_fee']/100:0; // 退款所需手续费
        } else {
            $group_refund_fee = 0;
        }

        if($this->config['open_allinyun']==1){
            import('@.ORG.AccountDeposit.AccountDeposit');
            $deposit = new AccountDeposit('Allinyun');
            $allyun = $deposit->getDeposit();

            $allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid']);

            if($allinyun_user['bizUserId']!=''){
                $allyun->setUser($allinyun_user);
                $params['bizOrderNo'] = 'group_'.$now_order['orderid'];
                $params['oriBizOrderNo'] = $now_order['third_id'];
                $params['amount'] = intval($now_order['payment_money']*100);
                $res = $allyun->refundApply($params);
                if($res['status']=='OK'){

                    $data_group_order['refund_detail'] = serialize($res);
                    $data_group_order['status'] = 3;
                    D('Group_order')->where(array('order_id' => $now_order['order_id']))->save($data_group_order);

                    /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/18---团购状态-已退款3-*/
                    D("System_order")->save_order_status('group',$now_order['order_id'],5, 3);
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'shop';
                    $params['order_id'] = $now_order['order_id'];
                    $params['title'] = '订单取消提醒';
                    $params['content'] = '用户取消订单';
                    invoke_v20_service($url, [$params]);
                    $this->success_tips('退款成功',U('My/group_order',array('order_id'=>$_GET['order_id'])));
                }else{
                    $this->error_tips('退款失败,'.$res['message']);
                }
            }
        }

        // 首先 计算出 已经使用的核销码数量和未使用的核销码数量  同步计算出需要抵扣的金额 线上支付+平台余额+商户余额+商户赠送余额+积分抵扣金额
        $pass_num = $now_order['pass_num']>1?intval($now_order['pass_num']):1; // 一份团购 对应生成核销券 份数
        $total_pass_num = $now_order['num']*$pass_num; // 改订单产生的总 核销券 份数
        $express_fee = $now_order['express_fee'] > 0 ? intval($now_order['express_fee']):0; // 运费
        $total_pay_money = $now_order['price'] * intval($now_order['num']) + $express_fee; // // 改订单实际支付的总金额 线上支付+平台余额+商户余额+商户赠送余额+积分抵扣金额+运费  直接单价*数量
        $total_pay_money = sprintf("%.2f",$total_pay_money);
        if ($now_order['card_discount']>0 && $now_order['card_discount']<10) {
            $card_discount_money = $total_pay_money - round($total_pay_money * $now_order['card_discount'] * 0.1, 2); // 优先会员卡折扣优惠金额
        } else {
            $card_discount_money = 0;
        }
		$total_pay_money = $total_pay_money - $now_order['wx_cheap']; // 去除微信端/app优惠
        $actual_total_pay_money = $total_pay_money - $now_order['card_price'] - $now_order['coupon_price'] - $card_discount_money; // 实际支付总金额   总金额-各种优惠金额（商家优惠券金额+平台优惠券金额+	微信优惠+会员卡折扣优惠金额）[用户等级优惠在单价上已经计算过了，不予考虑]
        $actual_total_pay_money = sprintf("%.2f",$actual_total_pay_money);
		//计算下订单记录的金额
		$total_order_pay = $now_order['merchant_balance'] + $now_order['balance_pay'] + $now_order['payment_money'] + $now_order['score_deducte'];
		if ($total_order_pay>$actual_total_pay_money) {
			// 兼容原来旧的 计算优惠顺序不同的金额
			$actual_total_pay_money = $total_order_pay;
		}
        // 计算没分核销码的单价
        $pass_price = sprintf("%.4f",$actual_total_pay_money/$total_pass_num);
        if($now_order['pass_array']){
            $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
            $unconsume_pass_num = $total_pass_num-$consume_num;
        }elseif($now_order['tuan_type']==2){
            $unconsume_pass_num = $total_pass_num;
        }else{
            $unconsume_pass_num = 1;
        }
        $need_refund_money = $unconsume_pass_num * $pass_price; // 未使用的核销券 需要对应退款的总金额
        $need_refund_money = sprintf("%.2f",$need_refund_money);
        $refund_money_total = $need_refund_money; // 总需要退款金额
        // 如果使用了积分且需退的金额大于0，计算退多少积分 优先退积分
        // 团购退款手续总费用
        $refund_fee_total = $need_refund_money*$group_refund_fee; // 团购退款手续总费用 = 总需要退款金额*手续费比率
        $refund_fee_total = sprintf("%.2f",$refund_fee_total);
        $refund_score = 0; // 默认需要退款 积分 为 0
        $refund_score_fee = 0; // 积分退款手续费-积分数
        if ($now_order['score_used_count']>0 && $need_refund_money>0) {
            // 首先判断积分是否有剩余需要退的
            if ($need_refund_money >= $now_order['score_deducte']) {
                $need_refund_money = $need_refund_money - $now_order['score_deducte']; // 计算还需要退款金额 = 需退款金额 - 积分抵扣金额
                $refund_score = $now_order['score_deducte'] * (1 - $group_refund_fee) * ($now_order['score_used_count']/$now_order['score_deducte']); // 需要退的积分数量 = 积分抵扣金额 * (1-退款手续费) * （使用积分数/对应抵扣金额数）
                $refund_score = sprintf("%.2f",$refund_score); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_score_fee = $now_order['score_used_count'] - $refund_score; // 积分退款手续费-积分数 = 总的使用积分数-需要退的积分数
                    $refund_score_fee = sprintf("%.2f",$refund_score_fee); // 保留两位小数
                }
            } else {
                $refund_score = $need_refund_money * (1 - $group_refund_fee) * ($now_order['score_used_count']/$now_order['score_deducte']); // 计算需要退的积分 = 需退金额 * (1-退款手续费) * （使用积分数/对应抵扣金额数）
                $refund_score = sprintf("%.2f",$refund_score);
                if ($group_refund_fee>0) {
                    $refund_score_fee = sprintf("%.2f",$need_refund_money * ($now_order['score_used_count']/$now_order['score_deducte'])) - $refund_score; // 积分退款手续费-积分数 = 总的需要退的积分（未计算手续费的）-需要退的积分数
                    $refund_score_fee = sprintf("%.2f",$refund_score_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 积分就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了商户会员卡赠送金额且还需退的金额大于0 接着退 商户会员卡赠送金额
        $refund_card_give_money = 0; // 默认需要退款 商户会员卡赠送金额 为 0
        $refund_card_give_fee = 0; // 商户会员卡赠送金额 退款手续费
        if($now_order['card_give_money'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['card_give_money']) {
                $need_refund_money = $need_refund_money - $now_order['card_give_money']; // 计算还需要退款金额 = 还需要退款的金额 - 商户会员卡赠送余额
                $refund_card_give_money = $now_order['card_give_money'] * (1 - $group_refund_fee); // 需要退的商户会员卡赠送余额 = 商户会员卡赠送余额 * (1-退款手续费)
                $refund_card_give_money = sprintf("%.2f",$refund_card_give_money);// 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_card_give_fee = $now_order['card_give_money'] - $refund_card_give_money; // 商户会员卡赠送金额手续费 = 总的商户会员卡赠送金额-需要退的商户会员卡赠送金额
                    $refund_card_give_fee = sprintf("%.2f",$refund_card_give_fee); // 保留两位小数
                }
            } else {
                $refund_card_give_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的商户会员卡赠送余额 = 需退金额* (1-退款手续费)
                $refund_card_give_money = sprintf("%.2f",$refund_card_give_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_card_give_fee = $need_refund_money - $refund_card_give_money; // 商户会员卡赠送金额手续费 = 需要退的金额-需要退的商户会员卡赠送金额
                    $refund_card_give_fee = sprintf("%.2f",$refund_card_give_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 商户会员卡赠送余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了商户会员卡金额且还需退的金额大于0 接着退 商户会员卡金额
        $refund_merchant_balance = 0; // 默认需要退款 商户会员卡金额 为 0
        $refund_merchant_balance_fee = 0; // 商户会员卡赠送金额 退款手续费
        if($now_order['merchant_balance'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['merchant_balance']) {
                $need_refund_money = $need_refund_money - $now_order['merchant_balance']; // 计算还需要退款金额 = 还需要退款的金额 - 商户会员卡金额
                $refund_merchant_balance = $now_order['merchant_balance'] * (1 - $group_refund_fee); // 需要退的商户会员卡金额= 需要退的商户会员卡金额 * (1-退款手续费)
                $refund_merchant_balance = sprintf("%.2f",$refund_merchant_balance); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_merchant_balance_fee = $now_order['merchant_balance'] - $refund_merchant_balance; // 商户会员卡金额手续费 = 需要退的金额-需要退的商户会员卡金额
                    $refund_merchant_balance_fee = sprintf("%.2f",$refund_merchant_balance_fee); // 保留两位小数
                }
            } else {
                $refund_merchant_balance = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的商户会员卡金额 = 需退金额 * (1-退款手续费)
                $refund_merchant_balance = sprintf("%.2f",$refund_merchant_balance); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_merchant_balance_fee = $need_refund_money - $refund_merchant_balance; // 商户会员卡赠送金额手续费 = 需要退的金额-需要退的商户会员卡赠送金额
                    $refund_merchant_balance_fee = sprintf("%.2f",$refund_merchant_balance_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 商户会员卡金额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了平台余额 且还需退的金额大于0 接着退 平台余额
        $refund_balance_pay = 0; // 默认需要退款 平台余额 为 0
        $refund_balance_pay_fee = 0; // 平台余额 退款手续费
        if($now_order['balance_pay'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['balance_pay']) {
                $need_refund_money = $need_refund_money - $now_order['balance_pay']; // 计算还需要退款金额 = 还需要退款的金额 - 平台余额
                $refund_balance_pay = $now_order['balance_pay'] * (1 - $group_refund_fee); // 需要退的平台余额 = 需要退的平台余额 * (1-退款手续费)
                $refund_balance_pay = sprintf("%.2f",$refund_balance_pay); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_balance_pay_fee = $now_order['balance_pay'] - $refund_balance_pay; // 平台余额 = 需要退的金额-需要退的平台余额
                    $refund_balance_pay_fee = sprintf("%.2f",$refund_balance_pay_fee); // 保留两位小数
                }
            } else {
                $refund_balance_pay = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的平台余额 = 需退金额 * (1-退款手续费)
                $refund_balance_pay = sprintf("%.2f",$refund_balance_pay); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_balance_pay_fee = $need_refund_money - $refund_balance_pay; // 平台余额手续费 = 需要退的金额-需要退的平台余额
                    $refund_balance_pay_fee = sprintf("%.2f",$refund_balance_pay_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了平台E卡支付 且还需退的金额大于0 接着退 平台E卡金额
		$refund_ecard_pay = 0; // 默认需要退款 平台E卡金额 为 0
		$refund_ecard_pay_fee = 0; // 平台E卡 退款手续费
		if($now_order['use_ecard_price'] > 0 && $need_refund_money>0){
		    $need_refund_money = sprintf("%.2f",$need_refund_money);
		    if ($need_refund_money >= $now_order['use_ecard_price']) {
		        $need_refund_money = $need_refund_money - $now_order['use_ecard_price']; // 计算还需要退款金额 = 还需要退款的金额 - 平台E卡余额
		        $refund_ecard_pay = $now_order['use_ecard_price'] * (1 - $group_refund_fee); // 需要退的平台E卡余额 = 需要退的平台E卡余额 * (1-退款手续费)
		        $refund_ecard_pay = sprintf("%.2f",$refund_ecard_pay); // 保留两位小数
		        if ($group_refund_fee>0) {
		            $refund_ecard_pay_fee = $now_order['use_ecard_price'] - $refund_ecard_pay; // 平台E卡余额 = 需要退的金额-需要退的平台余额
		            $refund_ecard_pay_fee = sprintf("%.2f",$refund_ecard_pay_fee); // 保留两位小数
		        }
		    } else {
		        $refund_ecard_pay = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的平台余额 = 需退金额 * (1-退款手续费)
		        $refund_ecard_pay = sprintf("%.2f",$refund_ecard_pay); // 保留两位小数
		        if ($group_refund_fee>0) {
		            $refund_ecard_pay_fee = $need_refund_money - $refund_ecard_pay; // 平台余额手续费 = 需要退的金额-需要退的平台余额
		            $refund_ecard_pay_fee = sprintf("%.2f",$refund_ecard_pay_fee); // 保留两位小数
		        }
		        $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
		    }
		}

        //  如果使用了线上支付 且还需退的金额大于0 接着退 线上支付
        $refund_payment_money = 0; // 默认需要退款 线上支付 为 0
        $refund_payment_money_fee = 0; // 线上支付 退款手续费
        if($now_order['payment_money'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['payment_money']) {
                $need_refund_money = $need_refund_money - $now_order['payment_money']; // 计算还需要退款金额 = 还需要退款的金额 - 线上支付金额
                $refund_payment_money = $now_order['payment_money'] * (1 - $group_refund_fee); // 需要退的线上支付 = 线上支付金额 * (1-退款手续费)
                $refund_payment_money = sprintf("%.2f",$refund_payment_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_payment_money_fee = $now_order['payment_money'] - $refund_payment_money; // 线上支付金额 = 需要退的金额-需要退的线上支付金额
                    $refund_payment_money_fee = sprintf("%.2f",$refund_payment_money_fee); // 保留两位小数
                }
            } else {
                $refund_payment_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的线上支付金额 = 需退金额 * (1-退款手续费)
                $refund_payment_money = sprintf("%.2f",$refund_payment_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_payment_money_fee = $need_refund_money - $refund_payment_money; // 线上支付金额手续费 = 需要退的金额-需要退的线上支付金额
                    $refund_payment_money_fee = sprintf("%.2f",$refund_payment_money_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了线下退款 且还需退的金额大于0  接着计算 线下退款金额
        $refund_offline_money = 0; // 默认需要退款 线下支付 为 0
        $refund_offline_money_fee = 0; // 线下支付 退款手续费
        if($now_order['pay_type'] == 'offline' && $need_refund_money>0){
            $offline_money = $total_pay_money-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['card_price']-$now_order['coupon_price']-$card_discount_money; // 计算线下支付金额

            if ($need_refund_money >= $offline_money) {
                $need_refund_money = $need_refund_money - $offline_money; // 计算还需要退款金额 = 还需要退款的金额 - 线下支付金额
                $refund_offline_money = $offline_money * (1 - $group_refund_fee); // 需要退的线下支付金额 = 线下支付金额 * (1-退款手续费)
                $refund_offline_money = sprintf("%.2f",$refund_offline_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_offline_money_fee = $offline_money - $refund_offline_money; // 线下支付金额 = 需要退的金额-需要退的线下支付金额
                    $refund_offline_money_fee = sprintf("%.2f",$refund_offline_money_fee); // 保留两位小数
                }
            } else {
                $need_refund_money = sprintf("%.2f",$need_refund_money);
                $refund_offline_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的线下支付 = 需退金额 * (1-退款手续费)
                $refund_offline_money = sprintf("%.2f",$refund_offline_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_offline_money_fee = $need_refund_money - $refund_offline_money; // 线下支付金额手续费 = 需要退的金额-需要退的线下支付金额
                    $refund_offline_money_fee = sprintf("%.2f",$refund_offline_money_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 所有的钱均在线下支付退完
            }


        }

        // 退款详情
        $refund_detail = array(
            'refund_score' => $refund_score,
            'refund_score_fee' => $refund_score_fee,
            'refund_card_give_money' => $refund_card_give_money,
            'refund_card_give_fee' => $refund_card_give_fee,
            'refund_merchant_balance' => $refund_merchant_balance,
            'refund_merchant_balance_fee' => $refund_merchant_balance_fee,
            'refund_balance_pay' => $refund_balance_pay,
            'refund_balance_pay_fee' => $refund_balance_pay_fee,
            'refund_ecard_pay' => $refund_ecard_pay,
            'refund_ecard_pay_fee' => $refund_ecard_pay_fee,
            'refund_payment_money' => $refund_payment_money,
            'refund_payment_money_fee' => $refund_payment_money_fee,
            'refund_offline_money' => $refund_offline_money,
            'refund_offline_money_fee' => $refund_offline_money_fee,
            'refund_time' => time()
        );
        $order_id = $now_order['order_id'];
        $data_group_order['order_id'] = $order_id;  // 对应团购id
        $data_group_order['refund_money'] = $refund_money_total; // 以实际总金额计算出来的需要退款总金额
        $data_group_order['refund_fee'] = $refund_fee_total; // 退款总手续费  以实际支付总金额计算

        //线下付款退款-如果支付方式线下付款， 对应退上面所做计算得出的退款金额
        if($now_order['pay_type'] == 'offline'){
            $data_group_order['order_id'] = $order_id;
            $data_group_order['refund_detail'] = serialize(array('refund_time'=>time()));
            $data_group_order['status'] = 3;
            if(D('Group_order')->data($data_group_order)->save()){
                //2015-12-24     线下退款时销量回滚
                $update_group = D('Group')->where(array('group_id' => $now_order['group_id']))->find();
                if($now_group['group_cate'] == 'booking_appoint') {// 场次预约
		            $ruleInfo = M('Group_booking_appoint_order')->where(['order_id'=>$now_order['order_id']])->find();
		        }
                $sale_count_total = $update_group['sale_count_total'] - $now_order['num'];
                $sale_count_total = $sale_count_total > 0 ? $sale_count_total : 0;
                if ($update_group['type'] == 3) {
                    // 判断是否为团购规格退款
                    $update_group_data = array();

                    if ($now_order['specifications_id']) {
                        $m_group_specifications = M('Group_specifications');
                        $specifications_info = $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->field('count_num, sale_count')->find();
                        $sale_count = $specifications_info['sale_count'] - $now_order['num'];
                        $sale_count = $sale_count > 0 ? $sale_count : 0;
                        $update_data = array('sale_count' => $sale_count);
                        // 货物总数为零 即不限制
                        if (($specifications_info['count_num'] > 0 && $sale_count < $specifications_info['count_num']) || $specifications_info['count_num'] == 0) {
                            $update_group_data['type'] = 1;
                        }
                        $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->save($update_data);
                        $update_group_data['sale_count_total'] = $sale_count_total;
                    } elseif($ruleInfo) {
                    	$v20_param = [
		            		'ruleId' => $ruleInfo['rule_id'], 
		            		'combineId' => $ruleInfo['combine_id'], 
		            		'num' => $now_order['num'],
		            		'type' => 2
                    	];
                        invoke_v20_service('\app\group\model\service\GroupBookingAppointService/updateStock', $v20_param);
                    } else {
                        $sale_count = $update_group['sale_count'] - $now_order['num'];
                        $sale_count = $sale_count > 0 ? $sale_count : 0;
                        $update_group_data = array('sale_count' => $sale_count);
                        if ($update_group['count_num'] > 0 && $sale_count < $update_group['count_num']) {
                            $update_group_data['type'] = 1;
                        }
                        $update_group_data['sale_count_total'] = $sale_count_total;
                    }
                    D('Group')->where(array('group_id' => $now_order['group_id']))->save($update_group_data);
                } else {
                    //退款时销量回滚
                    if ($now_order['specifications_id']) {
                        $m_group_specifications = M('Group_specifications');
                        $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->setDec('sale_count', $now_order['num']);
                        D('Group')->where(array('group_id' => $now_order['group_id']))->setDec('sale_count_total', $now_order['num']);
                    } elseif($ruleInfo) {
                    	$v20_param = [
		            		'ruleId' => $ruleInfo['rule_id'], 
		            		'combineId' => $ruleInfo['combine_id'], 
		            		'num' => $now_order['num'],
		            		'type' => 2
                    	];
                        $res = invoke_v20_service('\app\group\model\service\GroupBookingAppointService/updateStock', $v20_param);
                    } else {
                        D('Group')->where(array('group_id' => $now_order['group_id']))->setDec('sale_count', $now_order['num']);
                        D('Group')->where(array('group_id' => $now_order['group_id']))->setDec('sale_count_total', $now_order['num']);
                    }
                }

     			// 取消订单，回滚redis库存
            	$v20_param = [
            		'groupId' => $now_order['group_id'], 
            		'specificationsId' => $now_order['specifications_id'], 
            		'ruleId' => $ruleInfo['rule_id'], 
            		'combineId' => $ruleInfo['combine_id'], 
            		'num' => $now_order['num']
            	];
                invoke_v20_service('\app\group\model\service\order\GroupOrderService/addRedisStock', $v20_param);
                    
                //平台E卡退款
                if($now_order['use_ecard_price'] > 0 && $refund_ecard_pay>0){
                    if ($refund_ecard_pay_fee > 0) {
                        $msg = '退款 '.$now_order['order_name'].' 增加'.$this->config['ecard_alias_name'].'转退余额。'.'手续费：'.$refund_ecard_pay_fee;
                    } else {
                        $msg = '退款 '.$now_order['order_name'].' 增加'.$this->config['ecard_alias_name'].'转退余额';
                    }
                    $result = D('User')->add_money($now_order['uid'],$refund_ecard_pay,$msg);
                    if($result['error_code']){
                        $refund_detail['err_msg'] = $result['msg'];
                        $refund_detail['refund_id'] = $order_id;
                        $data_group_order['refund_detail'] = serialize($refund_detail);
                        D('Group_order')->data($data_group_order)->save();
                        $this->error_tips($result['msg']);
                    } else {
                        $refund_detail['refund_id'] = $order_id;
                        $refund_detail['refund_ecard_pay'] = $refund_ecard_pay;
                        $refund_detail['refund_ecard_pay_fee'] = $refund_ecard_pay_fee;
                    }
                    $go_refund_param['msg'] = '平台'.$this->config['ecard_alias_name'].'转退余额退款成功';
                }
                //用户积分退款是回滚
                if ($now_order['score_used_count']>0 && $refund_score>0) {
                    if ($refund_score_fee > 0) {
                        $msg = '退款 '.$now_order['order_name'].C('config.score_name').'回滚。'.'手续费： ' .$refund_score_fee. C('config.score_name');
                    } else {
                        $msg = '退款 '.$now_order['order_name'].C('config.score_name').'回滚。';
                    }
                    $result = D('User')->add_score($now_order['uid'],$refund_score,$msg);
                    if($result['error_code']){
                        $refund_detail['err_msg'] = $result['msg'];
                        $refund_detail['refund_id'] = $order_id;
                        $data_group_order['refund_detail'] = serialize($refund_detail);
                        D('Group_order')->data($data_group_order)->save();
                        $this->error_tips($result['msg']);
                    } else {
                        $refund_detail['refund_id'] = $order_id;
                        $refund_detail['refund_score'] = $refund_score;
                        $refund_detail['refund_score_fee'] = $refund_score_fee;
                    }
                    $go_refund_param['msg'] = $result['msg'];
                }
                //商家会员卡赠送余额需退款金额大于0 或者 商家会员卡余额需退款金额大于0
                if($refund_card_give_money>0 || $refund_merchant_balance>0){
                    if ($refund_card_give_fee > 0) {
                        $give_msg = '退款 '.$now_order['order_name'].' 增加赠送余额。'.'手续费： '.$refund_card_give_fee;
                    } else {
                        $give_msg = '退款 '.$now_order['order_name'].' 增加赠送余额';
                    }
                    if ($refund_merchant_balance_fee > 0) {
                        $msg = '退款 '.$now_order['order_name'].' 增加余额。'.'手续费：'.$refund_merchant_balance_fee;
                    } else {
                        $msg = '退款 '.$now_order['order_name'].' 增加余额';
                    }
                    $result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$refund_merchant_balance,$refund_card_give_money,0,$msg,$give_msg);

                    if($result['error_code']){
                        $refund_detail['err_msg'] = $result['msg'];
                        $refund_detail['refund_id'] = $order_id;
                        $data_group_order['refund_detail'] = serialize($refund_detail);
                        D('Group_order')->data($data_group_order)->save();
                        $this->error_tips($result['msg']);
                    } else {
                        $refund_detail['refund_id'] = $order_id;
                        $refund_detail['refund_card_give_money'] = $refund_card_give_money;
                        $refund_detail['refund_card_give_fee'] = $refund_card_give_fee;
                        $refund_detail['refund_merchant_balance'] = $refund_merchant_balance;
                        $refund_detail['refund_merchant_balance_fee'] = $refund_merchant_balance_fee;
                    }
                    $go_refund_param['msg'] = $result['msg'];
                }

                //平台余额退款
                if($now_order['balance_pay'] > 0 && $refund_balance_pay>0){
                    if ($refund_balance_pay_fee > 0) {
                        $msg = '退款 '.$now_order['order_name'].' 增加余额。'.'手续费：'.$refund_balance_pay_fee;
                    } else {
                        $msg = '退款 '.$now_order['order_name'].' 增加余额';
                    }
                    $result = D('User')->add_money($now_order['uid'],$refund_balance_pay,$msg);

                    if($result['error_code']){
                        $refund_detail['err_msg'] = $result['msg'];
                        $refund_detail['refund_id'] = $order_id;
                        $data_group_order['refund_detail'] = serialize($refund_detail);
                        D('Group_order')->data($data_group_order)->save();
                        $this->error_tips($result['msg']);
                    } else {
                        $refund_detail['refund_id'] = $order_id;
                        $refund_detail['refund_balance_pay'] = $refund_balance_pay;
                        $refund_detail['refund_balance_pay_fee'] = $refund_balance_pay_fee;
                    }
                    $go_refund_param['msg'] = '平台余额退款成功';
                }
                // 整体退款流程走完后 修改团购订单状态为 已退款
                // 记录线下支付需退金额
                $refund_detail['refund_offline_money'] = $refund_offline_money;
                $refund_detail['refund_offline_money_fee'] = $refund_offline_money_fee;
                $data_group_order['order_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                $data_group_order['status'] = 3;
                $data_group_order['refund_money'] = $refund_money_total; // 以实际总金额计算出来的需要退款总金额
                if ($refund_fee_total > 0) {
                    $data_group_order['refund_fee'] = $refund_fee_total; // 退款总手续费  以实际支付总金额计算
                } else {
                    $data_group_order['refund_fee'] = 0;
                }

                D('Group_order')->data($data_group_order)->save();
                //有优惠券时退优惠券
                if($now_order['coupon_id']){
                    //平台优惠券
                    D('System_coupon_hadpull')->where(['uid'=>$this->user_session['uid'],'id'=>$now_order['coupon_id']])->save(['is_use'=>0,'use_time'=>0]);
                }

                //有优惠券时退优惠券
                if($now_order['card_id']){
                    //商家优惠券
                    D('Card_new_coupon_hadpull')->where(['uid'=>$this->user_session['uid'],'id'=>$now_order['card_id']])->save(['is_use'=>0,'use_time'=>0]);
                }
                /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/18---团购状态-已退款3-*/
                D("System_order")->save_order_status('group',$order_id,5,3);
                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'shop';
                $params['order_id'] = $now_order['order_id'];
                $params['title'] = '订单取消提醒';
                $params['content'] = '用户取消订单';
                invoke_v20_service($url, [$params]);
                $this->success_tips('您使用的是线下支付！订单状态已修改为已退款。',U('My/group_order',array('order_id'=>$now_order['order_id'])));
                exit;
            }else{
                $this->error_tips('取消订单失败！请重试。');
            }
        }

        //线上支付
        $refund_detail['refund_id'] = $order_id;
        if($now_order['payment_money'] > 0 && $refund_payment_money>0){

            $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>'group','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();

            if(empty($payment_money_order)){// 获取新版支付信息
            	$pay_order_info =  M('Pay_order_info')->where(array('business_order_id'=>$now_order['order_id'],'business'=>'group','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
            }

            if($pay_order_info){// 新版支付退款

            	$refundParam = [
            		'order_no' => $pay_order_info['orderid'],
            		'refund_money' => $refund_payment_money,
            	];
            	$result = invoke_v20_service('\app\pay\model\service\PayService/refund',$refundParam);

                if(!$result || $result['data']['error_no']){
                	$refund_detail['err_msg'] = $result['data']['error_msg'];
	                $data_group_order['refund_detail'] = serialize($refund_detail);
	                D('Group_order')->data($data_group_order)->save();
	                $this->error_tips($result['data']['error_msg']);
                }

                $refund_detail['refund_payment_money'] = $result['data']['retval']['refund_money'];
        		$refund_detail['refund_id'] = $result['data']['retval']['refund_no'];

            }else{
            	if($now_order['payment_money'] == $payment_money_order['payment_money']){
	                //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
	                $now_order['payment_money'] = $payment_money_order['paid_money'];
	                if($payment_money_order['pay_rate'] > 0){
	                	$now_order['payment_money'] = $now_order['payment_money']*$payment_money_order['pay_rate'];
	                }
	            }
				
	            if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'xiaoma' || $payment_money_order['pay_method'] == 'nong')){
					if($payment_money_order['pay_method'] == 'wft'){
						$pay_method = 'wftpay';
					}
					if($payment_money_order['pay_method'] == 'paylinx'){
						$pay_method = 'paylinx';
					}
					if($payment_money_order['pay_method'] == 'wepayez'){
						$pay_method = 'wepayez';
					}
					if($payment_money_order['pay_method'] == 'weikebaba'){
						$pay_method = 'weikebaba';
					}
					if($payment_money_order['pay_method'] == 'xiaoma'){
						$pay_method = 'xiaoma';
					}
					if($payment_money_order['pay_method'] == 'nong'){
						$pay_method = 'nong';
					}
					import('@.ORG.LowFeePay');
					$lowfeepay = new LowFeePay($pay_method);
					$now_order['order_type'] = 'group';
					$now_order['orderNo'] = $payment_money_order['orderNo'];
					if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
						$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
						if(!empty($mer_juhe)){ 
							$lowfeepay->userId =$mer_juhe['userid'];
							$now_order['is_own'] =1 ;
							$now_order['orderNo'] =  $now_order['orderNo'].'_1';
						}
						
					}elseif($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
	                    $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$now_order['mer_id']))->find();
	                    if(!empty($mer_juhe)){ 
	                        $lowfeepay->user_id = $mer_juhe['user_id'];
	                        $lowfeepay->key = $mer_juhe['key'];
	                        $now_order['is_own'] =1 ;
	                        $now_order['orderNo'] =  $now_order['orderNo'];
	                    }
	                    
	                }
	                $now_order['payment_refund_money'] = $refund_payment_money;
					$go_refund_param= $lowfeepay->refund($now_order);
				}else if($payment_money_order['pay_method'] == 'chinaums'){
					import("@.ORG.pay.Chinaums");
	                $pay = new Chinaums();
	                if($payment_money_order['is_mobile_pay'] == 3){
	                    $mid = C("config.chinaums_mid_wxapp");
	                }
	                if($payment_money_order['is_mobile_pay'] == 2){
	                    $mid = C("config.chinaums_mid_app");
	                }
	                if($payment_money_order['is_mobile_pay'] == 1){
	                    $mid = C("config.chinaums_mid_wx");
	                }
	                $chinaums_merno = '';
	                if($now_order['mer_id']){
	                    $now_merchant = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->find();
	                    if(!empty($now_merchant['chinaums_merno'])){
	                        $chinaums_merno = $now_merchant['chinaums_merno'];
	                    }
	                }
	                $now_order['order_type'] = 'group';
	                $go_refund_param= $pay->refund($now_order['order_type'].'_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data'], $chinaums_merno, $mid, $payment_money_order);
				}else if($this->config['open_juhepay']==1 &&( $now_order['pay_type']=='weixin' || $now_order['pay_type']=='alipay')){
	                // 这里线上支付退款 直接对应订单退款，退的是整体金额 （如果不退整个金额，这里的金额就会退还过多）
	                import('@.ORG.LowFeePay');
	                $lowfeepay = new LowFeePay('juhepay');
	                $now_order['orderNo'] = 'group_'.$now_order['orderid'];
	                if($now_order['mer_id']){
	                    $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
	                    if(!empty($mer_juhe)){
	                        $lowfeepay->userId =$mer_juhe['userid'];
	                        $now_order['is_own'] =1 ;
	                        $now_order['orderNo'] =  $now_order['orderNo'].'_1';
	                    }

	                }
	                $now_order['payment_refund_money'] = $refund_payment_money;
	                $go_refund_param= $lowfeepay->refund($now_order);

	            }else if($this->config['open_pufa_pay']==1 &&( $now_order['pay_type']=='weixin')){
	                // 这里线上支付退款 直接对应订单退款，退的是整体金额 （如果不退整个金额，这里的金额就会退还过多）
	                import('@.ORG.LowFeePay');
	                $lowfeepay = new LowFeePay('pufapay');
	                $now_order['orderNo'] = 'group_'.$now_order['orderid'];
	                $now_order['payment_refund_money'] = $refund_payment_money;
	                $go_refund_param= $lowfeepay->refund($now_order);
	            }
	            else if($payment_money_order['pay_method'] == 'chinaums'){
					import('@.ORG.pay.Chinaums');
					$pay = new Chinaums();

					if ($payment_money_order['is_mobile_pay'] == 3) {
						$mid = C("config.chinaums_mid_wxapp");
					}
					if ($payment_money_order['is_mobile_pay'] == 2) {
						$mid = C("config.chinaums_mid_app");
					}
					if ($payment_money_order['is_mobile_pay'] == 1) {
						$mid = C("config.chinaums_mid_wx");
					}
					$chinaums_merno = '';
					if ($now_order['mer_id']) {
						$now_merchant = M("Merchant")->where(['mer_id' => $now_order['mer_id']])->find();
						if (!empty($now_merchant['chinaums_merno'])) {
							$chinaums_merno = $now_merchant['chinaums_merno'];
						}
					}
					$go_refund_param = $pay->refund($payment_money_order['order_type'] . '_' . $payment_money_order['orderid'], $refund_payment_money, $refund_payment_money, $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
	            
	            }else if($payment_money_order && $payment_money_order['pay_method'] == 'scrcu'){
					import("@.ORG.pay.Scrcu");
					$pay = new scrcu();
					
					$scrcu_mer_no = '';
					if($now_order['mer_id']){
						$now_merchant = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->find();
						if(!empty($now_merchant['scrcu_mer_no'])){
							$scrcu_mer_no = $now_merchant['scrcu_mer_no'];
						}
					}

					$go_refund_param= $pay->refund($now_order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
	            }else if($payment_money_order && strpos($payment_money_order['pay_type'],'xiantao') !== false){// 智慧仙桃支付退款
					import('@.ORG.pay.XianTaoCard');
					$pay = new XianTaoCard();

					$now_user = D('User')->get_user($now_order['uid']);
					try{
						// 退款
						$go_refund_param = $pay->refund($payment_money_order['orderNo'], $now_user['phone']);
					} catch(Exception $e){
						$this->error_tips($e->getMessage());
					}
				}
	            else {
	                if ($now_order['is_own'] && $now_order['pay_type'] != 'nmgpay') {
	                    $pay_method = array();
	                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
	                    foreach ($merchant_ownpay as $ownKey => $ownValue) {
	                        $ownValueArr = unserialize($ownValue);
	                        if ($ownValueArr['open']) {
	                            $ownValueArr['is_own'] = true;
	                            $pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
	                        }
	                    }
	                    $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
	                    if ($now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
	                        $pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
	                        $pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
	                        $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
	                        $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
	                        $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
	                        $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
	                        $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
	                        $pay_method['weixin']['config']['is_own'] = 2;
	                    }
//----------------
	                } else {
	                    $pay_method = D('Config')->get_pay_method(0, 0, 1);
	                }
					
					//小程序支付特殊处理
					if ($now_order['is_mobile_pay'] == 3) {
	                    $pay_method[$now_order['pay_type']]['config'] = array(
	                        'pay_weixin_appid' => $this->config['pay_wxapp_appid'],
	                        'pay_weixin_key' => $this->config['pay_wxapp_key'],
	                        'pay_weixin_mchid' => $this->config['pay_wxapp_mchid'],
	                        'pay_weixin_appsecret' => $this->config['pay_wxapp_appsecret'],
	                    );
						
						//自有支付特殊处理
	                    if($now_order['is_own']){
	                        $now_merchant_wxapp_ownpay = M('Weixin_app_bind')->where(array('other_id'=>$now_order['mer_id']))->find();
	                        $pay_method[$now_order['pay_type']]['config'] = array(
	                            'pay_weixin_appid' => $now_merchant_wxapp_ownpay['appid'],
	                            'pay_weixin_key' => $now_merchant_wxapp_ownpay['wxpay_key'],
	                            'pay_weixin_mchid' => $now_merchant_wxapp_ownpay['wxpay_merid'],
	                            'pay_weixin_appsecret' => $now_merchant_wxapp_ownpay['appsecret'],
	                            'is_own' => 1,
	                        );
	                        C('config.pay_weixin_client_cert', $now_merchant_wxapp_ownpay['public_key']);
	                        C('config.pay_weixin_client_key', $now_merchant_wxapp_ownpay['private_key']);
	                        
	                        $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
		                    if ($now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
		                        $pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_wxapp_appid'];
		                        $pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_wxapp_appsecret'];
		                        $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
		                        $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
		                        $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
		                        $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
		                        $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
		                        $pay_method['weixin']['config']['is_own'] = 2;
		                    }
	                    }
	                }

	                if (empty($pay_method)) {
	                    $this->error_tips('系统管理员没开启任一一种支付方式！');
	                }

	                $pay_class_name = ucfirst($now_order['pay_type']);
	                if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && $this->config['new_pay_alipay_app_public_key'] != '' && $this->config['new_pay_alipay_app_appid'] != '' && $this->config['new_pay_alipay_app_private_key'] != '') {
	                    $pay_class_name = 'AlipayApp';
	                    $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
	                    $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
	                    $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
	                }
                    if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                        $pay_class_name = 'Hqpay';
                        empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                    }
	                if (empty($pay_method[$now_order['pay_type']])) {
	                    $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
	                }
	                $import_result = import('@.ORG.pay.' . $pay_class_name);
	                if (empty($import_result)) {
	                    $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
	                }
	                $now_order['order_type'] = 'group';
	                if (!empty($now_order['orderid'])) {
	                    $now_order['old_order_id'] = $now_order['order_id'];
	                    $now_order['order_id'] = $now_order['orderid'];
	                }

	                $pay_class = new $pay_class_name($now_order, $refund_payment_money, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, $now_order['is_mobile_pay']);
	                $go_refund_param = $pay_class->refund();
	            }

	            if ($now_order['pay_type']=='nmgpay' && ($go_refund_param['status'] == 'y' || $go_refund_param['info']=='成功')) {
	                $data_group_order['type'] = 'ok';
	            } elseif ($now_order['pay_type']=='nmgpay'){
	                $go_refund_param['msg']=$go_refund_param['info'];
	            }

	            if(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok'){
	                $refund_detail['refund_payment_money'] = $refund_payment_money;
	                $refund_detail['refund_payment_money_fee'] = $refund_payment_money_fee;
	            }else{
	                $refund_detail['err_msg']=$go_refund_param['msg'];
	                $data_group_order['refund_detail'] = serialize($refund_detail);
	                D('Group_order')->data($data_group_order)->save();
	                $this->error_tips($go_refund_param['msg']);
	            }
            }
        }

        // 整体退款流程走完后 修改团购订单状态为 已退款
        // 记录线下支付需退金额
        $data_group_order['refund_detail'] = serialize($refund_detail);
        $data_group_order['status'] = 3;


		//平台E卡退款
        if($now_order['use_ecard_price'] > 0 && $refund_ecard_pay>0){
            if ($refund_ecard_pay_fee > 0) {
                $msg = '退款 '.$now_order['order_name'].' 增加'.$this->config['ecard_alias_name'].'转退余额。'.'手续费：'.$refund_ecard_pay_fee;
            } else {
                $msg = '退款 '.$now_order['order_name'].' 增加'.$this->config['ecard_alias_name'].'转退余额';
            }
            $result = D('User')->add_money($now_order['uid'],$refund_ecard_pay,$msg);
            if($result['error_code']){
                $refund_detail['err_msg'] = $result['msg'];
                $refund_detail['refund_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->error_tips($result['msg']);
            } else {
                $refund_detail['refund_id'] = $order_id;
                $refund_detail['refund_ecard_pay'] = $refund_ecard_pay;
                $refund_detail['refund_ecard_pay_fee'] = $refund_ecard_pay_fee;
            }
            $go_refund_param['msg'] = '平台'.$this->config['ecard_alias_name'].'转退余额退款成功';
        }
        //用户积分退款是回滚
        if ($now_order['score_used_count']>0 && $refund_score>0) {
            if ($refund_score_fee > 0) {
                $msg = '退款 '.$now_order['order_name'].C('config.score_name').'回滚。'.'手续费： ' .$refund_score_fee. C('config.score_name');
            } else {
                $msg = '退款 '.$now_order['order_name'].C('config.score_name').'回滚。';
            }
            $result = D('User')->add_score($now_order['uid'],$refund_score,$msg);
            if($result['error_code']){
                $refund_detail['err_msg'] = $result['msg'];
                $refund_detail['refund_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->error_tips($result['msg']);
            } else {
                $refund_detail['refund_id'] = $order_id;
                $refund_detail['refund_score'] = $refund_score;
                $refund_detail['refund_score_fee'] = $refund_score_fee;
            }
            $go_refund_param['msg'] = $result['msg'];
        }

        //商家会员卡赠送余额需退款金额大于0 或者 商家会员卡余额需退款金额大于0
        if($refund_card_give_money>0 || $refund_merchant_balance>0){
            if ($refund_card_give_fee > 0) {
                $give_msg = '退款 '.$now_order['order_name'].' 增加赠送余额。'.'手续费： '.$refund_card_give_fee;
            } else {
                $give_msg = '退款 '.$now_order['order_name'].' 增加赠送余额';
            }
            if ($refund_merchant_balance_fee > 0) {
                $msg = '退款 '.$now_order['order_name'].' 增加余额。'.'手续费：'.$refund_merchant_balance_fee;
            } else {
                $msg = '退款 '.$now_order['order_name'].' 增加余额';
            }
            $result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$refund_merchant_balance,$refund_card_give_money,0,$msg,$give_msg);

            if($result['error_code']){
                $refund_detail['err_msg'] = $result['msg'];
                $refund_detail['refund_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->error_tips($result['msg']);
            } else {
                $refund_detail['refund_id'] = $order_id;
                $refund_detail['refund_card_give_money'] = $refund_card_give_money;
                $refund_detail['refund_card_give_fee'] = $refund_card_give_fee;
                $refund_detail['refund_merchant_balance'] = $refund_merchant_balance;
                $refund_detail['refund_merchant_balance_fee'] = $refund_merchant_balance_fee;
            }
            $go_refund_param['msg'] = $result['msg'];
        }

        //平台余额退款
        if($now_order['balance_pay'] > 0 && $refund_balance_pay>0){
            if ($refund_balance_pay_fee > 0) {
                $msg = '退款 '.$now_order['order_name'].' 增加余额。'.'手续费：'.$refund_balance_pay_fee;
            } else {
                $msg = '退款 '.$now_order['order_name'].' 增加余额';
            }
            $result = D('User')->add_money($now_order['uid'],$refund_balance_pay,$msg);

            if($result['error_code']){
                $refund_detail['err_msg'] = $result['msg'];
                $refund_detail['refund_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->error_tips($result['msg']);
            } else {
                $refund_detail['refund_id'] = $order_id;
                $refund_detail['refund_balance_pay'] = $refund_balance_pay;
                $refund_detail['refund_balance_pay_fee'] = $refund_balance_pay_fee;
            }
            if ($result['error_code']) {
                $this->error_tips($result['msg']);
            }
            $go_refund_param['msg'] = '平台余额退款成功';
        }


        if ($refund_fee_total > 0) {
            $now_order['order_type'] = 'group';
            $now_order['refund'] = true;
            $now_order['refund_money'] = $refund_fee_total;
            $now_order['desc']='团购退款手续费';
            D('SystemBill')->bill_method($now_order['is_own'],$now_order);
        } else {
            $data_group_order['refund_fee'] = 0;
        }

        if($total_pass_num>$unconsume_pass_num) {
            //退款 原来没有成团的拼团组人数减1
            if($now_group['pin_num']!=0){
                $now_group_start = D('Group_start')->get_group_start_by_order_id($order_id);
                if($now_group_start['status']==0){
                    D('Group_start')->buyer_refund_dec_by_orderid($order_id,$now_group_start['id']);
                }
                if($total_pass_num==$unconsume_pass_num){
                    $data_group_order['status']=3;
                }
            }else{
                $data_group_order['status'] = 6;
            }
        }else{
            $data_group_order['status']=3;
        }
        $go_refund_param['msg'] = "退款成功！";
        D('Group_order')->data($data_group_order)->save();

        //有优惠券时退优惠券
        if($now_order['coupon_id']){
            //平台优惠券
            D('System_coupon_hadpull')->where(['uid'=>$this->user_session['uid'],'id'=>$now_order['coupon_id']])->save(['is_use'=>0,'use_time'=>0]);
        }

        //有优惠券时退优惠券
        if($now_order['card_id']){
            //商家优惠券
            D('Card_new_coupon_hadpull')->where(['uid'=>$this->user_session['uid'],'id'=>$now_order['card_id']])->save(['is_use'=>0,'use_time'=>0]);
        }

        $update_group = D('Group')->where(array('group_id' => $now_order['group_id']))->find();

        if($now_group['group_cate'] == 'booking_appoint') {// 场次预约
            $ruleInfo = M('Group_booking_appoint_order')->where(['order_id'=>$now_order['order_id']])->find();
        }
        $sale_count_total = $update_group['sale_count_total'] - $now_order['num'];
        $sale_count_total = $sale_count_total > 0 ? $sale_count_total : 0;
        if ($update_group['type'] == 3) {
            // 判断是否为团购规格退款
            $update_group_data = array();
            if ($now_order['specifications_id']) {
                $m_group_specifications = M('Group_specifications');
                $specifications_info = $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->field('count_num, sale_count')->find();
                $sale_count = $specifications_info['sale_count'] - $now_order['num'];
                $sale_count = $sale_count > 0 ? $sale_count : 0;
                $update_data = array('sale_count' => $sale_count);
                // 货物总数为零 即不限制
                if (($specifications_info['count_num'] > 0 && $sale_count < $specifications_info['count_num']) || $specifications_info['count_num'] == 0) {
                    $update_group_data['type'] = 1;
                }
                $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->save($update_data);
                $update_group_data['sale_count_total'] = $sale_count_total;
            } elseif($ruleInfo) {
            	$v20_param = [
            		'ruleId' => $ruleInfo['rule_id'], 
            		'combineId' => $ruleInfo['combine_id'], 
            		'num' => $now_order['num'],
            		'type' => 2
            	];
                $res = invoke_v20_service('\app\group\model\service\GroupBookingAppointService/updateStock', $v20_param);
            } else {
                $sale_count = $update_group['sale_count'] - $now_order['num'];
                $sale_count = $sale_count > 0 ? $sale_count : 0;
                $update_group_data = array('sale_count' => $sale_count);
                if ($update_group['count_num'] > 0 && $sale_count < $update_group['count_num']) {
                    $update_group_data['type'] = 1;
                }
                $update_group_data['sale_count_total'] = $sale_count_total;
            }
            D('Group')->where(array('group_id' => $now_order['group_id']))->save($update_group_data);
        } else {
            //退款时销量回滚
            if ($now_order['specifications_id']) {
                $m_group_specifications = M('Group_specifications');
                $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id'], 'sale_count' => array('egt', $now_order['num'])))->setDec('sale_count', $now_order['num']);
                D('Group')->where(array('group_id' => $now_order['group_id']))->setDec('sale_count_total', $now_order['num']);
            } elseif($ruleInfo) {
            	$v20_param = [
            		'ruleId' => $ruleInfo['rule_id'], 
            		'combineId' => $ruleInfo['combine_id'], 
            		'num' => $now_order['num'],
            		'type' => 2
            	];
                $res = invoke_v20_service('\app\group\model\service\GroupBookingAppointService/updateStock', $v20_param);
            } else {
                D('Group')->where(array('group_id' => $now_order['group_id'], 'sale_count' => array('egt', $now_order['num'])))->setDec('sale_count', $now_order['num']);
                D('Group')->where(array('group_id' => $now_order['group_id']))->setDec('sale_count_total', $now_order['num']);
            }
        }

		// 取消订单，回滚redis库存
    	$v20_param = [
    		'groupId' => $now_order['group_id'], 
    		'specificationsId' => $now_order['specifications_id'], 
    		'ruleId' => $ruleInfo['rule_id'], 
    		'combineId' => $ruleInfo['combine_id'], 
    		'num' => $now_order['num']
    	];
        invoke_v20_service('\app\group\model\service\order\GroupOrderService/addRedisStock', $v20_param);

        //酒店等其他业务增加库存
        if(!empty($now_order['trade_info'])){
            $trade_info = unserialize($now_order['trade_info']);

            switch($trade_info['type']){
                case 'hotel':
                    $where['mer_id']=$now_group['mer_id'];
                    $where['cat_id']=$trade_info['cat_id'];
                    $where['_string']="stock_day >=".$trade_info['dep_time'] .' AND stock_day <'.$trade_info['end_time'];
                    M('Trade_hotel_stock')->where($where)->setInc('stock',$trade_info['num']);

                    break;
            }
        }

        //短信提醒
        $sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'group');
        if ($this->config['sms_group_cancel_order'] == 1 || $this->config['sms_group_cancel_order'] == 3) {
            $sms_data['uid'] = $now_order['uid'];
            $sms_data['mobile'] = $now_order['phone'];
            $sms_data['sendto'] = 'user';
            $sms_data['content'] = '您购买 '.$now_order['order_name'].'的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
            Sms::sendSms($sms_data);
        }
        if ($this->config['sms_group_cancel_order'] == 2 || $this->config['sms_group_cancel_order'] == 3) {
            $merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $merchant['phone'];
            $sms_data['sendto'] = 'merchant';
            $sms_data['content'] = '顾客购买的' . $now_order['order_name'] . '的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
            Sms::sendSms($sms_data);
        }
        D('Group_order')->group_app_notice($order_id,5);
        D('Group_pass_relation')->change_refund_status($order_id);
        /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/18--团购状态-已退款3--*/
        D("System_order")->save_order_status('group',$order_id,5,3);
        //增加消息通知
        $url = '\app\common\model\service\user\UserNoticeService/addNotice';
        $params['type'] = 0;
        $params['business'] = 'group';
        $params['order_id'] = $now_order['order_id'];
        $params['title'] = '订单取消提醒';
        $params['content'] = '用户取消订单';
        invoke_v20_service($url, [$params]);
        //退商品积分
        $check_score = D('User_score_list')->where(['order_type'=>'group','order_id'=>$order_id,'score'=>['gt',0],'type'=>1])->getField('score',true);
        foreach ($check_score as $item){
            D('User')->use_score($now_order['uid'],
                $item,
                L_('X1订单退款扣除X2', ['X1'=>C('config.group_alias_name'),'X2'=>C('config.score_name')]),
                ['mer_id'=>$now_order['mer_id'],'store_id'=>$now_order['store_id'],'score_type'=>0,'order_id'=>$order_id,'order_type'=>'group']
            );
        }
        $this->success_tips($go_refund_param['msg'],U('Groupnew/order_detail',array('order_id'=>$_GET['order_id'],'time'=>time())));
    }

	//取消订单=>郭先森
	public function village_group_order_check_refund()
	{
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$now_order = D('Village_group_order')->where(array('order_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid']))->find();
		$platPay = D('Plat_order')->where(array('business_id'=>$_GET['order_id'],'business_type'=>'village_group'))->find();
		if($now_order['status'] == 12 || $now_order['status'] == 4){
			$this->error_tips('当前订单已退款！');
		}
		if(empty($now_order)){
			$this->error_tips('当前订单不存在');
		}
		if($now_order['status'] == 0){
			$this->error_tips('当前订单还未付款！');
		}

		if($now_order['status'] == 5){
			$this->error_tips('已发货不能取消订单！');
		}

        if(in_array($now_order['status'],[2,13])){
            $this->error_tips('已完成订单请到个人中心订单详情申请售后！');
        }

        if(in_array($now_order['status'],[6,7,8])){
            $this->error_tips('当前订单已发货不能取消订单！');
        }

		if($platPay['paid'] != 1){
			$this->error_tips('当前订单还未付款！');
		}
		//余额支付=>退款
		if(empty($platPay['pay_type']) && $platPay['paid'] == 1){
//			if($now_order['discount_price'] == $platPay['system_balance']){
			//$orderModel = D('Village_group_order')->where(array('order_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid']))->find();
			if($now_order['score_used_count'] !== 0){
				$score_used_count = $now_order['score_used_count'];//积分使用数量
			}else{
				$score_used_count = 0;
			}
			if(D('Village_group_order')->where(array('order_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid']))->save(array('status'=>'12','refund_money'=>$platPay['total_money'],'last_time'=>time()))){
                if($now_order['coupon_price'] !== 0 && !empty($now_order['coupon_id'])){
                    D('System_coupon_hadpull')->where(array('uid'=>$this->user_session['uid'],'id'=>$now_order['coupon_id']))->save(array('is_use'=>0));
                }
				$userModel = M('User')->where(array('uid'=>$this->user_session['uid'],'status'=>1))->find();
				if(!empty($userModel)){
					$add_result = D('User')->add_money($this->user_session['uid'],$platPay['total_money'],'社区团购申请售后退款,增加余额，订单编号' . $now_order['real_orderid']);
					fdump($add_result,'已支付,未消费',true);
					D('Plat_order')->where(array('business_id'=>$_GET['order_id'],'business_type'=>'village_group'))->save(array('is_refund'=>1,'refund_money'=>$platPay['total_money']));
				}
				$s_order_detail = M('Village_group_order_detail')->where(['order_id'=>$_GET['order_id']])->field('goods_id,num')->select();
				foreach ($s_order_detail as $gid) {
					M('Village_group_goods')->where(array('goods_id'=>$gid['goods_id']))->setDec('sell_count',$gid['num']);
				}
				if($score_used_count > 0){
					D("User")->add_score($this->user_session['uid'],$score_used_count,"社区团购取消订单，增加".$this->config['score_name']);
				}
			}
			D("System_order")->save_order_status('village_group',$_GET['order_id'],5);
//			}
			$go_refund_param['msg'] = '平台余额退款成功';
            //增加消息通知
            $url = '\app\common\model\service\user\UserNoticeService/addNotice';
            $params['type'] = 0;
            $params['business'] = 'village_group';
            $params['order_id'] = $now_order['order_id'];
            $params['title'] = '订单取消提醒';
            $params['content'] = '用户取消订单';
            invoke_v20_service($url, [$params]);
			$this->success_tips($go_refund_param['msg'],U('Village_group/orderDetail',array('order_id'=>$_GET['order_id'])));
		}
		//线上平台支付

		if($now_order['discount_price']>0 && $platPay['pay_money']>0){
			//实例化M()方法
			$Model = M();
			//开启事务
			$Model->startTrans();
			if($platPay['paid'] == 1){//支付状态
				//通过线上平台

				if(!empty($platPay['pay_type']) && $platPay['pay_money']>0 && !empty($now_order['pay_type']) ){
					$payment_money_order = M('Payment_money_order')->where(array('orderid'=>$platPay['orderid'],'order_type'=>'plat','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
					fdump($payment_money_order['pay_method'],'123456',true);

					if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba')){

						if($payment_money_order['pay_method'] == 'wft'){
							$pay_method = 'wftpay';
						}
						if($payment_money_order['pay_method'] == 'paylinx'){
							$pay_method = 'paylinx';
						}
						if($payment_money_order['pay_method'] == 'weikebaba'){
							$pay_method = 'weikebaba';
						}
						import('@.ORG.LowFeePay');
						$lowfeepay = new LowFeePay($pay_method);
//						$now_order['order_type'] = 'group';
						$villageGroupOrder['orderNo'] = $payment_money_order['orderNo'];
						$now_order['orderNo'] = 'plat_'.$now_order['orderid'];
						$now_order['order_type'] = 'plat';
						$now_order['payment_money'] = $platPay['pay_money'];
						$now_order['payment_refund_money'] = $platPay['pay_money'];
						$now_order['order_id'] = $platPay['orderid'];
						if($villageGroupOrder['mer_id']){
							$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$villageGroupOrder['mer_id']))->find();
							if(!empty($mer_juhe)){
								$lowfeepay->userId =$mer_juhe['userid'];
								$villageGroupOrder['is_own'] =1 ;
								$villageGroupOrder['orderNo'] =  $villageGroupOrder['orderNo'].'_1';
							}

						}
						if($villageGroupOrder['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
							$mer_juhe = M('Weikebaba')->where(array('mer_id'=>$villageGroupOrder['mer_id']))->find();
							if(!empty($mer_juhe)){
								$lowfeepay->user_id = $mer_juhe['user_id'];
								$lowfeepay->key = $mer_juhe['key'];
								$villageGroupOrder['is_own'] =1 ;
								$villageGroupOrder['orderNo'] =  $villageGroupOrder['orderNo'];
							}

						}
						$villageGroupOrder['order_type'] = 'plat';
						$villageGroupOrder['payment_money'] = $platPay['pay_money'];
						$villageGroupOrder['payment_refund_money'] = $platPay['pay_money'];
						$villageGroupOrder['order_id'] = $platPay['orderid'];

						$go_refund_param= $lowfeepay->refund($villageGroupOrder);

					}else if($this->config['open_juhepay']==1 &&( $now_order['pay_type']=='weixin' || $now_order['pay_type']=='alipay')){//聚合支付=>郭先森
						import('@.ORG.LowFeePay');
						$lowfeepay = new LowFeePay('juhepay');
						$now_order['orderNo'] = 'group_'.$now_order['orderid'];
//						if($now_order['mer_id']){
//							$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
//							if(!empty($mer_juhe)){
//								$lowfeepay->userId =$mer_juhe['userid'];
//								$now_order['is_own'] =1 ;
//								$now_order['orderNo'] =  $now_order['orderNo'].'_1';
//							}
//
//						}
						$go_refund_param= $lowfeepay->refund($now_order);

					}else if($this->config['open_pufa_pay']==1 && $platPay['pay_type']=='weixin'){//开启浦发支付并且是微信支付=>郭先森
						// 这里线上支付退款 直接对应订单退款，退的是整体金额 （如果不退整个金额，这里的金额就会退还过多）
						import('@.ORG.LowFeePay');
						$lowfeepay = new LowFeePay('pufapay');
						$now_order['orderNo'] = 'group_'.$now_order['orderid'];
						$go_refund_param= $lowfeepay->refund($now_order);
					}else{
						if ($now_order['is_own'] && $now_order['pay_type'] != 'nmgpay') {
							$pay_method = array();
							$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
							//不存在return
							if(empty($merchant_ownpay)){
								return false;
							}
							foreach ($merchant_ownpay as $ownKey => $ownValue) {
								$ownValueArr = unserialize($ownValue);
								if ($ownValueArr['open']) {
									$ownValueArr['is_own'] = true;
									$pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
								}
							}
							$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
							if ($now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
								$pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
								$pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
								$pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
								$pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
								$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
								$pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
								$pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
								$pay_method['weixin']['config']['is_own'] = 2;
							}
						} else {
							$pay_method = D('Config')->get_pay_method(0, 0, 1);
						}

						if (empty($pay_method)) {
							$this->error_tips('系统管理员没开启任一一种支付方式！');
						}

						$pay_class_name = ucfirst($now_order['pay_type']);
						if ($pay_class_name == 'Alipay' &&  $this->config['new_pay_alipay_app_public_key'] != '' && $this->config['new_pay_alipay_app_appid'] != '' && $this->config['new_pay_alipay_app_private_key'] != '') {
							$pay_class_name = 'AlipayApp';
							$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
							$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
							$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
						}
                        if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                            $pay_class_name = 'Hqpay';
                            empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                        }
						if (empty($pay_method[$now_order['pay_type']])) {
							$this->error_tips('您选择的支付方式不存在，请更新支付方式！');
						}
						$import_result = import('@.ORG.pay.' . $pay_class_name);
						if (empty($import_result)) {
							$this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
						}

						$now_order['order_type'] = 'plat';
						$now_order['orderid'] = $platPay['orderid'];
						if (!empty($now_order['order_id'])) {
							$now_order['order_id'] = $platPay['orderid'];
						}
						if ($platPay['is_mobile_pay'] == 3) {
							$pay_method[$now_order['pay_type']]['config'] = array(
								'pay_weixin_appid' => $this->config['pay_wxapp_appid'],
								'pay_weixin_key' => $this->config['pay_wxapp_key'],
								'pay_weixin_mchid' => $this->config['pay_wxapp_mchid'],
								'pay_weixin_appsecret' => $this->config['pay_wxapp_appsecret'],
							);

							if($platPay['is_own']){
								$now_merchant_wxapp_ownpay = M('Weixin_app_bind')->where(array('other_id'=>$now_order['mer_id']))->find();
								$pay_method[$now_order['pay_type']]['config'] = array(
									'pay_weixin_appid' => $now_merchant_wxapp_ownpay['appid'],
									'pay_weixin_key' => $now_merchant_wxapp_ownpay['wxpay_key'],
									'pay_weixin_mchid' => $now_merchant_wxapp_ownpay['wxpay_merid'],
									'pay_weixin_appsecret' =>$now_merchant_wxapp_ownpay['appsecret'],
								);
								C('config.pay_weixin_client_cert', $now_merchant_wxapp_ownpay['public_key']);
								C('config.pay_weixin_client_key', $now_merchant_wxapp_ownpay['private_key']);
							}
						}
						$now_order['payment_money'] = $platPay['pay_money'];
						$now_order['is_mobile_pay'] = $platPay['is_mobile_pay'];
						//退钱进行钱处理
						$pay_class = new $pay_class_name($now_order, $platPay['pay_money'], $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, $now_order['is_mobile_pay']);
						fdump($pay_class_name,'shequpintuan',true);
						fdump($pay_class,'shequpintuan',true);
						$go_refund_param = $pay_class->refund();
						fdump($go_refund_param,'shequpintuan',true);


					}
					if($go_refund_param['error']){
						$this->error_tips($go_refund_param['msg']);
						exit;
					}

					//进行退款数据操作
					if($now_order['discount_price'] == $platPay['pay_money'] || ($now_order['score_deducte'] + $platPay['pay_money']) == $now_order['discount_price']){
						//$orderModel = D('Village_group_order')->where(array('order_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid']))->find();
						if($now_order['coupon_price'] !== 0 && !empty($now_order['coupon_id'])){

							D('System_coupon_hadpull')->where(array('uid'=>$this->user_session['uid'],'id'=>$now_order['coupon_id']))->save(array('is_use'=>0));
						}else{

						}
						if($now_order['score_used_count'] !== 0){
							$score_used_count = $now_order['score_used_count'];//积分使用数量
						}else{
							$score_used_count = 0;
						}

						$userModel = M('User')->where(array('uid'=>$this->user_session['uid'],'status'=>1))->find();
						if(!empty($userModel)){
							$data['score_count'] = $score_used_count+$userModel['score_count'];//积分

							M('User')->where(array('uid'=>$this->user_session['uid'],'status'=>1))->save($data);
							D('Village_group_order')->where(array('order_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid']))->save(array('status'=>'12','refund_money'=>$platPay['total_money']));
							D('Plat_order')->where(array('business_id'=>$_GET['order_id'],'business_type'=>'village_group'))->save(array('is_refund'=>1,'refund_money'=>$platPay['total_money']));
						}
					}
					elseif($platPay['system_balance'] > 0){
						if(D('Village_group_order')->where(array('order_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid']))->save(array('status'=>'12','refund_money'=>$platPay['total_money']))){
							$userModel = M('User')->where(array('uid'=>$this->user_session['uid'],'status'=>1))->find();
							if(!empty($userModel)){
								$add_result = D('User')->add_money($this->user_session['uid'],$platPay['system_balance'],'社区团购申请售后退款,增加余额，订单编号' . $now_order['real_orderid']);
								D('Plat_order')->where(array('business_id'=>$_GET['order_id'],'business_type'=>'village_group'))->save(array('is_refund'=>1,'refund_money'=>$platPay['total_money']));
							}

						}
					}
					$s_order_detail = M('Village_group_order_detail')->where(['order_id'=>$_GET['order_id']])->field('goods_id,num')->select();
					foreach ($s_order_detail as $gid) {
						M('Village_group_goods')->where(array('goods_id'=>$gid['goods_id']))->setDec('sell_count',$gid['num']);
					}
					$go_refund_param['msg'] = "退款成功！";

					$Model->commit();
					D("System_order")->save_order_status('village_group',$_GET['order_id'],5);
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'village_group';
                    $params['order_id'] = $now_order['order_id'];
                    $params['title'] = '订单取消提醒';
                    $params['content'] = '用户取消订单';
                    invoke_v20_service($url, [$params]);
					$this->success_tips($go_refund_param['msg'],U('Village_group/orderDetail',array('order_id'=>$_GET['order_id'])));

				}else{
					$Model->rollback();
					$this->error_tips('退款失败');

				}
			}else{
				$Model->rollback();
				$this->error_tips('线上退款未进入处理,请联系开发人员!');
			}

		}else{
			$this->error_tips('请联系开发人员!');
		}


	}






	/*删除团购订单*/
	public function group_order_del(){
		$now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']));
		if(empty($now_order)){
			$this->error_tips('当前订单不存在！');
		}else if($now_order['paid']){
			$this->error_tips('当前订单已付款，不能删除。');
		}
		$condition_group_order['order_id'] = $now_order['order_id'];
		$data_group_order['status'] = 4;
		if(D('Group_order')->where($condition_group_order)->data($data_group_order)->save()){
			//退款时销量回滚
            /*-----记录系统订单信息 订单未支付取消 改写状态已取消 19/1/18---团购状态-未付款取消订单4-*/
            D("System_order")->save_order_status('group',$now_order['order_id'],5,4);
			$now_group = D('Group')->where(array('group_id' => $now_order['group_id']))->find();
			if($now_group['stock_reduce_method']){
				D('Group')->where(array('group_id' => $now_order['group_id'], 'sale_count' => array('egt', $now_order['num'])))->setDec('sale_count', $now_order['num']);
			}

			$this->success_tips('删除成功！',U('My/group_order_list'));
		}else{
			$this->error_tips('删除失败！请重试。');
		}
	}

	public function plat_order_refund(){
		$business_type = $_GET['business_type'];
		$order_id = $_GET['order_id'];
		$table = ucfirst($business_type).'_order';
		$model = D($table);
		$now_order = $model->get_order_by_orderid($order_id);
		$param['business_id'] = $_GET['order_id'];
		$param['business_type'] = $business_type;
		$plat_order = D('Plat_order')->get_order_by_business_id($param);

		if (empty($now_order)||empty($plat_order)) {
			$this->error_tips('当前订单不存在');
		}
		$can_refund = $model->can_refund_status($now_order);
		if ($plat_order['paid'] != 1 || !$can_refund) {
			$this->error_tips('当前订单店员正在处理中，不能退款或取消');
		}
		if (empty($plat_order['paid'])) {
			$this->error_tips('当前订单还未付款！');
		}
		$now_order['pay_order'] = $plat_order;

		$this->assign('now_order', $now_order);
		$this->display($business_type.'_order_refund');
	}

	public function plat_order_check_refund(){

		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$orderid = intval($_GET['order_id']);

		$now_order = M("Plat_order")->where(array('order_id' => $orderid))->find();
		
		if(empty($now_order)){
			$this->error_tips('当前订单不存在');
		}
		
		if(empty($now_order['paid'])){
			$this->error_tips('当前订单还未付款！');
		}


		$now_order['order_type'] = 'plat';
		$now_order['payment_money'] =$now_order['pay_money'];
		$table = ucfirst($now_order['business_type']).'_order';
		$model =D($table);
		
		$business_order = $model->get_order_by_orderid($now_order['business_id']);
		$can_refund = $model->can_refund_status($business_order);
		if ($now_order['paid'] != 1 || !$can_refund) {
			$this->error_tips('当前订单店员正在处理中，不能退款或取消');
		}
		
		$now_order['mer_id'] = $business_order['mer_id'];
		if($this->config['open_allinyun']==1){
			import('@.ORG.AccountDeposit.AccountDeposit');
			$deposit = new AccountDeposit('Allinyun');
			$allyun = $deposit->getDeposit();

			$allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid']);

			if($allinyun_user['bizUserId']!=''){
				$allyun->setUser($allinyun_user);
				$params['bizOrderNo'] = 'shop_'.$now_order['orderid'];
				$params['oriBizOrderNo'] = $now_order['third_id'];
				$params['amount'] = intval($now_order['payment_money']*100);
				$res = $allyun->refundApply($params);

				if($res['status']=='OK'){

					$refund_result = $model->afert_refund($business_order);
					if($now_order['pay_type']=='offline'){
						$this->success_tips('您使用的是线下支付！订单状态已修改为已退款。',$refund_result['url']);
					}else{
						$this->success_tips('退款成功',$refund_result['url']);
					}
				}else{
					$this->error_tips('退款失败,'.$res['message']);
				}
			}
		}

		$now_merchant = D('Merchant')->get_info($now_order['mer_id']);

		//$my_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
		$refund_status =false;
		if($now_order['pay_money'] != '0.00'){
			
			$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>'plat','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
			
            if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'weikebaba')){
				import('@.ORG.LowFeePay');
				if($payment_money_order['pay_method'] == 'wft'){
					$pay_method = 'wftpay';
				}
				if($payment_money_order['pay_method'] == 'weikebaba'){
					$pay_method = 'weikebaba';
				}
				$lowfeepay = new LowFeePay($pay_method);
				$now_order['order_type'] = 'plat';
				$now_order['orderNo'] = $payment_money_order['orderNo'];
				if($now_order['mer_id']){
					$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
					if(!empty($mer_juhe)){ 
						$lowfeepay->userId =$mer_juhe['userid'];
						$now_order['is_own'] =1 ;
						$now_order['orderNo'] =  $now_order['orderNo'].'_1';
					}
					
				}
				if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                    $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$now_order['mer_id']))->find();
                    if(!empty($mer_juhe)){ 
                        $lowfeepay->user_id = $mer_juhe['user_id'];
                        $lowfeepay->key = $mer_juhe['key'];
                        $now_order['is_own'] =1 ;
                    }
                    
                }
				$go_refund_param= $lowfeepay->refund($now_order);
			}else if($this->config['open_juhepay']==1 &&( $now_order['pay_type']=='weixin' || $now_order['pay_type']=='alipay')){
				import('@.ORG.LowFeePay');
				$lowfeepay = new LowFeePay('juhepay');
				$now_order['orderNo'] = 'plat_'.$now_order['orderid'];
				if($now_order['mer_id']){
                    $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
					if(!empty($mer_juhe)){
						$lowfeepay->userId =$mer_juhe['userid'];
						$now_order['is_own'] =1 ;
					    $now_order['orderNo'] =  $now_order['orderNo'].'_1';
					}

                }
				$go_refund_param= $lowfeepay->refund($now_order);

			}else if($this->config['open_pufa_pay']==1 &&( $now_order['pay_type']=='weixin')){
				import('@.ORG.LowFeePay');
				$lowfeepay = new LowFeePay('pufapay');
				$now_order['orderNo'] = 'plat_'.$now_order['orderid'];
				$go_refund_param= $lowfeepay->refund($now_order);
			}else if($payment_money_order && $payment_money_order['pay_method'] == 'scrcu'){
				import("@.ORG.pay.Scrcu");
				$pay = new scrcu();
				
				$scrcu_mer_no = '';
				if($now_order['mer_id']){
					$now_merchant = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->find();
					if(!empty($now_merchant['scrcu_mer_no'])){
						$scrcu_mer_no = $now_merchant['scrcu_mer_no'];
					}
				}

				$go_refund_param= $pay->refund($now_order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
			}else if($payment_money_order && strpos($payment_money_order['pay_type'],'xiantao') !== false){// 智慧仙桃支付退款
				import('@.ORG.pay.XianTaoCard');
				$pay = new XianTaoCard();

				$now_user = D('User')->get_user($now_order['uid']);
				try{
					// 退款
					$go_refund_param = $pay->refund($payment_money_order['orderNo'], $now_user['phone']);
				} catch(Exception $e){
					$this->error_tips($e->getMessage());
				}
			}else {
				if ($now_order['is_own'] && $now_order['pay_type'] != 'nmgpay') {
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $business_order['mer_id']))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
						}
					}
					$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
					if ($now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
						$pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
						$pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
						$pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
						$pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
						$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
						$pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
						$pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
						$pay_method['weixin']['config']['is_own'] = 2;
					}
				} else {
					$pay_method = D('Config')->get_pay_method(0, 0, 1);
				}

				if (empty($pay_method)) {
					$this->error_tips('系统管理员没开启任一一种支付方式！');
				}


				$pay_class_name = ucfirst($now_order['pay_type']);
				if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && $this->config['new_pay_alipay_app_public_key'] != '' && $this->config['new_pay_alipay_app_appid'] != '' && $this->config['new_pay_alipay_app_private_key'] != '') {
					$pay_class_name = 'AlipayApp';
					$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
					$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
					$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
				}
                if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                    $pay_class_name = 'Hqpay';
                    empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                }
				if (empty($pay_method[$now_order['pay_type']])) {
					$this->error_tips('您选择的支付方式不存在，请更新支付方式！');
				}
				$import_result = import('@.ORG.pay.' . $pay_class_name);
				if (empty($import_result)) {
					$this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
				}

				$order_id = $now_order['order_id'];
				$now_order['order_id'] = $now_order['orderid'];
				if ($now_order['is_mobile_pay'] == 3) {
					$pay_method[$now_order['pay_type']]['config'] = array(
							'pay_weixin_appid' => $this->config['pay_wxapp_appid'],
							'pay_weixin_key' => $this->config['pay_wxapp_key'],
							'pay_weixin_mchid' => $this->config['pay_wxapp_mchid'],
							'pay_weixin_appsecret' => $this->config['pay_wxapp_appsecret'],
					);
					if($now_order['is_own']){
						$now_merchant_wxapp_ownpay = M('Weixin_app_bind')->where(array('other_id'=>$now_order['mer_id']))->find();
						$pay_method[$now_order['pay_type']]['config'] = array(
								'pay_weixin_appid' => $now_merchant_wxapp_ownpay['appid'],
								'pay_weixin_key' => $now_merchant_wxapp_ownpay['wxpay_key'],
								'pay_weixin_mchid' => $now_merchant_wxapp_ownpay['wxpay_merid'],
								'pay_weixin_appsecret' =>$now_merchant_wxapp_ownpay['appsecret'],
						);
						C('config.pay_weixin_client_cert', $now_merchant_wxapp_ownpay['public_key']);
						C('config.pay_weixin_client_key', $now_merchant_wxapp_ownpay['private_key']);
					}
					$is_mobile = 3;
				} else {
					$is_mobile = 1;
				}
				$now_order['payment_money'] = $now_order['pay_money'];
				$now_order['is_mobile_pay'] = $platPay['is_mobile_pay'];
				$pay_class = new $pay_class_name($now_order, $now_order['pay_money'], $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, $is_mobile);
				$go_refund_param = $pay_class->refund();
			}
			$now_order['order_id'] = $orderid;
			$data_plat_order['order_id'] = $orderid;
			$data_plat_order['refund_detail'] = serialize($go_refund_param['refund_param']);
			if(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok'){
				$refund_status = true;
			}else{
				$this->error_tips($go_refund_param['msg']);
			}
			D('Plat_order')->data($data_plat_order)->save();

		}elseif($now_order['pay_type'] == 'offline') {
			$data_plat_order['order_id'] = $now_order['order_id'];
			$data_plat_order['refund_detail'] = serialize(array('refund_time'=>time()));
			D('Plat_order')->data($data_plat_order)->save();
		}

		//如果使用了积分 2016-1-15
		if ($now_order['system_score']>0) {

			$order_name=$now_order['order_name'];
			$result = D('User')->add_score($now_order['uid'],$now_order['system_score'],'退款 '.$order_name.$this->config['score_name'].'回滚');
			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_plat_order['order_id'] = $now_order['order_id'];
			$data_plat_order['refund_detail'] = serialize($param);
			D('Plat_order')->data($data_plat_order)->save();
			if ($result['error_code']) {
				$this->error_tips($result['msg']);
			}else{
				$refund_status = true;
			}
			$go_refund_param['msg'] .= $result['msg'];
		}

		//平台余额退款
		if($now_order['system_balance'] != '0.00'){
			$result = D('User')->add_money($now_order['uid'],$now_order['system_balance'],'退款 '.$now_order['order_name'].' 增加余额');
			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_plat_order['order_id'] = $now_order['order_id'];
			$data_plat_order['refund_detail'] = serialize($param);
			D('Plat_order')->data($data_plat_order)->save();
			if ($result['error_code']) {
				$this->error_tips($result['msg']);
			}else{
				$refund_status = true;
			}
			$go_refund_param['msg'] = '平台余额退款成功';
		}

		//商家会员卡余额退款
		if($now_order['merchant_balance_pay'] != '0.00'||$now_order['merchant_balance_give']!='0.00'){
			$card_mer_id = $now_order['mer_id'];

			// 商家联盟会员卡
			if ($now_order['union_mer_id']) {
				$card_mer_id = $now_order['union_mer_id'];
			}

			$result = D('Card_new')->add_user_money($card_mer_id,$now_order['uid'],$now_order['merchant_balance_pay'],$now_order['merchant_balance_give'],0,'退款 '.$now_order['order_name'].' 增加余额','退款 '.$now_order['order_name'].' 增加赠送余额');
			$param = array('refund_time' => time());
			if(!$result){
//				$param['err_msg'] = $result['msg'];
				$param['err_msg'] = '退款失败！';
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_plat_order['order_id'] = $now_order['order_id'];
			$data_plat_order['refund_detail'] = serialize($param);
			D('Plat_order')->data($data_plat_order)->save();
			if (!$result) {
//				$this->error_tips($result['msg']);
				$this->error_tips('退款失败！');
			}else{
				$refund_status = true;
			}
//			$go_refund_param['msg'] = $result['msg'];
			$go_refund_param['msg'] = '会员卡余额退款成功！';
		}


		//if($refund_status){
			//调用 打印 发短信 发模板消息 回滚库存 回调跳转地址
			$refund_result = $model->afert_refund($business_order);
			if(!$refund_status['error_code']){
				if($now_order['pay_type']=='offline'){
					$this->success_tips('您使用的是线下支付！订单状态已修改为已退款。',$refund_result['url']);
				}else{
					$this->success_tips($go_refund_param['msg'],$refund_result['url']);
				}
			}else{
				$this->error_tips($refund_result['msg']);
			}
		//}

	}

	public function ajax_group_order_del(){
		$database_group_order = D('Group_order');
		$now_order = $database_group_order->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']));
		if(empty($now_order)){
			exit(json_encode(array('status'=>0,'msg'=>'当前订单不存在！')));
		}

		$order_condition['order_id'] = $now_order['order_id'];
		$data['is_del'] = 1;
		if($database_group_order->where($order_condition)->data($data)->save()){
			exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'删除失败！')));
		}
	}

	public function ajax_gift_order_del(){
		if(IS_AJAX){
			$order_id = $_GET['order_id'] + 0;
			$database_gift_order = D('Gift_order');
			$now_order = $database_gift_order->get_order_detail_by_id($this->user_session['uid'],$order_id);

			if(empty($now_order)){
				exit(json_encode(array('status'=>0,'msg'=>'当前订单不存在！')));
			}

			$order_condition['order_id'] = $now_order['order_id'];
			$data['is_del'] = 1;
			$data['del_time'] = time();
			if($database_gift_order->where($order_condition)->data($data)->save()){
				exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
			}else{
				exit(json_encode(array('status'=>0,'msg'=>'删除失败！')));
			}
		}else{
			$this->error_tips('访问页面有误！');
		}
	}

	public function ajax_meal_order_del(){
		$database_meal_order = D('Meal_order');
		$now_order = $database_meal_order->get_order_by_id($this->user_session['uid'],intval($_GET['order_id']));
		if(empty($now_order)){
			exit(json_encode(array('status'=>0,'msg'=>'当前订单不存在！')));
		}

		$order_condition['order_id'] = $now_order['order_id'];
		$data['is_del'] = 1;
		if($database_meal_order->where($order_condition)->data($data)->save()){
			exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'删除失败！')));
		}
	}

	public function ajax_shop_order_del(){
		$database_shop_order = D('Shop_order');
		$now_order = $database_shop_order->get_order_by_id($this->user_session['uid'],intval($_GET['order_id']));

		if(empty($now_order)){
			exit(json_encode(array('status'=>0,'msg'=>'当前订单不存在！')));
		}

		$order_condition['order_id'] = $now_order['order_id'];
		$data['is_del'] = 1;
		if($database_shop_order->where($order_condition)->data($data)->save()){
			$order_condition['type'] = $now_order['order_from'] == '1' ? 'mall' : 'shop';
			D('System_order')->where($order_condition)->data($data)->save();
			exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'删除失败！')));
		}
	}

	/*删除预约订单*/
	public function appoint_order_del(){
		$database_appoint_order = D('Appoint_order');
		$now_order = $database_appoint_order->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']));
		if(empty($now_order)){
			$this->error_tips('当前订单不存在！');
		}else if($now_order['paid']){
			$this->error_tips('当前订单已付款，不能删除。');
		}
		$condition_group_order['order_id'] = $now_order['order_id'];
		$data_group_order['paid'] = 3;
		if($database_appoint_order->where($condition_group_order)->data($data_group_order)->save()){
			$this->success_tips('删除成功！',U('My/appoint_order_list'));
		}else{
			$this->error_tips('删除失败！请重试。');
		}
	}

	/*店铺收藏*/
	public function group_store_collect(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		// merchant_store_foodshop
		$collection_list = D('')->table(array(C('DB_PREFIX').'merchant_store'=>'ms', C('DB_PREFIX').'merchant_user_relation'=>'msc',C('DB_PREFIX').'merchant_store_foodshop'=>'msf'))->where("msc.uid= '".$this->user_session['uid']."' AND ms.store_id = msc.store_id AND msf.store_id = msc.store_id AND msc.type = 'foodshop'")->select();

		foreach ($collection_list as $k => $v) {
			$tmp_pic_info  = explode(';',$v['pic_info']);

            if ($tmp_pic_info[0]) {
                $collection_list[$k]['pic_info'] = stripos($tmp_pic_info[0], ',') ? str_replace(',', '/', $tmp_pic_info[0]) : $tmp_pic_info[0];
                $collection_list[$k]['pic_info'] = replace_file_domain($collection_list[$k]['pic_info']);
            } else {
                $collection_list[$k]['pic_info'] = '';
            }

			$collection_list[$k]['score_mean'] = $v['score_mean']==0 ? 5.0 : number_format($v['score_mean'],1);
	    }
        $this->assign('collection_list',$collection_list);
		$this->display();
	}

	public function ajax_store_collect(){
		$listRows = 8;
		$firstRow = $_POST['page'] * $listRows;
		$collection_list = D('')->table(array(C('DB_PREFIX').'merchant_store'=>'ms', C('DB_PREFIX').'merchant_user_relation'=>'msc'))->where("msc.uid= '".$this->user_session['uid']."' AND ms.store_id = msc.store_id AND msc.type = 'foodshop'")->limit($firstRow,$listRows)->select();

		foreach ($collection_list as $k => $v) {
	        $collection_list[$k]['pic_info'] = str_replace(',', '/', $v['pic_info']);
	    }
		$html='';
		foreach ($collection_list as $key => $value) {
			$html .= '<dd>
        				<a href="" class="react">
							<div class="dealcard poicard">
								<div class="dealcard-img imgbox">
									<img src="'.file_domain().'/upload/store/'.$value['pic_info'].'" style="width:100%;height:100%;">
								</div>
							    <div class="dealcard-block-right">
									<h6 class="dealcard-brand single-line">
										<span class="poiname">'.$value['name'].'</span>
										<span class="dealtype-icon">团</span>
									</h6>
									<div class="info">
										<span class="stars">&nbsp;</span>
									</div>
							        <div class="pos">'.$value['area_name'].'</div>
							    </div>
							</div>
       					</a>
       				</dd>';
		}


		if(is_array($collection_list)){
			exit(json_encode(array('error'=>1,'id'=>$id,'html'=>$html)));
		}else{
			exit(json_encode(array('error'=>2)));
		}
	}

	//手艺人收藏
	public function worker_collect(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$this->assign(D('Merchant_workers')->wap_get_worker_collect_list($this->user_session['uid']));
		$this->display();
	}

	/*商家收藏***商家中心暂时没有手机版***/
	public function merchant_collect(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}

		$this->assign(D('Merchant')->get_collect_list($this->user_session['uid']));
		$this->display();
	}
	/***图片上传** */

	public function ajaxImgUpload() {
		$mulu=isset($_GET['ml']) ? trim($_GET['ml']):'group';
		$mulu=!empty($mulu) ? $mulu : 'group';
		$filename = trim($_POST['filename']);
		$img = $_POST[$filename];
		$img = str_replace('data:image/png;base64,', '', $img);
		$img = str_replace(' ', '+', $img);
		$imgdata = base64_decode($img);
		$img_order_id = sprintf("%09d",$this->user_session['uid']);
		$rand_num = mt_rand(10,99).'/'.substr($img_order_id,0,3).'/'.substr($img_order_id,3,3).'/'.substr($img_order_id,6,3);
		$getupload_dir = "/upload/reply/".$mulu."/" .$rand_num;

		$upload_dir = "." . $getupload_dir;
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
		$newfilename = $mulu.'_' . date('YmdHis') . '.jpg';
		$save = file_put_contents($upload_dir . '/' . $newfilename, $imgdata);
		$save = file_put_contents($upload_dir . '/m_' . $newfilename, $imgdata);
		$save = file_put_contents($upload_dir . '/s_' . $newfilename, $imgdata);

		$file_handle = new file_handle();
		$file_handle->upload($upload_dir . '/' . $newfilename);
		$file_handle->upload($upload_dir . '/m_' . $newfilename);
		$file_handle->upload($upload_dir . '/s_' . $newfilename);
		
		if ($save) {
			$this->dexit(array('error' => 0, 'data' => array('code' => 1, 'siteurl'=>$this->config['site_url'],'imgurl' =>$getupload_dir . '/' . $newfilename, 'msg' => '')));
		} else {
			$this->dexit(array('error' => 1, 'data' => array('code' => 0, 'url' => '', 'msg' => '保存失败！')));
		}
	}

	/*团购评价*/
	public function group_feedback(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$group_id = $_GET['group_id'];
		$now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']),true);
		$this->assign('now_order',$now_order);
        $this->assign('group_id',$group_id);
		if(empty($now_order)){
			$this->error_tips('当前订单不存在！');
		}
		if(empty($now_order['paid'])){
			$this->error_tips('当前订单未付款！无法评论');
		}
		if(empty($now_order['status'])){
			$this->error_tips('当前订单未消费！无法评论');
		}
		if($now_order['status'] == 2){
			$this->error_tips('当前订单已评论');
		}
		if(IS_POST){
			$score = intval($_POST['score']);
			if($score > 5 || $score < 1){
				$this->error_tips('评分只能1到5分');
			}
			$inputimg=isset($_POST['inputimg']) ? $_POST['inputimg'] :'';
			$pic_ids=array();
            if($this->is_weixin_browser){
                $from_type = 0;
            }
            elseif($this->is_wxapp_browser){
                $from_type = 3;
            }
            elseif($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
                $from_type = 2;
            }
            elseif($this->is_app_browser){
                $from_type = 1;
            }
            else{
                $from_type = 0;
            }
			if(!empty($inputimg)){
				$database_reply_pic = D('Reply_pic');
				foreach($inputimg as $imgv){
					$imgv=str_replace('/upload/reply/group/','',$imgv);
					$imgtmp=explode('/',$imgv);
					$imgname=$imgtmp[count($imgtmp)-1];
					$reply_pic['name'] = $imgname;
					$reply_pic['pic'] = str_replace('/'.$imgname,'',$imgv).','.$imgname;
					$reply_pic['uid'] = $this->user_session['uid'];
					$reply_pic['order_type'] = '0';
					$reply_pic['order_id'] = intval($now_order['order_id']);
					$reply_pic['add_time'] = $_SERVER['REQUEST_TIME'];
					$pic_ids[] = $database_reply_pic->data($reply_pic)->add();
				}
			}
			$database_reply = D('Reply');
			$data_reply['parent_id'] = $now_order['group_id'];
			$data_reply['store_id'] = $now_order['store_id'];
			$data_reply['mer_id'] = $now_order['mer_id'];
			$data_reply['score'] = $score;
			$data_reply['order_type'] = '0';
			$data_reply['order_id'] = intval($now_order['order_id']);
			$data_reply['anonymous'] = intval($_POST['anonymous']);
			$data_reply['comment'] = $_POST['comment'];
			$data_reply['uid'] = $this->user_session['uid'];
			$data_reply['pic'] = !empty($pic_ids) ? implode(',',$pic_ids):'';
			$data_reply['add_time'] = $_SERVER['REQUEST_TIME'];
			$data_reply['add_ip'] = get_client_ip(0);
            !empty($inputimg) && $data_reply['reply_pic'] = implode(';', $inputimg);
			if($res = $database_reply->data($data_reply)->add()){
				D('Group')->setInc_group_reply($now_order,$score);
				D('Group_order')->change_status($now_order['order_id'],2);
                /*-----记录系统订单信息 订单未支付取消 改写状态已取消 19/1/18----*/
                D("System_order")->save_order_status('group',$now_order['order_id'],3);

                //记录下用户评论行为分析$reply_id,$uid,$content,$business_type,$comment_time,$from_type,$add_ip
                D('User_behavior_reply')->add_record($res,$this->user_session['uid'],$_POST['comment'],2,time(),$from_type,get_client_ip(0));

                if($now_order['is_group_combine']==0) {
                    $database_merchant_score = D('Merchant_score');
                    $now_merchant_score = $database_merchant_score->field('`pigcms_id`,`score_all`,`reply_count`')->where(array('parent_id' => $now_order['mer_id'], 'type' => '1'))->find();
                    if (empty($now_merchant_score)) {
                        $data_merchant_score['parent_id'] = $now_order['mer_id'];
                        $data_merchant_score['type'] = '1';
                        $data_merchant_score['score_all'] = $score;
                        $data_merchant_score['reply_count'] = 1;
                        $database_merchant_score->data($data_merchant_score)->add();
                    } else {
                        $data_merchant_score['score_all'] = $now_merchant_score['score_all'] + $score;
                        $data_merchant_score['reply_count'] = $now_merchant_score['reply_count'] + 1;
                        $database_merchant_score->where(array('pigcms_id' => $now_merchant_score['pigcms_id']))->data($data_merchant_score)->save();
                    }
                    $now_store_score = $database_merchant_score->field('`pigcms_id`,`score_all`,`reply_count`')->where(array('parent_id' => $now_order['store_id'], 'type' => '2'))->find();
                    if (empty($now_store_score)) {
                        $data_store_score['parent_id'] = $now_order['store_id'];
                        $data_store_score['type'] = '2';
                        $data_store_score['score_all'] = $score;
                        $data_store_score['reply_count'] = 1;
                        $database_merchant_score->data($data_store_score)->add();
                    } else {
                        $data_store_score['score_all'] = $now_store_score['score_all'] + $score;
                        $data_store_score['reply_count'] = $now_store_score['reply_count'] + 1;
                        $database_merchant_score->where(array('pigcms_id' => $now_store_score['pigcms_id']))->data($data_store_score)->save();
                    }
                }
                
				if($this->config['feedback_score_add']>0){
				  	D('User')->add_extra_score($this->user_session['uid'],$this->config['feedback_score_add'],$this->config['group_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name'],[
                        'mer_id'=>$now_order['mer_id'],
                        'store_id'=>$now_order['store_id'],
                        'order_id'=>$now_order['order_id'],
                        'order_type'=>'group',
                    ]);

					D('Scroll_msg')->add_msg('feedback',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'对'.$this->config['group_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name']);
				}

				$this->success_tips('评论成功',U('My/group_order',array('order_id'=>$now_order['order_id'])));
			}else{
				$this->error_tips('评论失败');
			}
		}
		$this->display();
	}

	/*订餐OR外卖评价*/
	public function meal_feedback(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$now_order = D('Meal_order')->where(array('uid' => $this->user_session['uid'], 'order_id' => intval($_GET['order_id'])))->find();
		$this->assign('now_order',$now_order);
		if(empty($now_order)){
			$this->error_tips('当前订单不存在！');
		}
		if(empty($now_order['paid'])){
			$this->error_tips('当前订单未付款！无法评论');
		}
		if(empty($now_order['status'])){
			$this->error_tips('当前订单未消费！无法评论');
		}
		if($now_order['status'] == 2){
			$this->error_tips('当前订单已评论');
		}
		if(IS_POST){
			$score = intval($_POST['score']);
			if($score > 5 || $score < 1){
				$this->error_tips('评分只能1到5分');
			}
			$inputimg=isset($_POST['inputimg']) ? $_POST['inputimg'] :'';
			$pic_ids=array();
			if(!empty($inputimg)){
				$database_reply_pic = D('Reply_pic');
				foreach($inputimg as $imgv){
					$imgv=str_replace('/upload/reply/meal/','',$imgv);
					$imgtmp=explode('/',$imgv);
					$imgname=$imgtmp[count($imgtmp)-1];
					$reply_pic['name'] = $imgname;
					$reply_pic['pic'] = str_replace('/'.$imgname,'',$imgv).','.$imgname;
					$reply_pic['uid'] = $this->user_session['uid'];
					$reply_pic['order_type'] = '1';
					$reply_pic['order_id'] = intval($now_order['order_id']);
					$reply_pic['add_time'] = $_SERVER['REQUEST_TIME'];
					$pic_ids[] = $database_reply_pic->data($reply_pic)->add();
				}
			}
			$database_reply = D('Reply');
			$data_reply['parent_id'] = $now_order['store_id'];
			$data_reply['store_id'] = $now_order['store_id'];
			$data_reply['mer_id'] = $now_order['mer_id'];
			$data_reply['score'] = $score;
			$data_reply['order_type'] = '1';
			$data_reply['order_id'] = intval($now_order['order_id']);
			$data_reply['anonymous'] = intval($_POST['anonymous']);
			$data_reply['comment'] = $_POST['comment'];
			$data_reply['uid'] = $this->user_session['uid'];
			$data_reply['pic'] = !empty($pic_ids) ? implode(',',$pic_ids):'';
			$data_reply['add_time'] = $_SERVER['REQUEST_TIME'];
			$data_reply['add_ip'] = get_client_ip(0);
			if ($database_reply->data($data_reply)->add()) {
				D('Merchant_store')->setInc_meal_reply($now_order['store_id'],$score);
				D('Meal_order')->change_status($now_order['order_id'],2);

				$database_merchant_score = D('Merchant_score');
				$now_merchant_score = $database_merchant_score->field('`pigcms_id`,`score_all`,`reply_count`')->where(array('parent_id'=>$now_order['mer_id'],'type'=>'1'))->find();
				if(empty($now_merchant_score)){
					$data_merchant_score['parent_id'] = $now_order['mer_id'];
					$data_merchant_score['type'] = '1';
					$data_merchant_score['score_all'] = $score;
					$data_merchant_score['reply_count'] = 1;
					$database_merchant_score->data($data_merchant_score)->add();
				}else{
					$data_merchant_score['score_all'] = $now_merchant_score['score_all']+$score;
					$data_merchant_score['reply_count'] = $now_merchant_score['reply_count']+1;
					$database_merchant_score->where(array('pigcms_id'=>$now_merchant_score['pigcms_id']))->data($data_merchant_score)->save();
				}
				$now_store_score=$database_merchant_score->field('`pigcms_id`,`score_all`,`reply_count`')->where(array('parent_id'=>$now_order['store_id'],'type'=>'2'))->find();
				if(empty($now_store_score)){
					$data_store_score['parent_id'] = $now_order['store_id'];
					$data_store_score['type'] = '2';
					$data_store_score['score_all'] = $score;
					$data_store_score['reply_count'] = 1;
					$database_merchant_score->data($data_store_score)->add();
				}else{
					$data_store_score['score_all'] = $now_store_score['score_all']+$score;
					$data_store_score['reply_count'] = $now_store_score['reply_count']+1;
					$database_merchant_score->where(array('pigcms_id'=>$now_store_score['pigcms_id']))->data($data_store_score)->save();
				}
				$type_msg = [
				    
                ];
                $desc_info = [
                    'mer_id'=>$now_order['mer_id'],
                    'store_id'=>$now_order['store_id'],
                    'order_id'=>$now_order['order_id'],
                    'order_type'=>'meal',
                ];
				if ($now_order['meal_type'] == 1) {
					if($this->config['feedback_score_add']>0){
					  	D('User')->add_extra_score($this->user_session['uid'],$this->config['feedback_score_add'],$this->config['group_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name'],$desc_info);
					  	D('Scroll_msg')->add_msg('feedback',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'对'.$this->config['group_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name']);
					}
					$this->success_tips('评论成功', U('Takeout/order_detail', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
				} else {
					if($this->config['feedback_score_add']>0){
					  	D('User')->add_extra_score($this->user_session['uid'],$this->config['feedback_score_add'],$this->config['group_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name'],$desc_info);

				  		D('Scroll_msg')->add_msg('feedback',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'对'.$this->config['group_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name']);
					}
					$this->success_tips('评论成功', U('Food/order_detail', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
				}
			} else{
				$this->error_tips('评论失败');
			}
		}
		$this->display();
	}

	/*预约评论*/
	public function appoint_feedback(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$order_id = $_GET['order_id'] + 0;
		$now_order = D('Appoint_order')->where(array('uid' => $this->user_session['uid'], 'order_id' => intval($_GET['order_id'])))->find();
		$this->assign('now_order',$now_order);
		if(empty($now_order)){
			$this->error_tips('当前订单不存在！');
		}

		if($now_order['type'] == 0){
			if(empty($now_order['service_status'])){
				$this->error_tips('当前订单未付款！无法评论');
			}
			if(empty($now_order['service_status'])){
				$this->error_tips('当前订单未消费！无法评论');
			}
		}

		$where['order_id'] = $order_id;
		$database_appoint_comment = D('Appoint_comment');
		$appoint_order_num = $database_appoint_comment->where($where)->count();
		if($appoint_order_num > 0){
			$this->error_tips('当前订单已评论');
		}
		$appoint = D('Appoint')->get_appoint_by_appointId($now_order['appoint_id']);
        $this->assign('now_appoint',$appoint);
        $database_merchant_workers = D('Merchant_workers');
        $database_appoint_visit_order_info = D('Appoint_visit_order_info');
        $database_appoint_supply = D('Appoint_supply');
        $Map['appoint_order_id'] = $now_order['order_id'];
        $appoint_visit_order_info = $database_appoint_visit_order_info->where($Map)->find();

        if(!$appoint_visit_order_info){
            $Map['order_id'] = $now_order['order_id'];
            $appoint_visit_order_info = $database_appoint_supply->where($Map)->find();
        }
        $merchant_workers_info = NULL;
        if($appoint_visit_order_info) {
            $_Map['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'] > 0 ? $appoint_visit_order_info['merchant_worker_id'] : $appoint_visit_order_info['worker_id'];
            $merchant_workers_info      = $database_merchant_workers->appoint_worker_info($_Map);
            if($merchant_workers_info){
                $serviceTime = $appoint_visit_order_info['end_time'] ??  $appoint_visit_order_info['add_time'];
                $merchant_workers_info['service_time'] = date('Y-m-d H:i', $serviceTime);
            }
        }
        $this->assign('merchant_workers_info',$merchant_workers_info);
		if(IS_POST){
			$score = intval($_POST['score']);
			$profession_total_score = $_POST['profession_score'] + 0;
			$communicate_total_score = $_POST['communicate_score'] + 0;
			$speed_total_score = $_POST['speed_score'] + 0;
			
            $professionScore = $profession_total_score;
            $communicateScore = $communicate_total_score;
            $speedScore = $speed_total_score;
            
            if(!empty($merchant_workers_info)){
                if($score > 5 || $score < 1 || $profession_total_score > 5 || $profession_total_score < 1 || $communicate_total_score > 5 || $communicate_total_score < 1|| $speed_total_score > 5 || $speed_total_score < 1 ){
                    $this->error_tips('评分只能1到5分');
                }
            }elseif($score > 5 || $score < 1){
                $this->error_tips('评分只能1到5分');
            }
			
            $totalScore = $score + $profession_total_score + $communicate_total_score + $speed_total_score;
			if($merchant_workers_info){//有服务人员的取评价平均分
                $averageScore = round(bcdiv($totalScore,4,2));
                $score = $averageScore;
            }else{
                $averageScore = $score; 
            }
			$inputimg=isset($_POST['inputimg']) ? $_POST['inputimg'] :'';
			$pic_ids=array();
			if(!empty($inputimg)){
				$database_reply_pic = D('Reply_pic');
				foreach($inputimg as $imgv){
					$imgv=str_replace('/upload/reply/appoint/','',$imgv);
					$imgtmp=explode('/',$imgv);
					$imgname=$imgtmp[count($imgtmp)-1];
					$reply_pic['name'] = $imgname;
					$reply_pic['pic'] = str_replace('/'.$imgname,'',$imgv).','.$imgname;
					$reply_pic['uid'] = $this->user_session['uid'];
					$reply_pic['order_type'] = '2';
					$reply_pic['order_id'] = intval($now_order['order_id']);
					$reply_pic['add_time'] = $_SERVER['REQUEST_TIME'];
					$pic_ids[] = $database_reply_pic->data($reply_pic)->add();
				}
			}
			$database_reply = D('Reply');
			$data_reply['parent_id'] = $now_order['appoint_id'];
			$data_reply['store_id'] = $now_order['store_id'];
			$data_reply['mer_id'] = $now_order['mer_id'];
			$data_reply['score'] = $averageScore;
			$data_reply['order_type'] = '2';
			$data_reply['order_id'] = intval($now_order['order_id']);
			$data_reply['anonymous'] = intval($_POST['anonymous']);
			$data_reply['comment'] = $_POST['comment'];
			$data_reply['uid'] = $this->user_session['uid'];
			$data_reply['pic'] = !empty($pic_ids) ? implode(',',$pic_ids):'';
			$data_reply['add_time'] = $_SERVER['REQUEST_TIME'];
			$data_reply['add_ip'] = get_client_ip(0);
			if ($database_reply->data($data_reply)->add()) {
				D('Appoint')->setInc_appoint_reply($now_order, $score);
				D('Appoint_order')->change_status($now_order['order_id'], 2);
                /*----系统订单信息-已评价 该系统订单状态为已评价---*/
                D('System_order')->save_order_status('appoint',$now_order['order_id'],3);

				$database_merchant_score = D('Merchant_score');
				$now_merchant_score = $database_merchant_score->field('`pigcms_id`,`score_all`,`reply_count`')->where(array('parent_id'=>$now_order['mer_id'],'type'=>'1'))->find();
				if(empty($now_merchant_score)){
					$data_merchant_score['parent_id'] = $now_order['mer_id'];
					$data_merchant_score['type'] = '1';
					$data_merchant_score['score_all'] = $score;
					$data_merchant_score['reply_count'] = 1;
					$database_merchant_score->data($data_merchant_score)->add();
				}else{
					$data_merchant_score['score_all'] = $now_merchant_score['score_all']+$score;
					$data_merchant_score['reply_count'] = $now_merchant_score['reply_count']+1;
					$database_merchant_score->where(array('pigcms_id'=>$now_merchant_score['pigcms_id']))->data($data_merchant_score)->save();
				}
				$now_store_score=$database_merchant_score->field('`pigcms_id`,`score_all`,`reply_count`')->where(array('parent_id'=>$now_order['store_id'],'type'=>'2'))->find();
				if(empty($now_store_score)){
					$data_store_score['parent_id'] = $now_order['store_id'];
					$data_store_score['type'] = '2';
					$data_store_score['score_all'] = $score;
					$data_store_score['reply_count'] = 1;
					$database_merchant_score->data($data_store_score)->add();
				}else{
					$data_store_score['score_all'] = $now_store_score['score_all']+$score;
					$data_store_score['reply_count'] = $now_store_score['reply_count']+1;
					$database_merchant_score->where(array('pigcms_id'=>$now_store_score['pigcms_id']))->data($data_store_score)->save();
				}

				//工作人员评分start
				if($appoint_visit_order_info){
                    if($merchant_workers_info){
                        $profession_total_score = $merchant_workers_info['profession_total_score'];
                        $communicate_total_score = $merchant_workers_info['communicate_total_score'];
                        $speed_total_score = $merchant_workers_info['speed_total_score'];
                        $profession_num = $merchant_workers_info['profession_num'];
                        $communicate_num = $merchant_workers_info['communicate_num'];
                        $speed_num = $merchant_workers_info['speed_num'];
                        
						$profession_total_score += $professionScore;
						$communicate_total_score += $communicateScore;
						$speed_total_score += $speedScore;
						$profession_num++;
						$communicate_num++;
						$speed_num++;

						$merchant_workers_data['profession_total_score'] = $profession_total_score;
						$merchant_workers_data['communicate_total_score'] = $communicate_total_score;
						$merchant_workers_data['speed_total_score'] = $speed_total_score;
						$merchant_workers_data['profession_num'] = $profession_num;
						$merchant_workers_data['communicate_num'] = $communicate_num;
						$merchant_workers_data['speed_num'] = $speed_num;
						$merchant_workers_data['profession_avg_score'] = $profession_total_score/$profession_num;
						$merchant_workers_data['communicate_avg_score'] = $communicate_total_score/$communicate_num;
						$merchant_workers_data['speed_avg_score'] = $speed_total_score/$speed_num;
						$merchant_workers_data['all_avg_score'] = ($merchant_workers_data['profession_avg_score'] + $merchant_workers_data['communicate_avg_score'] + $merchant_workers_data['speed_avg_score']) / 3;
						$merchant_workers_data['mer_id'] =  $now_order['mer_id'];
						$result = $database_merchant_workers->where($_Map)->data($merchant_workers_data)->save();
						if(!$result){
							$this->error_tips('工作人员评分失败！');
						}

						$database_appoint_comment = D('Appoint_comment');
						$_data['uid'] = $this->user_session['uid'];
						$_data['merchant_worker_id'] =  $appoint_visit_order_info['merchant_worker_id'] > 0 ? $appoint_visit_order_info['merchant_worker_id'] : $appoint_visit_order_info['worker_id'];
						$_data['appoint_id'] = $now_order['appoint_id'];
						$_data['profession_score'] = $professionScore;
						$_data['communicate_score'] = $communicateScore;
                        $_data['average_score'] = $averageScore;
						$_data['speed_score'] = $speedScore;
						$_data['score'] = $score;
						if($inputimg){
							$_data['comment_img'] = serialize($inputimg);
						}
						$_data['content'] = $_POST['comment'];
						$_data['add_time'] = time();
						$_data['order_id'] = $now_order['order_id'];
						$_data['mer_id'] = $now_order['mer_id'];

						if($database_appoint_comment->data($_data)->add()){
							$worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'] > 0 ?$appoint_visit_order_info['merchant_worker_id'] : $appoint_visit_order_info['worker_id'];
							$database_merchant_workers->where($worker_where)->setInc('comment_num');
							$database_appoint = D('Appoint');
							$database_appoint->where(array('appoint_id'=>$now_order['appoint_id']))->setInc('comment_num');
						}
					}else{
						$database_appoint_comment = D('Appoint_comment');
						$_data['score'] = $_POST['score'];
                        $_data['average_score'] = $averageScore;
						$_data['uid'] = $this->user_session['uid'];
						$_data['merchant_worker_id'] =  0;
						$_data['appoint_id'] = $now_order['appoint_id'];
						if($inputimg){
							$_data['comment_img'] = serialize($inputimg);
						}
						$_data['content'] = $_POST['comment'];
						$_data['add_time'] = time();
						$_data['order_id'] = $now_order['order_id'];
						$_data['mer_id'] = $now_order['mer_id'];
						$database_appoint_comment->data($_data)->add();
					}
				}
				//工作人员评分end
				if($this->config['feedback_score_add']>0){
				  	D('User')->add_extra_score($this->user_session['uid'],$this->config['feedback_score_add'],$this->config['appoint_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name'],[
                        'mer_id'=>$now_order['mer_id'],
                        'store_id'=>$now_order['store_id']??0,
                        'order_id'=>$now_order['order_id'],
                        'order_type'=>'appoint', 
                    ]);
			  		D('Scroll_msg')->add_msg('feedback',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'对'.$this->config['appoint_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name']);
				}
				$recordContent['content'] = [
					[
						'title' => '评价内容',
						'value' => $_POST['comment'] ?:'',
						'type'  => 'text'
					]
				];
				D('Appoint_order_records')->addOrderRecord($now_order['mer_id'], $now_order['order_id'], '用户评价', $recordContent);
				$this->success_tips('评论成功', U('My/appoint_order', array('order_id' => $now_order['order_id'])));
			} else{
				$this->error_tips('评论失败');
			}
		}
		$this->display();
	}

	/*全部订餐订单列表*/
	public function meal_order_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$where = " uid={$this->user_session['uid']} AND status<=3";//array('uid' => $this->user_session['uid'], 'status' => array('lt', 3));
		if ($status == -1) {
			$where .= " AND paid=0";
			$where['paid'] = 0;
		} elseif ($status == 1) {
			$where .= " AND paid=1 AND status=0";
		} elseif ($status == 2) {
			$where .= " AND paid=1 AND status=1";
		}
// 		$status == -1 && $where['paid'] = 0;
// 		$status == 1 && $where['status'] = 0;
// 		$status == 2 && $where['status'] = 1;

 		$where .= " AND is_del = 0";
		$order_list = D("Meal_order")->field(true)->where($where)->order('order_id DESC')->select();
		//$temp = $store_ids = array();
		foreach ($order_list as $st) {
			$store_ids[] = $st['store_id'];
		}
		$m = array();
		if ($store_ids) {
			$store_image_class = new store_image();
			$merchant_list = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_list as $li) {
				$images = $store_image_class->get_allImage_by_path($li['pic_info']);
				$li['image'] = $images ? array_shift($images) : array();
				unset($li['status']);
				$m[$li['store_id']] = $li;
			}
		}
		$list = array();
		foreach ($order_list as $ol) {
			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$list[] = array_merge($ol, $m[$ol['store_id']]);
			} else {
				$list[] = $ol;
			}
		}

		foreach($list as $key=>$val){
			$list[$key]['order_url'] = U('Meal/order_detail', array('mer_id' => $val['mer_id'], 'store_id' => $val['store_id'], 'order_id' => $val['order_id']));
		}

		$this->assign('order_list', $list);

		$this->display();
	}

	public function ajax_meal_order_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$where = " uid={$this->user_session['uid']} AND status<=3";//array('uid' => $this->user_session['uid'], 'status' => array('lt', 3));
		if ($status == -1) {
			$where .= " AND paid=0";
			$where['paid'] = 0;
		} elseif ($status == 1) {
			$where .= " AND paid=1 AND status=0";
		} elseif ($status == 2) {
			$where .= " AND paid=1 AND status=1";
		}
// 		$status == -1 && $where['paid'] = 0;
// 		$status == 1 && $where['status'] = 0;
// 		$status == 2 && $where['status'] = 1;
 		$where .= " AND is_del = 0";
		$order_list = D("Meal_order")->field(true)->where($where)->order('order_id DESC')->select();
		//$temp = $store_ids = array();
		foreach ($order_list as $st) {
			$store_ids[] = $st['store_id'];
		}
		$m = array();
		if ($store_ids) {
			$store_image_class = new store_image();
			$merchant_list = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_list as $li) {
				$images = $store_image_class->get_allImage_by_path($li['pic_info']);
				$li['image'] = $images ? array_shift($images) : array();
				unset($li['status']);
				$m[$li['store_id']] = $li;
			}
		}
		$list = array();
		foreach ($order_list as $ol) {
			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$list[] = array_merge($ol, $m[$ol['store_id']]);
			} else {
				$list[] = $ol;
			}
		}

		foreach($list as $key=>$val){
			$list[$key]['order_url'] = U('meal/order_detail', array('mer_id' => $val['mer_id'], 'store_id' => $val['store_id'], 'order_id' => $val['order_id']));
		}

		if(!empty($list)){
			exit(json_encode(array('status'=>1,'order_list'=>$list)));
		}else{
			exit(json_encode(array('status'=>0,'order_list'=>$list)));
		}
	}

	/*全部快店订单列表*/
	public function shop_order_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$where = "is_del=0 AND uid={$this->user_session['uid']}";//array('uid' => $this->user_session['uid'], 'status' => array('lt', 3));

		if(!empty($_GET['store_id'])){
			$where .= " AND store_id=".intval($_GET['store_id']);
		}
		if(!empty($_GET['mer_id'])){
			$where .= " AND mer_id=".intval($_GET['mer_id']);
		}

		if ($status == -1) {
			$where .= " AND paid=0 AND (expect_use_time>".time().' OR is_pick_in_store>=2 OR  expect_use_time=0)';
		} elseif ($status == 1) {
			$where .= " AND paid=1 AND status<2";
		} elseif ($status == 2) {
			$where .= " AND paid=1 AND status=2";
		}

// 		$status == -1 && $where['paid'] = 0;
// 		$status == 1 && $where['status'] = 0;
// 		$status == 2 && $where['status'] = 1;

		//除去商城订单
		$where.=' and order_from!=1';
 		$where .= " AND is_del = 0";
		$order_list = D("Shop_order")->get_order_list($where, 'order_id DESC', 11);//field(true)->where($where)->order('order_id DESC')->select();
		$order_list = $order_list['order_list'];
		//$temp = $store_ids = array();
		foreach ($order_list as $st) {
			$store_ids[] = $st['store_id'];
		}
		$m = array();
		if ($store_ids) {
			$store_image_class = new store_image();
			$merchant_list = D(_view("Merchant_store"))->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_list as $li) {
				if($li['logo']){
					$li['image'] = replace_file_domain($li['logo']);
				}else{
					$images = $store_image_class->get_allImage_by_path($li['pic_info']);
					$li['image'] = $images ? array_shift($images) : array();
				}
				unset($li['status']);
				$m[$li['store_id']] = $li;
			}
		}
		$list = array();
		foreach ($order_list as $ol) {
			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$list[] = array_merge($ol, $m[$ol['store_id']]);
			} else {
				$list[] = $ol;
			}
		}

		foreach($list as $key=>$val){
			if($val['paid']==1 && $val['status']==3){
				$where=array();
				$where['order_id']=$val['order_id'];
				$goods_type_num=M('Shop_order_detail')->where($where)->count(1);
				$list[$key]['goods_type_num']=$goods_type_num;
			}
			$list[$key]['order_url'] = U('Shop/status', array('order_id' => $val['order_id']));
		}

		// 是否有待评价订单
		$order_reply = M('Shop_order')->where(array('status'=>2,'paid'=>1,'uid'=>$this->user_session['uid']))->order('last_time DESC')->find();
		if ($order_reply) {
			if (C('config.feedback_score_add')) {
				$order_reply_text = L_('您有一笔待评价订单，评价后即可获得X1X2哦！',array('X1'=>C('config.feedback_score_add'),'X2'=>C('config.score_name')));
			}else{
				$order_reply_text = L_('您有一笔待评价订单,参与好评商家可能会意外奖励哦！');
			}
		}else{
			$order_reply_text = '';
		}
		$this->assign('order_list', $list);
		$this->assign('order_reply', $order_reply_text);
		$this->assign('order_reply_id', $order_reply['order_id']);
		$this->display();
	}

	public function ajax_shop_order_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$where = "is_del=0 AND uid={$this->user_session['uid']}";//array('uid' => $this->user_session['uid'], 'status' => array('lt', 3));
		if(!empty($_GET['store_id'])){
			$where .= " AND ord.store_id=".intval($_GET['store_id']);
		}
		if(!empty($_GET['mer_id'])){
			$where .= " AND ord.mer_id=".intval($_GET['mer_id']);
		}
		if ($status == -1) {		//待付款
			$where .= " AND paid=0 AND (expect_use_time>".time().' OR is_pick_in_store>=2 OR  expect_use_time=0)';
		} elseif ($status == 1) {	//未消费
			$where .= " AND paid=1 AND ord.status<2";
		} elseif ($status == 2) {	//待评价
			$where .= " AND paid=1 AND ord.status=2";
		}else if($status==3){	//待发货
			$where.=' and paid=1 and ord.status=0';
		}else if($status==4){	//待收货
			$where.=' and paid=1 and ord.status=1';
		}elseif($status==5){	//退款申请待处理
			$where.=' and ord.status in (2,3) and is_apply_refund=1';
		}elseif($status==6){	//退款申请已处理
			$where=str_replace('uid', 'ord.uid', $where);
		}

		$is_mall=I('get.is_mall',0,'intval');
		if($is_mall==1){
			$where.=' and order_from=1';
			if($status==0 || $status==-1){
				//商城订单去除无效的待付款活动订单
				$where.=' and (paid=1 or (paid=0 and group_id=0 and bar_id=0))';
			}			
		}else{
			$where.=' and order_from!=1';
		}

		$join = ' LEFT JOIN '.C('DB_PREFIX').'merchant_store as s on s.store_id=ord.store_id';
		if($status==6){		//退款申请已处理且无正在申请中的
			$sql=D('Shop_order')->alias('ord')->join('inner join '.C('DB_PREFIX').'shop_order_refund re on ord.order_id=re.order_id')->join($join)->where($where)->field('ord.*,s.name,sum(case when re.status=0 then 1 else 0 end) as apply_num')->group('ord.order_id')->having('apply_num=0')->buildSql();
			$order_list=M('')->table($sql.' as t')->order('order_id desc')->select();
		}else{
			$order_list = D("Shop_order")->alias('ord')->field('ord.*,s.name')->join($join)->where($where)->order('order_id DESC')->select();
			// var_dump(D("Shop_order")->_sql());
		}
		$get_goods=I('get.get_goods',0,'intval');
		if($get_goods==1){
			foreach ($order_list as $k=>$st) {
				$st['goods_list']=D('Shop_order')->get_good_details($st['order_id']);
				$order_list[$k]=$st;

				$store_ids[] = $st['store_id'];
			}
		}else{
			foreach ($order_list as $st) {
				$store_ids[] = $st['store_id'];
			}
		}
		$m = array();
		if ($store_ids) {
			$store_image_class = new store_image();
			$merchant_list = D(_view("Merchant_store"))->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_list as $li) {
				if($li['logo']){
					$li['image'] = replace_file_domain($li['logo']);
				}else{
					$images = $store_image_class->get_allImage_by_path($li['pic_info']);
					$li['image'] = $images ? array_shift($images) : array();
				}
				unset($li['status']);
				$m[$li['store_id']] = $li;
			}
		}
		$list = array();
		foreach ($order_list as $ol) {
			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$list[] = array_merge($ol, $m[$ol['store_id']]);
			} else {
				$list[] = $ol;
			}
		}

		foreach($list as $key=>$val){
			if($val['paid']==1 && $val['status']==3){
				$where=array();
				$where['order_id']=$val['order_id'];
				$goods_type_num=M('Shop_order_detail')->where($where)->count(1);
				$list[$key]['goods_type_num']=$goods_type_num;
			}
			if($is_mall==1){
				$list[$key]['order_url'] = U('Mall/status', array('order_id' => $val['order_id']));
			}else{
				$list[$key]['order_url'] = U('Shop/status', array('order_id' => $val['order_id']));
			}			
		}

		if(!empty($list)){
			exit(json_encode(array('status'=>1,'order_list'=>$list)));
		}else{
			exit(json_encode(array('status'=>0,'order_list'=>$list)));
		}
	}

	/*优惠券列表*/
	public function card_list(){
		redirect(get_base_url("pages/coupon/myCoupon"));

		// if(!$this->is_weixin_browser){
		// $this->error_tips('请使用微信浏览优惠券！');
		// }
		$use = empty($_GET['use']) ? '0' : '1';

		//合并 商家优惠券 + 平台优惠券
        $coupon_list_mer = D('Card_new_coupon')->get_user_all_coupon_list($this->user_session['uid']);
//var_dump($coupon_list_mer);exit();
        //商家优惠券 增加商家名称
        foreach ($coupon_list_mer as $key => $value) {
            $merchant_info = M('Merchant')->field('name')->where(['status'=>1,'mer_id'=>$value['mer_id']])->find();
            $coupon_list_mer[$key]['merchant_name'] = $merchant_info['name'];
            $store = explode(',', $value['store_id']);
            if(count($store) == 1) {  //单店铺
                $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'].'/packapp/plat/pages/store/homePage?store_id='.$value['store_id'];
            } else { //多店铺
                $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'].'/packapp/plat/pages/store/storeList?coupon_id='.$value['coupon_id'];
            }
        }

        //平台优惠券
        $coupon_list_system = D('System_coupon')->get_user_coupon_list($this->user_session['uid'], $this->user_session['phone']);

        foreach ($coupon_list_system as $key => $value) {
            if($value['use_type'] == 1) {
                $store_ids =  M('System_coupon_store')->field('store_id')->where(['coupon_id'=>$value['coupon_id']])->select();
                if(count($store_ids) == 1) { //单店铺
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'].'/packapp/plat/pages/store/homePage?store_id='.$store_ids[0]['store_id'];
                } else { //多店铺
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'].'/packapp/plat/pages/store/storeList?coupon_id='.$value['coupon_id'].'&coupon_type=system';
                }
            } else {
                if($value['cate_name'] == '社区拼团') { //跳转 社区团购首页
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'].'/wap.php?g=Wap&c=Village_group&a=index';
                } else {
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'].'/packapp/plat/pages/store/storeList?coupon_id='.$value['coupon_id'].'&coupon_type=system';
                }

            }
        }

        $coupon_list = $coupon_list_mer;
        foreach($coupon_list_system as $key => $value) {
            $coupon_list[] = $value;
        }
        $this->assign('cate_platform', D('System_coupon')->cate_platform());

//		if($_GET['coupon_type']=='mer') {
//			$coupon_list = D('Card_new_coupon')->get_user_all_coupon_list($this->user_session['uid']);
//			$this->assign('cate_platform', D('Card_new_coupon')->cate_platform());
//		}else{
//			$coupon_list=array();
//			$coupon_list = D('System_coupon')->get_user_coupon_list($this->user_session['uid'], $this->user_session['phone']);
//
//			$this->assign('cate_platform', D('System_coupon')->cate_platform());
//		}
//        var_dump(count($coupon_list));exit();
		$tmp = array();
		foreach ($coupon_list as $key => $v) {

			 //查看使用期限是否到期
            // 用户可使用的天数
            if ($v['valid_time']) {
                if ($v['receive_time']<$v['start_time']) {
                    $end_time = $v['start_time'] + $v['valid_time']*86400;
                }else{
                	$v['start_time'] = $v['receive_time'];
                    $end_time = $v['receive_time'] + $v['valid_time']*86400;
                }
                $v['end_time'] = $end_time>$v['end_time'] ? $v['end_time'] : $end_time;
               	if($v['end_time']<$_SERVER['REQUEST_TIME']&&$v['is_use']!=1){
	                $v['is_use'] = 2;
	            }
            }


            if($v['coupon_type'] == 'mer') {   //商家券
                if (!empty($tmp[$v['is_use']][$v['coupon_id']])) {
                    $tmp[$v['is_use']][$v['coupon_id']]['get_num']++;
                } else {
                    $tmp[$v['is_use']][$v['coupon_id']] = $v;
                    $mer = M('Merchant')->where(array('mer_id'=>$v['mer_id']))->find();
                    $tmp[$v['is_use']][$v['coupon_id']]['merchant']=$mer['name'];
                    $tmp[$v['is_use']][$v['coupon_id']]['get_num'] = 1;
                    switch($v['type']){
                        case 'all':
                            $url = $this->config['site_url'].'/wap.php';
                            break;
                        case 'group':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Group&a=index';
                            break;
                        case 'meal':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Meal_list&a=index';
                            break;
                        case 'appoint':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Appoint&a=index';
                            break;
                        case 'shop':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Shop&a=index';
                            break;
                        case 'mall':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Mall&a=index';
                            break;
                        case 'village_group':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Village_group&a=index';
                            break;
                    }
                    $tmp[$v['is_use']][$v['coupon_id']]['url'] = $url;
                }

                $tmp[$v['is_use']][$v['coupon_id']]['designated_shop'] = '';
            } elseif($v['coupon_type'] == 'system') {
                if (!empty($tmp[$v['is_use']][$v['coupon_id']])) {
                    $tmp[$v['is_use']][$v['coupon_id']]['get_num']++;
                } else {
                    $tmp[$v['is_use']][$v['coupon_id']] = $v;
                    $mer = M('Merchant')->where(array('mer_id'=>$v['mer_id']))->find();
                    $tmp[$v['is_use']][$v['coupon_id']]['merchant']=$mer['name'];
                    $tmp[$v['is_use']][$v['coupon_id']]['get_num'] = 1;
                    switch($v['type']){
                        case 'all':
                            $url = $this->config['site_url'].'/wap.php';
                            break;
                        case 'group':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Group&a=index';
                            break;
                        case 'meal':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Meal_list&a=index';
                            break;
                        case 'appoint':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Appoint&a=index';
                            break;
                        case 'shop':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Shop&a=index';
                            break;
                        case 'mall':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Mall&a=index';
                            break;
                        case 'village_group':
                            $url = $this->config['site_url'].'/wap.php?g=Wap&c=Village_group&a=index';
                            break;
                    }
                    $tmp[$v['is_use']][$v['coupon_id']]['url'] = $url;
                }

                $tmp[$v['is_use']][$v['coupon_id']]['designated_shop'] = '';
            }

            if ($v['use_type'] == 1) {
                $store_names = M('System_coupon_store')->alias('cs')
                    ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = cs.store_id')
                    ->field('s.name')
                    ->where(['cs.coupon_id' => $v['coupon_id']])
                    ->order('cs.id ASC')
                    ->limit(2)
                    ->getField('name', true);
                if ($store_names) {
                    $tmp[$v['is_use']][$v['coupon_id']]['designated_shop'] = count($store_names) > 1 ? $store_names[0] . L_('等店铺') : $store_names[0];
                }
            }

            //合并 优惠券
            $tmp_combine[$v['is_use']][] = $tmp[$v['is_use']][$v['coupon_id']];
            unset($tmp[$v['is_use']][$v['coupon_id']]);
		}

		$this->assign('coupon_list', $tmp_combine);
		$this->display();
	}

	public function cards()
	{
//		$card_list = D('Member_card_set')->get_all_card($this->user_session['uid']);
//		$this->assign('card_list',$card_list);
		//新商家会员卡
		$uid = $this->user_session['uid'];

		$card_list = D('Card_new')->get_user_all_card($uid);
		$this->assign('card_list',$card_list);

		$this->display('card_new');
	}

	public function order_list()
	{
		$type = isset($_GET['type']) ? intval($_GET['type']) : 1 ;
		if ($type == 1) {
			$order_list = D('Group')->wap_get_order_list($this->user_session['uid']);
			$this->assign('order_list',$order_list);
		} else {
			$where = array('uid' => $this->user_session['uid'], 'status' => array('lt', 3));
			$order_list = D("Meal_order")->field(true)->where($where)->order('order_id DESC')->select();
			$temp = $store_ids = array();
			foreach ($order_list as $st) {
				$store_ids[] = $st['store_id'];
			}
			$m = array();
			if ($store_ids) {
				$store_image_class = new store_image();
				$merchant_list = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
				foreach ($merchant_list as $li) {
					$images = $store_image_class->get_allImage_by_path($li['pic_info']);
					$li['image'] = $images ? array_shift($images) : array();
					unset($li['status']);
					$m[$li['store_id']] = $li;
				}
			}
			$list = array();
			foreach ($order_list as $ol) {
				if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
					$list[] = array_merge($ol, $m[$ol['store_id']]);
				} else {
					$list[] = $ol;
				}
			}
			$this->assign('order_list', $list);
		}
		$this->assign('type', $type);
		$this->display();
	}

	public function join_activity(){
		$uid = $this->user_session['uid'];
		import('@.ORG.wap_group_page');
		$tp_count = D('')->table(array(C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'merchant'=>'m'))->where("`ear`.`activity_list_id`=`eal`.`pigcms_id` AND `eal`.`mer_id`=`m`.`mer_id` AND `ear`.`uid`='$uid'")->group('`eal`.`pigcms_id`')->count();
		$P = new Page($tp_count,20,'page');
		$order_list = D('')->field('`eal`.`name` AS `product_name`,`m`.`name` AS `merchant_name`,`eal`.*,`m`.*')->table(array(C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'merchant'=>'m'))->where("`ear`.`activity_list_id`=`eal`.`pigcms_id` AND `eal`.`mer_id`=`m`.`mer_id` AND `ear`.`uid`='$uid'")->group('`eal`.`pigcms_id`')->order('`eal`.`pigcms_id` DESC')->limit($P->firstRow.','.$P->listRows)->select();
		if($order_list){
			$extension_image_class = new extension_image();
			foreach($order_list as &$value){
				$value['list_pic'] = $extension_image_class->get_image_by_path(array_shift(explode(';',$value['pic'])),'s');
				$value['url'] = U('My/join_activity_detail',array('id'=>$value['pigcms_id']));
				$value['money'] = floatval($value['money']);
				$value['type_txt'] = $this->activity_type_txt($value['type']);
			}
		}
		$this->assign('order_list',$order_list);
		$this->assign('pagebar',$P->show());
		$this->display();
	}

	public function join_activity_detail(){
		$condition_extension_activity_list['pigcms_id'] = $_GET['id'];
		$now_activity = D('Extension_activity_list')->field(true)->where($condition_extension_activity_list)->find();
		if(empty($now_activity)){
			$this->error_tips('该活动不存在');
		}
		$now_activity['type_txt'] = $this->activity_type_txt($now_activity['type']);
		$extension_image_class = new extension_image();
		$now_activity['list_pic'] = $extension_image_class->get_image_by_path(array_shift(explode(';',$now_activity['pic'])),'s');
		$now_activity['url'] = U('Wapactivity/detail',array('id'=>$now_activity['pigcms_id']));
		$now_activity['money'] = floatval($now_activity['money']);

		//活动归属的商家信息
		$now_merchant = D('Merchant')->field(true)->where(array('mer_id'=>$now_activity['mer_id']))->find();

		$record_list = D('Extension_activity_record')->field(true)->where(array('activity_list_id'=>$now_activity['pigcms_id'],'uid'=>$this->user_session['uid']))->order('`pigcms_id` DESC')->select();
		if(empty($record_list)){
			$this->error_tips('您未参与该活动');
		}
		$record_id_arr = array();
		foreach($record_list as $value){
			$record_id_arr[] = $value['pigcms_id'];
		}
		if($now_activity['type'] == 1){
			$number_list = D('Extension_yiyuanduobao_record')->field('`number`')->where(array('record_id'=>array('in',$record_id_arr)))->select();
			// shuffle($number_list);
			$this->assign('number_list',$number_list);
		}else if($now_activity['type'] == 2){
			$number_list = D('Extension_coupon_record')->field('`number`,`check_time`')->where(array('record_id'=>array('in',$record_id_arr)))->select();
			$this->assign('number_list',$number_list);
		}
		$this->assign('now_merchant',$now_merchant);
		$this->assign('now_activity',$now_activity);
		$this->assign('number_list',$number_list);
		$this->display();
	}

	protected function activity_type_txt($type){
		switch($type){
			case '1':
				return $this->config['activity_score_alias'];
			case '2':
				return '优惠券';
			case '3':
				return '秒杀';
			case '4':
				return '红包';
			case '5':
				return '卡券';
		}
	}

	public function join_lottery()
	{
		$result = D('Lottery')->join_lottery($this->user_session['uid']);
		$this->assign($result);
		$this->display();
	}

	public function follow_merchant()
	{
		$mod = new Model();
//		$sql = "SELECT b.* FROM  ". C('DB_PREFIX') . "merchant_user_relation AS a INNER JOIN  ". C('DB_PREFIX') . "merchant as b ON a.mer_id=b.mer_id WHERE a.openid='onfo6t5WPe6wJswql3ljRX9aeEUA'";
		$sql = "SELECT b.* FROM  ". C('DB_PREFIX') . "merchant_user_relation AS a INNER JOIN  ". C('DB_PREFIX') . "merchant as b ON a.mer_id=b.mer_id WHERE a.openid='{$this->user_session['openid']}'";
		$res = $mod->query($sql);
		$merchant_image_class = new merchant_image();
		foreach ($res as &$r) {
			$images = explode(";", $r['pic_info']);
			$images = explode(";", $images[0]);
			$r['fans_count'] = M('Merchant_user_relation')->where(array('mer_id'=>$r['mer_id']))->count();
			$r['img'] = $merchant_image_class->get_image_by_path($images[0]);
			$r['url'] = C('config.site_url').'/wap.php?c=My_card&a=merchant_card&mer_id=' . $r['mer_id'];
		}
		$this->assign('follow_list', $res);
		$this->display();
	}

	public function cancel_follow()
	{

		$mer_id = isset($_GET['mer_id']) ? intval($_GET['mer_id']) : 0;
		if (D('Merchant_user_relation')->where(array('mer_id' => $mer_id, 'openid' => $_SESSION['user']['openid']))->delete()) {
			D('Merchant')->where(array('mer_id' => $mer_id, 'fans_count' => array('gt', 0)))->setDec('fans_count');
			$this->success('取消关注成功', U('My/follow_merchant'));
		} else {

			$this->error('取消关注失败，请稍后重试', U('My/follow_merchant'));
		}
	}

	public function recharge(){
		if($this->config['nmgzhcs_appid'] && $this->user_session['openid']){
			redirect($this->config['nmgzhcs_base_url'].'/wxmember/recharge');
		}
		
		if( $this->config['open_user_recharge']==0){
			$this->error_tips('平台充值已关闭');
		}
		if($_POST['money']>0){
			if(IS_POST){
				$data_user_recharge_order['uid'] = $this->now_user['uid'];
				$money = floatval($_POST['money']);
				$upLevel = $_POST['label_level']??0;
				if ($upLevel) {
					$nowUpLevel = D('User_level')->where(['level' => $upLevel])->find();
					$nowUser = D('User')->where(['uid' => $this->now_user['uid']])->find();
					if (empty($nowUpLevel) || empty($nowUser)) {
						$this->error_tips('获取充值信息失败');
					}
					$leftMoney = floatval($nowUpLevel['use_money'] - $nowUser['now_money']);
					if ($leftMoney < 0 || $leftMoney != $money) {
						$this->error_tips('充值金额有误');
					}
				}
				if($this->config['recharge_hundreds']==1){
					$hundreds = $money/100;
					$little = $money%100;
					if($hundreds<1 || $little>0){
						$this->error_tips('充值必须是100的整数倍！');
					}
				}
				if(empty($money) || $money > 200000){
					$this->error_tips('请输入有效的金额！最高不能超过20万'.C('config.Currency_txt').'。');
				}
                if($_POST['label']){
                    $data_user_recharge_order['label'] = $_POST['label'];
                }
				$data_user_recharge_order['money'] = $money;
				// $data_user_recharge_order['order_name'] = '帐户余额在线充值';
				$data_user_recharge_order['add_time'] = $_SERVER['REQUEST_TIME'];
				$data_user_recharge_order['is_mobile_pay'] = 1;

				if($order_id = D('User_recharge_order')->data($data_user_recharge_order)->add()){
					if(IS_AJAX){
						if($_GET['type']=='gift'){
							$this->success(array('order_id'=>$order_id,'type'=>'gift'));
						}elseif($_GET['type']=='classify') {
							$this->success(array('order_id'=>$order_id,'type'=>'classify'));
						}elseif($_POST['type']=='level') {
							$this->success(array('order_id'=>$order_id,'type'=>'recharge'));
						}else{
							$this->success(array('order_id'=>$order_id,'type'=>'recharge'));
						}
					}else{
						if($_GET['type']=='gift'){
							redirect(U('Pay/check',array('order_id'=>$order_id,'type'=>'gift')));
						}elseif($_GET['type']=='classify') {
							redirect(U('Pay/check',array('order_id'=>$order_id,'type'=>'classify')));
						}elseif($_POST['type']=='level') {
							redirect(U('Pay/check',array('order_id'=>$order_id,'type'=>'recharge')));
						}else{
							redirect(U('Pay/check',array('order_id'=>$order_id,'type'=>'recharge')));
						}
					}
				}
			}else{
				$this->error('下单失败，请重试');
			}
		}else{
			$price_arr = array(100,200,300,400,600,800);
			$this->assign('price_arr',$price_arr);
        	$recharge_discount_list = M('Recharge_discount')->field(true)->where(array('status'=>1))->select();
			$this->assign('recharge_discount_list',$recharge_discount_list);
			$this->display('recharge');
		}
	}

	public function withdraw(){
		if($this->config['company_pay_open']=='0') {
			$this->error_tips('平台没有开启提现功能！');
		}
		if($this->config['open_allinyun']==1){
			redirect(U('setAccountDeposit/withdraw'));
		}

		$user_info = M('User')->where(array('uid'=>$this->user_session['uid']))->find();
		if($user_info['score_recharge_money']>0 && $user_info['now_money']<$user_info['score_recharge_money']){
			$this->error_tips('用户积分兑换余额数据错误');
		}
		$can_withdraw_money = $user_info['now_money']>=$user_info['score_recharge_money']?floatval((int)(($user_info['now_money']-$user_info['score_recharge_money'])*100)/100):$user_info['now_money'];
		if($can_withdraw_money<0){
			$this->error_tips('可提现金额错误');
		}
		if ($user_info['frozen_time'] < $_SERVER['REQUEST_TIME'] && $_SERVER['REQUEST_TIME'] < $user_info['free_time']) {
			$user_info['can_withdraw_money'] = $can_withdraw_money-$user_info['frozen_money']>0? $can_withdraw_money-$user_info['frozen_money']:0;
		}else{
			$user_info['can_withdraw_money'] = $can_withdraw_money;
		}
		$this->assign('user_info',$user_info);
		if(empty($user_info['is_follow'])){
			if($_POST['pay_type']==2){
                $this->ajaxReturn([
                    'info' => '您没有绑定微信',
                    'status' => 3,
                ]);
			}
		}
		if(IS_POST){
			if($_POST['money']>0){
				$money = $_POST['money'];
				if($money<$this->config['company_least_money']){
					$this->error_tips('不能低于最低提款额 '.$this->config['company_least_money'].C('config.Currency_txt').'!');
				}

				if($money>$can_withdraw_money){
					$this->error_tips('提款超出限额，申请失败！');
				}

        		//提现到微信要先实名认证
				if($_POST['pay_type'] == 2){
					// 实名认证
					$user_authentication = M('User_authentication')->where(['uid' => $this->user_session['uid']])->find();
					if(empty($user_authentication)){
						$return = [
							'info' => '提现前，请先实名认证哦~',
							'status' => 2,
							'url' => C('site_url').D('Village_house')->base_url.'packapp/plat/pages/my/authentication?back='.urlencode('/wap.php?g=Wap&c=Fenrun&a=withdraw')
						];
						$this->AjaxReturn($return);
					}elseif($user_authentication['authentication_status'] != 1){
						$return = [
							'info' => '提现前，请先实名认证哦~',
							'url' => C('site_url').D('Village_house')->base_url.'packapp/plat/pages/my/authentication_result?back='.urlencode('/wap.php?g=Wap&c=Fenrun&a=withdraw'),
							'status' => 2
						];
						$this->AjaxReturn($return);
					}
				}

				$data_companypay['pay_type'] = 'user';
				$data_companypay['pay_id'] = $user_info['uid'];
				$data_companypay['openid'] = $user_info['openid'];
				$data_companypay['nickname'] = $_POST['truename'];
				$data_companypay['phone'] = $user_info['phone'];
				$data_companypay['money'] = round($money*((100-$this->config['company_pay_user_percent'])/100),2)*100;
				$data_companypay['service_charges'] = $money*100-$data_companypay['money'];
				$data_companypay['desc'] = "用户提现{$money}".C('config.Currency_txt')."，用户ID: ".$user_info['uid'] ;
				if($this->config['company_pay_user_percent']>0){
					$data_companypay['desc'] .= '|手续费 '.getFormatNumber($data_companypay['service_charges']/100) .' 比例 '.$this->config['company_pay_user_percent'].'%';
				}
				$data_companypay['status'] = 0;
				$data_companypay['add_time'] = time();
				
				// 开启事务
				$database = D('');
				$database->startTrans();

				$use_result = D('User')->user_money($user_info['uid'],$money,'提款 '.$money.' 扣除余额'.$data_companypay['desc'],0,0,1);

				if($use_result['error_code']){
					$database->rollback();// 回滚事务
					$this->error_tips($use_result['msg']);
				}else{
					$msg = L_('申请成功，请等待审核！');
					if(C('config.open_real_time_withdrawal')){// 开启实时提现立马到账不需审核
						//请求第三方接口
						import('@.ORG.withdraw.Weixin');
						$weixinObj = new Weixin();
						$param['money'] = $data_companypay['money'];
						$param['openid'] = $data_companypay['openid'];
						$param['name'] = $data_companypay['nickname'];
						$param['partner_trade_no'] = date('YmdHis').$this->user_session['uid'];
						$result = $weixinObj->withdraw($param);
						if($result['error']){
							$database->rollback();// 回滚事务
							$this->error_tips($result['msg']);
						}

						// 修改状态
						$data_companypay['status'] = 1;
						$data_companypay['pay_time'] =  time();
						$data_companypay['third_id'] = $result['result']['payment_no'];//第三放订单id
						
						$msg = L_('提现成功！');
					}

					$database->commit();// 完成事务
					D('Companypay')->add($data_companypay);

					D('Scroll_msg')->add_msg('user_withdraw',$user_info['uid'],'用户'.str_replace_name($user_info['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']). '提现成功！');
					if($_POST['pay_type']==2){
						if($this->config['open_score_fenrun']){
							$url = U('My/money_list');
						}else{
							$url =U('My/transaction');
						}
						$this->success($msg,$url);die;
					}
					$this->success_tips($msg);die;
				}
			}else{
				$this->error_tips('数据不正确！');
			}
		}else{
			$where['pay_type']='user';
			$where['pay_id']=$user_info['uid'];
			$withdraw = M('Companypay');
			$count_withdraw = $withdraw->where($where)->count();
			import('@.ORG.system_page');
			$p = new Page($count_withdraw, 5);
			$withdraw_list = $withdraw->field('money,status,add_time,pay_time')->where($where)->order('pigcms_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
			$pagebar = $p->show();
			$share = new WechatShare($this->config,$_SESSION['openid']);
			$this->BioAuthticMethod = $share->getBioAuthticMethod($this->static_path);
			$this->assign('BioAuthticMethod', $this->BioAuthticMethod);
			$this->assign('pagebar', $pagebar);
			$this->assign('draw_info',$withdraw_list);
			$this->display();
		}
	}

	//积分充值余额，这部分余额不能提现
	public function score_recharge(){
		if(!$this->config['score_recharge']||$this->config['open_extra_price']==1) {
			$this->error_tips('平台没有开启'.$this->config['score_name'].'兑换余额功能！');
		}
		$user_score_use_percent = C('config.user_score_recharge_percent');
		$score_count = D('User')->where(array('uid'=>$this->user_session['uid']))->getField('score_count');
		if($_POST['score']>0){
			$_POST['score'] = abs($_POST['score']);
			if(IS_POST && $score_count > 0){
				if($_POST['score'] <= $score_count){
					$score_count = $_POST['score'];
				}
				$score_deducte = bcdiv($score_count,$user_score_use_percent,2);
				if($res = D('User')->add_money($this->user_session['uid'],floatval($score_deducte),$this->config['score_name'].'兑换 '.$score_deducte.C('config.Currency_txt').'到账户余额')){
					D('User')->use_score($this->user_session['uid'],$score_count,''.$this->config['score_name'].'兑换余额，减扣'.$this->config['score_name'].' '.round($score_count,2).' 个');
					D('User')->add_score_recharge_money($this->user_session['uid'],$score_deducte,'保存'.$this->config['score_name'].'兑换记录 '.$score_deducte.C('config.Currency_txt').'');
					D('Scroll_msg')->add_msg('score_recharge',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'使用'.$this->config['score_name'].'兑换余额');

					$this->success_tips($this->config['score_name']."兑换余额成功！",U('score_recharge'));
				}else{
					$this->error_tips($res['msg']);
				}
			}else{
				$this->error_tips('非法请求');
			}
		}else{
			$score_count = D('User')->where(array('uid'=>$this->user_session['uid']))->getField('score_count');
			$score_deducte = bcdiv($score_count,$user_score_use_percent,2);
			$this->assign('score_count',$score_count);
			$this->assign('score_deducte',$score_deducte);
			$share = new WechatShare($this->config,$_SESSION['openid']);
			$this->BioAuthticMethod = $share->getBioAuthticMethod($this->static_path);
			$this->assign('BioAuthticMethod', $this->BioAuthticMethod);
			$this->display();
		}
	}

	public function lifeservice(){
		$order_list = D('Service_order')->field(true)->where(array('uid'=>$this->user_session['uid'],'status'=>array('neq','0')))->order('`order_id` DESC')->select();
		foreach($order_list as &$value){
			$value['type_txt'] = $this->lifeservice_type_txt($value['type']);
			$value['type_eng'] = $this->lifeservice_type_eng($value['type']);
			$value['infoArr'] = unserialize($value['info']);
			$value['order_url'] = U('My/lifeservice_detail',array('id'=>$value['order_id']));
		}
		$this->assign('order_list', $order_list);
		$this->display();
	}

	public function lifeservice_detail(){
		$now_order = D('Service_order')->field(true)->where(array('order_id'=>$_GET['id']))->find();
		$now_order['infoArr'] = unserialize($now_order['info']);
		$now_order['type_txt'] = $this->lifeservice_type_txt($now_order['type']);
		$now_order['type_eng'] = $this->lifeservice_type_eng($now_order['type']);
		$now_order['pay_money'] = floatval($now_order['pay_money']);
		$this->assign('now_order', $now_order);
		$this->display();
	}

    public function spread_list()
    {
        if (IS_AJAX) {
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $page_size = 15;
            if (!isset($_GET['status'])) {
                //待结算订单
                $spread_list = D('User_spread_list')->field(true)->where(array('status' => '0', array('_string' => 'uid = ' . $this->user_session['uid'] . ' OR change_uid=' . $this->user_session['uid']), 'order_type' => array('neq', 'user_s_mer')))->order('`pigcms_id` DESC')->limit(($page - 1) * $page_size . ',' . $page_size)->select();
                if ($spread_list) {
                    foreach ($spread_list as $key => $value) {
                        if ($value['order_type'] == 'group') {
                            $order_info = $spread_list[$key]['order_info'] = D('Group_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                            if ($order_info['status'] == 0 && !$order_info['is_group_combine']) {
                                unset($spread_list[$key]);
                                continue;
                            }
                            $value['group_info'] = $spread_list[$key]['group_info'] = D('Group')->field('`group_id`,`name`')->where(array('group_id' => $value['third_id']))->find();
                        } else if ($value['order_type'] == 'shop' || $value['order_type'] == 'mall') {
                            $order_info = $spread_list[$key]['order_info'] = D('Shop_order')->field(true)->where(array('order_id' => $value['order_id']))->find();

                            if ($order_info['status'] == 0) {
                                unset($spread_list[$key]);
                                continue;
                            }
                            $value['shop_info'] = $spread_list[$key]['shop_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id' => $value['third_id']))->find();
                            
                            if($value['order_type'] == 'mall'){
	                        	$order_info = D('Mall_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
	                        	$order_info['total_price'] = $order_info['money_total'];
	                        	$value['order_info'] = $spread_list[$key]['order_info'] = $order_info;
	                        }

                        } else if ($value['order_type'] == 'store' || $value['order_type'] == 'cash') {
                            $order_info = $spread_list[$key]['order_info'] = D('Store_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                            if ($order_info['paid'] == 0) {
                                unset($spread_list[$key]);
                                continue;
                            }
                            $value['store_info'] = $spread_list[$key]['store_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id' => $value['third_id']))->find();

                        } else if ($value['order_type'] == 'meal') {
                            $order_info = $spread_list[$key]['order_info'] = D('Foodshop_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                            if ($order_info['paid'] == 0) {
                                unset($spread_list[$key]);
                                continue;
                            }
                            $value['meal_info'] = $spread_list[$key]['store_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id' => $value['third_id']))->find();

                        } else if ($value['order_type'] == 'sub_card') {
                            $order_info = $spread_list[$key]['order_info'] = D('Sub_card_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                            if ($order_info['paid'] == 0) {
                                unset($spread_list[$key]);
                                continue;
                            }
                            //$value['sub_card_info'] = $spread_list[$key]['store_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id'=>$value['third_id']))->find();

                        } else if ($value['order_type'] == 'yuedan') {
                            $order_info = $spread_list[$key]['order_info'] = D('Yuedan_service_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                        } else if ($value['order_type'] == 'user_level') {
                            $order_info = $spread_list[$key]['order_info'] = D('User_level')->field(true)->where(array('id' => $value['order_id']))->find();
                        } else if ($value['order_type'] == 'dining') {
                            $order_info = $spread_list[$key]['order_info'] = D('Dining_order_pay')->field(true)->where(array('pay_order_id' => $value['pay_order_id']))->find();
                        }
                        if ($value['spread_uid']) {
                            $value['spread_user'] = $spread_list[$key]['spread_user'] = D('User')->get_user($value['spread_uid']);
                        }
                        $value['get_user'] = $spread_list[$key]['get_user'] = D('User')->get_user($value['get_uid']);

                        //组成描述语句
                        if ($value['spread_user']) {
                            $spread_list[$key]['desc'] = array();
                            $spread_list[$key]['desc']['txt'] = '子用户 ' . $value['spread_user']['nickname'] . ' 推广用户 ' . $value['get_user']['nickname'] . ' 购买';
                        } else {
                            $spread_list[$key]['desc'] = array();
                            $spread_list[$key]['desc']['txt'] = '推广用户 ' . $value['get_user']['nickname'] . ' 购买';
                        }

                        if ($value['change_uid'] != 0) {
                            if ($this->user_session['uid'] != $value['change_uid']) {
                                $change_user = D('User')->get_user($value['change_uid'], 'uid');
                                $spread_list[$key]['desc']['txt'] .= '（佣金已结算给' . $change_user['nickname'] . ')';
                            } else {
                                $spread_list[$key]['desc']['txt'] .= '（佣金由' . $value['get_user']['nickname'] . '处结算过来)';
                            }
                        }

                        if ($value['order_type'] == 'group') {
                            $spread_list[$key]['desc']['url'] = U('Group/detail', array('group_id' => $value['group_info']['group_id']));
                            $spread_list[$key]['desc']['info'] = $order_info['total_money'] . C('config.Currency_txt') . '产品';
                        } elseif ($value['order_type'] == 'shop' || $value['order_type'] == 'mall') {
                            $spread_list[$key]['desc']['url'] = U('Shop/detail', array('store_id' => $value['shop_info']['store_id']));
                            $spread_list[$key]['desc']['info'] = $order_info['total_price'] . C('config.Currency_txt') . '产品';
                            if($value['order_type'] == 'mall'){
                                $order_info = D('Mall_order_detail')->field(true)->where(array('order_id' => $value['order_id']))->find();
                                $spread_list[$key]['desc']['info'] =  $order_info['name'];
                            }
                        } elseif ($value['order_type'] == 'store' || $value['order_type'] == 'cash') {
                            $spread_list[$key]['desc']['url'] = U('My/store_order_list');
                            $spread_list[$key]['desc']['info'] = $order_info['total_price'] . C('config.Currency_txt') . '产品';
                        } elseif ($value['order_type'] == 'meal') {
                            $spread_list[$key]['desc']['url'] = U('My/foodshop_order_list');
                            $spread_list[$key]['desc']['info'] = $order_info['total_price'] . C('config.Currency_txt') . '产品';
                        } elseif ($value['order_type'] == 'sub_card') {
                            $spread_list[$key]['desc']['url'] = U('My/sub_card_list');
                            $spread_list[$key]['desc']['info'] = $order_info['money'] . C('config.Currency_txt') . '产品';
                        } elseif ($value['order_type'] == 'yuedan') {
                            $spread_list[$key]['desc']['url'] = U('Yuedan/my_index');
                            $spread_list[$key]['desc']['info'] = $order_info['money'] . C('config.Currency_txt') . '产品';
                        } else if ($value['order_type'] == 'recharge') {
                            $spread_list[$key]['desc']['info'] = '平台充值';
                            $spread_list[$key]['desc']['url'] = U('My/my_index');
                            $alia_name = array('sub_card' => '充值');
                            $spread_list[$key]['desc']['info'] = '平台充值';
                        } elseif ($value['order_type'] == 'user_level') {
                            $spread_list[$key]['desc']['url'] = U('My/levelUpdate');
                            $spread_list[$key]['desc']['info'] = "会员等级{$order_info['level']}【{$order_info['lname']}】";
                        }elseif ($value['order_type'] == 'dining') {
                            $spread_list[$key]['desc']['info'] = $order_info['price'] . C('config.Currency_txt') . '产品';
                        }
                        $spread_list[$key]['add_date'] = date('Y-m-d H:i:s', $value['add_time']);
                    }
                }
            } else {
                $condition_spread_list['_string'] = 'uid = ' . $this->user_session['uid'] . ' OR change_uid=' . $this->user_session['uid'];
                if (in_array($_GET['status'], array(0, 1, 2))) {
                    $condition_spread_list['status'] = $_GET['status'];
                }
                $condition_spread_list['order_type'] = array('neq', 'user_s_mer');
                $spread_list = D('User_spread_list')->field(true)->where($condition_spread_list)->order('`pigcms_id` DESC')->limit(($page - 1) * $page_size . ',' . $page_size)->select();
                foreach ($spread_list as $key => $value) {
                    if ($value['spread_uid']) {
                        $value['spread_user'] = $spread_list[$key]['spread_user'] = D('User')->get_user($value['spread_uid']);
                    }
                    $value['get_user'] = $spread_list[$key]['get_user'] = D('User')->get_user($value['get_uid']);

                    if ($value['order_type'] == 'group') {
                        $value['group_info'] = $spread_list[$key]['group_info'] = D('Group')->field('`group_id`,`name`')->where(array('group_id' => $value['third_id']))->find();
                        //if($value['status'] == 0){
                        $value['order_info'] = $spread_list[$key]['order_info'] = D('Group_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                        //}
                    } else if ($value['order_type'] == 'shop' || $value['order_type'] == 'mall') {
                        $value['shop_info'] = $spread_list[$key]['shop_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id' => $value['third_id']))->find();
                        //if($value['status'] == 0){
                        $value['order_info'] = $spread_list[$key]['order_info'] = D('Shop_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                        //}
                    } else if ($value['order_type'] == 'store' || $value['order_type'] == 'cash') {
                        $value['store_info'] = $spread_list[$key]['store_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id' => $value['third_id']))->find();
                        //if($value['status'] == 0){
                        $value['order_info'] = $spread_list[$key]['order_info'] = D('Store_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                        //}
                    } else if ($value['order_type'] == 'meal') {
                        $value['meal_info'] = $spread_list[$key]['store_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id' => $value['third_id']))->find();
                        //if($value['status'] == 0){
                        $value['order_info'] = $spread_list[$key]['order_info'] = D('Foodshop_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                        //}
                    } else if ($value['order_type'] == 'sub_card') {
                        //$value['sub_card_info'] = $spread_list[$key]['store_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id'=>$value['third_id']))->find();
                        //if($value['status'] == 0){
                        $value['order_info'] = $spread_list[$key]['order_info'] = D('Sub_card_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                        //}
                    } else if ($value['order_type'] == 'yuedan') {
                        //$value['sub_card_info'] = $spread_list[$key]['store_info'] = D('Merchant_store')->field('`store_id`,`name`')->where(array('store_id'=>$value['third_id']))->find();
                        //if($value['status'] == 0){
                        $value['order_info'] = $spread_list[$key]['order_info'] = D('Yuedan_service_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                        //}
                    } else if ($value['order_type'] == 'user_level') {
                        $value['order_info'] = D('User_level')->field(true)->where(array('id' => $value['order_id']))->find();
                    } else if ($value['order_type'] == 'dining') {
                        $order_info = $spread_list[$key]['order_info'] = D('Dining_order_pay')->field(true)->where(array('pay_order_id' => $value['pay_order_id']))->find();
                    }else if ($value['order_type'] == 'vgroup') {
                        $value['group_info'] = $spread_list[$key]['group_info'] = D('Village_group')->field('`group_id`,`name`')->where(array('group_id' => $value['third_id']))->find();
                        //if($value['status'] == 0){
                        $value['order_info'] = $spread_list[$key]['order_info'] = D('Village_group_order')->field(true)->where(array('order_id' => $value['order_id']))->find();
                    }

                    //组成描述语句
                    if ($value['spread_user']) {
                        $spread_list[$key]['desc'] = array();
                        $spread_list[$key]['desc']['txt'] = '子用户 ' . $value['spread_user']['nickname'] . ' 推广用户 ' . $value['get_user']['nickname'] . ' 购买';
                    } else {
                        $spread_list[$key]['desc'] = array();
                        $spread_list[$key]['desc']['txt'] = '推广用户 ' . $value['get_user']['nickname'] . ' 购买';
                    }

                    if ($value['change_uid'] != 0) {
                        if ($this->user_session['uid'] != $value['change_uid']) {
                            $change_user = D('User')->get_user($value['change_uid'], 'uid');
                            $spread_list[$key]['desc']['txt'] = '（佣金已结算给' . $change_user['nickname'] . ')';
                        } else {
                            $spread_list[$key]['desc']['txt'] = '（佣金由' . $value['get_user']['nickname'] . '处结算过来)';
                        }
                    }
                    if ($value['order_type'] == 'group') {
                        $spread_list[$key]['desc']['url'] = U('Group/detail', array('group_id' => $value['group_info']['group_id']));
                        $spread_list[$key]['desc']['info'] = $value['order_info']['total_money'] . C('config.Currency_txt') . '产品';
                    } else if ($value['order_type'] == 'shop' || $value['order_type'] == 'mall') {
                        $spread_list[$key]['desc']['url'] = U('shop/detail', array('shop_id' => $value['shop_info']['shop_id']));
                        $spread_list[$key]['desc']['info'] = $value['order_info']['total_price'] . C('config.Currency_txt') . '产品';
                        if($value['order_type'] == 'mall'){
                            $order_info = D('Mall_order_detail')->field(true)->where(array('order_id' => $value['order_id']))->find();
                            $spread_list[$key]['desc']['info'] =  $order_info['name'];
                        }
                    } else if ($value['order_type'] == 'store' || $value['order_type'] == 'cash') {
                        $spread_list[$key]['desc']['info'] = $value['order_info']['total_price'] . C('config.Currency_txt') . '产品';
                        $spread_list[$key]['desc']['url'] = U('My/store_order_list');
                        $alia_name = array('store' => '优惠买单', 'cash' => '到店付');
                        $spread_list[$key]['desc']['info'] = $value['store_info']['name'] . $alia_name[$value['order_type']];
                    } else if ($value['order_type'] == 'meal') {
                        $spread_list[$key]['desc']['info'] = $value['order_info']['total_price'] . C('config.Currency_txt') . '产品';
                        $spread_list[$key]['desc']['url'] = U('My/foodshop_order_list');
                        $alia_name = array('store' => C('config.meal_alias_name'));
                        $spread_list[$key]['desc']['info'] = $value['meal_info']['name'] . $alia_name[$value['order_type']];
                    } else if ($value['order_type'] == 'sub_card') {
                        $spread_list[$key]['desc']['info'] = $value['order_info']['money'] . C('config.Currency_txt') . '产品';
                        $spread_list[$key]['desc']['url'] = U('My/sub_card_list');
                        $alia_name = array('sub_card' => '免单套餐');
                        $spread_list[$key]['desc']['info'] = $value['sub_card_info']['name'] . $alia_name[$value['order_type']];
                    } else if ($value['order_type'] == 'yuedan') {
                        $spread_list[$key]['desc']['info'] = $value['order_info']['money'] . C('config.Currency_txt') . '产品';
                        $spread_list[$key]['desc']['url'] = U('Yuedan/my_index');
                        $alia_name = array('sub_card' => '约单');
                        $spread_list[$key]['desc']['info'] = $value['yuedan']['name'] . $alia_name[$value['order_type']];
                    } else if ($value['order_type'] == 'recharge') {
                        $spread_list[$key]['desc']['info'] = '平台充值';
                        $spread_list[$key]['desc']['url'] = U('My/my_index');
                        $alia_name = array('sub_card' => '充值');
                        $spread_list[$key]['desc']['info'] = '平台充值';
                    } else if ($value['order_type'] == 'classifynew') {
                        $spread_list[$key]['desc']['info'] = '分类信息';
                        $spread_list[$key]['desc']['url'] = U('My/classifynew');
                    } elseif ($value['order_type'] == 'user_level') {
                        $spread_list[$key]['desc']['url'] = U('My/levelUpdate');
                        $spread_list[$key]['desc']['info'] = "会员等级{$value['order_info']['level']}【{$value['order_info']['lname']}】";
                    }elseif ($value['order_type'] == 'dining') {
                        $spread_list[$key]['desc']['info'] = $order_info['price'] . C('config.Currency_txt') . '产品';
                    }elseif ($value['order_type'] == 'vgroup') {
                        //$spread_list[$key]['desc']['url'] = U('Village_group/index', array('group_id' => $value['group_info']['group_id']));
                        $spread_list[$key]['desc']['info'] = $value['order_info']['total_money'] . C('config.Currency_txt') . '产品';
                    }
                    $spread_list[$key]['add_date'] = date('Y-m-d H:i:s', $value['add_time']);
                }
            }
            $this->ajaxReturn(['spread_list' => array_values($spread_list)]);
        } else {
            $this->display('spread_list_new');
        }
    }

	public function spread_check(){
		if($this->config['open_extra_price']==1){
			$money_name = C('config.extra_price_alias_name');
		}else{
			$money_name = '佣金';
		}
		$where = array(
				'pigcms_id'=>$_GET['id'],
				'_string'=>'uid='.$this->user_session['uid'].' OR change_uid='.$this->user_session['uid']
		);
		if(C('config.open_distributor')>0){
			$res= D('Distributor_agent')->get_effective($this->user_session['uid'],1);
			if($res['error_code']){
				$this->error($res['msg']);
			}
		}
		$now_spread = D('User_spread_list')->where($where)->find();
		if($now_spread && $now_spread['status'] == 0){
			if($now_spread['order_type'] == 'group'){
				$order_info = D('Group_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
				if($order_info['status'] == '1' || $order_info['status'] == '2'){
					if(D('User_spread_list')->where(array('pigcms_id'=>$_GET['id']))->data(array('status'=>1))->save()){
						//D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.$this->config['group_alias_name'].'商品获得佣金');
						if($now_spread['change_uid']!=0){
							D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买'.$this->config['group_alias_name'].'商品获得'.$money_name.'('.$money_name.'过户)');

							// 是否开启推广佣由金商家支付
							if (C('config.open_spread_by_merchant')==1) {
								D('Merchant_money_list')->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], '推广用户购买'.$this->config['group_alias_name'].'商品获得'.$money_name.'('.$money_name.'过户)扣除商家余额', $order_info['order_id']);
							}
						}else{
							if($this->config['open_extra_price']==1){
								D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.$this->config['group_alias_name'].'商品获得'.$money_name);
							}else{
								D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.$this->config['group_alias_name'].'商品获得'.$money_name);
							
								// 是否开启推广佣由金商家支付
								if (C('config.open_spread_by_merchant')==1) {
									D('Merchant_money_list')->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], '推广用户购买'.$this->config['group_alias_name'].'商品获得'.$money_name.'扣除商家余额', $order_info['order_id']);
								}

								D('Scroll_msg')->add_msg('spread',$now_spread['uid'],'用户'.str_replace_name($now_spread['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'获得推广用户的消费佣金');
							}
						}
						$this->success('结算完成');
					}else{
						$this->error('操作失败');
					}
				}else if($order_info['status'] == '3' || $order_info['status'] == '6'){
					if(D('User_spread_list')->where(array('pigcms_id'=>$_GET['id']))->data(array('status'=>2))->save()){
						$this->success('用户已退款');
					}else{
						$this->error('操作失败');
					}
				}
			}else if ($now_spread['order_type']=='shop' || $now_spread['order_type']=='mall'){
				$order_info = D('Shop_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
				/*----判断别名----*/
				$alias_name=$this->config['shop_alias_name'];
				if($now_spread['order_type']=='mall'){
					$alias_name=$this->config['mall_alias_name'];
				}
				
				if($order_info['status'] == '1' || $order_info['status'] == '2' || $order_info['status'] == '3'){
					if(D('User_spread_list')->where(array('pigcms_id'=>$_GET['id']))->data(array('status'=>1))->save()){
						//D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.$alias_name.'商品获得佣金');

						if($now_spread['change_uid']!=0){
							D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买'.$alias_name.'商品获得'.$money_name.'('.$money_name.'过户)');

							// 是否开启推广佣由金商家支付
							if (C('config.open_spread_by_merchant')==1) {
								D('Merchant_money_list')->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], '推广用户购买'.$alias_name.'商品获得'.$money_name.'('.$money_name.'过户)扣除商家余额', $order_info['order_id']);
							}
						}else{
							if($this->config['open_extra_price']==1){
								D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.$alias_name.'商品获得'.$money_name);
							}else{
								D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.$alias_name.'商品获得'.$money_name);
							
								// 是否开启推广佣由金商家支付
								if (C('config.open_spread_by_merchant')==1) {
									D('Merchant_money_list')->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], '推广用户购买'.$alias_name.'商品获得'.$money_name.'扣除商家余额', $order_info['order_id']);
								}

								D('Scroll_msg')->add_msg('spread',$now_spread['uid'],'用户'.str_replace_name($now_spread['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'获得推广用户的消费佣金');
							}
						}
						$this->success('结算完成');
					}else{
						$this->error('操作失败');
					}
				}else if($order_info['status'] == '4'||$order_info['status'] == '5'){
					if(D('User_spread_list')->where(array('pigcms_id'=>$_GET['id']))->data(array('status'=>2))->save()){
						$this->success('用户已退款');
					}else{
						$this->error('操作失败');
					}
				}
			}else if ($now_spread['order_type']=='store'||$now_spread['order_type']=='cash'){
				$order_info = D('Store_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();

					$alia_name = array('store'=>'优惠买单','cash'=>'到店付');
					if(D('User_spread_list')->where(array('pigcms_id'=>$_GET['id']))->data(array('status'=>1))->save()){
						if($now_spread['change_uid']!=0){
							D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买'.$alia_name[$now_spread['order_type']].'商品获得'.$money_name.'('.$money_name.'过户)');

							// 是否开启推广佣由金商家支付
							if (C('config.open_spread_by_merchant')==1) {
								D('Merchant_money_list')->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], '推广用户购买'.$alia_name[$now_spread['order_type']].'商品获得'.$money_name.'('.$money_name.'过户)扣除商家余额', $order_info['order_id']);
							}
						}else{
							if($this->config['open_extra_price']==1){
								D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.$alia_name[$now_spread['order_type']].'商品获得'.$money_name);
							}else{

								D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.$alia_name[$now_spread['order_type']].'商品获得'.$money_name);

								// 是否开启推广佣由金商家支付
								if (C('config.open_spread_by_merchant')==1) {
									D('Merchant_money_list')->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], '推广用户购买'.$alia_name[$now_spread['order_type']].'推广用户购买'.$alia_name[$now_spread['order_type']].'商品获得'.$money_name.'扣除商家余额', $order_info['order_id']);
								}

								D('Scroll_msg')->add_msg('spread',$now_spread['uid'],'用户'.str_replace_name($now_spread['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'获得推广用户的消费佣金');
							}
						}
						$this->success('结算完成');
					}else{
						$this->error('操作失败');
					}

			}else if ($now_spread['order_type']=='meal'){
				$order_info = D('Foodshop_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();

					$alia_name = array('store'=>C('config.meal_alias_name'));
					if(D('User_spread_list')->where(array('pigcms_id'=>$_GET['id']))->data(array('status'=>1))->save()){
						//D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买餐饮商品获得佣金');
						if($now_spread['change_uid']!=0){
							D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买餐饮商品获得'.$money_name.'('.$money_name.'过户)');

							// 是否开启推广佣由金商家支付
							if (C('config.open_spread_by_merchant')==1) {
                           		D('Merchant_money_list')->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], '推广用户购买'.$alia_name[$now_spread['order_type']].'推广用户购买餐饮商品获得'.$money_name.'('.$money_name.'过户)扣除商家余额', $order_info['order_id']);
							}
						}else{
							if($this->config['open_extra_price']==1){
								D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.$this->config['meal_alias_name'].'商品获得'.$money_name);
							}else{
								D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.$this->config['meal_alias_name'].'商品获得'.$money_name);

								// 是否开启推广佣由金商家支付
								if (C('config.open_spread_by_merchant')==1) {
									D('Merchant_money_list')->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], '推广用户购买'.$this->config['meal_alias_name'].'商品获得'.$money_name.'扣除商家余额', $order_info['order_id']);
								}

								D('Scroll_msg')->add_msg('spread',$now_spread['uid'],'用户'.str_replace_name($now_spread['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'获得推广用户的消费佣金');
							}
						}
						$this->success('结算完成');
					}else{
						$this->error('操作失败');
					}

			}else if ($now_spread['order_type']=='sub_card'){
				$order_info = D('Sub_card_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();

				$alia_name = array('sub_card'=>'次卡套餐');
				if(D('User_spread_list')->where(array('pigcms_id'=>$_GET['id']))->data(array('status'=>1))->save()){
					//D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买餐饮商品获得佣金');
					if($now_spread['change_uid']!=0){
						D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买免单套餐获得'.$money_name.'('.$money_name.'过户)');
					}else{
						D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买免单套餐商品获得'.$money_name);
						D('Scroll_msg')->add_msg('spread',$now_spread['uid'],'用户'.str_replace_name($now_spread['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'获得推广用户的消费佣金');

					}
					$this->success('结算完成');
				}else{
					$this->error('操作失败');
				}

			}else if ($now_spread['order_type']=='yuedan'){
				$order_info = D('Yuedan_service_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();

				$alia_name = array('yuedan'=>'约单');
				if(D('User_spread_list')->where(array('pigcms_id'=>$_GET['id']))->data(array('status'=>1))->save()){
					//D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买餐饮商品获得佣金');
					if($now_spread['change_uid']!=0){
						D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买约单获得'.$money_name.'('.$money_name.'过户)');
					}else{
						D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买约单获得'.$money_name);
						D('Scroll_msg')->add_msg('spread',$now_spread['uid'],'用户'.str_replace_name($now_spread['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'获得推广用户的消费佣金');
					}
					$this->success('结算完成');
				}else{
					$this->error('操作失败');
				}

			}

		}
	}

	public function spread_user_list(){
		if(!empty($_GET['uid'])){
			$user = M('User')->where(array('uid'=>$_GET['uid']))->find();
		}else{
			$user = $_SESSION['user'];
		}
		
		//修复当前用户的公众号扫码推广关系
		if($user['openid']){
			D('User_spread')->updateUid($user['openid'], $user['uid']);
		}
		$repair_where = ['spread_uid' => $user['uid']];
		$repair_where['_string'] = '`uid` IS NULL';
		$spred_list = D('User_spread')->where($repair_where)->select();
		if($spred_list){
			foreach($spred_list as $value){
				$tmp_user = D('User')->get_user($value['openid'], 'openid');
				if($tmp_user){
					D('User_spread')->updateUid($tmp_user['openid'], $tmp_user['uid']);
				}
			}
		}

		$res = D('User_spread')->get_spread_user($user['openid'],$user['uid']);
		$this->assign('res',$res);
		$this->assign('user',$user);
		$this->display();
	}

	//计算用户列表
	public function my_settlement_user(){
		$res = D('User_spread')->get_spread_change_user($this->user_session['uid']);
		$this->assign('res',$res);
		//$this->assign('user',$user);
		$this->display();
	}

	//解绑关系
	public function unbind_spread_change(){
		$uid=$_POST['uid'];
		if(M('User')->where(array('uid'=>$uid))->setField('spread_change_uid',0)){

			$this->AjaxReturn(array('error_code'=>0,'msg'=>'解绑成功'));
		}else{
			$this->AjaxReturn(array('error_code'=>1,'msg'=>'解绑失败'));
		}
		exit;
	}

	protected function lifeservice_type_txt($type){
		switch($type){
			case '1':
				$type_txt = '水费';
				break;
			case '2':
				$type_txt = '电费';
				break;
			case '3':
				$type_txt = '煤气费';
				break;
			default:
				$type_txt = '生活服务';
		}
		return $type_txt;
	}

	protected function lifeservice_type_eng($type){
		switch($type){
			case '1':
				$type_txt = 'water';
				break;
			case '2':
				$type_txt = 'electric';
				break;
			case '3':
				$type_txt = 'gas';
				break;
			default:
				$type_txt = 'life';
		}
		return $type_txt;
	}

	protected function getPayName($label){
		$payName = array(
				'weixin' => '微信支付',
				'tenpay' => '财付通支付',
				'yeepay' => '银行卡支付(易宝支付)',
				'allinpay' => '银行卡支付(通联支付)',
				'chinabank' => '银行卡支付(网银在线)',
		);
		return $payName[$label];
	}

	/****等级升级****/
	public function levelUpdate(){
		if($this->config['level_onoff']==0){
			$this->error_tips(L_('平台没有开启该功能'));
		}
		$now_user = D('User')->get_user($this->user_session['uid']);
		$next = $now_user['level']+1;
		if($_GET['nextlevel']){
			$next = $_GET['nextlevel']-1;
		}
		$level_adver = D('Adver')->get_adver_by_key('levelad',5);
		$this->assign('level_adver',$level_adver);
		$nextlevel = M(_view('User_level'))->where(array('level'=>array('eq',$next)))->find();
		$this->assign('nextlevel',$nextlevel['level']);
		//开卡人数
        $count = M('User')->where(array('level'=>array('gt',0),'status'=>1))->count('*');
        $this->assign('card_count',$count);
        //处理用户等级
        $tmparr = M(_view('User_level'))->field(true)->order('`id` ASC')->select();
        $levelarr = array();
        if ($tmparr) {
            foreach ($tmparr as $vv) {
                $vv['user_level']=$now_user['level'];
                $levelarr[$vv['level']] = $vv;
            }
        }
        $this->user_level = $levelarr;
        foreach($levelarr as $key => $v){
            $levelarr[$key]['description'] =  nl2br(strip_tags(htmlspecialchars_decode($v['description'])));
        }
        $this->assign('levelarr',$levelarr);
        $max_arr=M(_view('User_level'))->field(true)->order('level DESC')->select();
        if(!empty($max_arr)){
            $max=$max_arr[0]['level'];
        }else{
            $max=1;
        }
        $this->assign("level_max", $max);
        $this->assign('now_user',$now_user);
        $user = M('User')->where(array('uid'=>$this->user_session['uid']))->find();
        $this->assign('now_user_level',$user['level']);
        $m_user_level_gift = M('User_level_gift');
        $level_info = M('User_level')->where(array('level' => $now_user['level']))->find();
        $user_level_gift_info = $m_user_level_gift->where(array('level_id' => $level_info['id'], 'gift_status' => array('neq', 3)))->find();
        $this->assign("show_gift", $user_level_gift_info ? '1' : '0');
		$this->display();
	}

    /****选择等级升级****/
    public function levelUpdateBy(){
        if($this->config['level_onoff']==0){
            $this->error_tips(L_('平台没有开启该功能'));
        }
        $this->assign('type',$_GET['type']);
        $now_user = D('User')->get_user($this->user_session['uid']);
        $next = $now_user['level'];
        $nextlevels = M(_view('User_level'))->where(array('level'=>array('gt',$next)))->select();
        if(count($nextlevels)<1){
			$this->error_tips(L_('您已经是最高等级'));
        }
        $this->assign('nextlevels',$nextlevels);
        $this->display();
    }
	/**json格式封装函数* */

	private function dexit($data = '') {
		if (is_array($data)) {
			echo json_encode($data);
		} else {
			echo $data;
		}
		exit();
	}

	public function pay(){
		if(IS_POST){
			if($order_id = $this->store_order()){
				if (IS_AJAX) {
					$this->ajaxReturn(array('type' => 'store', 'order_id' => $order_id), 'JSON');
				}

				$is_ecard_recharge = I('is_card_recharge');
				$payUrl = get_base_url('pages/shop_new/confirmOrder/pay?order_id='.$order_id.'&type=store');
				redirect($payUrl);
				return ;
				
                redirect(U('Pay/check',array('type' => 'store', 'order_id' => $order_id, 'is_ecard_recharge' => $is_ecard_recharge)));
			}else{
				$this->error_tips(L_('创建订单失败'));
			}
		}

		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$mer_id = isset($_GET['mer_id']) ? intval($_GET['mer_id']) : 0;
		if(!$store_id && !$mer_id){
			$this->error_tips('非法访问');
		}
		if($store_id){
			$now_store = D('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();

			$now_store['discount_txt'] = unserialize($now_store['discount_txt']);
			$now_store['store_name']= $now_store['name'];
			$now_store['discount_type']= isset($now_store['discount_txt']['discount_type']) ? $now_store['discount_txt']['discount_type'] : 0;
			$now_store['discount_percent']= isset($now_store['discount_txt']['discount_percent']) ? $now_store['discount_txt']['discount_percent'] : 0;
			$now_store['condition_price']=isset($now_store['discount_txt']['condition_price']) ? $now_store['discount_txt']['condition_price'] : 0;
			$now_store['minus_price']= isset($now_store['discount_txt']['minus_price']) ? $now_store['discount_txt']['minus_price'] : 0;
			//扫到店付码成为推广用户
			if($_SESSION['openid'] && $_GET['spread']){
				$now_user = D('User')->get_user($_SESSION['openid'],'openid');
				if(!$now_user && C('config.open_yanglao_and_sande')){
					redirect(U('Login/weixin', ['referer' => urlencode($_SERVER['REQUEST_URI'])]));
				}
				if ($now_user) {
					D('Merchant_spread')->spread_add($now_store['mer_id'], $_SESSION['openid'],'scanpay',$store_id);
				}
			}

			if($this->config['open_score_get_percent']){
				$now_store['score_precent'] = $this->config['score_get_percent'];
			}else{
				$now_store['score_precent'] = $this->config['user_score_get']*100;
			}


			if($this->config['store_ticket_have'] && $now_store['bind_store_trade'] == 'ticket'){
				$database_store_trade_ticket = D('Merchant_store_trade_ticket');
				$condition_store_trade_ticket = array('store_id'=>$now_store['store_id']);
				$now_store['store_trade_ticket'] = $database_store_trade_ticket->where($condition_store_trade_ticket)->find();

				$now_store['store_trade_ticket']['limit_num'] = $now_store['store_trade_ticket']['limit_num'] > 1 ? $now_store['store_trade_ticket']['limit_num'] : 1;
				if(empty($now_store['store_trade_ticket']['insure_name'])){
					$now_store['store_trade_ticket']['insure_name'] = '保险';
				}
				$display_tpl_name = 'pay_ticket';
			}

			$this->assign('store',$now_store);
		}else if($mer_id){
			$store_list = D('Merchant_store')->get_store_list_by_merId($mer_id);
			if(empty($store_list)){
				$this->error_tips('商家暂未创建店铺');
			}
			if(count($store_list) == 1){
				$_GET['store_id'] = $store_list[0]['store_id'];
				$_GET['mer_id'] = 0;
				$this->pay();
				die;
			}
			$user_long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
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
			$res = sortArrayAsc($store_list,'Srange');
			$this->assign('store_list',$res);

		}


		if($_GET['money']){
			$default_money = $_GET['money'];
		}else if($now_store['store_trade_ticket'] && $now_store['store_trade_ticket']['default_money']){
			$default_money = $now_store['store_trade_ticket']['default_money'];
		}
		$level_off=false;
		$user_= M('User')->where(array('uid'=>$this->user_session['uid']))->find();
		$this->user_session['level'] = $user_['level'];
		$this->user_session['now_money'] = $user_['now_money'];
		if(  !empty($this->user_level) && !empty($this->user_session) && isset($this->user_session['level'])){
			$leveloff=!empty($now_store['leveloff']) ? unserialize($now_store['leveloff']) :'';

			/****type:0无优惠 1百分比 2立减*******/
			if(!empty($leveloff) && isset($leveloff[$this->user_session['level']]) && isset($this->user_level[$this->user_session['level']])){
				$level_off=$leveloff[$this->user_session['level']];
				if($level_off['type']==1){
					$level_off['offstr']='单价按原价'.$level_off['vv'].'%来结算';
				}elseif($level_off['type']==2){
					$level_off['offstr']='单价立减'.$level_off['vv'].C('config.Currency_txt');
				}

			}
			$this->assign('level_off',$level_off);
		}
		$this->assign('user_session',$user_session);
		$this->assign('default_money',$default_money);

		// 定制 用户成为商家老板下级
		$merchant = M('Merchant')->where(['mer_id'=>$now_store['mer_id']])->find();
		// 查询用户是否绑定过上下级关系
		if($user_ && $user_ ['uid'] != $merchant['boss_uid'] && C('config.open_bind_merchant_boss')){
			$where['_string'] = 'uid='.$user_['uid'];
			if($user_['openid']){
				$where['_string'] .= ' OR openid="'.$user_['openid'].'"';
			}
			$spreadCheck = M('User_spread')->where($where)->count();

			$this->assign('spreadCheck',$spreadCheck);
			$this->assign('boss_uid',$merchant['boss_uid']);
		}

		$this->assign('old_age_pension_per', D("Percent_rate")->getOldAgePension($mer_id ? $mer_id : $now_store['mer_id'], 'store', 1));

		// 充值优惠
    	$recharge_discount_list = M('Recharge_discount')->field(true)->where(array('status'=>1))->select();
		$this->assign('recharge_discount_list',$recharge_discount_list);
		
        //消费一元获得积分
        $buy_can_get_score = D('Goods_score')->getGoodsScore(1,'store');
        $this->assign('buy_can_get_score',$buy_can_get_score['score']?:0);
        $this->assign('score_round_two',C('config.score_round_two'));
        $this->assign('buy_can_get_score_txt',$buy_can_get_score['score_txt']?:0);
		$this->display($display_tpl_name);
	}

	public function ajax_get_store_list(){
		$mer_id = isset($_POST['mer_id']) ? intval($_POST['mer_id']) : 0;
		$store_list = D('Merchant_store')->get_store_list_by_merId($mer_id);
		$user_long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
		foreach ($store_list as &$vo) {
			//$vo['juli'] = ;
			$vo['Srange'] = getDistance($user_long_lat['lat'], $user_long_lat['long'], $vo['lat'], $vo['long']);
			$vo['range'] = getRange($vo['Srange'], false);
			$vo['discount_txt'] = unserialize($vo['discount_txt']);
			$vo['discount_type']= isset($vo['discount_txt']['discount_type']) ? $vo['discount_txt']['discount_type'] : 0;
			$vo['discount_percent']= isset($vo['discount_txt']['discount_percent']) ? $vo['discount_txt']['discount_percent'] : 0;
			$vo['condition_price']=isset($vo['discount_txt']['condition_price']) ? $vo['discount_txt']['condition_price'] : 0;
			$vo['minus_price']= isset($vo['discount_txt']['minus_price']) ? $vo['discount_txt']['minus_price'] : 0;
			$rangeSort[] = array('store_id'=>$vo['store_id'],'juli'=>$vo['Srange']);
		}
		$res = sortArrayAsc($store_list,'Srange');

		$this->ajaxReturn(array('store_list'=>$res));
	}

	public function store_order(){
		$store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $remark   = !empty($_POST['remark']) ? $_POST['remark'] : '';
		$is_ecard_recharge = intval(I('get.is_ecard_recharge')) ;
		$card_id = intval(I('get.card_id'));
		$data = array(
		    'store_id' => $store_id,
            'remark'   => $remark
        );
		if($_POST['isTicket']){
			if(!$this->config['store_ticket_have']){
				$this->error_tips('平台未开启票务插件');
			}
			$now_store = D('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();
			if(empty($now_store)){
				$this->error_tips('店铺不存在');
			}
			if(!$now_store['bind_store_trade'] == 'ticket'){
				$this->error_tips('店铺未开启票务插件');
			}
			$database_store_trade_ticket = D('Merchant_store_trade_ticket');
			$condition_store_trade_ticket = array('store_id'=>$now_store['store_id']);
			$now_store['store_trade_ticket'] = $database_store_trade_ticket->where($condition_store_trade_ticket)->find();
			$now_store['store_trade_ticket']['limit_num'] = $now_store['store_trade_ticket']['limit_num'] > 1 ? $now_store['store_trade_ticket']['limit_num'] : 1;

			if($_POST['pay_num'] < $now_store['store_trade_ticket']['limit_num']){
				$this->error_tips('购票数量小于店铺设定的最低数');
			}
			if($this->config['store_ticket_have_insure'] && $now_store['store_trade_ticket']['have_insure'] && $now_store['store_trade_ticket']['insure_mustby'] && !$_POST['choose_cinsure']){
				$this->error_tips('店铺设定必须要购买保险');
			}
			$data['ticketPrice'] = $_POST['ticketPrice'];
			$data['ticketNum'] = $_POST['pay_num'];
			//计算保险
			$data['ticketInsure'] = 0;
			if($_POST['choose_cinsure']){
				if($now_store['store_trade_ticket']['insure_tikcet_3'] != 0 && $data['ticketPrice'] >= $now_store['store_trade_ticket']['insure_tikcet_3']){
					$data['ticketInsure'] = $now_store['store_trade_ticket']['insure_3'];
				}else if($now_store['store_trade_ticket']['insure_tikcet_2'] != 0 && $data['ticketPrice'] >= $now_store['store_trade_ticket']['insure_tikcet_2']){
					$data['ticketInsure'] = $now_store['store_trade_ticket']['insure_2'];
				}else if($data['ticketPrice'] >= $now_store['store_trade_ticket']['insure_tikcet_1']){
					$data['ticketInsure'] = $now_store['store_trade_ticket']['insure_1'];
				}
			}

			$total_money = ($data['ticketPrice']+$data['ticketInsure'])*$data['ticketNum'];
			$data['total_price'] = $total_money;
			$no_discount_money = 0;
		}else{
			//普通优惠买单
			$total_money  = $tmp_total_money= isset($_POST['total_money']) ? (floatval($_POST['total_money'] * 100) / 100) : 0;
			$data['total_price'] = $total_money;
			$no_discount_money = isset($_POST['no_discount_money']) ? (intval($_POST['no_discount_money'] * 100) / 100) : 0;

			$now_store = D('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();
			if(empty($now_store)){
				$this->error_tips('店铺不存在');
			}
			if ($total_money <= 0){
				$this->error_tips('付款金额必须大于￥0');
			}
			$minus_price_true = 0;
			$price_true = $total_money;

			$level_off=false;
			$user_= M('User')->where(array('uid'=>$this->user_session['uid']))->find();
			$this->user_session['level'] = $user_['level'];
			if(  !empty($this->user_level) && !empty($this->user_session) && isset($this->user_session['level'])){
				$leveloff=!empty($now_store['leveloff']) ? unserialize($now_store['leveloff']) :'';
				/****type:0无优惠 1百分比 2立减*******/
				if(!empty($leveloff) && isset($leveloff[$this->user_session['level']]) && isset($this->user_level[$this->user_session['level']])){
					$level_off=$leveloff[$this->user_session['level']];
					if($level_off['type']==1){
						$level_off['offstr']='单价按原价'.$level_off['vv'].'%来结算';
					}elseif($level_off['type']==2){
						$level_off['offstr']='单价立减'.$level_off['vv'].C('config.Currency_txt');
					}
				}
			}
			$discount_compare = false ;
			$now_store['discount_txt'] = unserialize($now_store['discount_txt']);

			if (isset($now_store['discount_txt']['discount_type'])) {
				if ($now_store['discount_txt']['discount_type'] == 1) {
					if (isset($now_store['discount_txt']['discount_percent']) && $now_store['discount_txt']['discount_percent'] > 0) {
						$price_true = ($total_money - $no_discount_money) * $now_store['discount_txt']['discount_percent'] / 10 + $no_discount_money;
						$minus_price_true += $total_money - $price_true;

					}
				} elseif ($now_store['discount_txt']['discount_type'] == 2) {
					if (isset($now_store['discount_txt']['condition_price']) && $now_store['discount_txt']['condition_price'] > 0 && isset($now_store['discount_txt']['minus_price']) && $now_store['discount_txt']['minus_price']) {
						$minus_price_true += floor(($total_money - $no_discount_money) / $now_store['discount_txt']['condition_price']) * $now_store['discount_txt']['minus_price'];
						$price_true = $total_money - $minus_price_true;
					}
				}
			}

			if($now_store['vip_discount_type']>0){
				$level_discount_type = $level_off['type'];
				$discount_v =  $level_off['vv'];
				$after_subtract = 0;
				if($now_store['vip_discount_type']==1 && $level_discount_type){ //折扣最优
					$price_true = $total_money;
					if($level_discount_type==1){
						$after_subtract = floatval(($price_true - $no_discount_money)*(1-($discount_v/100)));
					}else if($level_discount_type==2){
						$after_subtract = $discount_v;
					}
					$discount_compare = true;
				}else if($now_store['vip_discount_type']==1 && empty($level_discount_type)){ // 折上折
					$data['discount_price'] = $minus_price_true;
				
				}else if($now_store['vip_discount_type']==2 ){ // 折上折
					$data['discount_price'] = $minus_price_true;
					$minus_price_true = 0;
					if($level_discount_type==1){
						$minus_price_true = floatval(($price_true - $no_discount_money)*(1-($discount_v/100)));
						$data['vip_discount_money'] = $minus_price_true;
					}else if($level_discount_type==2){
						$minus_price_true = $discount_v;
						$data['vip_discount_money'] = $minus_price_true;
					}
					$tmp_total_money = $price_true;
					$price_true = $price_true-$minus_price_true<0?0:$price_true-$minus_price_true;
					$total_money = $tmp_total_money;
				}
			}else{
				$data['discount_price'] = $minus_price_true;
			}
		}

		if ($minus_price_true == 0 && $price_true == 0) {
			$minus_price_true = 0;
			$price_true = $total_money;
		}

		if($discount_compare){
			if ($after_subtract>$minus_price_true) { // 会员等级优惠大于商家优惠
				$minus_price_true = $after_subtract;
				if($total_money-$minus_price_true<0){
					$minus_price_true = $total_money;
				}
				$data['vip_discount_money'] = $minus_price_true;
			}else{ // 使用商家优惠
				if($total_money-$minus_price_true<0){
					$minus_price_true = $total_money;
				}
				$data['discount_price'] = $minus_price_true;
			}
			
			$price_true = $total_money-$minus_price_true;
		}

		// 不登录状态下 保存openid或alipay_uid
		if ($this->user_session['uid']) {
			$data['uid'] = $this->user_session['uid'];
		}else{
			// 小程序端必须登录
			if($this->is_wxapp_browser){
				// 跳转登录
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				redirect(U('Login/index',$location_param));
			}
			if ($_SESSION['openid']) {
				$data['openid'] = strval($_SESSION['openid']);
			}
			if ($_SESSION['alipay_uid']) {
				$data['alipay_uid'] = strval($_SESSION['alipay_uid']);
			}
		}
        
		$data['mer_id'] = $now_store['mer_id'];
		$data['orderid'] = date("YmdHis") . mt_rand(10000000, 99999999);
		$data['name'] = '顾客现场自助支付-' . $now_store['name'];
        $data['price'] = $price_true;
		$data['no_discount_money'] = $no_discount_money;
		$data['dateline'] = time();
		$data['from_plat'] = 1;

		if($is_ecard_recharge == 1){
			$data['name'] = '一卡通充值:'. $card_id;
			$data['from_plat'] = 4;
			$data['remark'] = $card_id; //remark字段用来存一下充值订单的一卡通id
		}

		/*------优惠买单扫码支付，直接成为商家推广用户 2019/8/6--------*/
		if ($_POST['spread']) { // 扫码下单
			$data['is_scan'] = 1;
		}
		$order_id = D("Store_order")->add($data);
		if ($order_id) {
			/*----写入系统订单-----*/
			// 订单改变之后调用
            $data['business_type'] = 'store';
            $data['paid'] = 0;
			D('Store_order')->after_store_change($order_id,'store',1,$data);

			return $order_id;
		} else {
			return false;
		}
	}

	public function store_order_before(){
		$order_id  = $_GET['order_id'];
		$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();

		if(empty($now_order)){
			$this->error('当前订单不存在');
		}else if($now_order['paid'] == '1'){
			$this->error('该订单已经付款');
		}else if($now_order['uid'] != $this->user_session['uid']){
			if(M("Store_order")->where(array('order_id'=>$order_id))->data(array('uid'=>$this->user_session['uid']))->save()){
				redirect(U('Pay/check',array('type'=>'store','order_id'=>$order_id)));
			}else{
				$this->error('数据保存失败');
			}
		}else{
			redirect(U('Pay/check',array('type'=>'store','order_id'=>$order_id)));
		}
	}

	public function shop_order_before(){
		$order_id  = $_GET['order_id'];
		$now_order = M("Shop_order")->where(array('order_id'=>$order_id))->find();

		if(empty($now_order)){
			$this->error('当前订单不存在');
		}else if($now_order['paid'] == '1'){
			$this->error('该订单已经付款');
		}else if($now_order['uid'] != $this->user_session['uid']){
			if(M("Shop_order")->where(array('order_id'=>$order_id))->data(array('uid'=>$this->user_session['uid'], 'username' => $this->user_session['nickname'], 'userphone' => $this->user_session['phone']))->save()){
				D('System_order')->where(['order_id' => $order_id, 'type' => 'shop'])->save(['uid' => $this->user_session['uid']]);
				redirect(U('Pay/check',array('type'=>'shop','order_id'=>$order_id)));
			}else{
				$this->error('数据保存失败');
			}
		}else{
			redirect(U('Pay/check',array('type'=>'shop','order_id'=>$order_id)));
		}
	}


	public function appoint_order_before(){
		$order_id  = $_GET['order_id'];
		redirect(U('Pay/check',array('type'=>'balance-appoint','order_id'=>$order_id)));
	}

	public function house_order_before(){
		$order_id  = $_GET['order_id'] + 0;
		if(!$order_id){
			$this->error('传递参数有误！');
		}

		$now_order = M("House_village_pay_order")->where(array('order_id'=>$order_id))->find();
		$recharge_where['label'] = 'wap_village_'.$order_id;
		$recharge_order_info = M('User_recharge_order')->where($recharge_where)->find();
		$recharge_order_id = $recharge_order_info['order_id'];
//		if($this->now_user['now_money']>$now_order['money']){
			redirect( U('House/pay_order',array('order_id'=>$order_id)));die;
//		}
		if(empty($now_order)){
			$this->error('当前订单不存在');
		}else if($now_order['paid'] == '1'){
			$this->error('该订单已经付款');
		}else if($now_order['uid'] != $this->user_session['uid']){

			if(M("House_village_pay_order")->where(array('order_id'=>$order_id))->data(array('uid'=>$this->user_session['uid']))->save()){
				if(!$recharge_order_info){
					$recharge_data['uid'] = $this->user_session['uid'];
					$recharge_data['add_time'] = time();
					$recharge_data['money'] = $now_order['money'];
					$recharge_data['is_mobile_pay'] = 1;
					$recharge_data['label'] = 'wap_village_'.$order_id;
					$recharge_order_id = M('User_recharge_order')->data($recharge_data)->add();
				}
				redirect(U('Pay/check',array('type'=>'recharge','order_id'=>$recharge_order_id)));
			}else{
				$this->error('数据保存失败');
			}
		}else{
			if(!$recharge_order_info){
				$recharge_data['uid'] = $this->user_session['uid'];
				$recharge_data['add_time'] = time();
				$recharge_data['money'] = $now_order['money'];
				$recharge_data['is_mobile_pay'] = 1;
				$recharge_data['label'] = 'wap_village_'.$order_id;
				$recharge_order_id = M('User_recharge_order')->data($recharge_data)->add();
			}
			redirect(U('Pay/check',array('type'=>'recharge','order_id'=>$recharge_order_id)));
		}
	}

	// 社区 收银台缴费
	public function house_cashier_order_before(){
		$order_id  = $_GET['order_id'] + 0;
		if(!$order_id){
			$this->error('传递参数有误！');
		}
		redirect( U('House/pay_order_cashier',array('order_id'=>$order_id)));die;

	}

	/*全部订餐订单列表*/
	public function store_order_list($uid=0)
	{
	    if($uid > 0){
            $where = "uid={$uid} AND paid=1";
        }else{
            $where = "uid={$this->user_session['uid']} AND paid=1";
        }
		$order_list = D("Store_order")->field(true)->where($where)->order('order_id DESC')->select();
		$temp = $store_ids = array();
		foreach ($order_list as $st) {
			$store_ids[] = $st['store_id'];
		}
		$m = array();
		if ($store_ids) {
			$store_image_class = new store_image();
			$merchant_list = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_list as $li) {
				$images = $store_image_class->get_allImage_by_path($li['pic_info']);
				$li['image'] = $images ? array_shift($images) : array();
				unset($li['status']);
				$m[$li['store_id']] = $li;
			}
		}
		$list = array();
		foreach ($order_list as $ol) {
			$ol['price_pay'] = floatval($ol['balance_pay']+$ol['payment_money']+$ol['merchant_balance']+$ol['card_give_money']+$ol['score_deducte']+$ol['card_price']+$ol['coupon_price']) ;
		

	        if ($ol['card_discount']) {  
	            $ol['card_discount_money'] = round($ol['price']-($ol['price']-$ol['no_discount_money'])*$ol['card_discount']/10-$ol['no_discount_money'],2);
	        }else{
	             $ol['card_discount_money'] = 0;
	        }
	      
	        $ol['price'] = $ol['price'] - $ol['card_discount_money'];

	        $offline_money = $ol['price'] - $ol['price_pay'];

			$ol['offline_money'] = $offline_money ? round(floatval($offline_money),2) : 0;	

			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$list[] = array_merge($ol, $m[$ol['store_id']]);
			} else {
				$list[] = $ol;
			}
		}
        if($uid > 0){
            return $list;
        }
		$this->assign('order_list', $list);
		$this->display();
	}

	public function cardcode(){
		if(!empty($this->now_user['cardid'])){
			$_SESSION['tmp_cardid'] = substr($this->now_user['cardid'],0,1).substr($this->now_user['cardid'],-1).substr(uniqid('', true), 17).substr(microtime(), 2, 6);
			//$tmp[1] = substr($_SESSION['tmp_cardid'],0,4);
			//$tmp[2] = substr($_SESSION['tmp_cardid'],4,4);
			//$tmp[3] = substr($_SESSION['tmp_cardid'],8,6);
			//$_SESSION['tmp_cardid'] = implode('-',$tmp);
			D('Physical_card')->where(array('cardid'=>$this->now_user['cardid']))->setField('t_id',$_SESSION['tmp_cardid']);
			$this->assign('cardid',$this->now_user['cardid']);
			$this->display();
		}else{
			$this->error_tips('您没有绑定实体卡！请联系商家！');
		}

	}

	public function cardbarcode(){
		import('@.ORG.barcode');
		$colorFront = new BCGColor(0, 0, 0);
		$colorBack = new BCGColor(255, 255, 255);

		$font = new BCGFontFile($_SERVER['DOCUMENT_ROOT'].'/cms/Lib/ORG/barcode/font/Arial.ttf', 18);
		// Barcode Part
		$code = new BCGcode128();
		$code->setScale(2);
		$code->setColor($colorFront, $colorBack);
		$code->setFont($font);
		if($_GET['type']=='pay'){
			$this->pay_qrcode();
			$code->parse($_SESSION['tmp_payid']);
		}else{
			$code->parse($_SESSION['tmp_cardid']);
		}

		// Drawing Part
		$drawing = new BCGDrawing('', $colorBack);
		$drawing->setBarcode($code);
		$drawing->draw();

		header('Content-Type: image/png');
		$drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
	}

	public function cardqrcode(){
		import('@.ORG.phpqrcode');
		if($_GET['type']=='pay'){
			//$this->pay_qrcode();
			QRcode::png($_SESSION['tmp_payid'],false,2,8,2);
		}else{
			QRcode::png($_SESSION['tmp_cardid'],false,2,8,2);
		}
	}

	private function meal_after_refund($now_order)
	{
		$msg = '';
		//如果使用了优惠券
		if($now_order['card_id']){
			$result = D('Member_card_coupon')->add_card($now_order['card_id'],$now_order['mer_id'],$now_order['uid']);

			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			if ($result['error_code']) {
				return array('error' => 1, 'msg' => $result['msg']);
				$this->error_tips($result['msg']);
			}
			$msg = $result['msg'];
		}


		//如果使用了积分 2016-1-15
		if ($now_order['score_used_count']!=='0') {
			$order_info=unserialize($now_order['info']);
			$order_name=$order_info[0]['name']."*".$order_info[0]['num'];
			$result = D('User')->add_score($now_order['uid'],$now_order['score_used_count'],'退款 '.$order_name.$this->config['score_name'].'回滚');
			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_meal_order['status'] = 3;
			D('Group_order')->data($data_meal_order)->save();
			if ($result['error_code']) {
				return array('error' => 1, 'msg' => $result['msg']);
				$this->error_tips($result['msg']);
			}
			$msg .= $result['msg'];
		}

		//平台余额退款
		if($now_order['balance_pay'] != '0.00'){
			$add_result = D('User')->add_money($now_order['uid'],$now_order['balance_pay'],'退款 '.$now_order['order_name'].' 增加余额');

			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			if ($result['error_code']) {
				return array('error' => 1, 'msg' => $result['msg']);
				$this->error_tips($result['msg']);
			}
			$msg .= '平台余额退款成功';
			// 			if($add_result['error_code']){
			// 				$this->error_tips($add_result['msg']);
			// 			}
			// 			$go_refund_param['msg'] = $add_result['msg'];

			// 			$data_meal_order['order_id'] = $now_order['order_id'];
			// 			$data_meal_order['refund_detail'] = serialize(array('refund_time'=>time()));
			// 			$data_meal_order['status'] = 3;
			// 			D('Meal_order')->data($data_meal_order)->save();
		}
		//商家会员卡余额退款
		if($now_order['merchant_balance'] != '0.00'){
			$result = D('Member_card')->add_card($now_order['uid'],$now_order['mer_id'],$now_order['merchant_balance'],'退款 '.$now_order['order_name'].' 增加余额');

			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			if ($result['error_code']) {
				return array('error' => 1, 'msg' => $result['msg']);
				$this->error_tips($result['msg']);
			}
			$msg .= $result['msg'];
		}
		if(empty($now_order['pay_type'])){
			$data_meal_order['order_id'] = $now_order['order_id'];
			$data_meal_order['status'] = 3;
			D('Meal_order')->data($data_meal_order)->save();
			$msg .= '取消订单成功';
		}

		//退款时销量回滚
		if ($now_order['paid'] == 1 && date('m', $now_order['dateline']) == date('m')) {
			foreach (unserialize($now_order['info']) as $menu) {
				D('Meal')->where(array('meal_id' => $menu['id'], 'sell_count' => array('gt', $menu['num'])))->setDec('sell_count', $menu['num']);
			}
		}
		D("Merchant_store_meal")->where(array('store_id' => $now_order['store_id'], 'sale_count' => array('gt', 0)))->setDec('sale_count', 1);

		//退款打印

		$printHaddle = new PrintHaddle();
		$printHaddle->printit($now_order['order_id'], 'meal_order', 3);

		$mer_store = D('Merchant_store')->where(array('mer_id' => $this->mer_id, 'store_id' => $store_id))->find();
		$sms_data = array('mer_id' => $mer_store['mer_id'], 'store_id' => $mer_store['store_id'], 'type' => 'food');
		if ($this->config['sms_cancel_order'] == 1 || $this->config['sms_cancel_order'] == 3) {
			$sms_data['uid'] = $now_order['uid'];
			$sms_data['mobile'] = $now_order['phone'] ? $now_order['phone'] : $my_user['phone'];
			$sms_data['sendto'] = 'user';
			$sms_data['content'] = '您在 ' . $mer_store['name'] . '店中下的订单(订单号：' . $orderid . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_cancel_order'] == 2 || $this->config['sms_cancel_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $mer_store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = '顾客' . $now_order['name'] . '的预定订单(订单号：' . $orderid . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
			Sms::sendSms($sms_data);
		}
		return array('error' => 0, 'msg' => $msg);
	}

	public function return_refund()
	{
		echo "<html><head><meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\"></head><body><h1>这是一个return_url页面</h1></body></html>";
		die;
		$pay_class_name = ucfirst($_GET['pay_type']);
		$import_result = import('@.ORG.pay.'.$pay_class_name);
		if(empty($import_result)){
			$this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
		}
		$pay_class = new $pay_class_name('', '', $_GET['pay_type'], $pay_method[$_GET['pay_type']]['config'], '', 1);
		$go_refund_param = $pay_class->return_refund();
		if ($go_refund_param['error']) {
			$this->error_tips($go_refund_param['msg']);
		}
		$data = $go_refund_param['order_param'];
		if ($data['order_type'] == 'group') {

		} else {
			$now_order = M("Meal_order")->where(array('orderid' => $data['order_id']))->find();
		}
		$refund_param['refund_id'] = $data['sp_refund_no'];
		$refund_param['ret_code'] = $data['ret_code'];
		$refund_param['ret_detail'] = $data['ret_detail'];
		$refund_param['refund_time'] = $data['refund_time'];

		$data_meal_order['order_id'] = $now_order['order_id'];
		$data_meal_order['refund_detail'] = serialize($refund_param);
		if (empty($go_refund_param['error']) && $data['ret_code'] == 1) {
			echo "<html><head><meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\"></head><body><h1>这是一个return_url页面</h1></body></html>";
			$data_meal_order['status'] = 3;
		}
		D('Meal_order')->data($data_meal_order)->save();
		if($data_meal_order['status'] != 3){
			$this->error_tips($go_refund_param['msg']);
		}
		$result = $this->meal_after_refund($now_order);
		if ($result['error']) {
			$this->error_tips($result['msg']);
		}
		if ($now_order['meal_type'] == 1) {
			$this->success_tips($result['msg'], U('Takeout/order_detail', array('order_id' => $now_order['order_id'], 'store_id' => $now_order['store_id'], 'mer_id' => $now_order['mer_id'])));
		} else {
			$this->success_tips($result['msg'], U('Food/order_detail', array('order_id' => $now_order['order_id'], 'store_id' => $now_order['store_id'], 'mer_id' => $now_order['mer_id'])));
		}
	}

	//微信蓝牙
	public function wxblue(){
		C('open_authorize_wxpay',true);
		$share = new WechatShare($this->config,$_SESSION['openid']);
		$this->hideScript = $share->gethideOptionMenu(1);
		$this->assign('hideScript', $this->hideScript);
		$this->display();
	}

	//推广二维码
	public function my_spread_qrcode(){
		if(empty($this->now_user['openid'])){
			$this->error_tips("你没有绑定微信，无法生成推广二维码");
		}else{
			$this->assign('uid',$this->now_user['uid']);
			$spread = M('User_spread_qrcode');
			$spread_info = $spread->where(array('uid'=>$this->now_user['uid']))->find();
			$now_time = time();

			if(!empty($spread_info)){
				if($spread_info['qrcode_type']==0&&$now_time>strtotime(date('Y-m-d',$spread_info['last_time']))+86400){
					$this->get_spread_qrcode($spread_info['url'],$this->now_user['uid'], true);
					$spread_info = $spread->where(array('uid'=>$this->now_user['uid']))->find();
				}
				$effective_date = date('m月d号',$now_time+2592000);
				$spread= array("error_code"=>false,'id'=>$spread_info['qrcode_id'],'url'=>$spread_info['url'],'ticket'=>$spread_info['ticket'],'effective_date'=>$effective_date);
				$this->assign('spread_info',$spread);
			}
			
			//查找有没有底部导航
			$homeCategory = M('Home_menu_category')->where(['cat_key' => 'app_footer'])->find();
			$where_list['cat_id'] = $homeCategory['cat_id'];
			$where_list['status'] = '1';
			$home_menu_list = D('Home_menu')->home_menu_list($where_list);
			$this->assign('home_url', ($home_menu_list['list'][0]['url'] ? : $this->config['site_url'] . '/wap.php?c=Home&a=index'));
			$this->assign('home_index_full_url', get_base_url(D('House_village')->home_url));
			
			$this->display();
		}
	}

	//@param 推广海报
	public function my_spread_hb(){
		$where['uid'] = $this->user_session['uid'];
		$this->user_session = D('User')->get_user($where['uid'] );
		$spread_info = M('User_spread_qrcode')->where($where)->find();
		$res = D('Store_promote_setting')->where(array('status'=>1,'type'=>1))->select();
		if(empty($res)){
			$this->error_tips(L_('后台没有添加推广海报模板'));
		}
		$key = array_rand($res,1);
		$promote = $res[$key];
		$promote['qrcode'] = $spread_info['ticket'];
		$image_url = D('Store_promote_setting')->createImage($promote,  $spread_info['ticket'], $this->user_session, '');
		$this->assign('image',$image_url);
		$this->display();
	}

    public function my_spread()
    {
        //判断用户是否是vip推广商或者申请推广上待审核中
        $uid = $this->now_user['uid'];
        $spread = M('Merchant_spread_user')->where(['uid' => $uid])->find();
        $spread['is_apply_vip_spread'] = (new Merchant_spread_applyModel())->isApplyingVipSpread($uid);
        $spread['is_apply_city_spread'] = (new Merchant_spread_applyModel())->isApplyingCitySpread($uid);

        $config = $this->config;
        $config['open_vip_merchant_spread'] = C('config.open_vip_merchant_spread');
        $config['open_city_merchant_spread'] = C('config.open_city_merchant_spread');
        $this->assign('config', $config);
        $this->assign('spread', $spread);
        $this->display();
    }

	public function get_spread_qrcode($spread_url='',$uid=0,$is_func=false){
		$_POST['url'] = empty($spread_url) ? htmlspecialchars_decode($_POST['url']) : $spread_url;
		$_POST['uid'] = empty($uid)?$_POST['uid']:$this->now_user['uid'];
		$url = $_POST['url'];

		//将http链接转换成小程序path路径
		$getWxappPath = intval($_POST['get_wxapp_path'] ?? 0);
		if ($getWxappPath) {
			$url_arr = explode('/packapp/', $url);
			if (count($url_arr) == 2) {
				$sub5 = substr($url_arr[1], 0, 5);
				if ($sub5 === 'plat/') {
					$url_arr[1] = substr($url_arr[1], 5);
				}

				if (strpos($url_arr[1], '?') === false) {
					$wxapp_path = $url_arr[1] . '?spread_uid=' . $_POST['uid'];
				} else {
					$wxapp_path = $url_arr[1] . '&spread_uid=' . $_POST['uid'];
				}
			}else{
				//没有packapp,默认没有原生路径，跳转的webview页面
				$wxapp_path = 'pages/webview/tab_webview?url='.urlencode($url).'&spread_uid=' . $_POST['uid'];
			}

			if ($wxapp_path) {
				$this->ajaxReturn(['code' => 0, 'path' => $wxapp_path, 'message' => 'ok']);
			} else {
				$this->ajaxReturn(['code' => 1, 'path' => '', 'message' => '获取小程序路径失败，请检查复制的网址是否正确']);
			}
		}


		$url_info = parse_url($url);
		$n = preg_match('/(.*\.)?(\w+\.\w+)$/',$url_info['host'], $matches);

		if(!strpos($_POST['url'], $matches[2])&&empty($spread_url)){
			echo json_encode(array('error_code'=>1,'msg'=>'您输入的域名有误！'));exit;
		}
		$spread = M('User_spread_qrcode');
		$qrcode_id =900000000+$_POST['uid'];
		if($this->config['user_spread_qrcode_tmp']){
			$date['qrcode_type']=0;
			$res = D('Recognition')->get_tmp_qrcode($qrcode_id);
		}else{
			$date['qrcode_type']=1;
			if($this->config['pay_wxapp_important']){
				$res['ticket'] = $this->config['site_url'] . '/index.php?c=Recognition_wxapp&a=create_url_qrcode&spread_uid=' . $_POST['uid'] .'&webview_url=' . urlencode($url);
			}else{
				$res = D('Recognition')->get_new_qrcode('spread',$qrcode_id);
				$res['ticket']=$res['qrcode'];
			}
		}
		$date['qrcode_id']=$qrcode_id;
		if(strpos($url,'://')){
			$date['url']=$url;
		}else{
			$date['url']='http://'.$url;
		}
		if(!strpos($url,'openid')) {
			if (strpos($url, '?')) {
				$date['url'] .= "&openid=" . $this->now_user['openid'] . "&spread_uid=" . $_POST['uid'];
			} else {
				$date['url'] .= "?openid=" . $this->now_user['openid'] . "&spread_uid=" . $_POST['uid'];
			}
		}
		$date['url'] = html_entity_decode($date['url']);
		$date['ticket']=$res['ticket'];
		$date['last_time']=time();
		$where['uid']=$_POST['uid'];
		if($result=$spread->where($where)->find()){
			$spread->where($where)->save($date);
		}else{
			$date['create_time']=time();
			$date['uid']=$_POST['uid'];
			$id = $spread->data($date)->add();
		}
		if(!empty($result)){
			$id = $result['id'];
		}
		$res['qrcode_type']=$date['qrcode_type'];
		if($is_func){
			return $res['ticket'];
		}
        echo json_encode(array('error_code'=>0,'msg'=>$res,'id'=>$id));exit;
	}

	//佣金过户
	public function my_spread_change(){
		if(IS_POST){
			if($_POST['change_user']==$this->now_user['phone']){
				$this->AjaxReturn(array('error_code'=>1,'msg'=>'过户用户不能是自己'));
			}
			$bind_user = D('User')->get_user( $_POST['change_user'],'phone');
			if(empty($bind_user)){
				$this->AjaxReturn(array('error_code'=>1,'msg'=>'用户不存在'));
			}else{
				M('User')->where(array('uid'=>$this->now_user['uid']))->setField('spread_change_uid',$bind_user['uid']);
				$this->AjaxReturn(array('error_code'=>0,'msg'=>'绑定成功'));
			}
		}else{
			if($this->now_user['spread_change_uid']>0){
				$this->assign('change_user',D('User')->get_user($this->now_user['spread_change_uid']));
			}
			$this->display();
		}
	}

	//ajax 获取用户列表
	public function ajax_search_user(){
		$key = $_POST['key'];
		$value = $_POST['value'];
		$res = M('User')->field('nickname,phone')->where(array($key=>array('like','%'.$value.'%')))->select();
		if(empty($res)){
			$this->AjaxReturn(array('error_code'=>1,'msg'=>'没有相关用户'));
		}else{
			$this->AjaxReturn(array('error_code'=>0,'msg'=>$res));
		}
	}

	//	我的实名认证
	public function authentication(){
		$this->user_sessions();
		$uid	=	$this->user_session['uid'];
		$find	=	M('User_authentication')->field(true)->where(array('uid'=>$uid))->find();
		$this->assign('find',$find);
		$this->display();
	}

	//	我的实名认证提交
	public function authentication_json(){
		$where['uid']	=	$this->user_session['uid'];
		$find	=	M('User_authentication')->where($where)->find();
		$auth_data	=	array(
			'user_truename'			=>	$_POST['user_truename'],
			'user_id_number'		=>	$_POST['user_id_number'],
			'authentication_img'	=>	$_POST['authentication_img'],
			'authentication_back_img'=>	$_POST['authentication_back_img'],
			'hand_authentication'	=>	$_POST['hand_authentication'],
			'authentication_time'	=>	$_SERVER['REQUEST_TIME'],
			'examine_time'			=>	0,
			'authentication_status'	=>	0,
		);
		if($find){
			$user_authentication	=	M('User_authentication')->where($where)->data($auth_data)->save();
		}else{
			$auth_data['uid']	=	$this->user_session['uid'];
			$user_authentication	=	M('User_authentication')->data($auth_data)->add();
		}
		if(empty($user_authentication) && !$find){
			$this->returnCode('40000031');
		}else{
			$data['truename']	=	$_POST['user_truename'];
			$data['real_name']	=	2;
			$save	=	D('User')->scenic_save_user($where,$data);
			if($save){
				$_SESSION['user']['real_name']	=	2;
				$_SESSION['user']['truename']	=	$data['truename'];
			}
		}

		if(define(IS_HOUSE) == true){
			$url	=	U('village_my',array('village_id'=>$_POST['village_id']));
		}else{
			$url	=	$this->config['site_url'].U('myinfo');
		}
		$this->returnCode(0,$url);
	}

	//	我的实名认证展示
	public function authentication_index(){
		$this->user_sessions();
		$authentication	=	D('User_authentication')->field(true)->order('authentication_time DESC')->where(array('uid'=>$this->user_session['uid']))->find();
		if($authentication){
			$store_image_class = new scenic_image();
			$a_img = strstr($authentication['authentication_img'], ',',true);
			$b_img = strstr($authentication['authentication_back_img'], ',',true);
			if($a_img){
				$authentication['authentication_img'] = $store_image_class->get_image_by_path($authentication['authentication_img'],$this->config['site_url'],'aguide','1');
			}
			if($b_img){
				$authentication['authentication_back_img'] = $store_image_class->get_image_by_path($authentication['authentication_back_img'],$this->config['site_url'],'aguide','1');
			}
		}else{
			redirect(U('authentication'));
		}
		$this->assign('authentication',$authentication);
		$this->display();
	}

	/***图片上传** */
	public function authenticationUpload() {
		$mulu=isset($_GET['ml']) ? trim($_GET['ml']):'group';
		$mulu=!empty($mulu) ? $mulu : 'group';
		$filename = trim($_POST['filename']);
		$img = $_POST[$filename];
		$img = str_replace('data:image/png;base64,', '', $img);
		$img = str_replace(' ', '+', $img);
		$imgdata = base64_decode($img);
//		$img_order_id = sprintf("%09d",$this->user_session['uid']);
//		$rand_num = mt_rand(10,99).'/'.substr($img_order_id,0,3).'/'.substr($img_order_id,3,3).'/'.substr($img_order_id,6,3);
		$getupload_dir = "/upload/".$mulu."/" .$this->user_session['uid'];

		$upload_dir = "." . $getupload_dir;
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
		$newfilename = $mulu.'_' . date('YmdHis') . '.jpg';
		$save = file_put_contents($upload_dir . '/' . $newfilename, $imgdata);
		if ($save) {
			$this->dexit(array('error' => 0, 'data' => array('code' => 1, 'siteurl'=>$this->config['site_url'],'imgurl' =>$getupload_dir . '/' . $newfilename, 'msg' => '')));
		} else {
			$this->dexit(array('error' => 1, 'data' => array('code' => 0, 'url' => '', 'msg' => '保存失败！')));
		}
	}

	public function is_group_share(){
		if(!M('Group_order')->where(array('order_id'=>$_POST['order_id']))->save(array('is_share_group'=>1))){
			$this->error('更新组团购分享失败！');
		}else{
			$date['fid']=$_POST['order_id'];
			$date['uid']=$_POST['uid'];
			$date['order_id']=$_POST['order_id'];
			$result = M('Group_share_relation')->where($date)->find();
			if($result){
				$this->success('已经生成团购分组，不能再生成了！');
			}
			M('Group_share_relation')->add($date);
			$this->success('更新组团购分享成功！');
		}
	}

	public function ajax_group_share_num(){
		$uid = $_POST['uid'];
		$order_id = $_POST['order_id'];
		if(!$order_id||!uid){
			exit(json_encode(array('error_code'=>1,'msg'=>'传递参数有误！')));
		}
		$num = D('Group_share_relation')->get_share_num($uid,$order_id);

		exit(json_encode(array('error_code'=>0,'num'=>(int)$num)));

	}

	public function ajax_now_pin_num(){
		$uid = $_POST['uid'];
		$order_id = $_POST['order_id'];
		if(!$order_id||!uid){
			exit(json_encode(array('error_code'=>1,'msg'=>'传递参数有误！')));
		}
		$num = D('Group_start')->get_group_start_by_order_id($order_id);

		exit(json_encode(array('error_code'=>0,'num'=>$num)));

	}

	public  function  ajax_group_user(){
		$uid = $_POST['uid'];
		$order_id = $_POST['order_id'];
		$uids = explode(',',substr($_POST['uids'],0,-1));

		if(!$order_id||!uid){
			exit(json_encode(array('error_code'=>1,'msg'=>'传递参数有误！')));
		}
		if(empty($_POST['type'])){

			$res['user_arr'] = D('Group_share_relation')->get_share_user($uid,$order_id);
		}else{
			$res['user_arr'] =  D('Group_start')->get_buyerer_by_order_id($order_id);
		}
		$group_start =  D('Group_start')->get_group_start_by_order_id($order_id);
		foreach($res['user_arr'] as &$v){
			if(empty($v['pay_time'])){
				$v['pay_time'] = $group_start['last_time'];
			}
			$v['pay_time']  = date('Y-m-d H:i:s',$v['pay_time']);

			if(in_array($v['uid'],$uids)){
				$res['in'][] = $v['uid'];
			}
		}
		foreach($uids as $vv){
			if(!in_array($vv,$res['in'])){
				$res['not_in'][] = $vv;
			}
		}
		exit(json_encode(array('error_code'=>0,'res'=>$res)));
	}

	public function change_is_share(){
		if(!M('Group_order')->where(array('order_id'=>$_POST['order_id']))->save(array('is_share_group'=>2))){
			exit(json_encode(array('status'=>0,'msg'=>'取消失败！')));
		}
	}

	public function ajax_wap_user_del(){
		if(IS_AJAX){
			$order_id = $_POST['order_id'] + 0;
			if(empty($order_id)){
				exit(json_encode(array('msg' => '传递参数有误！','status' => 0)));
			}

			$database_appoint_order = D('Appoint_order');
			$where['order_id'] = $order_id;
			$data['is_del'] = 5;
			$data['del_time']= time();
            $now_order = $database_appoint_order->where($where)->find();
            if($now_order['service_status'] == 1){
                $this->returnCodeError('订单已服务!');
            }
			$result = $database_appoint_order->where($where)->data($data)->save();
			if(!empty($result)){
                $now_order['paid'] && $database_appoint_order->cancelPaidOrder($order_id);
                /*-----记录系统订单信息 订单取消成功 改写状态已取消 19/1/20----*/
                D("System_order")->save_order_status('appoint',$order_id,5);
				exit(json_encode(array('status'=>1,'msg'=>'取消成功！')));
			}else{
				exit(json_encode(array('status'=>0,'msg'=>'取消失败！')));
			}
		}else{
			$this->error_tips('访问页面错误！~~~');
		}
	}

	public function ajax_wap_appoint_del(){
		if(IS_AJAX){
			$order_id = $_POST['order_id'] + 0;
			if(empty($order_id)){
				exit(json_encode(array('msg' => '传递参数有误！','status' => 0)));
			}

			if($this->config['appoint_rule']){
				$database_appoint_order = D('Appoint_order');
				$now_order = $database_appoint_order->get_order_by_id($this->user_session['uid'] , $order_id);
				$appoint_service_time = strtotime($now_order['appoint_date']  . ' ' .  $now_order['appoint_time']);
				$appoint_before_cancel_time = $this->config['appoint_before_cancel_time'] * 60;
				if($now_order['paid']==3){
					exit(json_encode(array('msg' => '已经退款了，不能再退了！','status' => 1)));
				}
                if( (time() > ($appoint_service_time - $appoint_before_cancel_time)) && ($now_order['payment_status'] > 0)){
                    if($now_order['payment_status'] > 0){
						$where['order_id'] = $order_id;
						$fields['paid'] = 3;
						$fields['del_time'] = time();
						if($database_appoint_order->where($where)->data($fields)->save()){
							$now_order['order_type']='appoint';
							$now_appoint = M('Appoint')->field('appoint_name')->where(array('appoint_id'=>$now_order['appoint_id']))->find();

							if($now_order['product_payment_price']){
								$tmp_price = $now_order['product_payment_price'];
							}else{
								$tmp_price = $now_order['payment_price'];
							}


							$order_info['order_id'] = $now_order['order_id'];
							$order_info['store_id'] = $now_order['store_id'];
							$order_info['mer_id'] = $now_order['mer_id'];
							$order_info['order_type'] = 'appoint';
							$order_info['balance_pay'] = $now_order['balance_pay'];
							$order_info['score_deducte'] = $now_order['score_deducte'];
							$order_info['payment_money'] = $now_order['pay_money'];
							$order_info['is_own'] = $now_order['is_own'];
							$order_info['merchant_balance'] = $now_order['merchant_balance'];
							$order_info['score_used_count'] = $now_order['score_used_count'];
							$order_info['money'] = $order_info['balance_pay'] + $order_info['score_deducte'] + ($order_info['is_own'] ? 0 : $order_info['payment_money']) + $order_info['merchant_balance'];

							if($now_order['product_id'] > 0){
								$order_info['total_money'] = $now_order['product_price'];
							}else{
								$order_info['total_money'] = $now_order['appoint_price'];
							}

							//$result = D('Merchant_money_list')->add_money($now_order['mer_id'] , '用户预约 '.$now_appoint['appoint_name'].'*1 取消预约，定金' . $order_info['money'] . '记入收入',$order_info);
							$order_info['desc'] = '用户预约 '.$now_appoint['appoint_name'].'*1 取消预约，定金' . $order_info['money'] . '记入收入';
							 $order_info['score_discount_type']=$now_order['score_discount_type'];
							$result =D('SystemBill')->bill_method($order_info['is_own'],$order_info);
							$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'appoint');
							if ($this->config['user_cancel_appoint_order'] == 1 || $this->config['user_cancel_appoint_order'] == 3) {
								$sms_data['uid'] = $now_order['uid'];
								$sms_data['mobile'] = $now_order['phone'];
								$sms_data['sendto'] = 'user';
								$sms_data['content'] = '您购买 '.$now_appoint['appoint_name'].'的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
								Sms::sendSms($sms_data);
							}
							if ($this->config['user_cancel_appoint_order'] == 2 || $this->config['user_cancel_appoint_order'] == 3) {
								$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
								$sms_data['uid'] = 0;
								$sms_data['mobile'] = $merchant['phone'];
								$sms_data['sendto'] = 'merchant';
								$sms_data['content'] = '顾客购买的' . $now_appoint['appoint_name'] . '的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
								Sms::sendSms($sms_data);
							}
							if(!$result['error_code']){

                                // 取消成功推送消息给 商户
                                if ($now_order['mer_id']) {
                                    // 获取对应商户user账号openid
                                    $uid = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->getField('uid');
                                    $merchant_user_openid = M('User')->where(array('uid' => $uid))->getField('openid');

                                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                    // 处理备注
                                    $remark = '服务名称：'.$now_appoint['appoint_name'];
                                    if ($now_order['product_id'] > 0 && $now_order['product_price']) {
                                        $remark .= ';服务全价：'.$now_order['product_price'];
                                    } elseif ($now_order['appoint_price']) {
                                        $remark .= ';服务全价：'.$now_order['appoint_price'];
                                    }
                                    if ($now_order['appoint_time']) {
                                        $remark .= ';预约时间：'.$now_order['appoint_time'];
                                    }
                                    $model->sendTempMsg('TM00017',
                                        array(
                                            'wecha_id' => $merchant_user_openid,
                                            'first' => '用户在' . date('Y-m-d H:i',time()) . '时，取消了'.$now_appoint['appoint_name'].'预约订单，取消成功，订单号：'. $now_order['order_id'],
                                            'OrderSn' => $now_order['order_id'],
                                            'OrderStatus' => '取消成功',
                                            'remark' => $remark
                                        )
                                    );
                                }
                                // 如果存在预约的工作者 直接推送消息 如果订单里不存在 查询下抢单的工作者 在推送消息
                                if ($now_order['merchant_worker_id']) {
                                    // 存在工作人员id 直接推送
                                    $database_merchant_workers = M('Merchant_workers');
                                    $worker_where['merchant_worker_id'] = $now_order['merchant_worker_id'];
                                    $now_worker = $database_merchant_workers->where($worker_where)->find();
                                    if ($now_worker['openid']) {
                                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                        $first = '用户在' . date('Y-m-d H:i',time()) . '时，取消订单，请及时查看，订单号：'. $order_id;
                                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                                        $model->sendTempMsg('TM00017',
                                            array(
                                                'href' => $href,
                                                'wecha_id' => $now_worker['openid'],
                                                'first' => $first,
                                                'OrderSn' => $order_id,
                                                'OrderStatus' => '取消成功',
                                                'remark' => '\n请点击查看详细信息！'
                                            )
                                        );
                                    }
                                } else {
                                    // 查询下抢单的工作者 在推送消息
                                    $database_appoint_supply = M('Appoint_supply');
                                    $merchant_worker_id = $database_appoint_supply->where(array('appoint_id' => $now_order['appoint_id']))->getField('worker_id');
                                    if ($merchant_worker_id > 0) {
                                        $database_merchant_workers = M('Merchant_workers');
                                        $worker_where['merchant_worker_id'] = $merchant_worker_id;
                                        $now_worker = $database_merchant_workers->where($worker_where)->find();
                                        if ($now_worker['openid']) {
                                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                            $first = '用户在' . date('Y-m-d H:i',time()) . '时，取消订单，请及时查看，订单号：'. $order_id;
                                            $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                                            $model->sendTempMsg('TM00017',
                                                array(
                                                    'href' => $href,
                                                    'wecha_id' => $now_worker['openid'],
                                                    'first' => $first,
                                                    'OrderSn' => $order_id,
                                                    'OrderStatus' => '取消成功',
                                                    'remark' => '\n请点击查看详细信息！'
                                                )
                                            );
                                        }
                                    }
                                }


                                /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/20----*/
                                D("System_order")->save_order_status('appoint',$order_id,4);
								exit(json_encode(array('status' => 1,'msg' => '取消成功！')));
							}else{
								exit(json_encode(array('status' => 0,'msg' => '取消失败！')));
							}
						}
					}
				}else{
					$order_id = $_POST['order_id'] + 0;
					if(empty($order_id)){
						exit(json_encode(array('msg' => '传递参数有误！','status' => 0)));
					}
                    $now_appoint = M('Appoint')->field('appoint_name')->where(array('appoint_id'=>$now_order['appoint_id']))->find();

					$database_appoint_order = D('Appoint_order');
					$where['order_id'] = $order_id;
					$data['is_del'] = 5;
					$data['del_time']= time();
					$result = $database_appoint_order->where($where)->data($data)->save();
					if(!empty($result)){
						$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'appoint');
						if ($this->config['user_cancel_appoint_order'] == 1 || $this->config['user_cancel_appoint_order'] == 3) {
							$sms_data['uid'] = $now_order['uid'];
							$sms_data['mobile'] = $now_order['phone'];
							$sms_data['sendto'] = 'user';
							$sms_data['content'] = '您购买 '.$now_appoint['appoint_name'].'的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
							Sms::sendSms($sms_data);
						}
						if ($this->config['user_cancel_appoint_order'] == 2 || $this->config['user_cancel_appoint_order'] == 3) {
							$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
							$sms_data['uid'] = 0;
							$sms_data['mobile'] = $merchant['phone'];
							$sms_data['sendto'] = 'merchant';
							$sms_data['content'] = '顾客购买的' . $now_appoint['appoint_name'] . '的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
							Sms::sendSms($sms_data);
						}

                        // 取消成功推送消息给 商户
                        if ($now_order['mer_id']) {
                            // 获取对应商户user账号openid
                            $uid = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->getField('uid');
                            $merchant_user_openid = M('User')->where(array('uid' => $uid))->getField('openid');

                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            // 处理备注
                            $remark = '服务名称：'.$now_appoint['appoint_name'];
                            if ($now_order['product_id'] > 0 && $now_order['product_price']) {
                                $remark .= ';服务全价：'.$now_order['product_price'];
                            } elseif ($now_order['appoint_price']) {
                                $remark .= ';服务全价：'.$now_order['appoint_price'];
                            }
                            if ($now_order['appoint_time']) {
                                $remark .= ';预约时间：'.$now_order['appoint_time'];
                            }
                            $model->sendTempMsg('TM00017',
                                array(
                                    'wecha_id' => $merchant_user_openid,
                                    'first' => '用户在' . date('Y-m-d H:i',time()) . '时，取消了'.$now_appoint['appoint_name'].'预约订单，取消成功，订单号：'. $now_order['order_id'],
                                    'OrderSn' => $now_order['order_id'],
                                    'OrderStatus' => '取消成功',
                                    'remark' => $remark
                                )
                            );
                        }
                        // 如果存在预约的工作者 直接推送消息 如果订单里不存在 查询下抢单的工作者 在推送消息
                        if ($now_order['merchant_worker_id']) {
                            // 存在工作人员id 直接推送
                            $database_merchant_workers = M('Merchant_workers');
                            $worker_where['merchant_worker_id'] = $now_order['merchant_worker_id'];
                            $now_worker = $database_merchant_workers->where($worker_where)->find();
                            if ($now_worker['openid']) {
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $first = '用户在' . date('Y-m-d H:i',time()) . '时，取消订单，请及时查看，订单号：'. $order_id;
                                $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                                $model->sendTempMsg('TM00017',
                                    array(
                                        'href' => $href,
                                        'wecha_id' => $now_worker['openid'],
                                        'first' => $first,
                                        'OrderSn' => $order_id,
                                        'OrderStatus' => '取消成功',
                                        'remark' => '\n请点击查看详细信息！'
                                    )
                                );
                            }
                        } else {
                            // 查询下抢单的工作者 在推送消息
                            $database_appoint_supply = M('Appoint_supply');
                            $merchant_worker_id = $database_appoint_supply->where(array('appoint_id' => $now_order['appoint_id']))->getField('worker_id');
                            if ($merchant_worker_id > 0) {
                                $database_merchant_workers = M('Merchant_workers');
                                $worker_where['merchant_worker_id'] = $merchant_worker_id;
                                $now_worker = $database_merchant_workers->where($worker_where)->find();
                                if ($now_worker['openid']) {
                                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                    $first = '用户在' . date('Y-m-d H:i',time()) . '时，取消订单，请及时查看，订单号：'. $order_id;
                                    $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                                    $model->sendTempMsg('TM00017',
                                        array(
                                            'href' => $href,
                                            'wecha_id' => $now_worker['openid'],
                                            'first' => $first,
                                            'OrderSn' => $order_id,
                                            'OrderStatus' => '取消成功',
                                            'remark' => '\n请点击查看详细信息！'
                                        )
                                    );
                                }
                            }
                        }
                        /*-----记录系统订单信息 订单取消成功 改写状态已取消 19/1/20----*/
                        D("System_order")->save_order_status('appoint',$order_id,5);

                        exit(json_encode(array('status'=>1,'msg'=>'取消成功！')));
					}else{
						exit(json_encode(array('status'=>0,'msg'=>'取消失败！')));
					}
				}
			}
		}else{
			$this->error_tips('访问页面错误！~~~');
		}
	}

	public function ajax_wap_appoint_pay_balance(){
		if(IS_POST){
			$order_id = $_POST['order_id'] + 0;
			if(empty($order_id)){
				exit(json_encode(array('msg' => '传递参数有误！','status' => 0)));
			}

			if($this->config['appoint_rule']){
				$database_appoint_order = D('Appoint_order');
				$now_order = $database_appoint_order->get_order_by_id($this->user_session['uid'],$order_id);

				if(empty($now_order)){
					exit(json_encode(array('msg' => '该订单不存在！','status' => 0)));
				}

				//$now_user = D('User')->get_user($now_order['uid']);
				$now_pay_money = $now_order['product_price'] - $now_order['product_payment_price'];

				if(!$now_pay_money){
					$now_pay_money = $now_order['appoint_price'] - $now_order['payment_money'];
				}

				if($now_pay_money <= 0){
					exit(json_encode(array('status' => 0,'msg' => '数据处理有误！')));
				}

				$href = U('Pay/check', array('order_id' => $order_id, 'type' => 'balance-appoint'));
				exit(json_encode(array('url' => $href,'status' => 1,'msg'=>'余额不足')));
			}
		}else{
			$this->error_tips('访问页面错误！~~~');
		}
	}

//	public function appoint_order_refund()
//	{
//		if (empty($this->user_session)) {
//			$this->error_tips('请先进行登录！');
//		}
//
//
//		$this->display();
//	}

	public function shop_order_refund()
	{
		if (empty($this->user_session)) {
			$this->error_tips(L_('请先进行登录！'));
		}

		$order_id = intval($_GET['order_id']);
		$now_order = D("Shop_order")->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']));
		if (empty($now_order)) {
			$this->error_tips(L_('当前订单不存在'));
		}
		if (!($now_order['paid'] == 1 && ($now_order['status'] == 0 || $now_order['status'] == 5 || $now_order['status'] == 1))) {
			$this->error_tips(L_('当前订单店员正在处理中，不能退款或取消'));
		}
		if (empty($now_order['paid'])) {
			$this->error_tips(L_('当前订单还未付款！'));
		}
		/*添加拼团订单判断*/
		if($now_order['group_id']>0){
			$team_id=$now_order['team_id'];
			if($team_id>0){
				$team_status=M('Mall_group_team')->where(array('id'=>$team_id))->getField('status');
				if($team_status!=1){
					$this->error_tips(L_('抱歉，未成功拼团订单不能手动退款或取消！'));
				}
			}
		}

		if (!($now_order['paid'] == 1 && ($now_order['status'] == 0 || $now_order['status'] == 5|| $now_order['status'] == 1))) {
			$this->error_tips(L_('订单必须是未消费状态才能取消！'), U('Shop/status',array('order_id' => $now_order['order_id'])));
		} elseif ($now_order['status'] > 3 && !($now_order['paid'] == 1 && $now_order['status'] == 5)) {
			$this->redirect(U('Shop/status',array('order_id' => $now_order['order_id'])));
		}
		$now_order['pay_type_txt'] = D('Pay')->get_pay_name($now_order['pay_type'], $now_order['is_mobile_pay']);
		$service_charge = M("Payment_money_order")->where("paid_money > 0 AND order_id='".$order_id."'")->field("service_charge")->find();
		$now_order['service_charge'] = $service_charge ? $service_charge['service_charge'] : 0.00;
		$this->assign('now_order', $now_order);
		$this->display();
	}
	public function test_apply_cancle(){
		import('@.ORG.plan.plan_shop_order_apply_cancel');
		$m = new plan_shop_order_apply_cancel();
		$m->runTask();
	}
	//取消订单
	public function shop_order_check_refund()
	{
		if (empty($this->user_session)) {
			$this->error_tips(L_('请先进行登录！'));
		}
		$order_id = intval($_GET['order_id']);
		$now_order = D("Shop_order")->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']));
		$refund_rate = D("Shop_order")->get_period_refund_rate($now_order['order_id']);//适配周期购期数退款
		if(empty($now_order)){
			$this->error_tips(L_('当前订单不存在'));
		}
		if($_GET['apply']){//这里是[申请]取消订单
			if($now_order['status']<>0||$now_order['paid']<>1){
				//$this->error_tips(L_('抱歉：订单状态不正确，不能申请取消订单！'));
			}

			$apply_count = D('Shop_order_log')->where(['order_id' => $order_id, 'status' => 41])->count();
			if($apply_count>1){
				$this->error_tips(L_('抱歉，最多只可申请两次退款！'));
			}
			$save_data = [
				'status' => 11,
				'apply_cancle_time' => time(),
			];
			$r = M('Shop_order')->where(['order_id'=>$order_id])->data($save_data)->save();
			if($r){
				D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 41,'note'=>$apply_count+1));

				$this->success_tips('申请成功',U('Mall/status',['order_id'=>$order_id]));
			}else{
				$this->success_tips('操作失败',U('Mall/status',['order_id'=>$order_id]));
			}
			exit;
		}
		if($this->config['open_allinyun']==1){
			import('@.ORG.AccountDeposit.AccountDeposit');
			$deposit = new AccountDeposit('Allinyun');
			$allyun = $deposit->getDeposit();

			$allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid']);

			if($allinyun_user['bizUserId']!=''){
				$allyun->setUser($allinyun_user);
				$params['bizOrderNo'] = 'shop_'.$now_order['orderid'];
				$params['oriBizOrderNo'] = $now_order['third_id'];
				$params['amount'] = intval($now_order['payment_money']*100);
				$res = $allyun->refundApply($params);

				if($res['status']=='OK'){
					$data_shop_order['order_id'] = $now_order['order_id'];
					$data_shop_order['status'] = 4;
					$data_shop_order['last_time'] = time();
					$data_shop_order['refund_detail'] = serialize($res);
					$return = $this->shop_refund_detail($now_order, $now_order['store_id']);
					if ($return['error_code']) {
						$this->error_tips($return['msg']);
					}
					D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10));
					D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));

                    $printHaddle = new PrintHaddle();
                    $printHaddle->printit($order_id, 'shop_order', 3);
					D('Shop_order')->data($data_shop_order)->save();
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'shop';
                    $params['order_id'] = $order_id;
                    $params['title'] = '订单取消提醒';
                    $params['content'] = '用户取消订单';
                    invoke_v20_service($url, [$params]);
					$this->success_tips(L_('退款成功'), 'javascript:history.go(-2);');
				}else{
					$this->error_tips(L_('退款失败') . '，' . $res['message']);
				}
			}
		}


		$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
		
		$store_id = $now_order['store_id'];
		$this->mer_id = $now_order['mer_id'];
		if (!($now_order['paid'] == 1 && ($now_order['status'] == 0 || $now_order['status'] == 5))) {
			$this->error_tips(L_('当前订单店员正在处理中，不能退款或取消'));
		}
		if (empty($now_order['paid'])) {
			$this->error_tips(L_('当前订单还未付款！'));
		}
		if (!($now_order['paid'] == 1 && ($now_order['status'] == 0 || $now_order['status'] == 5))) {
			$this->error_tips(L_('订单必须是未消费状态才能取消！'), U('Shop/status',array('order_id' => $now_order['order_id'])));
		} elseif ($now_order['status'] > 3 && !($now_order['paid'] == 1 && $now_order['status'] == 5)) {
			$this->redirect(U('Shop/status',array('order_id' => $now_order['order_id'])));
		}
		$mer_store = D('Merchant_store')->where(array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']))->find();
		$my_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
		
		//线下支付退款
		$data_shop_order['cancel_type'] = 5;//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）
		if ($now_order['pay_type'] == 'offline') {
			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize(array('refund_time' => time()));
			$data_shop_order['status'] = 4;
			if (D('Shop_order')->data($data_shop_order)->save()) {
				$return = $this->shop_refund_detail($now_order, $store_id);
				if ($return['error_code']) {
					$this->error_tips($return['msg']);
				} else {
                    //用户取消订单  //对接叮叮配送===>郭先森
					if($now_order['dingding_trade_no']!=''){
						//处理叮叮取消订单======>郭先森
						$merchantStore = D('Merchant_store')->where(array('store_id'=>$now_order['store_id'],'is_open_dingding'=>1))->find();
						import('@.ORG.Dingding');
						$getShopInfo = new Dingding();
						$postData = array(
							'headquarters_id'=>1,
							'username'=>$merchantStore['dingding_user_name'],
							'per_userid'=>$merchantStore['dingding_per_userid'],
						);
						$res = $getShopInfo->getShopInfo($postData);
						$res = json_decode($res,true);
						if($res['code'] == 0){
							return array('error_code' => 1, 'msg' => L_('店鋪信息不存在,请联系开发者！'));
						}
						$dingDing = new Dingding(C('config.dingding_fromplatform'),C('config.dingding_form_app_key'),C('config.dingding_appid'),$res['result']['code'],$merchantStore['dingding_per_userid'],$merchantStore['dingding_user_name'],$merchantStore['dingding_pass'],$res['result']['phone']);
						$postData = array(
							'origin_id'=>$now_order['real_orderid'],
							'cancel_id'=>4,
							'cancelReason'=>'顾客取消订单',
							'timestamp'     => time(),
						);
						$res = $dingDing->orderCancel($postData);

						$res = json_decode($res,true);
						fdump($res,"dingding---yonghu---orderCancel",true);
					}
                    D("System_order")->save_order_status($now_order['order_from'] == '1' ? 'mall' : 'shop',$order_id, 4);
                    D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10));
                    D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
					D('Shop_order')->cancelTwOrder($order_id);
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'shop';
                    $params['order_id'] = $order_id;
                    $params['title'] = '订单取消提醒';
                    $params['content'] = '用户取消订单';
                    invoke_v20_service($url, [$params]);
					$this->success_tips(L_('您使用的是线下支付！订单状态已修改为已退款。'),U('Shop/status',array('order_id' => $now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
				}
			} else {
				$this->error_tips(L_('取消订单失败！请重试。'));
			}
		} else {
            //开通平台会员卡金额不能扣除，优先级：在线支付 > 平台余额
            if ($now_order['is_open_membership_card'] == 1 && $now_order['membership_card_price'] > 0) {
                $left = $now_order['membership_card_price'];
                $fields = ['payment_money', 'balance_pay', 'merchant_balance', 'card_give_money'];
                foreach ($fields as $f) {
                    if ($now_order[$f] >= $left) {
                        $now_order[$f] = $now_order[$f] - $left;
                        $left = 0;
                        break;
                    } else {
                        $left = $left - $now_order[$f];
                        $now_order[$f] = 0;
                    }
                }
            }

			//员工卡退款
			$employee_score_pay = $now_order['employee_score_pay'];
			$employee_balance_pay = $now_order['employee_balance_pay'];
			if($employee_score_pay > 0 || $employee_balance_pay > 0){
				$condition = [];
				$condition['uid'] = $now_order['uid'];
				$condition['user_id'] = $now_order['employee_card_user_id'];
				$condition['status'] = 1;
				$employeeCardUserModel = M('Employee_card_user');
				$employeeCardUser = $employeeCardUserModel->where($condition)->find();
				if($employeeCardUser){
					$shop_alias_name = c('config.shop_alias_name');
					$employeeCardLogModel = M('Employee_card_log');
					$employeeCondition = [
						'user_id'	=>	$now_order['employee_card_user_id']
					];
					$time = time();
					if($employee_score_pay > 0){
						$employeeSaveData = [
							'card_score'	=>	$employeeCardUser['card_score'] + $employee_score_pay,
							'last_time'		=>	$time
						];
						$employeeCardUserModel->where($employeeCondition)->data($employeeSaveData)->save();

						$employeeCardLogModel->add([
							'card_id'		=>	$employeeCardUser['card_id'],
							'user_id'		=>	$employeeCardUser['user_id'],
							'mer_id'		=>	$employeeCardUser['mer_id'],
							'uid'			=>	$employeeCardUser['uid'],
							'num'			=>	$employee_score_pay,
							'type'			=>	'score',
							'change_type'	=>	'increase',
							'description'	=>	$shop_alias_name . '商品退款，增加'. $employee_score_pay . '积分，订单号：' . $now_order['real_orderid'],
							'user_desc'		=>	$shop_alias_name . '商品退款，增加'. $employee_score_pay . '积分',
							'operate_type'	=>	'user',
							'operate_id'	=>	$now_order['uid'],
							'add_time'		=>	$time,
							'log_type'		=>	10,
							'pay_type'		=>	'score'
						]);
					}

					if($employee_balance_pay > 0){
						$employeeSaveData = [
							'card_money'	=>	$employeeCardUser['card_money'] + $employee_balance_pay,
							'last_time'		=>	$time
						];
						$employeeCardUserModel->where($employeeCondition)->data($employeeSaveData)->save();

						$employeeCardLogModel->add([
							'card_id'		=>	$employeeCardUser['card_id'],
							'user_id'		=>	$employeeCardUser['user_id'],
							'mer_id'		=>	$employeeCardUser['mer_id'],
							'uid'			=>	$employeeCardUser['uid'],
							'num'			=>	$employee_balance_pay,
							'type'			=>	'money',
							'change_type'	=>	'increase',
							'description'	=>	$shop_alias_name . '商品退款，增加余额'. $employee_balance_pay . '元，订单号：' . $now_order['real_orderid'],
							'user_desc'		=>	$shop_alias_name . '商品退款，增加余额'. $employee_balance_pay . '元',
							'operate_type'	=>	'user',
							'operate_id'	=>	$employeeCardUser['uid'],
							'add_time'		=>	$time,
							'log_type'		=>	10,
							'pay_type'		=>	'money'
						]);

					}

				}else{
					$employee_score_pay = $employee_balance_pay = 0;
					fdump_sql(['msg'=>'员工卡不存在',$now_order], 'employee_pay_refund_error');
				}
			}

			if ($now_order['payment_money'] != '0.00') {
				
				$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>['IN',['shop','mall']],'pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();

				if($now_order['payment_money'] == $payment_money_order['payment_money']){
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $now_order['payment_money'] = $payment_money_order['paid_money'];
                    if($payment_money_order['pay_rate'] > 0){
                    	$now_order['payment_money'] = $now_order['payment_money']*$payment_money_order['pay_rate'];
                    }
                }

				if(C("config.is_refund_balance") == 1 && $payment_money_order){
					//2019年6月17日，新增需求， 退款到余额 add by lumin
					$alias_name=C('config.shop_alias_name');
					if($now_order['order_from']==1){
						$alias_name=C('config.mall_alias_name');
					}

					$refund_online_money = $payment_money_order['paid_money']*$refund_rate;

					// 是否可退到用户余额
					$refund_status = true;

					// 子商户需要扣除商家余额
					if ($now_order['is_own']) {
						$type = $now_order['order_from'] == '1' ? 'mall' : 'shop';

                        $mdata = D('Merchant_money_list')
                            ->field('percent,type,money, system_take')
                            ->where(array('type' => $type,'income'=>1, 'order_id' => $now_order['real_orderid']))
                            ->find();
                        $returnSystemTake = $returnTakePercent = 0;
                        if($mdata['system_take'] > 0){
                            $returnSystemTake = bcmul(bcdiv($refund_online_money,$mdata['money'],2), $mdata['system_take'],2);
                            $returnTakePercent = $mdata['percent'];
                        }
                        
						$result = D('Merchant_money_list')->use_money($now_order['mer_id'],$refund_online_money , $type, L_("X1商品退款，减少余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])),$now_order['real_orderid'],$returnTakePercent,$returnSystemTake);
						if($result['error_code']){
							$refund_status = false;
							$go_refund_param['err_msg'] = $result['msg'];
						}
					}

					if ($refund_status) {
						$result = D('User')->add_money($now_order['uid'],$refund_online_money, L_("X1商品退款，增加余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid']))); 
					}
					
					$go_refund_param = array('refund_time' => time());
					if($result['error_code']){
						$go_refund_param['err_msg'] = $result['msg'];
					} else {
						$go_refund_param['refund_id'] = $now_order['order_id'];
						$go_refund_param['type'] = 'ok';
					}
				}
				else{
					if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'xiaoma' || $payment_money_order['pay_method'] == 'nong')){
						if($payment_money_order['pay_method'] == 'wft'){
							$pay_method = 'wftpay';
						}
						if($payment_money_order['pay_method'] == 'paylinx'){
							$pay_method = 'paylinx';
						}
						if($payment_money_order['pay_method'] == 'wepayez'){
							$pay_method = 'wepayez';
						}
						if($payment_money_order['pay_method'] == 'weikebaba'){
							$pay_method = 'weikebaba';
						}
						if($payment_money_order['pay_method'] == 'xiaoma'){
							$pay_method = 'xiaoma';
						}
						if($payment_money_order['pay_method'] == 'nong'){
							$pay_method = 'nong';
						}
						import('@.ORG.LowFeePay');
						$lowfeepay = new LowFeePay($pay_method);
						$now_order['order_type'] = $payment_money_order['order_type'] ?: 'shop';
						$now_order['orderNo'] = $payment_money_order['orderNo'];
						if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
		                    $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
							if(!empty($mer_juhe)){ 
								$lowfeepay->userId =$mer_juhe['userid'];
								$now_order['is_own'] =1 ;
							    $now_order['orderNo'] =  $now_order['orderNo'].'_1';
							}
							
		                }
		                if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
	                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$now_order['mer_id']))->find();
	                        if(!empty($mer_juhe)){ 
	                            $lowfeepay->user_id = $mer_juhe['user_id'];
	                            $lowfeepay->key = $mer_juhe['key'];
	                            $now_order['is_own'] =1 ;
	                            $now_order['orderNo'] =  $now_order['orderNo'];
	                        }
	                        
	                    }
						$go_refund_param= $lowfeepay->refund($now_order);
					}else if($payment_money_order && $payment_money_order['pay_method'] == 'lichu'){//利楚支付
                        $config = json_decode($payment_money_order['third_plat_data'],true);
                        $config['out_trade_no'] = $payment_money_order['orderNo'];
                        require_once $_SERVER['DOCUMENT_ROOT'] . "/v20/extend/pay/LichuPay.php";
                        $pay = new LichuPay();
                        $go_refund_param= $pay->refund([
                            'real_orderid'=>$now_order['real_orderid'],
                            'refund_fee'=>$payment_money_order['paid_money'] * 100
                        ],$config);
                    }else if($payment_money_order && $payment_money_order['pay_method'] == 'chinaums'){
						import("@.ORG.pay.Chinaums");
	                	$pay = new Chinaums();
	                	if($payment_money_order['is_mobile_pay'] == 3){
	                		$mid = C("config.chinaums_mid_wxapp");
	                	}
	                	if($payment_money_order['is_mobile_pay'] == 2){
	                		$mid = C("config.chinaums_mid_app");
	                	}
	                	if($payment_money_order['is_mobile_pay'] == 1){
	                		$mid = C("config.chinaums_mid_wx");
	                	}
	                	$chinaums_merno = '';
						if($now_order['mer_id']){
							$now_merchant = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->find();
							if(!empty($now_merchant['chinaums_merno'])){
								$chinaums_merno = $now_merchant['chinaums_merno'];
							}
						}
						$ordertype = $now_order['order_from'] == '1' ? 'mall' : 'shop';
	                	$go_refund_param= $pay->refund($ordertype.'_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
					}else if($payment_money_order && $payment_money_order['pay_method'] == 'lakala'){
						import('@.ORG.pay.Lakala');
						$pay_config = json_decode($payment_money_order['pay_config'],true);
						$pay = new Lakala([],$pay_config);
						$go_refund_param= $pay->refund('shop_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data']);
					}else if($payment_money_order && $payment_money_order['pay_method'] == 'scrcu'){
						import("@.ORG.pay.Scrcu");
	                	$pay = new scrcu();
	                	
	                	$scrcu_mer_no = '';
						if($now_order['mer_id']){
							$now_merchant = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->find();
							if(!empty($now_merchant['scrcu_mer_no'])){
								$scrcu_mer_no = $now_merchant['scrcu_mer_no'];
							}
						}

	                	$go_refund_param= $pay->refund($now_order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
					}elseif($payment_money_order && $payment_money_order['pay_method'] == 'farmersbankpay'){
						import("@.ORG.pay.Farmersbankpay");
                        $_POST['app_type'] = 'wxapp';
                        $pay_method = D('Config')->get_pay_method(0,0,1);
                        $pay_class = new Farmersbankpay($now_order, $now_order['payment_money']*$refund_rate, 'farmersbankpay', $pay_method['farmersbankpay']['config'], $this->user_session, 1);
                        $go_refund_param = $pay_class->refund();
                    }elseif($payment_money_order && $payment_money_order['pay_method'] == 'mallbook'){
                        import('@.ORG.pay.Mallbookpay.MallbookPayService');
                        $pay = new MallbookPayService();
                        $go_refund_param = $pay->refund($now_order['orderid'], $now_order['payment_money']*$refund_rate, $now_order['orderid']);
                    }else if($this->config['open_juhepay']==1 &&( $now_order['pay_type']=='weixin' || $now_order['pay_type']=='alipay')){
						import('@.ORG.LowFeePay');
						$lowfeepay = new LowFeePay('juhepay');
						$now_order['orderNo'] = 'shop_'.$now_order['orderid'];
						if($now_order['mer_id']){
		                    $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
							if(!empty($mer_juhe)){
								$lowfeepay->userId =$mer_juhe['userid'];
								$now_order['is_own'] =1 ;
							    $now_order['orderNo'] =  $now_order['orderNo'].'_1';
							}

		                }
						$go_refund_param= $lowfeepay->refund($now_order);

					}else if($this->config['open_pufa_pay']==1 &&( $now_order['pay_type']=='weixin')){
						import('@.ORG.LowFeePay');
						$lowfeepay = new LowFeePay('pufapay');
						$now_order['orderNo'] = 'shop_'.$now_order['orderid'];
						$go_refund_param= $lowfeepay->refund($now_order);

					}else if($payment_money_order && strpos($payment_money_order['pay_type'],'xiantao') !== false){// 智慧仙桃支付退款
						import('@.ORG.pay.XianTaoCard');
						$pay = new XianTaoCard();
		
						$now_user = D('User')->get_user($now_order['uid']);
						try{
							// 退款
							$go_refund_param = $pay->refund($payment_money_order['orderNo'], $now_user['phone']);
						} catch(Exception $e){
							$this->error_tips($e->getMessage());
						}
					}else {
						if ($now_order['is_own'] && $now_order['pay_type'] != 'nmgpay') {
							$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
							$pay_method = array();
							$own_type = '';
							$city_ownpay = D('City_ownpay')->field('city_id',true)->where(array('city_id'=>$now_merchant['city_id']))->find();
							foreach($city_ownpay as $ownKey=>$ownValue){
		                        if($ownKey == 'weixin'){
			                        $ownValueArr = unserialize($ownValue);
			                        if($ownValueArr['open']){
			                            $ownValueArr['is_own'] = 6;
			                            $pay_method = $ownValueArr;
			                            $own_type = 'city';
			                        }
			                    }
		                    }
		                    if(empty($own_type)){
								$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
								foreach ($merchant_ownpay as $ownKey => $ownValue) {
									$ownValueArr = unserialize($ownValue);
									if ($ownValueArr['open']) {
										$ownValueArr['is_own'] = true;
										$pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
										$own_type = 'merchant';
									}
								}
							}
							if (!$own_type && $now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
								if($now_order['is_mobile_pay'] == 3){
									$pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_wxapp_appid'];
									$pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_wxapp_appsecret'];
								}
								else{
									$pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
									$pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
								}
								$pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
								$pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
								$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
								$pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
								$pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
								$pay_method['weixin']['config']['is_own'] = 2;
								$own_type = 'service';//服务商
							}
							if($own_type == 'city'){
								$pay_method = [
									'weixin' => [
										'config' => [
											'pay_weixin_appid' => $this->config['pay_weixin_appid'],
											'pay_weixin_appsecret' => $this->config['pay_weixin_appsecret'],
											'pay_weixin_mchid' => $this->config['pay_weixin_sp_mchid'],
											'pay_weixin_key' => $this->config['pay_weixin_sp_key'],
											'pay_weixin_client_cert' => $this->config['pay_weixin_sp_client_cert'],
											'pay_weixin_client_key' => $this->config['pay_weixin_sp_client_key'],
											'sub_mch_id' => $pay_method['sub_mch_id']
										]
									]
								];
							}
						} else {
							$is_app = false;
							if ((stripos($_SERVER['HTTP_USER_AGENT'], 'Android') || stripos($_SERVER['HTTP_USER_AGENT'], 'IOS')) && $now_order['pay_type'] == 'alipay') $is_app = true;
							$pay_method = D('Config')->get_pay_method(0, 0, 1, $is_app);
							if ($now_order['is_mobile_pay'] == 2) {
								$pay_method['weixin'] = $pay_method['weixinapp'];
							}
						}

						if (empty($pay_method)) {
							$this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
						}

						$pay_class_name = ucfirst($now_order['pay_type']);
						if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && $this->config['new_pay_alipay_app_public_key'] != '' && $this->config['new_pay_alipay_app_appid'] != '' && $this->config['new_pay_alipay_app_private_key'] != '') {
							$pay_class_name = 'AlipayApp';
							$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
							$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
							$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
						}
                        if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                            $pay_class_name = 'Hqpay';
                            empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                        }
						if (empty($pay_method[$now_order['pay_type']])) {
							$this->error_tips(L_('您选择的支付方式不存在，请更新支付方式！'));
						}

						$import_result = import('@.ORG.pay.' . $pay_class_name);
						if (empty($import_result)) {
							$this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
						}
						D('Shop_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_refund' => 1));
						$now_order['order_type'] = 'shop';		
						if($now_order['order_from'] ==1){
			              	//商城
			              	$now_order['order_type'] = 'mall';
						}
						$now_order['order_id'] = $now_order['orderid'];
						if ($now_order['is_mobile_pay'] == 3 && empty($own_type)) {
							$pay_method[$now_order['pay_type']]['config'] = array(
									'pay_weixin_appid' => $this->config['pay_wxapp_appid'],
									'pay_weixin_key' => $this->config['pay_wxapp_key'],
									'pay_weixin_mchid' => $this->config['pay_wxapp_mchid'],
									'pay_weixin_appsecret' => $this->config['pay_wxapp_appsecret'],
							);
							C('config.pay_weixin_client_cert', $this->config['pay_wxapp_cert']);
							C('config.pay_weixin_client_key', $this->config['pay_wxapp_cert_key']);
							/*商家小程序自有支付*/
							if($now_order['is_own']){
								$now_merchant_wxapp_ownpay = M('Weixin_app_bind')->where(array('other_id'=>$now_order['mer_id']))->find();
								if($now_merchant_wxapp_ownpay){
									$pay_method[$now_order['pay_type']]['config'] = array(
										'pay_weixin_appid' => $now_merchant_wxapp_ownpay['appid'],
										'pay_weixin_key' => $now_merchant_wxapp_ownpay['wxpay_key'],
										'pay_weixin_mchid' => $now_merchant_wxapp_ownpay['wxpay_merid'],
										'pay_weixin_appsecret' =>$now_merchant_wxapp_ownpay['appsecret'],
									);
									C('config.pay_weixin_client_cert', $now_merchant_wxapp_ownpay['public_key']);
									C('config.pay_weixin_client_key', $now_merchant_wxapp_ownpay['private_key']);
								}
							}
						}
						
						$pay_class = new $pay_class_name($now_order, $now_order['payment_money']*$refund_rate, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, 1);
						$go_refund_param = $pay_class->refund();
					}
				}
				$now_order['order_id'] = $order_id;
				$data_shop_order['order_id'] = $order_id;
				$data_shop_order['refund_detail'] = serialize($go_refund_param);
				if (empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok') {
					$data_shop_order['status'] = 4;
				}
				$data_shop_order['last_time'] = time();
				D('Shop_order')->data($data_shop_order)->save();
				if($data_shop_order['status'] != 4){
					$this->error_tips($go_refund_param['msg']);
				}else{
					//增加erp生成退货单、负数收款单 2019/02/19
					if(C('Config.open_yongyou_erp')==1){
						D('Shop_order')->erp_after_pay($now_order,3);
					}
					$go_refund_param['msg'] = L_("在线支付退款成功") . ' ';
				}
			}
			
			//商城订单 配送订单在未发货以前 自提订单在确认消费以前 都退还商户或平台优惠券 tapd_story_1001389/2019-11-08
			if($now_order['order_status']<2 && $now_order['status']<2 && $now_order['order_from']==1){
				//退还商家优惠券
				if($now_order['card_id']){
					$use_result = D('Card_new_coupon')->return_coupon($now_order['card_id'],$now_order['order_id'],'shop',$now_order['mer_id'],$now_order['uid']);
					if($use_result['error_code']){
						$this->error_tips($use_result['msg']);
					}
				}
				//退还平台优惠券
				if($now_order['coupon_id']){
					$use_result = D('System_coupon')->return_coupon($now_order['coupon_id'],$now_order['order_id'],'shop',$now_order['mer_id'],$now_order['uid']);
					if($use_result['error_code']){
						return array('error'=>1,'msg'=>$use_result['msg']);
					}
				}
			}

			$return = $this->shop_refund_detail($now_order, $store_id,$refund_rate);
			if ($return['error_code']) {
				$this->error_tips($return['msg']);
			} else {
				$go_refund_param['msg'] .= $return['msg'];
			}

			if (empty($now_order['pay_type'])) {
				$data_shop_order['order_id'] = $now_order['order_id'];
				$data_shop_order['status'] = 4;
				$data_shop_order['last_time'] = time();
				D('Shop_order')->data($data_shop_order)->save();
				//用户取消订单  //对接叮叮配送===>郭先森
				if($now_order['dingding_trade_no']!=''){
					//处理叮叮取消订单======>郭先森
					$merchantStore = D('Merchant_store')->where(array('store_id'=>$now_order['store_id'],'is_open_dingding'=>1))->find();
					import('@.ORG.Dingding');
					$getShopInfo = new Dingding();
					$postData = array(
						'headquarters_id'=>1,
						'username'=>$merchantStore['dingding_user_name'],
						'per_userid'=>$merchantStore['dingding_per_userid'],
					);
					$res = $getShopInfo->getShopInfo($postData);
					$res = json_decode($res,true);
					if($res['code'] == 0){
						return array('error_code' => 1, 'msg' => L_('店鋪信息不存在,请联系开发者！'));
					}
					$dingDing = new Dingding(C('config.dingding_fromplatform'),C('config.dingding_form_app_key'),C('config.dingding_appid'),$res['result']['code'],$merchantStore['dingding_per_userid'],$merchantStore['dingding_user_name'],$merchantStore['dingding_pass'],$res['result']['phone']);
					$postData = array(
						'origin_id'=>$now_order['real_orderid'],
						'cancel_id'=>4,
						'cancelReason'=>'顾客取消订单',
						'timestamp'     => time(),
					);
					$res = $dingDing->orderCancel($postData);

					$res = json_decode($res,true);
					fdump($res,"dingding---yonghu---orderCancel",true);
				}
				$go_refund_param['msg'] .= ' ' . L_('取消订单成功');
			}
			if(empty($go_refund_param['msg'])){
				$go_refund_param['msg'] .= ' ' . L_('取消订单成功');
			}
			
			
			//短信提醒
			$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'shop');
			if ($this->config['sms_cancel_order'] == 1 || $this->config['sms_cancel_order'] == 3) {
				$sms_data['uid'] = $now_order['uid'];
				$sms_data['mobile'] = $now_order['phone'];
				$sms_data['sendto'] = 'user';
				$sms_data['content'] = '您购买 ' . ($now_order['order_from'] == 1 ? $this->config['mall_alias_name'] : $this->config['shop_alias_name']) . '的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
				Sms::sendSms($sms_data);
			}
			if ($this->config['sms_cancel_order'] == 2 || $this->config['sms_cancel_order'] == 3) {
				$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
				$sms_data['uid'] = 0;
				$sms_data['mobile'] = $merchant['phone'];
				$sms_data['sendto'] = 'merchant';
				$sms_data['content'] = '顾客购买的' . ($now_order['order_from'] == 1 ? $this->config['mall_alias_name'] : $this->config['shop_alias_name']) . '的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
				Sms::sendSms($sms_data);
			}
			
			
			D("System_order")->save_order_status($now_order['order_from'] == '1' ? 'mall' : 'shop',$order_id, 4);
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10));
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
			D('Shop_order')->cancelTwOrder($order_id);

			if($now_order['order_from']==1){
				$return_url = U('Mall/status',['order_id'=>$order_id]);
			}else{
				$return_url = U('Shop/status',['order_id'=>$order_id]);
			}
			
			//如果该订单有免单行为需要扣除用户所得面单金额
            $flipping_event_awards_log = D('Flipping_event_awards_log')->where(['order_id'=>$order_id,'uid'=>$this->user_session['uid'],'status'=>['neq',2]])->select();
			if($flipping_event_awards_log){
			    foreach ($flipping_event_awards_log as $log_item){
                    if($log_item['awards_type']==1) {
                        D('User')->user_money($this->user_session['uid'], $log_item['money'], '抽奖免单取消订单扣除余额', 24, $order_id);
                    }
                    $flip_res = D('Flipping_event_awards_log')->where(['id'=>$log_item['id']])->delete();
                    $stock_inc_res = D('Flipping_event_awards')->where(['id'=>$log_item['awards_id']])->setInc('stocks_num',1);
                    fdump_api([$order_id,$now_order['uid'],$flip_res,D('Flipping_event_awards_log')->getLastSql(),$stock_inc_res,D('Flipping_event_awards')->getLastSql(),$log_item],'flipping_event',1);
                }
            }
			
            //增加消息通知
            $url = '\app\common\model\service\user\UserNoticeService/addNotice';
            $params['type'] = 0;
            $params['business'] = 'shop';
            $params['order_id'] = $order_id;
            $params['title'] = '订单取消提醒';
            $params['content'] = '用户取消订单';
            invoke_v20_service($url, [$params]);
			$this->success_tips($go_refund_param['msg'],$return_url);

		}
	}

	private function shop_refund_detail($now_order, $store_id,$refund_rate=1)
	{
		$order_id  = $now_order['order_id'];

		$mer_store = D('Merchant_store')->where(array('mer_id' => $this->mer_id, 'store_id' => $store_id))->find();
		//业务别称
		$alias_name=C('config.shop_alias_name');
		if($now_order['order_from']==1){
			$alias_name=C('config.mall_alias_name');
		}

		$zbw_return = false;
		if($this->config['zbw_key']){

		    $zbw_return = true;
			$now_user = D('User')->get_user($this->user_session['uid']);
			$result = D('ZbwErp')->VipRetSheet($now_user['zbw_cardid'],$now_order['balance_pay']*$refund_rate,$now_order['score_used_count']*$refund_rate,$now_order['real_orderid'], $alias_name. '商品退款，增加余额，订单编号' . $now_order['real_orderid']);
			if($result['result']){
				$go_refund_param['msg'] .= L_('退款成功！');
			}else{
				$this->error_tips($result['err']);
			}
		}

		//如果使用了积分 2016-1-15
		if (!$zbw_return && $now_order['score_used_count'] != 0) {
		    $result = D('User')->add_score($now_order['uid'],$now_order['score_used_count']*$refund_rate, $alias_name. '商品退款，' . C('config.score_name') . '回滚，订单编号' . $now_order['real_orderid']);
			$param = array('refund_time' => time());
			if ($result['error_code']) {
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_shop_order['status'] = 4;
			D('Shop_order')->data($data_shop_order)->save();
			if ($result['error_code']) {
				return $result;
				$this->error_tips($result['msg']);
			}
			$go_refund_param['msg'] .= ' '.$result['msg'];
		}
		//平台E卡退款
		if (!$zbw_return && $now_order['use_ecard_price'] != '0.00') {
			$desc=$alias_name. '商品退款，增加'.$this->config['ecard_alias_name'].'转退余额，订单编号' . $now_order['real_orderid'];	
		    $add_result = D('User')->add_money($now_order['uid'],$now_order['use_ecard_price']*$refund_rate,$desc);

			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_shop_order['status'] = 4;
			D('Shop_order')->data($data_shop_order)->save();
			if ($result['error_code']) {
				return $result;
				$this->error_tips($result['msg']);
			}
			$go_refund_param['msg'] .= $this->config['ecard_alias_name'].'退款成功';
		}
		//平台余额退款
		if (!$zbw_return && $now_order['balance_pay'] != '0.00') {

		    $add_result = D('User')->add_money($now_order['uid'],$now_order['balance_pay']*$refund_rate, $alias_name . '商品退款，增加余额，订单编号' . $now_order['real_orderid']);

			$param = array('refund_time' => time());
			if($add_result['error_code']){
				$param['err_msg'] = $add_result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_shop_order['status'] = 4;
			D('Shop_order')->data($data_shop_order)->save();
			if ($result['error_code']) {
				return $result;
				$this->error_tips($result['msg']);
			}
			$go_refund_param['msg'] .= ' 平台余额退款成功';
		}
        //一卡通退款
        $card_pay_info = json_decode($now_order['internal_card_pay'], true);
        $time = time();
        if (!empty($card_pay_info)){
            $card_DB = M('internal_membership_card');
            $card_log_DB = M('internal_membership_card_log');
            foreach($card_pay_info as $card_id => $card_pay_money){
                $card = $card_DB->where(array('card_id' => $card_id))->find();
                $card_balance = round($card['balance_money'] * 100) / 100;

                $save_data['balance_money'] = round(($card_balance + $card_pay_money) * 100) / 100;
                $save_data['last_time'] = $time;
                $card_DB->where(array('card_id' => $card_id))->save($save_data);
                $condition_card_log['card_id'] = $card_id;
                $condition_card_log['operation_type'] = 3; //交易退款
                $condition_card_log['before_balance'] = $card_balance;
                $condition_card_log['after_balance'] = $save_data['balance_money'];
                $condition_card_log['money'] = $card_pay_money;
                $condition_card_log['business_type'] = 'shop';
                $condition_card_log['order_id'] = $now_order['real_orderid'];
                $condition_card_log['uid'] = $now_order['uid'];
                $condition_card_log['describe'] = '快店商品退款, 退回余额'. $card_pay_money .'元, 订单编号'.$now_order['real_orderid'];
                $condition_card_log['mer_id'] = $now_order['mer_id'];
                $condition_card_log['store_id'] = $now_order['store_id'];
                $condition_card_log['create_time'] = $time;
                $card_log_DB->add($condition_card_log);
				D('Internal_card')->send_wx_msg($card_id, '+'.getFormatNumber($card_pay_money), '快店商品退款');
			}
        }
		//企业预存款退回
        if ($now_order['qiye_pay'] > 0) {
            $staff = M('Qiye_staff')->where(['job_number' => $now_order['uid']])->find();
            if (M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setInc('money', $now_order['qiye_pay'])) {
                $log = [
                    'staff_id' => $staff['staff_id'],
                    'uid' => $now_order['uid'],
                    'type' => 1,
                    'money' => $now_order['qiye_pay'],
                    'time' => time(),
                    'ip' => get_client_ip(),
                    'desc' => L_("X1商品退款，增加余额，订单编号X2", array("X1" => $alias_name, "X2" => $now_order['real_orderid'])),
                    'order_id' => $now_order['order_id'],
                    'real_orderid' => $now_order['real_orderid'],
                    'store_id' => $store_id,
                    'order_type' => 'shop',
                    'now_money' => $staff['money'] + $now_order['qiye_pay']
                ];
                M('Qiye_staff_money_list')->add($log);
                $result = ['error_code' => 0, 'msg' => 'OK'];
            } else {
                $result = ['error_code' => 1, 'msg' => L_('企业预存款余额增加失败！请联系管理员协助解决。')];
            }
            $param = array('refund_time' => time());
            if ($result['error_code']) {
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $result['error_code'] || $data_shop_order['status'] = 4;
			D('Shop_order')->data($data_shop_order)->save();
            if ($result['error_code']) {
                return $result;
            }
            $go_refund_param['msg'] = L_('企业预存款退款成功');
        }
		
		//商家会员卡余额退款
		if ($now_order['merchant_balance'] != '0.00'||$now_order['card_give_money']!='0.00') {
			//$result = D('Member_card')->add_card($now_order['uid'],$now_order['mer_id'],$now_order['merchant_balance'],'退款 ' . $mer_store['name'] . '(' . $order_id . ')  增加余额');

			// 商家联盟会员卡
			$card_mer_id = $now_order['mer_id'];
			if ($now_order['union_mer_id']) {
				$card_mer_id = $now_order['union_mer_id'];
			}

		    $result = D('Card_new')->add_user_money($card_mer_id,$now_order['uid'],$now_order['merchant_balance']*$refund_rate,$now_order['card_give_money']*$refund_rate,0,$alias_name . '商品退款，增加余额，订单编号' . $now_order['real_orderid'], $alias_name . '商品退款，增加赠送余额，订单编号' . $now_order['real_orderid']);


			$param = array('refund_time' => time());
			if ($result['error_code']) {
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_shop_order['status'] = 4;
			D('Shop_order')->data($data_shop_order)->save();
			if ($result['error_code']) {
				return $result;
				$this->error_tips($result['msg']);
			}
			$go_refund_param['msg'] .= $result['msg'];
		}

        //未接单的快店订单退款如果使用了优惠券（平台、商家）则退还优惠券
        if ($now_order['coupon_id'] > 0 || $now_order['card_id'] > 0) {
            $isOrderTake = M('Shop_order_log')->where(['status' => 2, 'order_id' => $order_id])->find();
            if (empty($isOrderTake)) {
                if ($now_order['coupon_id'] > 0) {
                    M('System_coupon_hadpull')->where(['id' => $now_order['coupon_id']])->save(['is_use' => 0, 'use_time' => 0]);
                }
                if ($now_order['card_id'] > 0) {
                    M('Card_new_coupon_hadpull')->where(['id' => $now_order['card_id']])->save(['is_use' => 0, 'use_time' => 0]);
                }
            }
        }

		//退款打印
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($now_order['order_id'], 'shop_order', 3);

// 		$msg = ArrayToStr::array_to_str($now_order['order_id'], 'shop_order');
// 		$op = new orderPrint($this->config['print_server_key'], $this->config['print_server_topdomain']);
// 		$op->printit($this->mer_id, $store_id, $msg, 3);

// 		$str_format = ArrayToStr::print_format($now_order['order_id'], 'shop_order');
// 		foreach ($str_format as $print_id => $print_msg) {
// 			$print_id && $op->printit($this->mer_id, $store_id, $print_msg, 3, $print_id);
// 		}

		$sms_data = array('mer_id' => $mer_store['mer_id'], 'store_id' => $mer_store['store_id'], 'type' => 'shop');
		if ($this->config['sms_shop_cancel_order'] == 1 || $this->config['sms_shop_cancel_order'] == 3) {
			$sms_data['uid'] = $now_order['uid'];
			$sms_data['mobile'] = $now_order['userphone'] ? $now_order['userphone'] : $my_user['phone'];
			$sms_data['sendto'] = 'user';
			$sms_data['content'] = '您在 ' . $mer_store['name'] . '店中下的订单(订单号：' . $order_id . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_shop_cancel_order'] == 2 || $this->config['sms_shop_cancel_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $mer_store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = '顾客' . $now_order['username'] . '的预定订单(订单号：' . $order_id . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
			Sms::sendSms($sms_data);
		}

		//退款时销量回滚
		if (($now_order['paid'] == 1 || $now_order['reduce_stock_type'] == 1) && $now_order['is_rollback'] == 0) {
			$goods_obj = D("Shop_goods");
			foreach ($now_order['info'] as $menu) {
				$goods_obj->update_stock($menu, 1);//修改库存
			}
			D('Shop_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_rollback' => 1));
		}
		D("Merchant_store_shop")->where(array('store_id' => $now_order['store_id'], 'sale_count' => array('gt', 0)))->setDec('sale_count', 1);
		//退款时销量回滚

		$go_refund_param['error_code'] = false;
		return $go_refund_param;
	}

	/**
	 * 商城评论
	 */
	public function mall_feedback()
	{
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$now_order = D('Shop_order')->get_order_detail(array('uid' => $this->user_session['uid'], 'order_id' => intval($_GET['order_id'])));


		if (empty($now_order)) {
			$this->error_tips('当前订单不存在！');
		}
		if (empty($now_order['paid'])) {
			$this->error_tips('当前订单未付款！无法评论');
		}
		if ($now_order['status'] < 2) {
			$this->error_tips('当前订单未消费！无法评论');
		}

		$img_data=array();
		if(count($now_order['info'])==1){
			if ($now_order['status'] == 3) {
				$this->error_tips('当前订单已评论');
			}
		}
		$order_detail_id=I('order_detail_id','','intval');
		//格式化商品订单信息
		$list=array();
		$is_service=I('is_service',0,'intval');
		if($is_service==1 && $now_order['is_pick_in_store']>=2){
			$is_service=0;
		}
		if($is_service!=1 || $order_detail_id!=0){
			foreach ($now_order['info'] as $k => $v) {
				if($order_detail_id>0 && $v['id']!=$order_detail_id){
					continue;
				}
				if($order_detail_id>0 && $v['is_goods']>0){
					$this->error_tips('当前订单商品已评论');
				}
				$goods_id=$v['goods_id'];
				if(isset($img_data[$goods_id])){
					$arr=$img_data[$goods_id];
				}else{
					$arr=D('Shop_goods')->get_goods_image($goods_id);
					$img_data[$goods_id]=$arr;
				}
				$spec_id=$v['spec_id']>0?$v['spec_id']:0;
				$v['image']=$arr[$spec_id];
				$list[$k]=$v;
			}
		}
		$this->assign('now_order', $now_order);
		$this->assign('list', $list);
		$this->assign('order_id', $_GET['order_id']);
		//单独评论配送
		if($is_service==1 && $order_detail_id==0){
			$this->assign('is_service', $is_service);
			$this->display();
		}elseif(count($list)==1){
			if(empty($order_detail_id) && $now_order['is_pick_in_store']<2){
				$is_service=1;
			}
			$this->assign('is_service', $is_service);			
			$this->display();
		}else{
			$this->display('order_goods_list');
		}
	}

	//配送员评论
	public function service_comment(){
		$order_id=I('order_id','','intval');
		//配送员评论
		$dscore=I('dscore',5,'intval');
		$comment=I('comment','','trim,htmlspecialchars');
		$info= D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 2))->find();
		if ($info) {
		    $where=array();
		    $where['order_id']=$order_id;
		    $where['item']=2;
		    $data=array();
		    $data['score']=$dscore;
		    $data['status']=6;
		    $data['comment_time']=time();
		    $data['comment']=$dcomment;
		    $data['userId']=$this->user_session['uid'];
		    D('Deliver_supply')->where($where)->save($data);

		    if ($user = D('Deliver_user')->field(true)->where(array('uid' => $info['uid'],'status'=>array('neq',4)))->find()) {
		        $userData = array();
		        $userData['reply_count'] = $user['reply_count'] + 1;
		        $userData['total_score'] = $user['total_score'] + $dscore;
		        $userData['average_score'] = round($userData['total_score'] / $userData['reply_count'], 2);
		        D('Deliver_user')->where(array('uid' => $info['uid'],'status'=>array('neq',4)))->save($userData);
		    }
		    //更新订单的评论分数
		    M('Reply')->where(array('order_id'=>$order_id,'order_type'=>3))->save(array('deliver_score'=>$dscore));
		}
		return $dscore;
	}

	//订单商品评论
	public function add_goods_comment(){
		if(empty($this->user_session)){
			exit(json_encode(array('status' => 0, 'msg' => '请先进行登录！')));
			$this->error_tips('请先进行登录！');
		}
		$order_id=I('order_id','','intval');
		
		//商品评论信息
		$score=I('whole',5,'intval');
		$comment=I('textAre','','trim,htmlspecialchars');

		$uid=$this->user_session['uid'];
		//获取当前订单信息
		$now_order=D('Shop_order')->get_order_detail(array('uid'=>$uid,'order_id'=>$order_id));
		
		
		if (empty($now_order)) {
			exit(json_encode(array('status' => 0, 'msg' => '当前订单不存在！')));
		}
		$is_service=I('is_service',0,'intval');
		//订单商品主键id
		$order_detail_id=I('order_detail_id','','intval');
		if($is_service==1){	//配送员评论
			$dscore=$this->service_comment();
			D('Merchant_store')->setInc_shop_reply($now_order['store_id'], null,$dscore);
			if($order_detail_id<=0){
				exit(json_encode(array('status' => 1, 'msg' => '评论成功',  'url' => U('Shop/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])))));
				$this->success_tips('评论成功', U('Shop/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
			}
		}

		if (empty($comment)) {
		    exit(json_encode(array('status' => 0, 'msg' => '请填写您的宝贵意见！')));
		}
		if (empty($now_order['paid'])) {
			exit(json_encode(array('status' => 0, 'msg' => '当前订单未付款！无法评论')));
		}
		if ($now_order['status'] < 2) {
			exit(json_encode(array('status' => 0, 'msg' => '当前订单未消费！无法评论')));
		}
		//单商品订单
		if (count($now_order['info'])==1 && $now_order['status'] == 3) {
			exit(json_encode(array('status' => 0, 'msg' => '当前订单已评论')));
		}else{
			$info=D('Shop_order_detail')->where(array('id' => $order_detail_id))->find();
			if($info['is_goods']==1){
				exit(json_encode(array('status' => 0, 'msg' => '当前订单商品已评论')));
			}
		}

		/*评论图片*/
		$inputimg=isset($_POST['inputimg']) ? $_POST['inputimg'] :'';
		$pic_ids=array();
		if(!empty($inputimg)){
			$database_reply_pic = D('Reply_pic');
			foreach($inputimg as $imgv){
				$imgv=str_replace('/upload/reply/shop/','',$imgv);
				$imgtmp=explode('/',$imgv);
				$imgname=$imgtmp[count($imgtmp)-1];
				$reply_pic['name'] = $imgname;
				$reply_pic['pic'] = str_replace('/'.$imgname,'',$imgv).','.$imgname;
				$reply_pic['uid'] = $this->user_session['uid'];
				$reply_pic['order_type'] = 3;
				$reply_pic['order_id'] = intval($now_order['order_id']);
				$reply_pic['add_time'] = $_SERVER['REQUEST_TIME'];
				$pic_ids[] = $database_reply_pic->data($reply_pic)->add();
			}
		}

		$database_reply = D('Reply');
		$data_reply['parent_id'] = $now_order['store_id'];
		$data_reply['store_id'] = $now_order['store_id'];
		$data_reply['mer_id'] = $now_order['mer_id'];
		$data_reply['score'] = $score;
		$data_reply['order_type'] = 3;
		$data_reply['order_id'] = intval($now_order['order_id']);
		$data_reply['order_detail_id'] = $order_detail_id;
		$data_reply['anonymous'] = 1;
		$data_reply['comment'] = $comment;
		$data_reply['uid'] = $uid;
		$data_reply['pic'] = !empty($pic_ids) ? implode(',',$pic_ids):'';
		$data_reply['add_time'] = $_SERVER['REQUEST_TIME'];
		$data_reply['add_ip'] = get_client_ip(0);
		$data_reply['goods'] = '';
		//配送员评分
		$dscore=M('Deliver_supply')->where(array('order_id'=>$order_id,'item'=>2))->getField('score');
		$data_reply['deliver_score'] = $dscore?$dscore:5;
		$res=$database_reply->data($data_reply)->add();
		if($res){
            if($this->is_weixin_browser){
                $from_type = 0;
            }
            elseif($this->is_wxapp_browser){
                $from_type = 3;
            }
            elseif($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
                $from_type = 2;
            }
            elseif($this->is_app_browser){
                $from_type = 1;
            }
            else{
                $from_type = 0;
            }
			//记录下用户评论行为分析$reply_id,$uid,$content,$business_type,$comment_time,$from_type,$add_ip
			D('User_behavior_reply')->add_record($res,$uid,$comment,0,time(),$from_type,get_client_ip(0));

			D('Merchant_store')->setInc_shop_reply($now_order['store_id'], $score);
			//增加商品评论数
			D('Shop_goods')->where(array('goods_id' => $info['goods_id']))->setInc('reply_count', 1);		
			//更改订单商品评论状态为“已评论”
			D('Shop_order_detail')->where(array('id' => $order_detail_id))->save(array('is_goods' => 1));
			//判断是不是第一个评论
			$where=array();
			$where['order_id']=$order_id;
			$where['order_detail_id']=array('gt',0);
			$num=M('Reply')->where($where)->count(1);
			if($num==1){
				D('Shop_order')->change_status($now_order['order_id'], 3,'mall');
				D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 8));	
				if($this->config['feedback_score_add']>0){
					/* 重要！！！*/
					if($now_order['order_from']==1){
						$alias_name=$this->config['mall_alias_name'];
						$alias_name=$alias_name?$alias_name:'商城';
					}else{
						$alias_name=$this->config['shop_alias_name'];
					}

				  	D('User')->add_extra_score($this->user_session['uid'],$this->config['feedback_score_add'],$alias_name.'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name'],[
                        'mer_id'=>$now_order['mer_id'],
                        'store_id'=>$now_order['store_id']??0,
                        'order_id'=>$now_order['order_id'],
                        'order_type'=>'shop',
                    ]);
				  	D('Scroll_msg')->add_msg('feedback',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'对'.$alias_name.'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name']);
				}
			}
			if($now_order['order_from']==1){
				$url=U('Mall/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']));
			}else{
				$url=U('Shop/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']));	
			}
			

			if(count($now_order['info'])>1){
				$url=U('My/shop_feedback', array('order_id' => $now_order['order_id']));
			}
			exit(json_encode(array('status' => 1, 'msg' => '评论成功',  'url' =>$url)));
			$this->success_tips('评论成功',$url);
		}else{
			$this->error_tips('评论失败，请重试！');
		}

	}

	/**
	 * 快店评论
	 */
	public function shop_feedback()
	{
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$now_order = D('Shop_order')->get_order_detail(array('uid' => $this->user_session['uid'], 'order_id' => intval($_GET['order_id'])));


		if (empty($now_order)) {
			$this->error_tips('当前订单不存在！');
		}
		if (empty($now_order['paid'])) {
			$this->error_tips('当前订单未付款！无法评论');
		}
		if ($now_order['status'] < 2) {
			$this->error_tips('当前订单未消费！无法评论');
		}
		if ($now_order['status'] == 3) {
			$this->error_tips('当前订单已评论');
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

		$this->assign('now_order', $now_order);


		$this->display();
	}

	//订单评论
	public function add_comment()
	{
		if(empty($this->user_session)){
			exit(json_encode(array('status' => 0, 'msg' => '请先进行登录！')));
			$this->error_tips('请先进行登录！');
		}
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$goods_ids = isset($_POST['goods_ids']) ? $_POST['goods_ids'] : 0;
		$score = isset($_POST['whole']) ? $_POST['whole'] : 5;
		$comment = isset($_POST['textAre']) ? htmlspecialchars(trim($_POST['textAre'])) : '';
		
		$dscore = isset($_POST['score']) ? $_POST['score'] : 5;
		$dcomment = isset($_POST['comment']) ? htmlspecialchars(trim($_POST['comment'])) : '';
		

		$now_order = D('Shop_order')->get_order_detail(array('uid' => $this->user_session['uid'], 'order_id' => $order_id));

		if (empty($comment)) {
		    exit(json_encode(array('status' => 0, 'msg' => L_('请填写您的宝贵意见！'))));
		}
		if (empty($now_order)) {
			exit(json_encode(array('status' => 0, 'msg' => L_('当前订单不存在！'))));
		}
		if (empty($now_order['paid'])) {
			exit(json_encode(array('status' => 0, 'msg' => L_('当前订单未付款！无法评论'))));
		}
		if ($now_order['status'] < 2) {
			exit(json_encode(array('status' => 0, 'msg' => L_('当前订单未消费！无法评论'))));
		}
		if ($now_order['status'] == 3) {
			exit(json_encode(array('status' => 0, 'msg' => L_('当前订单已评论'))));
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
		$data_reply['uid'] = $this->user_session['uid'];
		$data_reply['pic'] = '';
		$data_reply['add_time'] = $_SERVER['REQUEST_TIME'];
		$data_reply['add_ip'] = get_client_ip(0);
		$data_reply['goods'] = $goods;
		$data_reply['deliver_score'] = $dscore;
		if ($res = $database_reply->data($data_reply)->add()) {
			
			if($this->is_weixin_browser){
                $from_type = 0;
            }
            elseif($this->is_wxapp_browser){
                $from_type = 3;
            }
            elseif($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
                $from_type = 2;
            }
            elseif($this->is_app_browser){
                $from_type = 1;
            }
            else{
                $from_type = 0;
            }
			//记录下用户评论行为分析$reply_id,$uid,$content,$business_type,$comment_time,$from_type,$add_ip
			D('User_behavior_reply')->add_record($res,$this->user_session['uid'],$comment,1,time(),$from_type,get_client_ip(0));

		    D('Merchant_store')->setInc_shop_reply($now_order['store_id'], $score, $dscore);
			D('Shop_order')->change_status($now_order['order_id'], 3,'shop');
			D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 8));
			foreach ($goods_ids as $goods_id) {
				if (in_array($goods_id, $goodsids)) {
					D('Shop_goods')->where(array('goods_id' => $goods_id))->setInc('reply_count', 1);
					D('Shop_order_detail')->where(array('goods_id' => $goods_id, 'order_id' => $order_id))->save(array('is_goods' => 1));
				}
			}

			if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $now_order['order_id'], 'item' => 2))->find()) {
			    D('Deliver_supply')->where(array('order_id' => $now_order['order_id'], 'item' => 2))->save(array('score' => $dscore, 'status' => 6, 'comment_time' => time(), 'comment' => $dcomment,'userId' => $this->user_session['uid']));

			    if ($user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find()) {
			        $userData = array();
			        $userData['reply_count'] = $user['reply_count'] + 1;
			        $userData['total_score'] = $user['total_score'] + $dscore;
			        $userData['average_score'] = round($userData['total_score'] / $userData['reply_count'], 2);
			        D('Deliver_user')->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->save($userData);

			        // 只对系统配送员有效
					if (C('config.open_deliver_level') && $user['group']==1 && $dscore >= 4 ) {
						$score = floatval(C('config.deliver_level_good_reply_get_score'));
						$desc = '用户好评获得'.$score.'积分';
						$res = D('Deliver_user')->add_score($supply['uid'],$score,$desc,3,$supply);
					}elseif(C('config.open_deliver_level') && $user['group']==1 && $dscore < 3 ) {
						$score = floatval(C('config.deliver_level_poor_reply_deduct_score'));
						$desc = '用户差评评扣除'.$score.'积分';
						$res = D('Deliver_user')->deduct_score($supply['uid'],$score,$desc,5,$supply);
					}

			    }
			}

			if($this->config['feedback_score_add']>0){
			  	D('User')->add_extra_score($this->user_session['uid'],$this->config['feedback_score_add'],$this->config['shop_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name'],[
                    'mer_id'=>$now_order['mer_id'],
                    'store_id'=>$now_order['store_id']??0,
                    'order_id'=>$now_order['order_id'],
                    'order_type'=>'shop',
                ]);
			  	D('Scroll_msg')->add_msg('feedback',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'对'.$this->config['shop_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name']);
			}
			exit(json_encode(array('status' => 1, 'msg' => L_('评论成功'),  'url' => U('Shop/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])))));
			$this->success_tips(L_('评论成功'), 'javascript:history.go(-1);');
		}
	}

	public function refund_back()
	{
		import('@.ORG.pay.Unionpay');

		$pay_class = new Unionpay('', '', 'unionpay', $pay_method['unionpay']['config'], '', 1);
		$get_pay_param = $pay_class->return_url();
		if ($get_pay_param['error']) {
			//TODO 退款失败的操作
		}
	}

	/*全部订餐订单列表*/
	public function foodshop_order_list()
	{
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;

		$where['uid'] = $this->user_session['uid'];
		$where['is_del'] = 0;
		if ($status != -1) {
			$where['status'] = $status;
		}

		$where['is_foodshop_new'] = 0;
		$order_list = D("Foodshop_order")->get_order_list($where, 'order_id DESC', 0);
		$order_list = $order_list['order_list'];

		foreach ($order_list as $st) {
			$store_ids[] = $st['store_id'];
		}
		$m = array();
		if ($store_ids) {
			$store_image_class = new store_image();
			$merchant_list = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_list as $li) {
				$images = $store_image_class->get_allImage_by_path($li['pic_info']);
				$li['image'] = $images ? array_shift($images) : array();
				unset($li['status']);
				$m[$li['store_id']] = $li;
			}
		}
		$list = array();
		foreach ($order_list as $ol) {
			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$list[] = array_merge($ol, $m[$ol['store_id']]);
			} else {
				$list[] = $ol;
			}
		}
		foreach ($list as $key => $val) {
			if ($val['status'] <= 1 || $val['status'] == 5) {
				$list[$key]['order_url'] = U('Foodshop/book_success', array('order_id' => $val['order_id']));
			} else {
				$list[$key]['order_url'] = U('Foodshop/order_detail', array('order_id' => $val['order_id']));
			}
		}
		$this->assign('order_list', $list);
		$this->display();
	}

	# 删除餐饮订单
	public function ajax_foodshop_order_del(){
		$database_shop_order = D('Foodshop_order');
		$now_order = $database_shop_order->get_order_by_id($this->user_session['uid'],intval($_GET['order_id']));

		if(empty($now_order)){
			exit(json_encode(array('status'=>0,'msg'=>'当前订单不存在！')));
		}

		$order_condition['order_id'] = $now_order['order_id'];
		$data['is_del'] = 1;
		if($database_shop_order->where($order_condition)->data($data)->save()){
			exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'删除失败！')));
		}
	}

	public function ajax_foodshop_order_list()
	{
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;

		$where['uid'] = $this->user_session['uid'];
		$where['is_del'] = 0;
		if ($status != -1) {
			$where['status'] = $status;
		}

		$where['is_foodshop_new'] = 0;

		$order_list = D("Foodshop_order")->get_order_list($where, 'order_id DESC', 0);
		$order_list = $order_list['order_list'];

		foreach ($order_list as $st) {
			$store_ids[] = $st['store_id'];
		}
		$m = array();
		if ($store_ids) {
			$store_image_class = new store_image();
			$merchant_list = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_list as $li) {
				$images = $store_image_class->get_allImage_by_path($li['pic_info']);
				$li['image'] = $images ? array_shift($images) : array();
				unset($li['status']);
				$m[$li['store_id']] = $li;
			}
		}
		$list = array();
		foreach ($order_list as $ol) {
			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$list[] = array_merge($ol, $m[$ol['store_id']]);
			} else {
				$list[] = $ol;
			}
		}

		foreach($list as $key => $val){
			if ($val['status'] <= 1) {
				$list[$key]['order_url'] = U('Foodshop/book_success', array('order_id' => $val['order_id']));
			} else {
				$list[$key]['order_url'] = U('Foodshop/order_detail', array('order_id' => $val['order_id']));
			}
		}
		if (!empty($list)) {
			exit(json_encode(array('status' => 1, 'order_list' => $list)));
		} else {
			exit(json_encode(array('status' => 0, 'order_list' => $list)));
		}
	}

	/**
	 * 快店评论
	 */
	public function foodshop_feedback()
	{
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$now_order = D('Foodshop_order')->get_order_detail(array('uid' => $this->user_session['uid'], 'order_id' => intval($_GET['order_id'])));


		if (empty($now_order)) {
			$this->error_tips('当前订单不存在！');
		}

		if ($now_order['status'] < 3) {
			$this->error_tips('当前订单未消费！无法评论');
		}
		if ($now_order['status'] == 4) {
			$this->error_tips('当前订单已评论');
		}

		if (isset($now_order['info'])) {
			$list = array();
			$goods_ids = array();
			foreach ($now_order['info'] as $row) {
				if (!in_array($row['goods_id'], $goods_ids) && empty($row['is_must'])) {
					$goods_ids[] = $row['goods_id'];
					$list[] = $row;
				}
			}
			$now_order['info'] = $list;
		}

		$this->assign('now_order', $now_order);
		$this->display();
	}

	public function foodshop_comment()
	{
		if(empty($this->user_session)){
			exit(json_encode(array('status' => 0, 'msg' => '请先进行登录！')));
			$this->error_tips('请先进行登录！');
		}
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$goods_ids = isset($_POST['goods_ids']) ? $_POST['goods_ids'] : 0;
		$score = isset($_POST['whole']) ? $_POST['whole'] : 5;
		$comment = isset($_POST['textAre']) ? htmlspecialchars($_POST['textAre']) : 0;
		if (empty($comment)) {
			exit(json_encode(array('status' => 0, 'msg' => '评论内容不能为空！')));
		}
		$now_order = D('Foodshop_order')->get_order_detail(array('uid' => $this->user_session['uid'], 'order_id' => $order_id));

		if (empty($now_order)) {
			exit(json_encode(array('status' => 0, 'msg' => '当前订单不存在！')));
		}

		if ($now_order['status'] < 3) {
			exit(json_encode(array('status' => 0, 'msg' => '当前订单未消费！无法评论')));
		}
		if ($now_order['status'] == 4) {
			exit(json_encode(array('status' => 0, 'msg' => '当前订单已评论')));
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
		$inputimg=isset($_POST['inputimg']) ? $_POST['inputimg'] :'';
		$pic_ids=array();
		if(!empty($inputimg)){
			$database_reply_pic = D('Reply_pic');
			foreach($inputimg as $imgv){
				$imgv=str_replace('/upload/reply/foodshop/','',$imgv);
				$imgtmp=explode('/',$imgv);
				$imgname=$imgtmp[count($imgtmp)-1];
				$reply_pic['name'] = $imgname;
				$reply_pic['pic'] = str_replace('/'.$imgname,'',$imgv).','.$imgname;
				$reply_pic['uid'] = $this->user_session['uid'];
				$reply_pic['order_type'] = 4;
				$reply_pic['order_id'] = intval($now_order['order_id']);
				$reply_pic['add_time'] = $_SERVER['REQUEST_TIME'];
				$pic_ids[] = $database_reply_pic->data($reply_pic)->add();
			}
		}

		$database_reply = D('Reply');
		$data_reply['parent_id'] = $now_order['store_id'];
		$data_reply['store_id'] = $now_order['store_id'];
		$data_reply['mer_id'] = $now_order['mer_id'];
		$data_reply['score'] = $score;
		$data_reply['order_type'] = 4;//新的餐饮
		$data_reply['order_id'] = intval($now_order['order_id']);
		$data_reply['anonymous'] = 1;
		$data_reply['comment'] = $comment;
		$data_reply['uid'] = $this->user_session['uid'];
		$data_reply['pic'] = '';
		$data_reply['add_time'] = $_SERVER['REQUEST_TIME'];
		$data_reply['add_ip'] = get_client_ip(0);
		$data_reply['goods'] = $goods;
		$data_reply['pic'] = !empty($pic_ids) ? implode(',',$pic_ids):'';
		if ($database_reply->data($data_reply)->add()) {
			D('Merchant_store')->setInc_foodshop_reply($now_order['store_id'], $score);
			D('Foodshop_order')->change_status($now_order['order_id'], 4);

            /* 增加系统订单记录 2020/09/12 */
			D('Foodshop_order')->add_system_order($order_id,2);

// 			D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 8));
			foreach ($goods_ids as $goods_id) {
				if (in_array($goods_id, $goodsids)) {
					D('Foodshop_goods')->where(array('goods_id' => $goods_id))->setInc('reply_count', 1);
					D('Foodshop_order_detail')->where(array('goods_id' => $goods_id, 'order_id' => $order_id))->save(array('is_goods' => 1));
				}
			}
			if($this->config['feedback_score_add']>0){
			  	D('User')->add_extra_score($this->user_session['uid'],$this->config['feedback_score_add'],$this->config['meal_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name'],[
                    'mer_id'=>$now_order['mer_id'],
                    'store_id'=>$now_order['store_id']??0,
                    'order_id'=>$now_order['order_id'],
                    'order_type'=>'foodshop',
                ]);
			  	D('Scroll_msg')->add_msg('feedback',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'对'.$this->config['meal_alias_name'].'评论获得'.$this->config['feedback_score_add'].'个'.$this->config['score_name']);
			}
			exit(json_encode(array('status' => 1, 'msg' => '评论成功',  'url' => U('Foodshop/order_detail', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])))));
		}
	}

	# 公共接口
	public function user_sessions(){
		if(empty($this->user_session)){
			$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
			redirect(U('Login/index',$location_param));
		}
	}

	public function user_sessions_json(){
		if(empty($this->user_session)){
			$this->returnCode('20020008');
		}
	}

	# 车主实名认证
	public function car_owner(){
		$find	=	M('User_authentication_car')->field(true)->where(array('uid'=>$this->user_session['uid']))->order('add_time DESC')->find();
		if($find){
			$image_class = new scenic_image();
			$find['authentication_img'] = $image_class->get_car_by_path($find['authentication_img'],$this->config['site_url'],'authentication_car','s');
			$find['authentication_back_img'] = $image_class->get_car_by_path($find['authentication_back_img'],$this->config['site_url'],'authentication_car','s');
			$find['drivers_license'] = $image_class->get_car_by_path($find['drivers_license'],$this->config['site_url'],'authentication_car','s');
			$find['driving_license'] = $image_class->get_car_by_path($find['driving_license'],$this->config['site_url'],'authentication_car','s');
		}
		$this->assign('find',$find);
		$this->display();
	}

	# 申请车主认证
	public function car_apply(){
		$plate_number	=	$this->plate_number();
		if($this->config['international_phone']){
			$province_list = D('Area')->get_arealist_by_areaPid(0,true);
			$plate_number = array();
			foreach($province_list as $value){
				$plate_number[] = array(
					'id' => $value['area_id'],
					'name' => $value['area_name'],
					'front' => $value['area_name'],
				);
			}
		}
		$find	=	M('User_authentication_car')->field(true)->where(array('uid'=>$this->user_session['uid']))->order('add_time DESC')->find();
		if($find){
			if($find['status'] == 2){
				$find	=	0;
			}
		}
		$this->assign('plate_number',$plate_number);
		$this->assign('find',$find);
		$this->display();
	}

	public function car_apply_json(){
		$_POST['uid']	=	$this->user_session['uid'];
		$_POST['add_time']	=	$_SERVER['REQUEST_TIME'];
		$add	=	M('User_authentication_car')->data($_POST)->add();
		if($add){
		    // 添加上处理车主认证审核提醒对应的后台管理者
            $admin_info = M('Admin')->where(array('openid' => array('neq', ''), 'status' => 1, 'car_authentication_notice' => 1))->field('openid, realname, account')->select();
            if (!empty($admin_info)) {
                $user_info = M('User')->where(array('uid' => $this->user_session['uid']))->field('nickname')->find();
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                foreach ($admin_info as $val) {
                    if ($val['openid']) {
                        $model->sendTempMsg('OPENTM405462911',
                            array(
                                'wecha_id' => $val['openid'],
                                'first' => $user_info['nickname'] . '用户提交车主认证审核，请及时登录系统后台进行处理',
                                'keyword1' => '车主认证审核提醒',
                                'keyword2' =>'未查看',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                'keyword4' => $user_info['nickname'],
                                'remark' => '\n请及时登录系统后台进行处理！'
                            )
                        );
                    }
                }
            }

            $this->returnCode(0,U('car_apply'));
		}else{
			$this->returnCode('20046028');
		}
	}

	# 车牌
	public function plate_number(){
		$arr	=	array(
			array('id'=>1,'name'=>'北京','front'=>'京'),
			array('id'=>2,'name'=>'天津','front'=>'津'),
			array('id'=>3,'name'=>'上海','front'=>'沪'),
			array('id'=>4,'name'=>'重庆','front'=>'渝'),
			array('id'=>5,'name'=>'内蒙古自治区','front'=>'蒙'),
			array('id'=>6,'name'=>'维吾尔自治区','front'=>'新'),
			array('id'=>7,'name'=>'西藏自治区','front'=>'藏'),
			array('id'=>8,'name'=>'宁夏回族自治区','front'=>'宁'),
			array('id'=>9,'name'=>'广西壮族自治区','front'=>'桂'),
			array('id'=>10,'name'=>'香港特别行政区','front'=>'港'),
			array('id'=>11,'name'=>'澳门特别行政区','front'=>'澳'),
			array('id'=>12,'name'=>'黑龙江省','front'=>'黑'),
			array('id'=>13,'name'=>'吉林省','front'=>'吉'),
			array('id'=>14,'name'=>'辽宁省','front'=>'辽'),
			array('id'=>15,'name'=>'山西省','front'=>'晋'),
			array('id'=>16,'name'=>'河北省','front'=>'冀'),
			array('id'=>17,'name'=>'青海省','front'=>'青'),
			array('id'=>18,'name'=>'山东省','front'=>'鲁'),
			array('id'=>19,'name'=>'河南省','front'=>'豫'),
			array('id'=>20,'name'=>'江苏省','front'=>'苏'),
			array('id'=>21,'name'=>'安徽省','front'=>'皖'),
			array('id'=>22,'name'=>'浙江省','front'=>'浙'),
			array('id'=>23,'name'=>'福建省','front'=>'闽'),
			array('id'=>24,'name'=>'江西省','front'=>'赣'),
			array('id'=>25,'name'=>'湖南省','front'=>'湘'),
			array('id'=>26,'name'=>'湖北省','front'=>'鄂'),
			array('id'=>27,'name'=>'广东省','front'=>'粤'),
			array('id'=>28,'name'=>'海南省','front'=>'琼'),
			array('id'=>29,'name'=>'甘肃省','front'=>'甘'),
			array('id'=>30,'name'=>'陕西省','front'=>'陕'),
			array('id'=>31,'name'=>'贵州省','front'=>'贵'),
			array('id'=>32,'name'=>'云南省','front'=>'滇'),
			array('id'=>33,'name'=>'四川省','front'=>'川'),
		);
		return $arr;
	}

	/* 图片上传 */
    public function ajaxWebUpload(){
		if ($_FILES['file']['error'] != 4) {
        	$width = '900,450';
        	$height = '500,250';
			$param = array('size' => 2);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
            $param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = $width;
            $param['thumbMaxHeight'] = $height;
            $param['thumbRemoveOrigin'] = false;
			$image = D('Image')->handle($this->user_session['uid'], 'authentication_car', 1, $param);
			if ($image['error']) {
				exit(json_encode(array('error' => 1,'message' =>$image['msg'])));
			} else {
				exit(json_encode(array('error' => 0, 'url' => $image['url']['file'], 'title' => $image['title']['file'])));
			}
		}else{
			exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
		}
    }

	public function group_recive_confirm()
	{
		$order_id = $_GET['order_id'];
		$condition_group_order['order_id'] = $order_id;
		$now_order = M('Group_order')->where($condition_group_order)->find();
		if($now_order['status']==1){
			$this->error_tips('该订单已经确认收货了',U('My/group_order',array('order_id'=>$order_id)));
		}
		if ($now_order['paid'] == 1 && $now_order['status'] == 0) {
			$data_group_order['status'] = 1; //原来是1
			$data_group_order['use_time'] = time();
		}
		if (M('Group_order')->where($condition_group_order)->data($data_group_order)->save()) {
			$now_user = D('User')->get_user($now_order['uid'],'uid');
			$express_nmae = D('Express')->get_express($now_order['express_type']);
			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			$href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
			$model->sendTempMsg('TM00017', array('href' => $href,
					'wecha_id' => $now_user['openid'],
					'first' => $this->config['group_alias_name'].'快递收货通知',
					'OrderSn' => $now_order['real_orderid'],
					'OrderStatus' =>'您在'.date('Y-m-d H:i:s').'已确认收货',
					'remark' =>'快递号：'.$now_order['express_id'].'('.$express_nmae['name'].')'), $now_order['mer_id']);

			D('Group')->group_notice($now_order,1);
            /*-----记录系统订单信息 已收货 改写状态已收货 20/10/26----*/
            D("System_order")->save_order_status('group',$now_order['order_id'],2);
            //增加消息通知
            $url = '\app\common\model\service\user\UserNoticeService/addNotice';
            $params['type'] = 0;
            $params['business'] = 'group';
            $params['order_id'] = $now_order['order_id'];
            $params['mer_id'] = $now_order['mer_id'];
            $params['store_id'] = $now_order['store_id'];
            $params['uid'] = $now_order['uid'];
            $params['title'] = '核销成功，已经领取商品';
            $params['content'] = '您已经领取团购商品，请核对订单';
            invoke_v20_service($url, [$params]);
			$this->success_tips('订单状态修改成功',U('My/group_order',array('order_id'=>$order_id)));
		}else{
			$this->error_tips('订单状态修改失败',U('My/group_order',array('order_id'=>$order_id)));
		}

	}

	/*
	 * 到店付订单详情
	 * */
	public function store_order_detail(){
		if($_GET['wxapp_share_page']){

			$result = M('Store_order')->where(array('order_id' => $_GET['order_id']))->setField('share_status', 1);
			if($this->config['share_coupon']&&$this->user_session['uid']){
				$result = D('System_coupon')->share_coupon(array('order_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid'],'type'=>'store'));
                $this->assign('openShare', $result['error_code'] ? 0 : 1);
			}
		}
		$order = D('Store_order')->get_order_by_id($this->user_session['uid'],$_GET['order_id']);
		if(empty($order)){
			$this->error_tips('订单不存在');
		}

		if ($order['card_discount']) {  
            $order['card_discount_money'] = round($order['price']-($order['price']-$order['no_discount_money'])*$order['card_discount']/10-$order['no_discount_money'],2);
        }else{
             $order['card_discount_money'] = 0;
        }
		$order['pay_type_str']  = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid'],0,$order);
        if($order['payment_money'] > 0){
            switch ($order['pay_type']){
                case 'weixin':
                case 'wechat':
                case 'weixinh5':
                    $order['payment_money_type'] = '微信支付';
                    break;
                case 'alipay':
                case 'alipayh5':
                    $order['payment_money_type'] = '支付宝支付';
                    break;
                default:
                    $order['payment_money_type'] = '在线支付';
            }
        }
        
        //一卡通支付金额展示
        $internal_card_pay = 0;
        $card_pay_info = json_decode($order['internal_card_pay'], true);
        if(!empty($card_pay_info)){
            foreach ($card_pay_info as $card_id => $pay_money){
                $internal_card_pay += $pay_money;
            }
        }
        $order['internal_card_pay'] = round($internal_card_pay * 100 / 100, 2);
        
        $order['price'] = $order['price'] - $order['card_discount_money'];
        $order['pay_total'] = $order['payment_money']+$order['score_deducte']+$order['card_price']+$order['coupon_price']+$order['merchant_balance']+$order['card_give_money']+$order['balance_pay'];

        $offline_money = $order['price'] - ($order['payment_money']+$order['score_deducte']+$order['card_price']+$order['coupon_price']+$order['merchant_balance']+$order['card_give_money']+$order['balance_pay']);
        $offline_money = $offline_money - $order['internal_card_pay'];
        
		$order['offline_money'] = $offline_money ? round(floatval($offline_money),2) : 0;

		M('Store_order')->where(array('order_id'=>$order['order_id']))->setInc('show_lottery_first',1);
		$store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
		$store_image_class = new store_image();
		$images = $store_image_class->get_allImage_by_path($store['pic_info']);
		$store['image'] = isset($images[0]) ? $images[0] : '';
		
		if ($this->user_session['uid']) {
			$tm = time();
			$lottery  = M('Lottery_shop')->where(array('mer_id'=>$order['mer_id'],'status'=>1,'start_time'=>['lt',$tm],'end_time'=>['gt',$tm]))->find();
			$lottery_info  = M('Lottery_shop_list')->where(array('uid'=>$this->user_session['uid'],'order_id'=>$_GET['order_id']))->find();
            if (empty($lottery_info) && C('config.lottery_must_share') == 1) {
                /*$lottery_info = [
                    'uid' => $this->user_session['uid'],
                    'mer_id' => $order['mer_id'],
                    'order_id' => $_GET['order_id'],
                    'lottery_id' => 0,
                    'status' => 0,
                    'add_time' => $tm,
                    'lottery_time' => 0,
                    'award_time' => 0,
                    'is_win' => 0,
                    'return' => ''
                ];
                M('Lottery_shop_list')->add($lottery_info);*/
                fdump_sql(M('Lottery_shop_list')->_sql(),'lottery_shop_list_store_order_detail');
                M('Store_order')->where(array('order_id' => $order['order_id']))->setField('share_status',1);
                $order['share_status']=1;
            }
			C('config.share_rand_send_coupon')==1 && $coupon_where['rand_send'] = 1;

			$coupon_where['status']=1;
			$share_coupon = D('System_coupon')->get_coupon_list($coupon_where);
		}
		if(!empty($order['pay_get_score']) && explode('_', $order['pay_get_score'])){
			$pay_get_score = explode('_', $order['pay_get_score']);
			if($pay_get_score[0] == '0'){
				M('Store_order')->where(array('order_id'=>$order['order_id']))->setField('pay_get_score','1_'.$pay_get_score[1]);
				$this->assign('pay_get_score', "恭喜，您获得".$this->config['score_name']."：".$pay_get_score[1]);
			}
		}

		$this->assign('share_coupon', $share_coupon);
		$this->assign('lottery', $lottery);
		$this->assign('lottery_info', $lottery_info);
		$this->assign('store',$store);
		$this->assign('order',$order);
		if($this->is_app_browser){
			$this->display('store_order_detail_app');
		}else{
			$this->display();
		}
	}

	/*
	 * 签到功能
	 * */
	public function sign(){
		if(C('config.open_custom_sign')==1){
			$where=array();
			$where['is_del']=0;
			$custom_num=M('sign_setting')->where($where)->count(1);
			if($custom_num>0){
				$this->redirect('custom_sign');
			}
		}
		
		$plat_index_url = C('config.site_url').D('House_village')->base_url;
		$return = D('User')->sign_in($this->user_session['uid']);
		$from=I('get.from','','trim');
		if($return['error_code']==2){
			$this->error_tips($return['msg'],U('Wap/My/bind_user'));
		}else if($return['error_code']==1){
			$this->error_tips($return['msg'],$plat_index_url);		
		}else{
			$this->success_tips($return['msg'],$plat_index_url);
		}
	}

	//自定义签到功能
	public function custom_sign(){
		if(C('config.open_custom_sign')==0){
			$this->error_tips('抱歉，该功能未开启！');
		}
		$where=array();
		$where['is_del']=0;
		$list=M('sign_setting')->where($where)->select();
		if(count($list)==0){
			$this->error_tips('抱歉，该功能未开启！！');
		}
		$coupon_arr=M('system_coupon')->getField('coupon_id,discount,is_discount,discount_value',true);
		$jifenAll = 0;//累计可领取的积分
		foreach ($list as $k => $v) {
			$reward_arr=json_decode($v['reward_info'],true);
			if($reward_arr['coupon_id']>0 && $v['type']==2){
				$coupon_info=$coupon_arr[$reward_arr['coupon_id']];
				if($coupon_info['is_discount']==1){
					$v['discount']=floatval($coupon_info['discount_value']);
					$v['type']=3;	
				}else{
					$v['price']=$coupon_info['discount'];
					$v['type']=2;
				}
			}else{
				$v['jifen']=$reward_arr['jifen'];
				$v['type']=1;
                $jifenAll += $v['jifen'];
			}
			$list[$k]=$v;
		}
		//最后一天的奖励
		$max_day=count($list);
		$max_reward_info=$list[$max_day-1];
		$max_reward_info['day']=$max_day;

		$sign_day=0;
		$is_sign=0;
		//获取已签到天数
		$uid=$this->user_session['uid'];
		$sign_info=M('User_sign')->where(array('uid'=>$uid))->order('id desc')->find();
		$now_time=time();
		if($sign_info){
			if(strtotime(date('Ymd',$now_time))-strtotime(date('Ymd',$sign_info['sign_time']))<=86400){
				$sign_day=$sign_info['tday']%$max_day;
				if(date('Ymd')==date('Ymd',$sign_info['sign_time'])){
					$is_sign=1;
					if($sign_day==0 || $sign_info['tday']>$max_day){
						$sign_day=$max_day;
					}
				}elseif($sign_info['tday']>$max_day){
					$sign_day=0;
				}				
			}
		}
        //查询今日领取奖励
        $today = $is_sign ? $list[$sign_day-1] : $list[$sign_day];
		if($today){
            $reward_arr=json_decode($today['reward_info'],true);
            if($reward_arr['coupon_id']>0 && $today['type']==2){
                $coupon_info=$coupon_arr[$reward_arr['coupon_id']];
                if($coupon_info['is_discount']==1){
                    $today['value']=floatval($coupon_info['discount_value']);
                    $today['score_name'] = '折优惠券';
                }else{
                    $today['value']=$coupon_info['discount'];
                    $today['score_name'] = '优惠券';
                }
            }else{
                $today['value']=$reward_arr['jifen'];
                $today['score_name'] = C('config.score_name');
            }
        }

		$this->assign('list',$list);
		$this->assign('is_sign',$is_sign);
		$this->assign('sign_day',$sign_day);
		$this->assign('max_reward_info',$max_reward_info);

		//导航start
        $gift_adver_list = D('Adver')->get_adver_by_key('wap_custorm_sign_slider', 3);
		$this->assign('gift_adver_list',$gift_adver_list);
        //导航end

        $this->assign('today',$today);
        $this->assign('jifen_all',$jifenAll);
		$this->display();
	}

	//签到
	public function custom_sign_api(){
		$where=array();
		$where['is_del']=0;
		$list=M('sign_setting')->where($where)->select();
		if(empty($list)){
			$this->error_tips('网络出错，请重试！');
		}
		$return = D('User')->custom_sign_in($this->user_session['uid'],$list);
		if($return['error_code']==2){
			$return['url']=U('Wap/Login/index');
		}
		$this->success($return);
	}

	public function map(){
		$this->display();
	}

	/*
	 * 分润钱包
	 * */
	public function fenrun_money(){
		$this->display();
	}

	public function my_spread_code(){

		if(empty($this->now_user['spread_code'])){
			$spread_code = $this->make_password();
			M('User')->where(array('uid'=>$this->now_user['uid']))->setField('spread_code',$spread_code);
		}else{
			$spread_code = $this->now_user['spread_code'];
		}
		if($this->now_user['openid']){

			$spread_info = M('User_spread_qrcode')->where(array('uid'=>$this->now_user['uid']))->find();
			if($spread_info && $spread_info['qrcode_type']==0&&time()>strtotime(date('Y-m-d',$spread_info['last_time']))+86400){
				$this->get_spread_qrcode($spread_info['url'],$this->now_user['uid']);
				$spread_info = M('User_spread_qrcode')->where(array('uid'=>$this->now_user['uid']))->find();
			}else if(empty($spread_info)){
				$this->get_spread_qrcode($this->config['site_url'].'/wap.php',$this->now_user['uid']);
				$spread_info = M('User_spread_qrcode')->where(array('uid'=>$this->now_user['uid']))->find();

			}

			if($this->config['open_distributor']==1){
				$this->get_spread_qrcode($this->config['site_url'].'/packapp/merchant/reg.html?spread_code='.$spread_code,$this->now_user['uid']);
				$spread_info = M('User_spread_qrcode')->where(array('uid'=>$this->now_user['uid']))->find();
			}
			$this->assign('spread_qrcode',$spread_info['ticket']);
		}
		$this->assign('spread_code',$spread_code);
		$this->display();
	}

	public function fenrun_recharge_money(){
		$fenrun_money = $_POST['fenrn_money'];
		if($fenrun_money <=0 && $fenrun_money>$this->user_session['fenrun_money']){
			$this->error_tips('您输入的分润金额有误');
		}
		$result = D('User')->fenrun_recharge($fenrun_money);
		if($result['error_code']){
			$this->error_tips($result['msg']);
		}else{
			$this->success_tips($result['msg']);
		}
		$this->display();
	}

	//用户付款而二维码
	public function pay_qrcode(){
		//14位码
		$_SESSION['tmp_payid'] = substr($this->user_session['uid'],0,1).substr($this->user_session['uid'],-1).substr(uniqid('', true), 17).substr(microtime(), 2, 6);
		if(M('Tmp_payid')->where(array('payid'=>$_SESSION['tmp_payid'],'uid'=>$this->user_session['uid']))->find()){
			$this->pay_qrcode();
		}
		$date['uid'] = $this->user_session['uid'];
		$date['payid'] = $_SESSION['tmp_payid'];
		$date['add_time'] = $_SERVER['REQUEST_TIME'];
		M('Tmp_payid')->add($date);
		if(IS_POST){
			$this->success(1);exit;
		}

		return true;
	}

	public function scan_order(){
		$res = D('Store_order')->get_order_by_payid($this->user_session['uid'],$_SESSION['tmp_payid']);
		if(empty($res)){
			$this->error('');
		}
		$this->success('',U('Pay/check',array('type'=>'store','order_id'=>$res['order_id'])));
	}

	//抽奖概率
	public function lottery_shop(){
		$type = $_GET['type'];
		$order_id = $_GET['order_id'];
		$where['order_id']=$order_id;
		$where['uid']=$this->user_session['uid'];

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
		if($now_order) {
		    //微信分享成功回调废弃，无需验证分享
			/*if($now_order['share_status']==0){
				$this->error_tips('您还没有分享给朋友或朋友圈，请分享后再抽奖',U('Shop/status',array('order_id'=>$order_id)));
			}*/

			$mer_id = $now_order['mer_id'];
			$lottery   = M('Lottery_shop')->where(array('mer_id' => $mer_id, 'status' => 1))->find();
			$lottery['content'] = unserialize($lottery['content']);
			$lottery['sys_content'] = unserialize($lottery['sys_content']);

			$prize_arr = array(
					'0' => array('id' => 0,'image_url'=>replace_file_domain($lottery['sys_content'][0]['image_url']),'is_win'=>1, 'title'=>$lottery['sys_content'][0]['title'],'v' => $lottery['sys_content'][0]['probability'],'type'=>$lottery['sys_content'][0]['type'],'link'=>$lottery['sys_content'][0]['link'],'advertisement'=>$lottery['sys_content'][0]['advertisement']),
					'1' => array('id' => 1,'image_url'=>replace_file_domain($lottery['content'][0]['image_url']),'is_win'=>$lottery['content'][0]['is_win'], 'title'=>$lottery['content'][0]['title'],'v' => $lottery['content'][0]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][0]['link'],'advertisement'=>$lottery['content'][0]['advertisement']),
					'2' => array('id' => 2,'image_url'=>replace_file_domain($lottery['content'][1]['image_url']),'is_win'=>$lottery['content'][1]['is_win'], 'title'=>$lottery['content'][1]['title'],'v' => $lottery['content'][1]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][1]['link'],'advertisement'=>$lottery['content'][1]['advertisement']),
					'3' => array('id' => 3,'image_url'=>replace_file_domain($lottery['sys_content'][1]['image_url']),'is_win'=>1, 'title'=>$lottery['sys_content'][1]['title'],'v' => $lottery['sys_content'][1]['probability'],'type'=>$lottery['sys_content'][0]['type'],'link'=>$lottery['sys_content'][1]['link'],'advertisement'=>$lottery['sys_content'][1]['advertisement']),
					'4' => array('id' => 4,'image_url'=>replace_file_domain($lottery['content'][2]['image_url']),'is_win'=>$lottery['content'][2]['is_win'], 'title'=>$lottery['content'][2]['title'],'v' => $lottery['content'][2]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][2]['link'],'advertisement'=>$lottery['content'][2]['advertisement']),
					'5' => array('id' => 5,'image_url'=>replace_file_domain($lottery['sys_content'][2]['image_url']),'is_win'=>1, 'title'=>$lottery['sys_content'][2]['title'],'v' => $lottery['sys_content'][2]['probability'],'type'=>$lottery['sys_content'][0]['type'],'link'=>$lottery['sys_content'][2]['link'],'advertisement'=>$lottery['sys_content'][2]['advertisement']),
					'6' => array('id' => 6,'image_url'=>replace_file_domain($lottery['content'][3]['image_url']),'is_win'=>$lottery['content'][3]['is_win'], 'title'=>$lottery['content'][3]['title'],'v' => $lottery['content'][3]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][3]['link'],'advertisement'=>$lottery['content'][3]['advertisement']),
					'7' => array('id' => 7,'image_url'=>replace_file_domain($lottery['content'][4]['image_url']),'is_win'=>$lottery['content'][4]['is_win'], 'title'=>$lottery['content'][4]['title'],'v' => $lottery['content'][4]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][4]['link'],'advertisement'=>$lottery['content'][4]['advertisement']),
					'8' => array('id' => 8,'image_url'=>replace_file_domain($lottery['sys_content'][3]['image_url']),'is_win'=>1, 'title'=>$lottery['sys_content'][3]['title'],'v' => $lottery['sys_content'][3]['probability'],'type'=>$lottery['sys_content'][0]['type'],'link'=>$lottery['sys_content'][3]['link'],'advertisement'=>$lottery['sys_content'][3]['advertisement']),
					'9' => array('id' => 9,'image_url'=>replace_file_domain($lottery['content'][5]['image_url']),'is_win'=>$lottery['content'][5]['is_win'], 'title'=>$lottery['content'][5]['title'],'v' => $lottery['content'][5]['probability'],'type'=>$lottery['content'][0]['type'],'link'=>$lottery['content'][5]['link'],'advertisement'=>$lottery['content'][5]['advertisement']),
			);

			foreach ($prize_arr as $key => $val) {
				$prize_arr[$key]['link'] = $val['link'] ? htmlspecialchars_decode(htmlspecialchars_decode($val['link'])) : '';
				$prize_arr[$key]['advertisement'] = $val['advertisement'] ? replace_file_domain($val['advertisement']) : ''; 
				$arr[$val['id']] = $val['v'];
			}

			$rid = $this->get_rand($arr); //根据概率获取奖项id

			if(!$res = M('Lottery_shop_list')->where(array('uid'=>$this->user_session['uid'],'order_id'=>$order_id))->find()){
				$data['lottery_id'] = $rid;
				$data['mer_id'] = $mer_id;
				$data['uid'] = $this->now_user['uid'];
				$data['order_id'] = $order_id;
				$data['add_time'] = $_SERVER['REQUEST_TIME'];
				$data['status'] = 0;
				$data['is_win'] = $prize_arr[$rid]['is_win'];
				M('Lottery_shop_list')->add($data);
                fdump_sql(M('Lottery_shop_list')->_sql(),'lottery_shop_list_wap_lottery_shop');
			}else{

				if($res['status']==1){
					$this->error_tips('您已经抽过奖了，请在获奖记录里查看',U('lottery_shop_list'));
				}
				$rid = $res['lottery_id'];
			}
			$this->assign('rid',$rid);
			$lottery['lottery_rule'] = str_replace(PHP_EOL,'<br>',$lottery['lottery_rule']);
			$this->assign('lottery',$lottery);
			$this->assign('prize_arr',$prize_arr);

		}else{
			$this->error_tips('订单不存在');
		}
		$this->display();
	}

    //更新分享状态
    public function ajax_share_friend()
    {
        $type = $_POST['order_type'];
        $order_id = $_POST['order_id'];
        $where['order_id'] = $order_id;
        $where['uid'] = $this->user_session['uid'];
        //$where['share_status']=0;
        switch ($type) {
            case 'shop':
                if ($res = M('Shop_order')->where($where)->find()) {
                    $res['paid'] == 1 && $res['share_status'] == 0 && $result = M('Shop_order')->where($where)->setField('share_status', 1);
                    $res['share_status'] = 1;
                }
                break;
            case 'store':
                if ($res = M('Store_order')->where($where)->find()) {
                    $res['share_status'] == 0 && $result = M('Store_order')->where($where)->setField('share_status', 1);
                    $res['share_status'] = 1;
                    $res['status'] = 2;
                }
                break;
            default:
                $this->error('无效请求');
                break;
        }
        if (!$res['paid']) {
            $this->error('订单未支付');
        }
        if ($this->config['open_share_lottery']) {
            $lottery = M('Lottery_shop')->where(array('mer_id' => $res['mer_id'], 'status' => 1))->find();
            if (empty($lottery)) {
                $this->error('无效请求');
            }
        }
        if ($res['share_status'] == 1) {
            if ($this->config['open_share_lottery'] == 0) {
                if ($this->config['share_coupon']) {
                    $result = D('System_coupon')->share_coupon(array('order_id' => $order_id, 'uid' => $this->user_session['uid'], 'type' => $type));
                }
                if ($result['error_code']) {
                    $this->error($result['msg']);
                } else {
                    $this->success('分享成功');
                    die;
                }
            } else if ($result) {
                $this->success('分享成功');
                die;
            }
        }

        if ($this->config['open_share_lottery']) {
            if ($res['status'] == 2 or $res['status'] == 3 or $res['status'] == 1) {
                $res['share_status'] && $this->success('可以抽奖', U('My/lottery_shop', array('order_id' => $order_id, 'type' => $type)));
            } else {
                $res['share_status'] && $this->error('订单完成后才能抽奖');
            }
        } else if ($this->config['share_coupon']) {
            $res['status'] == 2 && $res['share_status'] && $this->success('获得分享优惠券', U('Share_lottery/my_get_coupon', array('order_id' => $order_id, 'type' => $type)));
        }
        $this->error('无效请求');
    }

	public function birthday(){
		if(IS_POST){
            if (isset($_POST['add_time'])) {
                unset($_POST['add_time']);
            }
			M('User')->where(array('uid' => $this->now_user['uid']))->save($_POST);
			$this->success_tips('保存成功',U('My/myinfo'));
		}else{
			$this->display();
		}
	}
	
	public function birthday_new(){
		redirect(U('Redirect/packapp',array('url'=>urlencode('my/birthday.html'))));
	}
	
	public function email(){
		redirect(U('Redirect/packapp',array('url'=>urlencode('my/email.html'))));
	}


	public function lottery_shop_list(){
		$this->display();
	}

	public function lottery_shop_list_json(){
		$page	=	$_POST['page'];
		$Lottery	=	D('Lottery_shop_list');

		$condition_user_score_list['uid'] = $this->user_session['uid'];
		$condition_user_score_list['award_time'] = array('gt',0);
		$_GET['page'] = isset($_POST['page'])?$_POST['page']:$_GET['page'];
		import('@.ORG.user_page');
		$count = $Lottery->where($condition_user_score_list)->count();
		$p = new Page($count,10);
		//$return['score_list'] = $Lottery->join(' as l left join '.C('DB_PREFIX').'lottery_shop ls ON l.mer_id = ls.mer_id')->where($condition_user_score_list)->order('`add_time` DESC')->limit($p->firstRow,$p->listRows)->select();
		$return['score_list'] = $Lottery->where($condition_user_score_list)->order('`add_time` DESC')->limit($p->firstRow,$p->listRows)->select();
		if($_GET['page'] >  $p->totalPage &&  $p->totalPage>0){
			$return['score_list'] = array();
		}
		$return['pagebar'] = $p->show();
		$return['count'] = count($return['score_list']);
		$sys_lottery = array(0,3,5,9);
		$mer_lottery = array(1,2,4,6,7,8,);

		foreach($return['score_list'] as &$v){
			$v['return'] = unserialize($v['return']);
			$v['time_s']	=	date('Y/m/d H:i',$v['award_time']);
		}
		echo json_encode($return);
	}

	//检查是否已经抽过奖了
	public function ajax_check_lottery(){

		if($lottery = M('Lottery_shop_list')->where(array('uid'=>$this->user_session['uid'],'order_id'=>$_POST['order_id']))->find()){
			$type = $_POST['type'];
			$where=array('uid'=>$this->user_session['uid'],'order_id'=>$_POST['order_id']);
			switch($_POST['type']){
				case 'shop':
					$now_order = M('Shop_order')->where($where)->find();
					break;
				case 'store':
					$now_order = M('Store_order')->where($where)->find();
					$type='cash';
					break;
			}
			if($lottery['status']){
				$this->error(L_('您已经抽过奖了'));
			}else{
				if($_POST['award']){

					$data['status']=1;
					$data['lottery_time']=time();

					if($lottery['is_win']&&$lottery['lottery_time']==0 && $lottery['award_time']==0){
						$lottery_info   = M('Lottery_shop')->where(array('mer_id' => $lottery['mer_id'], 'status' => 1))->find();

						// 不在抽奖时间
						if ($lottery['start_time']&&$lottery['end_time']) {
							if ($lottery['end_time']<time()) {
								$this->error(L_('抽奖已结束'));
							}
							if ($lottery['start_time']>time()) {
								$this->error(L_('抽奖未开始'));
							}
						}
						
						// 抽奖金额已达上限
						if ($lottery['total_money']&&$lottery['total_money']<=$lottery['give_out_money']) {
							$this->error(L_('抽奖已结束'));
						}

						$lottery_info['content'] = unserialize($lottery_info['content']);
						$lottery_info['sys_content'] = unserialize($lottery_info['sys_content']);

						$sys_lottery = array(0,3,5,8);
						$mer_lottery = array(1,2,4,6,7,9,);

						if( in_array($lottery['lottery_id'],$sys_lottery)){
							$key = array_keys($sys_lottery,$lottery['lottery_id']);
							$key =$key[0];

							$lottery_info['sys_content'][$key]['link'] = htmlspecialchars($lottery_info['sys_content'][$key]['link']);

							if($lottery_info['sys_content'][$key]['type']==0 ){  //红包  平台发
								if($this->user_session['openid']==''){
									$error_msg = L_('用户未绑定微信，无法发送红包');
								}else {
									// 单独设置红包比例
									if($lottery_info['sys_content'][$key]['redpack_percent']>0 ){
										// 单独红包比例
										$redpack_percent = $lottery_info['sys_content'][$key]['redpack_percent'];
										// 订单实付总金额
										$pay_money = $now_order['balance_pay'] + $now_order['payment_money'] + $now_order['score_deducte'] + $now_order['merchant_balance'];
										// 红包金额
										$order_info['payment_money'] = $pay_money = round($pay_money * $redpack_percent / 100, 2);
									}else{
										$order_info['payment_money'] = $pay_money = round(($pay_money = $now_order['balance_pay'] + $now_order['payment_money'] + $now_order['score_deducte'] + $now_order['merchant_balance']) * $this->config['redpack_percent'] / 100, 2);
										if($pay_money>200){
											$pay_money=200;
										}
									}
									
									if($pay_money>=1) {
										$pay_method                  = D('Config')->get_pay_method($notOnline, $notOffline, true);
										$import_result               = import('@.ORG.pay.Weixin');
										$order_info['red_desc'] = L_('微信红包');
										$pay_class              = new Weixin($order_info, $pay_money, 'weixin', $pay_method['weixin']['config'], $this->user_session, 1);
										$return                 = $pay_class->sendredpack();
										
										if (!$return['error']) { // 发放成功
											// 记录已发放金额
											M('Lottery_shop')->where(array('mer_id' => $lottery['mer_id']))->setInc('give_out_money',$pay_money);

											$return['msg']  ="{$this->config[$type.'_alias_name']}订单后抽奖，抽奖抽中红包,".$return['msg'].'红包金额'.$pay_money.C('config.Currency_txt');
											D('Scroll_msg')->add_msg('fxcj',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).  $return['msg']);

											$data['return']         = serialize($return);
											$data['award_time']     = time();
											M('Lottery_shop_list')->where(array('uid' => $this->user_session['uid'], 'order_id' => $_POST['order_id']))->save($data);

											$href  = $this->config['site_url'] . '/wap.php?c=My&a=lottery_shop_list';
											$model = new templateNews($this->config['wechat_appid'], $this->config['wechat_appsecret']);

                                            $templates_info = M('Tempmsg')->where(array('tempkey' => 'OPENTM206854010'))->find();
                                            if ($templates_info && $templates_info['tempid']) {
                                                $model->sendTempMsg('OPENTM206854010', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '分享抽奖中奖信息', 'keyword1' => '分享抽奖，抽中微信红包' . $pay_money . C('config.Currency_txt').'。' . $return['msg'], 'keyword2'=>date('Y-m-d H:i:s'),'remark' => '请及时查收！'), '');
                                            } else {
                                                $model->sendTempMsg('TM00785', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '分享抽奖中奖信息', 'program' => '分享抽奖，抽中微信红包' . $pay_money . C('config.Currency_txt'), 'result'=>$return['msg'],'remark' => '请及时查收！'), '');
                                            }
                                            $this->success('红包发送成功，注意查收');
                                        } else {
                                            echo json_encode(['status' => 200, 'info' => '红包发送失败：' . $return['refund_param']['err_msg']]);
                                            exit;
                                        }
									}else{
										$return['msg']  ="由于计算出您本次获得的红包金额小于1".C('config.Currency_txt')."，故无法发送红包，抱歉！";
										$data['return']         = serialize($return);
										$data['award_time']     = time();
										M('Lottery_shop_list')->where(array('uid' => $this->user_session['uid'], 'order_id' => $_POST['order_id']))->save($data);
										$this->error($return['msg'] );
									}
								}
							}elseif($lottery_info['sys_content'][$key]['type']==1){  //优惠券
								$coupon_id = $lottery_info['sys_content'][$key]['coupon_id'];
								$return = D('System_coupon')->send_coupon_by_id($coupon_id,$this->user_session['uid']);//不管超不超过限制都送但是不能超过总数，超过报错
								$return['msg'] = "{$this->config[$type.'_alias_name']}订单后抽奖,抽中系统优惠券，".$return['msg'];
								D('Scroll_msg')->add_msg('fxcj',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).  $return['msg']);

								$data['return']  = serialize($return);
								$data['award_time']  = time();
								M('Lottery_shop_list')->where(array('uid'=>$this->user_session['uid'],'order_id'=>$_POST['order_id']))->save($data);
								if(!$return['error']) {

									$href  = $this->config['site_url'] . '/wap.php?c=My&a=lottery_shop_list';
									$model = new templateNews($this->config['wechat_appid'], $this->config['wechat_appsecret']);

                                    $templates_info = M('Tempmsg')->where(array('tempkey' => 'OPENTM206854010'))->find();
                                    if ($templates_info && $templates_info['tempid']) {
                                        $model->sendTempMsg('OPENTM206854010', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '分享抽奖中奖信息', 'keyword1' => "分享抽奖抽中优惠券【{$lottery_info['sys_content'][$key]['title']}】。".$return['msg'], 'keyword2'=>date('Y-m-d H:i:s'),'remark' => '请及时查收！'), $lottery_info['mer_id']);
                                    } else {
                                        $model->sendTempMsg('TM00785', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '分享抽奖中奖信息', 'program' => "分享抽奖抽中优惠券【{$lottery_info['sys_content'][$key]['title']}】", 'result'=>$return['msg'], 'remark' => '请及时上线联系商家进行兑奖！'), $lottery_info['mer_id']);
                                    }
									$this->success($return['msg']);
								}else{
									$this->error($return['msg']);
								}
							}elseif($lottery_info['sys_content'][$key]['type']==2){
                               //平台广告

								$link = urldecode($lottery_info['sys_content'][$key]['link']);

								$advertisement = $lottery_info['sys_content'][$key]['advertisement'];
								$data['award_time'] = time();
								$data['return']     = serialize(['error'=>0,'msg'=>$lottery_info['sys_content'][$key]['title']??'平台广告']);
								M('Lottery_shop_list')->where(array('uid' => $this->user_session['uid'], 'order_id' => $_POST['order_id']))->save($data);
								$return['link'] = htmlspecialchars_decode(htmlspecialchars_decode($link));
								$return['advertisement'] = replace_file_domain($advertisement);
								$return['status'] = 100;
								if($link){
									echo json_encode($return);exit;
								}
							}
						}else{
							//商家设置选项
							$key = array_keys($mer_lottery,$lottery['lottery_id']);
							$key =$key[0];
							//fdump('content_'.$lottery_info['content'][$key]['type'],'郭杰',true);
							$lottery_info['content'][$key]['link'] = urlencode($lottery_info['content'][$key]['link']);
							if($lottery_info['content'][$key]['type']==0){  //优惠券
								$coupon_id = $lottery_info['content'][$key]['coupon_id'];
								$return = D('Card_new_coupon')->send_coupon_by_id($coupon_id,$this->user_session['uid']);//不管超不超过限制都送但是不能超过总数，超过报错
								$return['msg'] = "{$this->config[$type.'_alias_name']}订单后抽奖,抽中商家优惠券，".$return['msg'];
								D('Scroll_msg')->add_msg('fxcj',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).  $return['msg']);

								$data['return']  = serialize($return);
								$data['award_time']  = time();
								M('Lottery_shop_list')->where(array('uid'=>$this->user_session['uid'],'order_id'=>$_POST['order_id']))->save($data);
								if(!$return['error']) {

									$href  = $this->config['site_url'] . '/wap.php?c=My&a=lottery_shop_list';
									$model = new templateNews($this->config['wechat_appid'], $this->config['wechat_appsecret']);

                                    $templates_info = M('Tempmsg')->where(array('tempkey' => 'OPENTM206854010'))->find();
                                    if ($templates_info && $templates_info['tempid']) {
                                        $model->sendTempMsg('OPENTM206854010', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '分享抽奖中奖信息', 'keyword1' => "分享抽奖抽中优惠券【{$lottery_info['content'][$key]['title']}】。" . $return['msg'], 'keyword2'=>date('Y-m-d H:i:s'),'remark' => '请及时查收！'), $lottery_info['mer_id']);
                                    } else {
                                        $model->sendTempMsg('TM00785', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '分享抽奖中奖信息', 'program' => "分享抽奖抽中优惠券【{$lottery_info['content'][$key]['title']}】", 'result'=>$return['msg'],'remark' => '请及时上线联系商家进行兑奖！'), $lottery_info['mer_id']);
                                    }

                                    $this->success($return['msg']);
								}else{
									$this->error($return['msg']);
								}
							}elseif($lottery_info['content'][$key]['type']==2){
								//商家广告
								$link = urldecode($lottery_info['content'][$key]['link']);

								$advertisement = $lottery_info['content'][$key]['advertisement'];

								$data['award_time'] = time();
								$data['return']     = serialize(['error'=>0,'msg'=>$lottery_info['content'][$key]['title']??'商家广告']);
								M('Lottery_shop_list')->where(array('uid' => $this->user_session['uid'], 'order_id' => $_POST['order_id']))->save($data);

								$return['link'] = htmlspecialchars_decode(htmlspecialchars_decode($link));
								$return['advertisement'] = replace_file_domain($advertisement);
								$return['status'] = 100;
								if($link){
									echo json_encode($return);exit;
								}
							}else {
								$return['error'] = 0;
								if ($lottery['is_win']) {
									$return['msg']   = "{$this->config[$type.'_alias_name']}订单后抽奖，抽中商家奖【{$lottery_info['content'][$key]['title']}】";
									D('Scroll_msg')->add_msg('fxcj',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).  $return['msg']);
								} else {
									$return['error'] = 1;
//									$return['msg'] = "分享{$this->config[$type.'_alias_name']}订单后抽奖，未中奖{$lottery_info['content'][$key]['title']}";
									$return['msg'] = "{$this->config[$type.'_alias_name']}订单后抽奖，未中奖";
								}
								$data['award_time'] = time();
								$data['return']     = serialize($return);

								M('Lottery_shop_list')->where(array('uid' => $this->user_session['uid'], 'order_id' => $_POST['order_id']))->save($data);
								if (!$return['error']) {
									$href  = $this->config['site_url'] . '/wap.php?c=My&a=lottery_shop_list';
									$model = new templateNews($this->config['wechat_appid'], $this->config['wechat_appsecret']);

                                    $templates_info = M('Tempmsg')->where(array('tempkey' => 'OPENTM206854010'))->find();
                                    if ($templates_info && $templates_info['tempid']) {
                                        $model->sendTempMsg('OPENTM206854010', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '分享抽奖中奖信息', 'keyword1' => "{$this->config[$type.'_alias_name']}订单后抽奖。" . $return['msg'], 'keyword2'=>date('Y-m-d H:i:s'),'remark' => '请及时查收！'), $lottery_info['mer_id']);
                                    } else {
                                        $model->sendTempMsg('TM00785', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '分享抽奖中奖信息', 'program' => "{$this->config[$type.'_alias_name']}订单后抽奖", 'result'=>$return['msg'],'remark' => '请及时上线联系商家进行兑奖！'), $lottery_info['mer_id']);
                                    }
									$this->success($return['msg']);
								}else{
									$this->error($return['msg']);
								}
							}
						}
					}else{
						$lottery_info   = M('Lottery_shop')->where(array('mer_id' => $lottery['mer_id'], 'status' => 1))->find();
						$lottery_info['content']  = unserialize($lottery_info['content']);
						$key = $lottery['lottery_id'];
						$return['error']=1;
						$return['msg'] = "{$this->config[$type.'_alias_name']}订单后抽奖，未中奖{$lottery_info['content'][$key]['title']}";

						$data['award_time'] = time();
						$data['return']     = serialize($return);
						M('Lottery_shop_list')->where(array('uid' => $this->user_session['uid'], 'order_id' => $_POST['order_id']))->save($data);
						if(!$return['error']) {
							$this->success($return['msg']);
						}else{
							$this->error($return['msg']);
						}
					}
				}else{
					$this->success('可以抽奖');
				}
			}
		}else{
			$this->error('订单不存在');
		}
	}

	//智百威同步
	public function zbw_sync(){
		$now_user = D('User')->get_user($this->user_session['uid']);
		if( $now_user['phone']){
			$zbw_card = D('ZbwErp')->GetVipInfoTel($now_user['phone']);
			if(empty($zbw_card)){
				$zbw_card = D('ZbwErp')->VipCreate($now_user);
			}else{
				$now_money = D('ZbwErp')->GetVipSpareCash($zbw_card['card_id']);
				$score = D('ZbwErp')->GetVipSpareCash($zbw_card['card_id']);
			}

		}
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

	public function make_password( $length = 8)
	{
		$str = substr(md5(time()), 0, 6);
		if(!M('User')->where(array('spread_code'=>$str))->find()){
			return $str;
		}else{
			return  $this->make_password();
		}
	}

	//分类管理员申请页面
	public function activity_apply(){
        $where = array(
            'uid' => $this->now_user['uid']
        );
        $list = M('Classify_manager')->field(true)->where($where)->find();
        if ($list) {
            redirect(U('My/activity_owner', array('id' => $list['id'])));
        }
		$this->display();
	}

	public function activity_owner(){
		$this->display();
	}

	public function ajax_express(){
		$express_list = M('Express')->field(true)->where(array('status'=>1))->select();
		if($express_list){
			exit(json_encode(array('status'=>0,'list'=>$express_list)));
		}else{
			exit(json_encode(array('status'=>1,'list'=>array())));
		}
	}
	
	//跳转商家客服中转页，目的是为了网页授权获取用户 openid
	public function concact_kefu(){
        if (C('IM_JMESSAGE_OPEN')) {
            $storeId = intval($_GET['store_id']);
            $merId = M('Merchant_store')->where(['store_id' => $storeId])->getField('mer_id');
            $storeKf = M('Merchant_store_kefu')->where(array('store_id' => $storeId))->find();
            if ($storeKf) {
                $url = build_im_chat_url('user_' . $this->now_user['uid'], $storeKf['username'], 'user2store', ['from' => $_GET['from']]);
                redirect($url);
                exit;
            } else {
                $this->error_tips('客服未配置');
            }
        }

		$key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => ($this->now_user['openid'] ? $this->now_user['openid'] : $this->now_user['uid'])), $this->config['im_appkey']);
		$url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . ($this->now_user['openid'] ? $this->now_user['openid'] : $this->now_user['uid']) . '&key=' . $key . '&title=' . urlencode('联系客服') . '#serviceList_' . $_GET['mer_id'];
		redirect($url);
	}
	
	/* 跳转到IM聊天系统 */
	public function go_im(){
		$openid = $this->now_user['openid'] ? $this->now_user['openid'] : $this->now_user['uid'];
		$key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' =>$openid ), $this->config['im_appkey']);
		
		$hash = ltrim($_GET['hash'],'group_user');
		if($hash != $_GET['hash'] && preg_match('/^(\d+)$/',$hash)){
			$nowUser = D('User')->get_user($hash);
			if($nowUser && $nowUser['openid']){
				$_GET['hash'] = 'group_'.$nowUser['openid'];
			}
		}
		
		$url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $openid . '&key=' . $key .($_GET['title'] ? '&title='.urlencode($_GET['title']) : ''). ($_GET['hash'] ? '#'.$_GET['hash'] : '');
		redirect($url);
	}

	/**商户付款码*/
	public function my_store_card_code() {
        $this->user_sessions();
        $uid = $this->user_session['uid'];
        $where = array(
            'uid'=>$uid
        );
        $user_payid = M('User_payid')->where($where)->find();
        if (!$user_payid) {
        	$user_payid = $this->user_pay_qrcode($uid);
        }else{
        	$user_payid = $user_payid['payid']; 
        }

        $code_info = $user_payid;
        //$url = "http://qr.liantu.com/api.php?w=200&text=" . urlencode($code_info);
		$url = C('config.site_url') . "/index.php?g=Index&c=Recognition&a=get_own_qrcode&size=8&qrCon=" . urlencode($code_info);

        $this->assign('code_url', $url);

        // 条形码
        $my_store_card_barcode = $this->config['site_url'].U('My/my_store_card_barcode',array('code_info'=>$code_info));
        $this->assign('my_store_card_barcode', $my_store_card_barcode);


        $this->display();
    }

    /**获取二维码支付id*/
    private function  user_pay_qrcode($uid) {
        $user_payid = substr($uid,0,1).substr($uid,-1).substr(uniqid('', true), 17).substr(microtime(), 2, 6);
        // 该支付id 只能使用 30秒  改成不限时间的
        $where = array(
            'payid'=>$user_payid,
            'uid'=>$uid
        );
        if(M('User_payid')->where($where)->find()){
            $this->user_pay_qrcode($uid);
        }
        $data['uid'] = $uid;
        $data['payid'] = $user_payid;
        $data['phone'] = $this->now_user['phone'];
        $data['add_time'] = $_SERVER['REQUEST_TIME'];
        M('User_payid')->add($data);
        return $user_payid;
    }

    /**刷新二维码*/
    public function refresh_pay_qrcode() {
        $this->user_sessions();
        $uid = $this->user_session['uid'];
        $condition_info['uid'] = $uid;
        M('User_payid')->where($condition_info)->delete();
        $user_payid = $this->user_pay_qrcode($uid);
        $code_info = $user_payid;
        //$url = "http://qr.liantu.com/api.php?w=200&text=" . urlencode($code_info);
		$url = C('config.site_url') . "/index.php?g=Index&c=Recognition&a=get_own_qrcode&size=8&qrCon=" . urlencode($code_info);
        // 条形码
        $my_store_card_barcode = $this->config['site_url'].U('My/my_store_card_barcode',array('code_info'=>$code_info));

        exit(json_encode(array('status'=>1,'url'=>$url, 'card_barcode' => $my_store_card_barcode)));
    }

	/**二维码收款*/
	public function my_receipt_code() {
        $this->user_sessions();
        $uid = $this->user_session['uid'];
        $code_url = $this->config['site_url'] . '/wap.php?g=Wap&c=My&a=payment_other&uid='.$uid;
        //$url = "http://qr.liantu.com/api.php?text=" . urlencode($code_url);
		$url = C('config.site_url') . "/index.php?g=Index&c=Recognition&a=get_own_qrcode&size=8&qrCon=" . urlencode($code_url);

        $this->assign('code_url', $url);
        $this->display();
    }

    /**验证登录密码*/
    public function check_user_pwd() {
        $this->user_sessions();
        $uid = $this->user_session['uid'];
        $pwd = isset($_POST['password']) ? $_POST['password'] : '';
        $user_info = M('User')->where(array('uid'=>$uid))->field('phone')->find();
        $phone = $user_info['phone'];
        $login_result = D('User')->checkin($phone, $pwd);
        if($login_result['error_code']){
            $this->error($login_result['msg']);
        }else{
            $this->success('验证成功！');
        }
    }

    /**转赠余额*/
    public function payment_other()
    {
        if ($this->config['user_transfer_balance_switch'] != 1) {
            $this->error_tips('平台没有开启余额转赠功能！');
        }
        // 获取转赠者信息
        $other_uid = I('uid');
        if (!$other_uid) {
            $this->error_tips('无转赠对象！');
        }
        if ($this->user_session['uid'] == $other_uid) {
            $this->error_tips('不可转赠给自己！');
        }
        $other_info = M('User')->where(array('uid' => $other_uid))->find();
        if (!$other_info) {
            $this->error_tips('无转赠对象信息！');
        }
        $other_info['nickname'] = $other_info['nickname'] ? $other_info['nickname'] : substr_replace($other_info['phone'], '****', 3, 4);
        $this->assign('other_info', $other_info);

        $user_info = M('User')->where(array('uid' => $this->user_session['uid']))->find();
        if ($user_info['score_recharge_money'] > 0 && $user_info['now_money'] < $user_info['score_recharge_money']) {
            $this->error_tips('用户积分兑换余额数据错误');
        }
        $can_withdraw_money = $user_info['now_money'] >= $user_info['score_recharge_money'] ? floatval((int)(($user_info['now_money'] - $user_info['score_recharge_money']) * 100) / 100) : $user_info['now_money'];
        if ($can_withdraw_money < 0) {
            $this->error_tips('可转赠金额错误');
        }
        if ($user_info['frozen_time'] < $_SERVER['REQUEST_TIME'] && $_SERVER['REQUEST_TIME'] < $user_info['free_time']) {
            $user_info['can_withdraw_money'] = $can_withdraw_money - $user_info['frozen_money'] > 0 ? $can_withdraw_money - $user_info['frozen_money'] : 0;
        } else {
            $user_info['can_withdraw_money'] = $can_withdraw_money;
        }
        $this->assign('user_info', $user_info);

        if (IS_POST) {
            $money = $_POST['money'];
            if (empty($other_uid)) {
                $this->error('无转赠对象');
            }
            if ($money > $can_withdraw_money) {
                $this->error('转赠超出限额，转赠失败！');
            }

            if ($user_info['score_recharge_money'] > 0 && $user_info['now_money'] < $user_info['score_recharge_money']) {
                $this->error('用户积分兑换余额数据错误，申请失败！');
            }
            $user_money_desc = '转赠用户 ' . $other_info['nickname'] . ', 扣除余额';
            $use_result = D('User')->user_money($user_info['uid'], $money, $user_money_desc, 0, 0, 1);
            if ($use_result['error_code']) {
                $this->error($use_result['msg']);
            }
            $user_info['nickname'] = $user_info['nickname'] ? $user_info['nickname'] : substr_replace($user_info['phone'], '****', 3, 4);
            $other_money_desc = '获取用户 ' . $user_info['nickname'] . ' 转赠, 增加余额';
            $add_result = D('User')->add_money($other_uid, $money, $other_money_desc);
            if ($add_result['error_code']) {
                D('User')->add_money($user_info['uid'], $money, '转赠失败，余额增加');
                $this->error($add_result['msg']);
            } else {
                // 成功后调往余额记录
                $url = U('My/transaction');
                $this->success("转赠成功！", $url);
            }

        } else {
            $this->display();
        }
    }

    /**付款条形码*/
    public function my_store_card_barcode()
    {
        import('@.ORG.barcode');
        $colorFront = new BCGColor(0, 0, 0);
        $colorBack = new BCGColor(255, 255, 255);
        $code_info = I('code_info');

        $font = new BCGFontFile($_SERVER['DOCUMENT_ROOT'] . '/cms/Lib/ORG/barcode/font/Arial.ttf', 18);
        // Barcode Part
        $code = new BCGcode128();
        $code->setScale(2);
        $code->setColor($colorFront, $colorBack);
        $code->setFont(0);

        $code->parse($code_info);

        // Drawing Part
        $drawing = new BCGDrawing('', $colorBack);
        $drawing->setBarcode($code);
        $drawing->draw();

        header('Content-Type: image/png');
        $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    }

    /**反馈*/
    public function feedback()
    {
       $this->display();
    }

    /**申请成为分销人员*/
    public function distributor_apply()
    {
    	$now_distributor = D('Distributor')->where(array('uid'=>$this->user_session['uid']))->find();
    	if (IS_POST) {
            $res = D('Distributor')->where(array('phone'=>$_POST['phone'],'status'=>['neq',2]))->find();
            if($res) {
                exit(json_encode(array('code'=>0,'msg'=>'手机号已存在~')));
            }
    		if ($now_distributor) {
    			$_POST['status'] = 0;
    			$save = D('Distributor')->where(array('uid'=>$this->user_session['uid']))->save($_POST);
    		}else{
    			$_POST['uid'] = $this->user_session['uid'];
    			$_POST['add_time'] = time();
    			$save = D('Distributor')->add($_POST);
    		}
    		if ($save) {
				exit(json_encode(array('code'=>0,'msg'=>'申请成功，请等待审核！','url'=>U('index'))));
    		}else{
				exit(json_encode(array('code'=>1,'msg'=>'申请失败')));
    		}
    	}

    	$province_list_temp = array();
		$project_arr_temp = array();
		$city_arr_temp = array();

    	if ($now_distributor) {
    		$now_province = D('Area')->where(array('area_id'=>$now_distributor['province_id']))->find();
    		$now_city = D('Area')->where(array('area_id'=>$now_distributor['city_id']))->find();
			$now_project = M('Appoint_project')->where(array('id'=>$now_distributor['project_id']))->find();
			$now_project_city = M('Appoint_project_city')->where(array('project_city_name'=>$now_project['city_name']))->find();
			$this->assign('now_province',$now_province);
			$this->assign('now_city',$now_city);
			$this->assign('now_project',$now_project);
			$this->assign('now_project_city',$now_project_city);
			$this->assign('now_distributor',$now_distributor);
			// 当前省份下城市
			$area_city =  D('Area')->get_arealist_by_areaPid($now_distributor['province_id'],true);
			$area_city = $area_city ? $area_city : array();
	    	foreach ($area_city as $key => $value) {
				$city_arr_temp[] = array(
					'title' => $value['area_name'],
					'value' => $value['area_id'],
				);
			}

			// 当前城市下项目
			$project_arr = M('Appoint_project')->where(array('city_name'=>$now_project['city_name']))->select();
			$project_arr = $project_arr ? $project_arr : array();
	    	foreach ($project_arr as $key => $value) {
				$project_arr_temp[] = array(
					'title' => $value['project_name'],
					'value' => $value['id'],
				);
			}
    	}

    	if ($this->config['many_city']) {
    		$province_list = D('Area')->where(array('is_open'=>1,'area_type'=>1))->select();
    	}
    	foreach ($province_list as $key => $value) {
			$province_list_temp[] = array(
				'title' => $value['area_name'],
				'value' => $value['area_id'],
			);
		}

    	$project_city_arr = D('Appoint_project_city')->select();
    	$project_city_arr_tmp = array();
    	foreach ($project_city_arr as $key => $value) {
			$project_city_arr_tmp[] = array(
				'title' => $value['project_city_name'],
				'value' => $value['id'],
			);
		}

		$this->assign('province_arr',json_encode($province_list_temp));
		$this->assign('project_arr',json_encode($project_arr_temp));
		$this->assign('city_arr',json_encode($city_arr_temp));
		$this->assign('project_city_arr',json_encode($project_city_arr_tmp));
       	$this->display();
    }

    public function distributor_spread_list(){
		if(isset($_GET['status'])){
			if(in_array($_GET['status'],array(0,1,2))){
				$condition_spread_list['status'] = $_GET['status'];
			}
		}else{
			$condition_spread_list['status'] = 0;
		}
		$condition_spread_list['_string'] = 'spread_uid = '.$this->user_session['uid'];
		//待结算订单
		$spread_list = D('Distributor_spread_list')->field(true)->where($condition_spread_list)->order('`pigcms_id` DESC')->select();
		if($spread_list){
			foreach($spread_list as $key=>$value){
				$value['get_user'] = $spread_list[$key]['get_user'] = D('User')->get_user($value['order_uid']);
				//组成描述语句
                $spread_list[$key]['desc'] = array();
				$spread_list[$key]['desc']['txt'] = '推广用户 '.$value['get_user']['nickname'].' 购买';
				
				switch ($value['order_type']) {
					case 'group':
						$order_info = $spread_list[$key]['order_info'] = D('Group_order')->field(true)->where(array('order_id'=>$value['order_id']))->find();
						// if($order_info['status'] == 0){
						// 	unset($spread_list[$key]);
						// 	continue;
						// }
						$value['group_info'] = $spread_list[$key]['group_info'] = D('Group')->field('`group_id`,`name`')->where(array('group_id'=>$value['third_id']))->find();
						$spread_list[$key]['desc']['info'] = $value['group_info']['name'].'产品';
						break;
					case 'mall':
						$order_info = $spread_list[$key]['order_info'] = D('Shop_order')->field(true)->where(array('order_id'=>$value['order_id']))->find();

						// if($order_info['status'] == 0){
						// 	unset($spread_list[$key]);
						// 	continue;
						// }
						$spread_list[$key]['shop_info'] = D('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id'=>$value['third_id']))->find();
						$spread_list[$key]['desc']['info'] = $spread_list[$key]['shop_info']['name'].'产品';
						break;
					case 'appoint':
						$order_info = $spread_list[$key]['order_info'] = D('Appoint_order')->field(true)->where(array('order_id'=>$value['order_id']))->find();

						// if($order_info['status'] == 0){
						// 	unset($spread_list[$key]);
						// 	continue;
						// }
						$spread_list[$key]['appoint_info'] = D('Appoint')->field('`appoint_id`,`appoint_name`')->where(array('appoint_id'=>$value['third_id']))->find();
						$spread_list[$key]['desc']['info'] = $spread_list[$key]['appoint_info']['appoint_name'] .'产品';
						break;
				}
			}
		}

		$this->assign('spread_list',$spread_list);
		$this->display();
	}

	//根据城市ID得到符合的区县或商城
	public function ajax_get_area(){
		$area_id = intval($_POST['area_id']);
		// var_dump($area_id);
		$database_area = D('Area');
		if(!empty($area_id)){
			$area_city = $database_area->get_arealist_by_areaPid($area_id,true);
			
		}
		if(!$area_city){
			$area_city = array();
		}

		$area_list_temp = array();
    	foreach ($area_city as $key => $value) {
			$area_list_temp[] = array(
				'title' => $value['area_name'],
				'value' => $value['area_id'],
			);
		}
		echo json_encode($area_list_temp);
	}

	//根据城市得到符合的项目
    public function ajax_get_project(){
        $city_name = $_POST['city_name'];
        if(!empty($city_name)){
            $project = M('Appoint_project')->where(array('city_name'=>$city_name))->select();
        }
        if(!$project){
            $project = array();
        }

        $project_temp = array();
        foreach ($project as $key => $value) {
            $project_temp[] = array(
                'title' => $value['project_name'],
                'value' => $value['id'],
            );
        }
        echo json_encode($project_temp);
    }



    /**激活码功能区块*/
    /**绑定激活码*/
    public function bind_activation_code(){
        $activation_code = $_POST['activation_code']?trim($_POST['activation_code']):'';
        $uid = $this->user_session['uid'];
        if (!$activation_code) {
            $this->error("请填写激活码！");
        }
        $code_listDb = D('User_level_activation_code');
        $userDb = D('User');
        $user_level = D('User_level');
        $now_user =  $userDb->where(array('uid' => $uid))->find();
        if (!$now_user) {
            $this->error('未获取到您的帐号信息，请重新登录！',U('Login/index'));
        }
        $code_info = $code_listDb->where(array('activation_code' => $activation_code, 'status' => array('neq', 4)))->find();
        if (!$code_info) {
            $this->error("激活码不存在或者已经被删除！");
        }
        if ($code_info['uid'] && $code_info['uid'] != $uid) {
            $this->error("该激活码已失效！");
        }
        if ($code_info['status'] == 2) {
            $this->error("该激活码已失效！");
        }
        if ($code_info['status'] == 3) {
            $this->error("该激活码已失效！");
        }
        $user_level_info = $user_level->where(array('id' => $code_info['level_id']))->find();
        if (!$user_level_info) {
            $this->error("会员等级不存在或者已经被删除！");
        }
        //等级变化更新成为该等级的时间
        if ($now_user['level'] == $user_level_info['level']) {
            $this->error("您已是该激活码对应等级，请勿重新激活！");
        } elseif ($now_user['level'] > $user_level_info['level']) {
            $this->error("操作失败，当前等级高于激活码等级！");
        }
        $code_msg = $code_listDb->where(array('uid' => $uid, 'status' => array('neq', 4), 'activation_code' => array('neq',$activation_code)))->find();
        if ($code_msg && $now_user['level']>0) {
            if ($code_msg['status']==1 && $now_user['level']==$user_level_info['level']) {
                $this->error("您已是该激活码对应等级，请勿重新激活！");
            } elseif ($code_msg['status']==1 && $now_user['level']>$user_level_info['level']) {
                $this->error("操作失败，当前等级高于激活码等级！");
            }
        }
        //等级变化更新成为该等级的时间
        $now_time = time();
        $data_user['level'] = intval($user_level_info['level']);
        $data_user['level_time'] = $now_time;
        $save = $userDb->where(array('uid' => $uid))->data($data_user)->save();
        if ($save) {
            $data['last_time'] = time();
            $data['status'] = 1;
            $data['uid'] = $uid;
            $info = $code_listDb->where(array('activation_code' => $activation_code))->data($data)->save();
            // 清除多余的
            if ($code_msg) {
                $code_listDb->where(array('code_id' => $code_msg['code_id']))->save(array('status' => 3, 'uid' => '', 'activation_time'=>'', 'last_time' => time()));
            }
            // 添加操作记录
            $log = array(
                'type' => 1,
                'log_type' => 3,
                'code_id' => $code_info['code_id'],
                'uid' => $uid,
                'log_post' => serialize($_POST),
                'log_info' => '用户使用了激活码',
                'log_time' => time()
            );
            M('User_level_activation_code_log')->add($log);
            // 记录激活对应获取积分
            D('User_level_open_log')->add_reward($uid, $user_level_info, $uid, 1, 2, $now_time, 3, '用户使用激活码开通会员等级');
            
            if($data_user['level'] > 0){//升级等级送优惠券
                $user_level = M('User_level')->where(array('level'=>$data_user['level']))->find();
                if(!empty($user_level['coupon_ids'])){
                    $hadpull = M('System_coupon_hadpull');
                    $coupon_id = explode(',',$user_level['coupon_ids']);
                    foreach($coupon_id as $v){
                        $coupon = M('System_coupon')->field(true)->where(array('coupon_id'=>$v))->find();
                        $hadpull_count = $hadpull->where(array('uid'=>$uid,'coupon_id'=>$v))->count();
                        //实际可领取的数量
                        $receive_num=$coupon['limit']-$hadpull_count;
                        //剩余的数量
                        $after_num=$coupon['num']-$coupon['had_pull'];
                        $reduce_num=min($receive_num,$after_num);
                        $where['coupon_id']=$v;
                        M('System_coupon')->where($where)->setInc('had_pull',$reduce_num);
                        M('System_coupon')->where($where)->setField('last_time',$_SERVER['REQUEST_TIME']);
                        $data_all=array();
                        for($i=0;$i<$reduce_num;$i++){
                            $data=array();
                            $data['coupon_id'] = $v;
                            $data['num'] = 1;
                            $data['receive_time'] =$_SERVER['REQUEST_TIME'];
                            $data['status'] = 0;

                            $data['uid']  = $uid;
                            $data_all[]=$data;
                        }
                        $hadpull->addAll($data_all);
                    }
                }
            }
            $this->success('激活成功!');
        } else {
            $this->error("激活失败，请重试！");
        }
    }


    /**获取等级礼包*/
    public function receive_level_gift() {
        $uid = $this->user_session['uid'];
        $now_user = M('User')->where(array('uid' => $uid))->find();
        if ($now_user['level'] <= 0) {
            $this->error_tips("对不起，您不符合领取条件！");
        }
        $m_user_level_gift_order = M('User_level_gift_order');
        $m_user_level_gift = M('User_level_gift');
        $m_user_level = M('User_level');
        $level_info = $m_user_level->where(array('level' => $now_user['level']))->find();
        if (empty($level_info)) {
            $this->error_tips("当前用户会员等级不存在或者已经被删除！");
        }
        $user_level_gift_info = $m_user_level_gift->where(array('level_id' => $level_info['id'], 'gift_status' => array('neq', 3)))->find();
        if (empty($user_level_gift_info) || $user_level_gift_info['gift_status']!=1) {
            $this->error_tips("礼包领取失败，请稍后再试！");
        }
        $user_level_gift_info['gift_img'] = replace_file_domain($user_level_gift_info['gift_img']);

        $level_gift_where = array(
            'level_time' => $now_user['level_time'],
            'level_id' => $level_info['id'],
            'uid' => $uid,
        );
        $user_level_gift_order_info = $m_user_level_gift_order->where($level_gift_where)->order('add_time DESC')->find();
        if (empty($user_level_gift_order_info)) {
            $other_where = array(
                'uid' => $uid,
                'status' => 0,
            );
            $user_level_gift_order_info = $m_user_level_gift_order->where($other_where)->order('add_time DESC')->find();
            if (empty($user_level_gift_order_info)) {
                $this->error_tips("对不起，您不符合领取条件！");
            }
            $user_level_gift_info = $m_user_level_gift->where(array('level_id' => $user_level_gift_order_info['level_id'], 'gift_status' => array('neq', 3)))->find();
            if (empty($user_level_gift_info) || $user_level_gift_info['gift_status']!=1) {
                $this->error_tips("礼包领取失败，请稍后再试！");
            }
        } elseif ($user_level_gift_order_info['status'] > 0) {
            $other_where = array(
                'uid' => $uid,
                'status' => 0,
            );
            $user_level_gift_order_info = $m_user_level_gift_order->where($other_where)->order('add_time DESC')->find();
            if (empty($user_level_gift_order_info)) {
                $this->error_tips("对不起，您已领取过礼包！");
            }
            $user_level_gift_info = $m_user_level_gift->where(array('level_id' => $user_level_gift_order_info['level_id'], 'gift_status' => array('neq', 3)))->find();
            if (empty($user_level_gift_info) || $user_level_gift_info['gift_status']!=1) {
                $this->error_tips("礼包领取失败，请稍后再试！");
            }
        }
        $this->assign('user_level_gift_info', $user_level_gift_info);
        $this->assign('user_level_gift_order_info', $user_level_gift_order_info);

        $address_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']):0;
        $deliver_info = array();
        $user_address_list = array();
        $user_address_temp = D('User_adress')->field(true)->where(array('uid' => $this->user_session['uid']))->order('`default` DESC,`adress_id` DESC')->select();
        $deliver_info['address_count'] = count($user_address_temp);
        foreach ($user_address_temp as $address) {
            if ($address_id == $address['adress_id']) {
                $user_adress = $address;
                break;
            }
            if(empty($address_id) && $address['default']){
                $user_adress = $address;
                break;
            }
            $user_address_list[] = $address;
        }
        if (empty($user_adress) && $user_address_list) {
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
        $this->assign('user_adress', $user_adress);

        $this->display();
    }

    /**用户领取礼包*/
    public function receive_gift() {
        $uid = $this->user_session['uid'];
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']):0;
        $adress_id = isset($_POST['adress_id']) ? intval($_POST['adress_id']):0;
        $m_user_level_gift_order = M('User_level_gift_order');
        $m_user_level_gift = M('User_level_gift');

        if ($adress_id<=0) {
            $this->error("对不起，请选择地址！");
        }

        $where = array(
            'uid' => $uid,
            'order_id' => $order_id,
        );
        $user_level_gift_order_info = $m_user_level_gift_order->where($where)->find();
        if (empty($user_level_gift_order_info)) {
            $this->error("对不起，您不符合领取条件！");
        }elseif ($user_level_gift_order_info['status'] > 0) {
            $this->error("对不起，您已领取过礼包！");
        }
        // 通过未领取的订单获取最新等级的礼包信息
        $user_level_gift_info = $m_user_level_gift->where(array('level_id' => $user_level_gift_order_info['level_id'], 'gift_status' => array('neq', 3)))->find();
        if (empty($user_level_gift_info) || $user_level_gift_info['gift_status']!=1) {
            $this->error_tips("礼包领取失败，请稍后再试！");
        }

        $get_remark = isset($_POST['get_remark']) ? trim($_POST['get_remark']):'';
        $get_name = isset($_POST['get_name']) ? trim($_POST['get_name']):'';
        $get_address = isset($_POST['get_address']) ? trim($_POST['get_address']):'';
        $get_phone = isset($_POST['get_phone']) ? trim($_POST['get_phone']):'';
        if (!$get_address) {
            $this->error("对不起，请选择地址！");
        }

        $nowtime = date("ymdHis");
        $orderid = $nowtime . rand(10, 99) . sprintf("%08d", $this->user_session['uid']);
        $order = array(
            'gift_name' => $user_level_gift_info['gift_name'],
            'gift_id' => $user_level_gift_info['gift_id'],
            'gift_img' => $user_level_gift_info['gift_img'],
            'gift_url' => $user_level_gift_info['gift_url'],
            'get_remark' => $get_remark,
            'get_name' => $get_name,
            'get_address' => $get_address,
            'get_phone' => $get_phone,
            'get_time' => time(),
            'orderid' => $orderid,
            'status' => 1
        );
        if ($adress_id>0) {
            $user_address = D('User_adress')->field(true)->where(array('adress_id' => $adress_id))->find();
            if ($user_address) {
                $order['longitude'] = $user_address['longitude'] > 0 ? $user_address['longitude'] : '';
                $order['latitude'] = $user_address['latitude'] > 0 ? $user_address['latitude'] : '';
                $order['province'] = $user_address['province'] > 0 ? $user_address['province'] : 0;
                $order['city'] = $user_address['city'] > 0 ? $user_address['city'] : 0;
                $order['area'] = $user_address['area'] > 0 ? $user_address['area'] : 0;
            }
        }
        $save = $m_user_level_gift_order->where($where)->data($order)->save();
        if ($save) {
            $this->success('领取成功!');
        } else {
            $this->error_tips("礼包领取失败，请稍后再试！");
        }
    }

    // 充值优惠
    public function courtesy_recharge(){
		// 充值优惠
    	$recharge_discount_list = M('Recharge_discount')->field(true)->where(array('status'=>1))->select();
		$this->assign('recharge_discount_list',$recharge_discount_list);
        $this->display();
    }

	// 充值卡充值
	public function recharge_card(){
		// 充值优惠
		$recharge_discount_list = M('Recharge_discount')->field(true)->where(array('status'=>1))->select();
		$this->assign('recharge_discount_list',$recharge_discount_list);
		$this->display();
	}

	// 充值卡充值提交
	public function do_recharge_card(){
		$card_password = $_POST['card_password'];
		if (!$this->config['open_recharge_card']) {
            $this->error_tips(L_("平台未开启充值卡充值"));
		}
		if (!$card_password) {
            $this->error_tips(L_("请输入秘钥"));
		}

		$where['uid'] = $this->user_session['uid'];
		$start_time = strtotime(date('Y-m-d'));
		$end_time = $start_time + 86400 -1;
		$where['add_time'] = array('between',array($start_time,$end_time));
		$error_times = M('Recharge_card_error_log')->where($where)->count();
		if ($error_times>=5) {
            $this->error_tips(L_("您已输错5次秘钥，请明天重试。"));
		}
		
		// 充值卡
		$recharge_card_coupon = M('Recharge_card_coupon')->where(array('card_password'=>$card_password,'is_del'=>0))->find();
		if (!$recharge_card_coupon) {
			// 记录次数
			M('Recharge_card_error_log')->add(array('uid'=>$this->user_session['uid'],'add_time'=>time()));
            $this->error_tips(L_("无效秘钥"));
		}
		if ($recharge_card_coupon['status']==0) {
            $this->error_tips(L_("卡券已失效"));
		}
		if ($recharge_card_coupon['use_status']==1) {
            $this->error_tips(L_("秘钥已使用过"));
		}

		// 充值卡
		$recharge_card = M('Recharge_card')->where(array('card_id'=>$recharge_card_coupon['card_id'],'is_del'=>0,'status'=>1))->find();
		if (!$recharge_card) {
            $this->error_tips(L_("卡券已失效"));
		}

		$save_data['uid'] = $this->user_session['uid'];
		$save_data['use_status'] = 1;
		$save_data['use_time'] = time();
		$res = M('Recharge_card_coupon')->where(array('card_password'=>$card_password))->save($save_data); 
		if ($res) {
			M('Recharge_card')->where(array('card_id'=>$recharge_card_coupon['card_id']))->setInc('use_num',1);
			// 增加余额
			$res = D('User')->add_money($save_data['uid'],$recharge_card_coupon['money'],L_('X1充值 增加余额，卡号:X2',['X1' => $this->config['recharge_card_alias_name'],'X2'=>$recharge_card_coupon['card_code']]));
			if (!$res['error_code']) {
            	$this->success_tips(L_("充值成功"),U('My/my_money'));
			}else{
            	$this->error_tips(L_("充值失败"));
			}
		}else{
            $this->error_tips(L_("充值失败2"));
		}
	}

	// 获取充值卡详情
	public function get_recharge_card(){
		$card_password = $_POST['card_password'];
		if (!$this->config['open_recharge_card']) {
			exit(json_encode(array('code'=>1,'msg'=>'平台未开启充值卡充值')));
		}
		
		// 充值卡
		$recharge_card_coupon = M('Recharge_card_coupon')->where(array('card_password'=>$card_password,'is_del'=>0))->find();
		if (!$recharge_card_coupon) {
			exit(json_encode(array('code'=>1,'msg'=>'无效的秘钥')));
		}
		if ($recharge_card_coupon['status']==0) {
			exit(json_encode(array('code'=>1,'msg'=>'卡券未激活')));
		}
		if ($recharge_card_coupon['use_status']==1) {
			exit(json_encode(array('code'=>1,'msg'=>'秘钥已使用过')));
		}

		// 充值卡
		$recharge_card = M('Recharge_card')->where(array('card_id'=>$recharge_card_coupon['card_id'],'is_del'=>0,'status'=>1))->find();
		if (!$recharge_card) {
			exit(json_encode(array('code'=>1,'msg'=>'卡券未激活')));
		}
		exit(json_encode(array('code'=>0,'data'=>$recharge_card_coupon)));
	}



	/**摄像机和门禁消息显示页面*/
	public function my_house_tip()
    {
        if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }
        $uid = $this->user_session['uid'];
        $d_user_tip = M('User_tip');
        $user_tip_list = $d_user_tip->where(array('uid' => $uid))->order('add_time DESC')->limit(10)->select();

        $tip_read = array();
        // 查询小区名称
        if ($user_tip_list) {
            foreach ($user_tip_list as &$item) {
                if ($item['content']) {
                    $item['content'] = unserialize($item['content']);
                }
                $tip_read[] = $item['tip_id'];
            }
        }
        $d_user_tip->where(array('tip_id' => array('in', $tip_read),'is_read'=>0))->data(array('is_read'=>1,'read_time'=>time()))->save();
        $this->assign('list',$user_tip_list);

        $this->display();
    }


    // 摄像机和门禁消息显示页面
    public function my_house_tip_ajax(){
        $listRows = 10;
        $firstRow = $_POST['page'] * $listRows;
        $uid = $this->user_session['uid'];
        $d_user_tip = M('User_tip');
        $user_tip_list = $d_user_tip->where(array('uid' => $uid))->order('add_time DESC')->limit($firstRow,$listRows)->select();
        if(empty($user_tip_list)){
            exit(json_encode(array('error'=>2,'msg'=>'暂无数据！')));
        }
        $tip_read = array();
        // 查询小区名称
        $html = '';
        if ($user_tip_list) {
            foreach ($user_tip_list as $item) {
                if ($item['content']) {
                    $content = unserialize($item['content']);
                } else {
                    $content = array();
                }
                $html .='<div class="tip_time">'.date('Y-m-d H:i:s', $item['add_time']) . '</div>';
                $html .='<li>';
                if ($item['title']) {
                    $html .= '<div class="top clr"><div class="fl">'.$item['title'].'</div></div>';
                }
                $html .='<div class="con">';
                if ($item['content'] || $item['pre_title']!='' || $item['end_title']!='') {
                    $html .='<div class="con_top">';
                    if ($item['pre_title']!='') {
                        $html .="<p>{$item['pre_title']}</p>";
                    }
                    if (!empty($content)) {
                        foreach ($content as $val) {
                            $html .="<p>{$val['title']}：<span>{$val['content']}</span></p>";
                        }
                    }
                    if ($item['end_title']!='') {
                        $html .="<p>{$item['end_title']}</p>";
                    }
                    $html .='</div>';

                    if ($item['url']) {
                        $html .="<div class='remark link_url' data-url='{$item['url']}'>";
                        if ($item['url_title']) {
                            $html .="<div>{$item['url_title']}</div>";
                        } else {
                            $html .="<div>查看详情</div>";
                        }
                    }
                }
                $html .='</div>';
                $html .='</li>';
                $tip_read[] = $item['tip_id'];
            }
        }
        $d_user_tip->where(array('tip_id' => array('in', $tip_read),'is_read'=>0))->data(array('is_read'=>1,'read_time'=>time()))->save();

        exit(json_encode(array('error'=>1,'msg'=>'返回成功','publishListHtml'=>$html)));
    }

    /**
     * 专为IM客服定制的订单列表公共页面
     */
    public function im_order_list()
    {
        C('DEFAULT_THEME', 'default');
        $type = trim($_GET['type']);
        $imTicket = trim($_GET['im_ticket']);
        if (empty($type) || empty($imTicket)) {
            $this->error('参数有误');
        }
        $parseTicket = ticket::get($imTicket, 'im', true);
        if (!$parseTicket && $parseTicket['uid'] < 1) {
            $this->error('非法请求');
        }
        $uid = intval($parseTicket['uid']);
        $limit = 150;
        if ($type == 'shop' || $type == 'mall') {
            //快店和商城
            $where = ['o.uid' => $uid, 'o.paid' => 1];
            if ($type == 'shop') {
                $where['o.order_from'] = ['NEQ', 1];
            } else {
                $where['o.order_from'] = 1;
            }
            $orderlists = M('Shop_order')->alias('o')->join(C('DB_PREFIX') . 'merchant_store AS s ON o.store_id = s.store_id')
                ->where($where)
                ->field('o.order_id,o.orderid,o.real_orderid,o.price,o.status,o.order_status,o.paid,s.name,s.logo,s.pic_info')
                ->order('o.order_id DESC')
                ->limit($limit)
                ->select();
            $store_image_class = new store_image();
            foreach ($orderlists as $k => $v) {
                if (empty($v['logo']) && !empty($v['pic_info'])) {
                    $images = $store_image_class->get_allImage_by_path($v['pic_info']);
                    $orderlists[$k]['logo'] = $images ? array_shift($images) : array();
                }
            }
        } else if ($type == 'group') {
            //团购
            $orderlists = D('Group')->get_new_order_list($uid, 0, false, $limit);
            foreach ($orderlists['order_list'] as &$order_value) {
                $order_value['merchant_logo'] = replace_file_domain($order_value['merchant_logo']);
            }
            $orderlists = isset($orderlists['order_list']) ? $orderlists['order_list'] : [];
        } else if ($type == 'foodshop') {
            //餐饮
            $orderlists = D("Foodshop_order")->alias('o')->join(C('DB_PREFIX') . 'merchant_store AS s ON o.store_id = s.store_id')
                ->where(['o.uid' => $uid, 'o.is_del' => 0, 'o.status' => ['gt', 0], 'o.is_foodshop_new' => 0])
                ->order('o.order_id DESC')
                ->field('o.*,s.logo,s.name')
                ->limit($limit)
                ->select();
        } else if ($type == 'appoint') {
            //预约
            $orderlists = D('Appoint')->wap_get_order_list($uid, 0);
        } else if ($type == 'store') {
            //到店付
            $orderlists = $this->store_order_list($uid);
        } else if ($type == 'gift') {
            $orderlists = D('Gift_order')->get_order_list($uid, -2, true, $limit);
            if (isset($orderlists['order_list'])) {
                $orderlists = $orderlists['order_list'];
            }
        } else if ($type == 'mobile_charge') {
            $orderlists = M('Mobile_recharge_order')->order('order_id DESC')->select();
        } else if ($type == 'sub_card') {
            $orderlists = D('Sub_card_order')->sub_card_list_by_uid($uid);
        } else if ($type == 'xinxi') {
            $where = "(`o`.`uid`='$uid' OR `o`.`seller_user_id`='$uid') AND `o`.`classify_userinput_id`=`g`.`id` AND `o`.`paid`='1'";
            $table = array(C('DB_PREFIX') . 'classify_userinput' => 'g', C('DB_PREFIX') . 'classify_order' => 'o');
            $orderlists = D('Classify_userinput')->field('`o`.*,`g`.`title`')->Distinct(true)->where($where)->table($table)->order('`o`.`order_time` DESC')->limit($limit)->select();
        } else if ($type == 'scenic') {
            $_POST = [
                'uid' => $uid,
                'page' => $limit,
                'paid' => 2,
                'pc' => true
            ];
            $rs = (new Scenic_userAction())->ticket_order_list_json();
            $orderlists = isset($rs['user_order']) ? $rs['user_order'] : [];
        }
        $this->assign('orderlists', $orderlists);
        $this->assign('type', $type);
        $this->display();
    }

    //直播/短视频佣金列表
    public function live_money_list(){
    	$now_live_money = M("User_money_list")->where(['uid'=>$this->now_user['uid'],'is_live'=>1])->sum("money");
    	$now_live_money = $now_live_money ? : 0;
    	$this->assign("now_live_money",$now_live_money);
    	$this->display();
    }

    public function live_money_list_json(){
    	$page	=	$_POST['page'];
		$transaction	=	D('User_money_list')->get_list($this->now_user['uid'],$page,20, 1);
		$transaction['count'] = count($transaction['money_list']);
		foreach($transaction['money_list'] as $k=>$v){
			$transaction['money_list'][$k]['time_s']	=	date('Y/m/d H:i',$v['time']);
			$transaction['money_list'][$k]['money']	=	floatval($transaction['money_list'][$k]['money']	);
		}
		echo json_encode($transaction);
	}
    // 收藏店铺 商品
    public function do_collect(){
    	$data['type'] = $_POST['type']; // 收藏类型
    	$data['uid'] = $this->user_session['uid']; // 用户id

    	if (!$this->user_session['uid']) {
        		exit(json_encode(array('error'=>1,'msg'=>'请先登录')));
    	}
    	switch ($_POST['type']) {
    		case 'foodshop':
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
        		exit(json_encode(array('error'=>3,'msg'=>L_('参数收藏类型错误'))));
    			break;
    	}

		$info = D('Merchant_user_relation')->where($data)->find();

		// 取消收藏
        if($info){
			if($info = D('Merchant_user_relation')->where($data)->delete()){
        		exit(json_encode(array('error'=>0,'msg'=>L_('取消收藏成功'))));
			}else{
        		exit(json_encode(array('error'=>3,'msg'=>L_('取消收藏失败！请重试'))));
			}
        }
        if($_POST['type']=="mallgoods"){
            $data1['goods_id'] = $_POST['collect_id'];
            $goods_info=M('Shop_goods')->where($data1)->find();
            $data['store_id']=$goods_info['store_id'];
        }

        // 收藏
        $data['dateline'] = time();
        $res = D('Merchant_user_relation')->data($data)->add();
        if($res){
            exit(json_encode(array('error'=>0,'msg'=>L_('收藏成功'))));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>L_('收藏失败请重试'))));
        }
    }

    /**
     * 快店评价图片上传
     * @author 张涛
     * @date 2020/4/2
     */
    public function uploadPic()
    {
        $picType = isset($_POST['picType']) ? $_POST['picType'] : 'reply';
        if ($_FILES['file']['error'] != 4) {
            $width = '900,450';
            $height = '500,250';
            $param = [
                'size' => 20,
                'thumb' => true,
                'imageClassPath' => 'ORG.Util.Image',
                'thumbPrefix' => 'm_,s_',
                'thumbMaxWidth' => '900,450',
                'thumbMaxHeight' => '500,250',
                'thumbRemoveOrigin' => false
            ];
            $image = D('Image')->handle($this->user_session['uid'], $picType, 1, $param);
            if ($image['error']) {
                $this->ajaxReturn(['errorCode' => 1, 'errorMsg' => $image['msg']]);
            } else {
                $title = $image['title']['file'];
                $url = (new shop_order_reply_image())->get_image_by_path($title, 's');
                $this->ajaxReturn(['errorCode' => 0, 'errorMsg' => 'success', 'result' => array('url' => $url, 'title' => $image['url']['file'])]);
            }
        } else {
            $this->ajaxReturn(['errorCode' => 1, 'errorMsg' => L_('没有选择图片')]);
        }
    }
	
	public function ajaxGetWxImg(){
		$picType = isset($_POST['picType']) ? $_POST['picType'] : 'group';
		
		$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $up_dir = './upload/'.$picType.'/'.$rand_num.'/';
		if(!file_exists($up_dir)){
			mkdir($up_dir, 0777, true);
		}
        $up_name = $up_dir . uniqid();
		
		fdump($up_name, 'up_dir');
		
		//微信授权获得access_token
		$access_token_array = D('Access_token_expires')->get_access_token();
		if ($access_token_array['errcode']) {
			$this->error('获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
		}
		$access_token = $access_token_array['access_token'];
		
		import('ORG.Net.Http');
		$http = new Http();
		$url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $_POST['serverId'];
		
		fdump($url, 'up_dir', 1);
		
		$result = $http->curlDownload($url, $up_name . '.tmp');
		
		$imageInfo = getimagesize($up_name . '.tmp');
		
		switch($imageInfo['mime']){
			case 'image/png':
				$imageInfo['extension'] = 'png';
				break;
			case 'image/gif':
				$imageInfo['extension'] = 'png';
				break;
			default:
				$imageInfo['extension'] = 'jpg';
		}
		
		rename($up_name . '.tmp', $up_name . '.' . $imageInfo['extension']);
		
		$up_name = $up_name . '.' . $imageInfo['extension'];

		//判断是否需要上传至云存储
		$file_handle = new file_handle();
		$file_handle->upload($up_name);
		
		fdump(['url' => replace_file_domain($up_name), 'title' => ltrim($up_name, '.')], 'up_dir', 1);
		
		$this->success(['url' => replace_file_domain($up_name), 'title' => ltrim($up_name, '.')]);
	}

    /**
     * 升级推广商等级
     * @author: 张涛
     * @date: 2021/01/14
     */
    public function upgradeMerSpread()
    {
        $uid = $this->user_session['uid'];
        if (IS_POST) {
            $type = I('post.type', 1, 'intval');
            try {
                if ($type < 1) {
                    throw new Exception('请选择升级类型');
                }
                if ($uid < 1) {
                    throw new Exception('未登录');
                }
                $setting = D('Merchant_spread_setting')->where(['type' => $type])->find();
                if (empty($setting)) {
                    throw new Exception('推广商未配置');
                }
                if (M('Merchant_spread_apply')->where(['uid' => $uid, 'type' => $type, 'handle_status' => 0, 'is_del' => 0])->find()) {
                    throw new Exception('您的推广商审核中，请勿重复申请');
                }
                if (M('Merchant_spread_apply')->where(['uid' => $uid, 'type' => $type, 'handle_status' => 1, 'is_del' => 0])->find()) {
                    throw new Exception('您已经是推广商，请勿重复申请');
                }
                $order = [
                    'order_sn' => build_real_orderid($uid),
                    'paid' => 0,
                    'uid' => $uid,
                    'type' => $type,
                    'price' => $setting['spread_price'],
                    'form_data' => $_POST['form_data'] ? serialize($_POST['form_data']) : '',
                    'create_time' => time()
                ];
                $orderId = M('Merchant_spread_order')->add($order);

                $payUrl = C('site_url') . '/packapp/plat/pages/shop_new/confirmOrder/pay?order_id=' . $orderId . '&type=spread';
                $rs = ['error_no' => 0, 'error_msg' => '', 'retval' => ['order_id' => $orderId, 'type' => 'spread', 'pay_url' => $payUrl]];
            } catch (\Exception $e) {
                $rs = ['error_no' => 1, 'error_msg' => $e->getMessage()];
            }
            $this->ajaxReturn($rs);
        } else {
            $comeFrom = I('get.come_from', '', 'trim');
            $type = I('get.type', 0, 'intval');
            $settingMod = D('Merchant_spread_setting');
            $fieldsMod = D('Merchant_spread_setting_fields');
            $spread = M('Merchant_spread_user')->where(['uid' => $uid])->find();
            if ($comeFrom == 'qrcode') {
                if ($spread['is_city_spread']) {
                    $this->redirect('My/citySpread');
                }
                if ($spread['is_vip_spread']) {
                    $this->redirect('My/vipSpread');
                }
            }

            $openTypes = [];
            if (C('config.open_vip_merchant_spread') == 1) {
                $openTypes[] = 1;
            }
            if (C('config.open_city_merchant_spread') == 1) {
                $openTypes[] = 2;
            }
            if ($type > 0) {
                $openTypes = [$type];
            }
            if ($openTypes) {
                $settings = $settingMod->field('*,spread_price*spread_percent/100 AS rate_money')->where(['type' => ['IN', $openTypes]])->select();
            } else {
                $settings = [];
			}
            $maxRateMoney = getFormatNumber(max(array_column($settings,'rate_money')));
			$spreadTypes = [];
            foreach ($settings as $k => $v) {
                $settings[$k]['spread_money'] = $maxRateMoney;
                $settings[$k]['name'] = $v['type'] == 1 ? 'vip推广商' : '城市推广商';
				$fields = $fieldsMod->where(['setting_id' => $v['id']])->order('sort DESC,id ASC')->select();
				if($v['type'] == 1){
					$spreadTypes[]=[
						'value'=>1,
						'text'=>'vip推广商',
						'commission'=> $settings[$k]['spread_money'],
                        'price' => getFormatNumber($settings[$k]['spread_price'])
					];
				}else if($v['type'] == 2){
					$spreadTypes[]=[
						'value'=>2,
						'text'=>'城市推广商',
						'commission'=> $settings[$k]['spread_money'],
                        'price' => getFormatNumber($settings[$k]['spread_price'])
					];
				}
                $arr = [];
                foreach ($fields as $f) {
                    if ($f['type'] == 0) {
                        //单行文本
                        $arr[] = [
                            'name' => $f['name'],
                            'type' => 'input',
                            'label' => $f['name'],
                            'required' => boolval($f['iswrite']),
                            'value' => ''
                        ];
                    } else if ($f['type'] == 1) {
                        //多行文本
                        $arr[] = [
                            'name' => $f['name'],
                            'type' => 'textarea',
                            'label' => $f['name'],
                            'required' => boolval($f['iswrite']),
                            'value' => ''
                        ];
                    } else if ($f['type'] == 3) {
                        //下拉选择框
                        $useField = unserialize($f['use_field']);
                        if ($useField) {
                            $useField = array_map(function ($rs) {
                                return ['value' => $rs, 'text' => $rs];
                            }, $useField);
                        } else {
                            $useField = [];
                        }
                        $arr[] = [
                            'name' => $f['name'],
                            'type' => 'select',
                            'label' => $f['name'],
                            'required' => boolval($f['iswrite']),
                            'value' => '',
                            'list' => $useField
                        ];
                    } else if ($f['type'] == 5) {
                        //身份证正反面
                        $arr[] = [
                            'name' => $f['name'],
                            'type' => 'idcard_image',
                            'label' => $f['name'],
                            'required' => boolval($f['iswrite']),
                            'front' => '',
                            'back' => ''
                        ];
                    } else if ($f['type'] == 6) {
                        //身份证号
                        $arr[] = [
                            'name' => $f['name'],
                            'type' => 'idcard',
                            'label' => $f['name'],
                            'required' => boolval($f['iswrite']),
                            'value' => ''
                        ];
                    } else if ($f['type'] == 7) {
                        //日期格式
                        $arr[] = [
                            'name' => $f['name'],
                            'type' => 'date',
                            'label' => $f['name'],
                            'required' => boolval($f['iswrite']),
                            'value' => ''
                        ];
                    } else if ($f['type'] == 9) {
                        //上传图片
                        $arr[] = [
                            'name' => $f['name'],
                            'type' => 'image',
                            'label' => $f['name'],
                            'required' => boolval($f['iswrite']),
                            'max_count' => 1,
                            'value' => []
                        ];
                    }
                }
                $settings[$k]['fields'] = $arr;
			}
			$this->assign('spreadTypes',$spreadTypes);
            $this->assign('settings',$settings);
            $this->display();
        }
    }

    public function ajaxGetForm(){
		$type = I('get.type',0,'intval');
        $settingMod = D('Merchant_spread_setting');
        $fieldsMod = D('Merchant_spread_setting_fields');
       
		$settings = $settingMod->where(['type' => $type])->select();
        foreach ($settings as $k => $v) {
            $settings[$k]['spread_money'] = getFormatNumber($v['spread_price'] * $v['spread_percent']/100);
            $settings[$k]['name'] = $v['type'] == 1 ? 'vip推广商' : '城市推广商';
            $settings[$k]['price'] = getFormatNumber($v['spread_price']);
            $fields = $fieldsMod->where(['setting_id' => $v['id']])->order('sort DESC,id ASC')->select();
            $arr = [];
            foreach ($fields as $f) {
                if ($f['type'] == 0) {
                    //单行文本
                    $arr[] = [
                        'name' => $f['name'],
                        'type' => 'input',
                        'label' => $f['name'],
                        'required' => boolval($f['iswrite']),
                        'value' => ''
                    ];
                } else if ($f['type'] == 1) {
                    //多行文本
                    $arr[] = [
                        'name' => $f['name'],
                        'type' => 'textarea',
                        'label' => $f['name'],
                        'required' => boolval($f['iswrite']),
                        'value' => ''
                    ];
                } else if ($f['type'] == 3) {
                    //下拉选择框
                    $useField = unserialize($f['use_field']);
                    if ($useField) {
                        $useField = array_map(function ($rs) {
                            return ['value' => $rs, 'text' => $rs];
                        }, $useField);
                    } else {
                        $useField = [];
                    }
                    $arr[] = [
                        'name' => $f['name'],
                        'type' => 'select',
                        'label' => $f['name'],
                        'required' => boolval($f['iswrite']),
                        'value' => '',
                        'list' => $useField
                    ];
                } else if ($f['type'] == 5) {
                    //身份证正反面
                    $arr[] = [
                        'name' => $f['name'],
                        'type' => 'idcard_image',
                        'label' => $f['name'],
                        'required' => boolval($f['iswrite']),
                        'front' => '',
                        'back' => ''
                    ];
                } else if ($f['type'] == 6) {
                    //身份证号
                    $arr[] = [
                        'name' => $f['name'],
                        'type' => 'idcard',
                        'label' => $f['name'],
                        'required' => boolval($f['iswrite']),
                        'value' => ''
                    ];
                } else if ($f['type'] == 7) {
                    //日期格式
                    $arr[] = [
                        'name' => $f['name'],
                        'type' => 'date',
                        'label' => $f['name'],
                        'required' => boolval($f['iswrite']),
                        'value' => ''
                    ];
                } else if ($f['type'] == 9) {
                    //上传图片
                    $arr[] = [
                        'name' => $f['name'],
                        'type' => 'image',
                        'label' => $f['name'],
                        'required' => boolval($f['iswrite']),
                        'max_count' => 1,
                        'value' => []
                    ];
                }
            }
            $settings[$k]['fields'] = $arr;
        }
        $this->ajaxReturn(['settings'=>$settings[0]??[]]);
    }

    public function inviteSpreadUpdate()
    {
        $uid = $this->user_session['uid'];
        $spreadUrl = U('My/upgradeMerSpread', ['come_from'=>'qrcode'], true, false, true);
        $openTypes = [];
        C('config.open_vip_merchant_spread') == 1 && $openTypes[] = 1;
        C('config.open_city_merchant_spread') == 1 && $openTypes[] = 2;
        if (empty($openTypes)) {
            $settings = [];
        } else {
            $settings = M('Merchant_spread_setting')->where(['type' => ['IN', $openTypes]])->order('type ASC')->select();
        }
        $spread = M('Merchant_spread_user')->where(['uid' => $uid])->find();
        $this->assign('settings', $settings);
        $this->assign('spreadUrl', $spreadUrl);
        $this->assign('uid', $uid);
        $this->assign('userinfo', $this->user_session);
        $this->assign('spread',$spread);
        $this->display();
    }

    public function vipSpread()
    {
        $uid = $this->now_user['uid'];
        $spread = M('Merchant_spread_user')->where(['uid' => $uid])->find();
        $spread['is_apply_city_spread'] = (new Merchant_spread_applyModel())->isApplyingCitySpread($uid);
        if ($spread['is_vip_spread'] != 1) {
            $this->error_tips('您还不是vip推广商');
        }

        $inviteNum = M('Merchant_spread_invite_record')->where(['uid'=>$uid])->count();
        $this->assign('inviteNum',$inviteNum);
        $this->assign('spread',$spread);
        $this->display();
    }

    public function citySpread()
    {
        $uid = $this->now_user['uid'];
        $spread = M('Merchant_spread_user')->where(['uid' => $uid])->find();
        if ($spread['is_city_spread'] != 1) {
            $this->error_tips('您还不是城市推广商');
        }
        $inviteNum = M('Merchant_spread_invite_record')->where(['uid'=>$uid])->count();
        $inviteMerchantNum =M('Merchant_spread_invite_merchant')->where(['uid'=>$uid])->count();
        $this->assign('inviteNum',$inviteNum);
        $this->assign('inviteMerchantNum',$inviteMerchantNum);
        $this->assign('spread',$spread);
        $this->display();
    }

    public function spreadLists()
    {
        if (IS_POST) {
            $uid = $this->now_user['uid'];
            $page = I('post.page', 1, 'intval');
            $type = I('post.type', 0, 'intval');
            $mod = M('Merchant_spread_invite_record');
            $lists = $mod->alias('r')->join(C('DB_PREFIX') . 'user u ON r.invite_uid=u.uid')
                ->where(['r.uid' => $uid])
                ->field('r.*,u.nickname,u.avatar')
                ->limit(($page - 1) * 15, 15)
                ->order('r.id DESC')
                ->select();
            foreach ($lists as $k => $v) {
                $lists[$k]['time_s'] = date('Y/m/d H:i', $v['create_time']);
            }
            $this->ajaxReturn(['lists' => $lists]);
        } else {
            $this->display();
        }
    }

    public function inviteMerchantLists()
    {
        if (IS_POST) {
            $uid = $this->now_user['uid'];
            $page = I('post.page', 1, 'intval');
            $lists = M('Merchant_spread_invite_merchant')->alias('r')->join(C('DB_PREFIX') . 'merchant m ON m.mer_id=r.mer_id')
                ->where(['r.uid' => $uid])
                ->field('r.*,m.name')
                ->limit(($page - 1) * 15, 15)
                ->order('r.id DESC')
                ->select();
            foreach ($lists as $k => $v) {
                $lists[$k]['time_s'] = date('Y/m/d H:i', $v['create_time']);
            }
            $this->ajaxReturn(['lists' => $lists]);
        } else {
            $this->display();
        }
    }

    public function myCityInviteCode()
    {
        $uid = $this->user_session['uid'];
        $mod = M('Merchant_spread_user');
        $user = $mod->where(['uid' => $uid, 'is_city_spread' => 1])->find();
        if (empty($user)) {
            $this->error('您还不是城市推广商');
        } else {
            if (empty($user['city_invite_code'])) {
                $user['city_invite_code'] = createRandomStr(9);
                $mod->where(['uid' => $uid])->save(['city_invite_code' => $user['city_invite_code']]);
            }
        }
        $this->assign('cityInviteCode', $user['city_invite_code']);
        $this->display();
    }

    public function commission()
    {
        if (IS_POST) {
            $uid = $this->now_user['uid'];
            $page = I('post.page', 1, 'intval');
            $lists = M('Merchant_spread_commission')->alias('c')->join(C('DB_PREFIX') . 'merchant m ON m.mer_id=c.mer_id')
                ->where(['c.uid' => $uid])
                ->field('c.*,m.name')
                ->limit(($page - 1) * 15, 15)
                ->order('c.id DESC')
                ->select();
            foreach ($lists as $k => $v) {
                $lists[$k]['time_s'] = date('Y/m/d H:i', $v['create_time']);
                $lists[$k]['consume_money'] = getFormatNumber($v['consume_money']);
                $lists[$k]['money'] = getFormatNumber($v['money']);
            }
            $this->ajaxReturn(['lists' => $lists]);
        } else {
            $this->display();
        }
    }

	public function scoreMall(){
		$merId = I('get.mer_id',0,'intval');
		$uid = $this->user_session['uid'];

        $userCard = M('Card_userlist')->where(['uid'=>$uid,'mer_id'=> $merId])->find();
		if(empty($userCard)){
			$this->error('您还未领取该商家会员卡');
		}
		$url = $this->config['site_url'] . D('House_village')->base_url .'pages/my/pointExchange/pointExchange?card_id='.intval($userCard['id']);
		redirect($url);
	}
}
?>