<?php
/*
 * 店员中心
 */

class StoreAction extends BaseAction{

	protected $store;

	protected $now_merchant;

	protected $merchant_status = [];

	public function _initialize(){
		$this->merchant_status = array(0=>L_('关闭'), 1=>L_('正常'), 2=>L_('待审核'),3=>L_('欠费'),4=>L_('删除'));
		parent::_initialize();

		/**** 新框架登录20200509	start****/
		$v2o_ticket = $_COOKIE['storestaff_access_token'] ? $_COOKIE['storestaff_access_token'] : Token::getToken(); 
		if($v2o_ticket){
			import("@.ORG.Token");
			$ticket = Token::checkToken($v2o_ticket);
			if ($ticket && $ticket['memberId']) {
				$user_id = $ticket['memberId'];
				$login_res = D('Merchant_store_staff')->v20Login($user_id);
				if($login_res){
					$this->staff_session = session('staff');
					$this->assign('staff_session', $this->staff_session);
				}
			}
		}
		/**** 新框架登录	end ****/

		if($_GET['web_session']){
			$database_store_staff = D('Merchant_store_staff');
			$condition_store_staff['web_session'] = $_GET['web_session'];
			$now_staff = $database_store_staff->field(true)->where($condition_store_staff)->find();
			session('staff',$now_staff);
			session('merchant',null);
			$this->staff_session = session('staff');
			$this->assign('staff_session', $this->staff_session);
		}

		if(ACTION_NAME != 'login' && ACTION_NAME != 'cashierBack' && !$_POST['is_mer']){
			if(empty($this->staff_session)){
				redirect(U('Store/login'));
				exit();
			}else{
				$staff_type=array(0=>L_('店小二'),1=>L_('核销员'),2=>L_('店长'));
				$this->staff_session['type_name'] = $staff_type[$this->staff_session['type']];
				$this->assign('staff_session',$this->staff_session);
				$database_merchant_store = D('Merchant_store');
				$condition_merchant_store['store_id'] = $this->staff_session['store_id'];
				$this->store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
				if(empty($this->store)){
					$this->error(L_('店铺不存在！'));
				}
				//设置时区
	            if(!empty($this->store['city_id']) || !empty($this->store['area_id'])){
	                $area_db = M("Area");
	                if(!empty($this->store['city_id'])){
	                    $city_info = $area_db->where('area_id='.$this->store['city_id'])->field('timezone')->find();
	                }
	                if(!empty($this->store['area_id'])){
	                    $area_info = $area_db->where('area_id='.$this->store['area_id'])->field('timezone')->find();
	                }
	                //area的时区优先于城市的时区
	                if(!empty($area_info['timezone']) || !empty($city_info['timezone'])){
	                    date_default_timezone_set($area_info['timezone'] ? $area_info['timezone'] : $city_info['timezone']);
						D('')->query("SET time_zone='+" . get_timeout_num_by_name() . "'");
	                }
	            }
				$have_mall1=M('Merchant_store_shop')->where(array('store_id'=>$this->staff_session['store_id']))->getField('store_id');
				$open_from_shop=M('Merchant_store_foodshop')->where(array('store_id'=>$this->staff_session['store_id']))->getField('open_from_shop');
	            //是否开启商城应该从这个状态判断
                $have_mall = M('Merchant_store')->field(true)->where(array('store_id' => $this->staff_session['store_id']))->getField('have_mall');
                if($have_mall1 && $have_mall){
                   $have_mall=1;
                }
                $this->store['have_mall']=$have_mall;
				$this->store['open_from_shop']=$open_from_shop;

				$this->assign('store',$this->store);
			}
		}
		// if($this->config['open_score_fenrun']){
			$this->now_merchant = D('Merchant')->get_info($this->store['mer_id']);
			if($this->now_merchant['score_get_percent']>=0){
				$this->config['score_get'] = $this->now_merchant['score_get_percent']/100;
			}
			if(isset($this->now_merchant) && $this->now_merchant['is_open_scenic']==0){
				$this->staff_session['is_verify_ticket']=0;
				$this->assign('staff_session',$this->staff_session);
			}
		// }

		$this->assign('mer_id',$this->store['mer_id']);
	}

	public function check_merchant(){

		if($this->now_merchant['status']!=1){
			if($this->now_merchant['merchant_end_time']>0 && $this->now_merchant['merchant_end_time']<time()){
				exit(json_encode(array('error'=>'1','msg'=>L_('商家已过期，暂不可使用该功能！'))));
			}
			exit(json_encode(array('error'=>'1','msg'=>L_('商家已X1，暂不可使用该功能！',$this->merchant_status[$this->now_merchant['status']]))));
		}else{
			 // 开启店员权限功能 店小二
            if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) { // 店小二 无收银权限
                if ($_POST['type'] == 'shop') {
					exit(json_encode(array('error'=>'1','msg'=>L_('您当前身份为【店小二】，暂无权限，请联系管理员！'))));
                } elseif ($_POST['type'] == 'foodshop') {
					exit(json_encode(array('error'=>'1','msg'=>L_('您当前身份为【店小二】，暂无结账权限，请联系管理员！'))));
                }elseif ($_POST['type'] == 'store') {
					exit(json_encode(array('error'=>'1','msg'=>L_('您当前身份为【店小二】，暂无创建订单权限，请联系管理员！'))));
                }else{
					exit(json_encode(array('error'=>'0','msg'=>'')));
                }
            }else{
				exit(json_encode(array('error'=>'0','msg'=>'')));
            }

		}
	}

	public function login(){
		if(IS_POST){
			if(md5($_POST['verify']) != $_SESSION['merchant_store_login_verify']){
				exit(json_encode(array('error'=>'1','msg'=>L_('验证码不正确！'),'dom_id'=>'verify')));
			}

			$database_store_staff = D('Merchant_store_staff');
			$condition_store_staff['username'] = $_POST['account'];
			$now_staff = $database_store_staff->field(true)->where($condition_store_staff)->find();

			if(empty($now_staff)){
				exit(json_encode(array('error'=>'2','msg'=>L_('帐号不存在！'),'dom_id'=>'account')));
			}
			$pwd = md5($_POST['pwd']);
			if($pwd != $now_staff['password']){
				exit(json_encode(array('error'=>'3','msg'=>L_('密码错误！'),'dom_id'=>'pwd')));
			}

			$store = M('Merchant_store')->field(true)->where(array('store_id' => $now_staff['store_id']))->find();
			if(empty($store) || $store['status'] != 1){
				exit(json_encode(array('error'=>'2','msg'=>L_('店铺状态不正常'),'dom_id'=>'account')));
			}
			$database_merchant = D('Merchant');
			$condition_merchant['mer_id'] = $store['mer_id'];
			$now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
			if($now_merchant['status'] == 4){
				exit(json_encode(array('error'=>'2','msg'=>L_('商户已被删除'),'dom_id'=>'account')));
			}
			if($now_merchant['status'] != 1){
				exit(json_encode(array('error'=>'2','msg'=>L_('商户状态不正常'),'dom_id'=>'account')));
			}

			$data_store_staff['id'] = $now_staff['id'];
			$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];

			if(stripos($_SERVER['HTTP_USER_AGENT'],'pigcmsPackSoftApp') !== false || stripos($_SERVER['HTTP_USER_AGENT'],'pigcmsPackSoftWebviewApp') !== false){
				$data_store_staff['web_session'] = hash('sha256',generate_password(32).'_'.$now_staff['id'],false);
				setcookie('storestaff_web_session',$data_store_staff['web_session'],$_SERVER['REQUEST_TIME']+2764800,'/');
			}

			if($database_store_staff->data($data_store_staff)->save()){
				session('staff',$now_staff);
				session('merchant',null);
				session('is_login_from',1);

				if ($now_staff['type'] == 2) {
    				if(!empty($now_merchant['last_ip'])){
    				    import('ORG.Net.IpLocation');
    				    $IpLocation = new IpLocation();
    				    $last_location = $IpLocation->getlocation($now_merchant['last_ip']);
    				    $now_merchant['last']['country'] = iconv('GBK','UTF-8',$last_location['country']);
    				    $now_merchant['last']['area'] = iconv('GBK','UTF-8',$last_location['area']);
    				}
    				$now_merchant['store_id'] = $now_staff['store_id'];
    				// 判断是否已登录商家
    				if (session('merchant')) {
    					$merchant_login = 1;
    				}
    				session('merchant', $now_merchant);
				}
				exit(json_encode(array('error'=>'0','msg'=>'登录成功,现在跳转~','dom_id'=>'account','merchant_login'=>$merchant_login)));
			}else{
				exit(json_encode(array('error'=>'6','msg'=>'登录信息保存失败,请重试！','dom_id'=>'account')));
			}
		}else{
			//cookie自动登录
			if($_COOKIE['storestaff_web_session']){
				$database_store_staff = D('Merchant_store_staff');
				$condition_store_staff['web_session'] = $_COOKIE['storestaff_web_session'];
				$now_staff = $database_store_staff->field(true)->where($condition_store_staff)->find();
				if($now_staff){
					$data_store_staff['id'] = $now_staff['id'];
					$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];

					if(stripos($_SERVER['HTTP_USER_AGENT'],'pigcmsPackSoftApp') !== false || stripos($_SERVER['HTTP_USER_AGENT'],'pigcmsPackSoftWebviewApp') !== false){
						$data_store_staff['web_session'] = hash('sha256',generate_password(32).'_'.$now_staff['id'],false);
						setcookie('storestaff_web_session',$data_store_staff['web_session'],$_SERVER['REQUEST_TIME']+2764800,'/');
					}

					if($database_store_staff->data($data_store_staff)->save()){
						session('staff',$now_staff);
						session('merchant',null);
						session('is_login_from',1);
						if ($now_staff['type'] == 2) {
							$store = M('Merchant_store')->field(true)->where(array('store_id' => $now_staff['store_id']))->find();
							$database_merchant = D('Merchant');
							$condition_merchant['mer_id'] = $store['mer_id'];
							$now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
							$now_merchant['store_id'] = $now_staff['store_id'];
							session('merchant', $now_merchant);
						}
						redirect(U('Store/index'));
					}
				}
				setcookie('storestaff_web_session','',$_SERVER['REQUEST_TIME']-1,'/');
			}

        	redirect($this->config['site_url'] . '/v20/public/platform/#/usernew/storestaff/login');
			$this->display();
		}
	}

	public function index(){
        redirect($this->config['site_url'] . '/v20/public/platform/#/storestaff/storestaff.index/index');
        return false;
		//语言列表
		$lang = D('Lang');
		$this->assign('lang_list',$lang->service_lang);
		$this->config['system_lang'] = cookie('system_lang') ? cookie('system_lang') : 'chinese';
		
		//判断预约权限 
		if($this->config['appoint_page_row']){
			$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
			$merchant_menus = $now_merchant['menus'];
			$merchant = explode(',', $merchant_menus);
			if ($merchant && $merchant[0] && !in_array(60,$merchant)) {
				$this->store['have_appoint'] = false; // 预约
			} else {
                $this->store['have_appoint'] = true; // 预约
            }
            $this->assign('store',$this->store);
		}
		
		$this->display();
	}

    //修改密码
    public function changePwd(){
        if (isset($_POST['old_password'])) {
            if (md5($_POST['old_password'])!=$this->staff_session['password']) {
				exit(json_encode(array('error'=>'1','msg'=>'原密码输入错误')));
            }
        }

        if(md5($_POST['password']) == $this->staff_session['password']){
			exit(json_encode(array('error'=>'1','msg'=>'不能与原密码一致')));
		}

        if(strlen($_POST['password']) < 6){
			exit(json_encode(array('error'=>'1','msg'=>'请输入6位或以上的密码')));
		}

        $condition_staff['id'] = $this->staff_session['id'];
        $data_staff['password'] = md5($_POST['password']);
        if(M('Merchant_store_staff')->where($condition_staff)->data($data_staff)->save()){
        	$this->staff_session['password'] = $data_staff['password'];
			session('staff',$this->staff_session);
			session('merchant',null);
			exit(json_encode(array('error'=>'0','msg'=>'修改成功')));
        }else{
			exit(json_encode(array('error'=>'1','msg'=>'修改失败')));
        }
    }

	public function coupon_list(){
		$store_id = $this->store['store_id'];
		$condition_where = "`ear`.`uid`=`u`.`uid` AND `ear`.`activity_list_id`=`eal`.`pigcms_id` AND `ecr`.`record_id`=`ear`.`pigcms_id` AND `ecr`.`store_id`='$store_id'";

		$condition_table = array(C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_coupon_record'=>'ecr',C('DB_PREFIX').'user'=>'u');
		$order_list = D('')->field('`eal`.`name`,`ecr`.*,`ear`.`time`,`u`.`uid`,`u`.`nickname`,`u`.`phone`')->where($condition_where)->table($condition_table)->order('`ecr`.`check_time` DESC')->select();
		$this->assign('order_list',$order_list);
		$this->display();
	}

	public function coupon_find(){
		if(IS_POST){
			$mer_id = $this->store['mer_id'];
			$condition_where = "`ear`.`uid`=`u`.`uid` AND `ear`.`activity_list_id`=`eal`.`pigcms_id` AND `ecr`.`record_id`=`ear`.`pigcms_id` AND `eal`.`mer_id`='$mer_id'";
			$find_value = $_POST['find_value'];
			$store_id = $this->store['store_id'];
			if($_POST['find_type'] == 1 && strlen($find_value) == 16){
				$condition_where .= " AND `ecr`.`number`='$find_value'";
			}else{
				$condition_where .= " AND `ecr`.`store_id`='$store_id'";
				if($_POST['find_type'] == 1){
					$condition_where .= " AND `ecr`.`number` like '$find_value%'";
				}else if($_POST['find_type'] == 2){
					$condition_where .= " AND `eal`.`pigcms_id` like '$find_value%'";
				}else if($_POST['find_type'] == 3){
					$condition_where .= " AND `u`.`uid`='$find_value'";
				}else if($_POST['find_type'] == 4){
					$condition_where .= " AND `u`.`nickname`='$find_value'";
				}else if($_POST['find_type'] == 5){
					$condition_where .= " AND `u`.`phone` like '$find_value%'";
				}
			}
			$condition_table = array(C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_coupon_record'=>'ecr',C('DB_PREFIX').'user'=>'u');
			$order_list = D('')->field('`eal`.`name`,`ecr`.*,`ear`.`time`,`u`.`uid`,`u`.`nickname`,`u`.`phone`,`ecr`.`check_time`')->where($condition_where)->table($condition_table)->order('`ecr`.`check_time` DESC')->select();
			if($order_list){
				foreach($order_list as $key=>$value){
					$order_list[$key]['time_txt'] = date('Y-m-d H:i:s',$value['time']);
					$order_list[$key]['check_time_txt'] = date('Y-m-d H:i:s',$value['check_time']);
				}
			}
			$return['list'] = $order_list;
			$return['row_count'] = count($order_list);
			echo json_encode($return);
		}else{
			$this->display();
		}
	}

	public function coupon_verify(){
		$mer_id = $this->store['mer_id'];
		$condition_table = array(C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_coupon_record'=>'ecr');
		$condition_where = "`ear`.`activity_list_id`=`eal`.`pigcms_id` AND `ecr`.`record_id`=`ear`.`pigcms_id` AND `eal`.`mer_id`='$mer_id' AND `ecr`.`pigcms_id`='{$_GET['id']}'";
		$now_order = D('')->field('`ecr`.`pigcms_id`,`eal`.`pigcms_id` as id,`eal`.`money`,`eal`.`name`')->where($condition_where)->table($condition_table)->find();
		if(!empty($now_order)){
			if(D('Extension_coupon_record')->where(array('pigcms_id'=>$now_order['pigcms_id']))->data(array('check_time'=>time(),'store_id'=>$this->store['store_id'],'last_staff'=>$this->staff_session['name']))->save()){
				//验证增加商家余额
				if($now_order['money']>0){
					$now_order['order_type'] ='coupon';
					$now_order['order_id'] =$now_order['pigcms_id'];
					$now_order['mer_id']  = $mer_id;
					//D('Merchant_money_list')->add_money($this->store['mer_id'],L_('用户购买').$now_order['name'].L_('记入收入'),$now_order);
					$now_order['desc']=L_('用户购买X记入收入',$now_order['name']);
					D('SystemBill')->bill_method(0,$now_order);
					//商家推广分佣
					$now_user = M('User')->where(array('uid'=>$now_order['uid']))->find();
					D('Merchant_spread')->add_spread_list($now_order,$now_user,$now_order['order_type'],$now_user['nickname'].L_('用户购买平台活动商品获得佣金'));
				}
				$this->success(L_('验证成功！'));
			}else{
				$this->error(L_('验证失败！请重试。'));
			}
		}else{
			$this->error(L_('当前订单不存在！'));
		}
	}

	/* 团购相关 */
	protected function check_group(){
		if(empty($this->store['have_group'])){
			$this->error(L_('您访问的店铺没有开通X1功能！',$this->config['group_alias_name']));
		}
	}

	//团购列表
	public function group_list(){
		$this->check_group();
		$store_id = $this->store['store_id'];

		$condition_where = "`o`.`uid`=`u`.`uid` AND `o`.`group_id`=`g`.`group_id` AND `o`.`store_id`='$store_id'";

		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$condition_where .= " AND `o`.`real_orderid`='" . htmlspecialchars($_GET['keyword'])."'";
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$_GET['keyword']))->find();
				$condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($tmp_result['order_id'])."'";
			} elseif ($_GET['searchtype'] == 'name') {
				$condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
			} elseif ($_GET['searchtype'] == 'phone') {
				$condition_where .= " AND `u`.`phone`='" . htmlspecialchars($_GET['keyword']) . "'";
			} elseif ($_GET['searchtype'] == 's_name') {
				$condition_where .= " AND `g`.`s_name` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
			}elseif ($_GET['searchtype'] == 'third_id') {
				$condition_where .= " AND `o`.`third_id` =".$_GET['keyword'];
			}
		}


		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;

		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';

		if ($status != -1) {
			if($status==0){
				$condition_where .= " AND `o`.`status`={$status} ";
				$condition_where .= " AND `o`.`paid`=1 ";
			}else if($status==4){
				$condition_where .= " AND `o`.`status`={$status} ";
				$condition_where .= " AND `o`.`paid`=1 ";
			}else if($status==8){
				$condition_where .= " AND `o`.`paid`=0 ";
			}else if($status==3){
				$condition_where .= " AND (`o`.`status`=3 OR `o`.`status`=6)";
				$condition_where .= " AND `o`.`paid`=1 ";
			}else{

				$condition_where .= " AND `o`.`status`={$status} ";
			}
		}




		if($pay_type){
			if($pay_type=='balance'){
				$condition_where .= " AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 )";
			}else{
				$condition_where .= " AND `o`.`pay_type`='{$pay_type}'";
			}
		}

		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}

			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$_GET['searchTimeType'] = $_GET['searchTimeType'] ? $_GET['searchTimeType'] : 'add_time';
            $condition_where .= " AND (o.".$_GET['searchTimeType']." BETWEEN ".$period[0].' AND '.$period[1].")";
			//$condition_where['_string']=$time_condition;
		}

		$condition_table = array(C('DB_PREFIX').'group_order'=>'o',C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'user'=>'u');

		$order_count = D('')->where($condition_where)->table($condition_table)->count();
		import('@.ORG.merchant_page');
		$p = new Page($order_count, 15);

		$order_list = D('')->field('`o`.`phone` AS `group_phone`,`o`.*,`g`.`s_name`,`u`.`uid`,`u`.`nickname`,`u`.`phone`')->where($condition_where)->table($condition_table)->order('`o`.`add_time` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

		// 团购优惠组合
   		$combineIds = array_column($order_list, 'combine_id');
        $combineList = array_column(D('Group_combine_activity')->where(['combine_id'=>['in',$combineIds]])->select(),'title','combine_id');
		foreach ($order_list as &$item) {
			if($item['is_head'] || $item['pin_fid']>0){
				$group_start = D('Group_start')->get_group_start_by_order_id($item['order_id']);
				$item['group_start_status']  =$group_start['status'];
			}else{
				$item['group_start_status']  = 1;

			}

			// 团购优惠组合
            if($combineList[$item['combine_id']]){
                $item['s_name'] .= '【'.$combineList[$item['combine_id']].'】';
            }
		}
		$this->assign('order_list',$order_list);
		$this->assign('pagebar',$p->show());
		$pay_method = D('Config')->get_pay_method('','',1);
		$this->assign('pay_method',$pay_method);
		$this->assign(array( 'status' => $status,'pay_type'=>$pay_type));
		$this->assign('status_list', D('Group_order')->get_status_list());
		$this->display();
	}

	//团购券列表
	public function group_coupon(){
		$this->check_group();
		$store_id = $this->store['store_id'];

		$condition_where = "`o`.`tuan_type`=0 AND `o`.`store_id`='$store_id' AND o.paid = 1";

		if(!empty($_GET['staff_name'])){//核销员姓名
            $condition_where .= " and gp.staff_name like '%".$_GET['staff_name']."%'";
		}
        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){//核销时间
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error(L_('结束时间应大于开始时间！'));
            }

            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $condition_where .= " AND (gp.verify_time BETWEEN ".$period[0].' AND '.$period[1].")";
        }
        if(!empty($_GET['verify_type'])){//核销方式
            $condition_where .= " and gp.verify_type = ".$_GET['verify_type'];
        }
        if(!empty($_GET['status'])){//状态
            if($_GET['status']==1){//待核销
                $condition_where .= " and (gp.status = 0 or o.status = 0) and ((g.effective_type=0 and g.deadline_time>=".time().")||(g.effective_type=1 and g.deadline_time*24*60*60+o.pay_time>=".time()."))";
            }elseif($_GET['status']==2){//已核销
                $condition_where .= " and gp.status = 1";
            }elseif($_GET['status']==3){//已过期
                $condition_where .= " and gp.status = 0 and ((g.effective_type=0 and g.deadline_time<".time().")||(g.effective_type=1 and g.deadline_time*24*60*60+o.pay_time<".time()."))";
            }elseif($_GET['status']==4){//已退款
                $condition_where .= " and gp.status = 2";
            }
        }

        if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'group_pass') {
                $condition_where .= " AND `gp`.`group_pass`='" . htmlspecialchars($_GET['keyword'])."'";
            } elseif ($_GET['searchtype'] == 'real_orderid') {
                $condition_where .= " AND `o`.`real_orderid`='" . htmlspecialchars($_GET['keyword']) . "'";
            } elseif ($_GET['searchtype'] == 'nickname') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= " AND `u`.`phone`='" . htmlspecialchars($_GET['keyword']) . "'";
            }
        }

        $prefix = C('DB_PREFIX');
        $join = ' as o inner join '.$prefix.'group as g on `o`.`group_id`=`g`.`group_id` inner join '.$prefix.'user as u on `o`.`uid`=`u`.`uid` left join '.$prefix.'group_pass_relation as gp on `gp`.`order_id`=`o`.`order_id`';
		$order_count = D('Group_order')->where($condition_where)->join ($join)->count();
		import('@.ORG.merchant_page');
		$p = new Page($order_count, 15);

		if($_GET['export']){
			$order_list = D('Group_order')->field('IF(gp.verify_time,gp.verify_time,o.verify_time) as verify_time,g.s_name as name,IF(gp.group_pass,gp.group_pass,o.group_pass) as group_pass,gp.status,o.real_orderid,u.nickname,u.phone,gp.verify_type,IF(gp.staff_name,gp.staff_name,o.staff_name) as staff_name,g.deadline_time,g.effective_type,o.pay_time,o.order_id,gp.id,o.status as o_status')->where($condition_where)->join ($join)->order('`o`.`order_id` DESC,`gp`.`id` DESC')->select();     
        }else{
			$order_list = D('Group_order')->field('IF(gp.verify_time,gp.verify_time,o.verify_time) as verify_time,g.s_name as name,IF(gp.group_pass,gp.group_pass,o.group_pass) as group_pass,gp.status,o.real_orderid,u.nickname,u.phone,gp.verify_type,IF(gp.staff_name,gp.staff_name,o.staff_name) as staff_name,g.deadline_time,g.effective_type,o.pay_time,o.order_id,gp.id,o.status as o_status')->where($condition_where)->join ($join)->order('`o`.`order_id` DESC,`gp`.`id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
		}

		// 团购优惠组合
   		$combineIds = array_column($order_list, 'combine_id');
        $combineList = array_column(D('Group_combine_activity')->where(['combine_id'=>['in',$combineIds]])->select(),'title','combine_id');
		foreach ($order_list as &$item) {
            $item['verify_time'] = $item['verify_time'] ? date('Y-m-d H:i:s',$item['verify_time']) : '';
            if($item['status']===null){
                if($item['o_status']==0){
                    $item['status'] = 0;
                }elseif(in_array($item['o_status'],[1,2])){
                    $item['status'] = 1;
                }elseif($item['o_status']==3){
                    $item['status'] = 2;
                }
            }
            $item['status_color'] = '#444444';
            if($item['status'] == 1){
                $item['status_color'] = '#080';
            }elseif($item['status'] == 2){
                $item['status_color'] = '#F00';
            }
            $item['can_verify'] = 0;
            if($item['status'] == 0){
                $item['can_verify'] = 1;
                if(($item['effective_type'] == 0 && $item['deadline_time'] < time()) || ($item['effective_type'] == 1 && $item['deadline_time']*24*60*60+$item['pay_time'] < time())){
                    $item['can_verify'] = 0;
                }
            }
            $item['status'] = D('Group_pass_relation')->get_status_msg($item['status'],$item['deadline_time'],$item['effective_type'],$item['pay_time']);
            $item['verify_type'] = D('Group_pass_relation')->get_verify_type_msg($item['verify_type']);
		}
		if($_GET['export']){
            $this->group_coupon_export($order_list);exit;
        }
		$this->assign('order_list',$order_list);
		$this->assign('pagebar',$p->show());
		$pay_method = D('Config')->get_pay_method('','',1);
		$this->assign('pay_method',$pay_method);
//		$this->assign(array( 'status' => $status,'pay_type'=>$pay_type));
		$this->assign('status_list', D('Group_order')->get_status_list());
		$this->display();
	}
    public function group_coupon_export($order_list){
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '团购券记录';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        

        $length = ceil(count($order_list)/ 1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千个信息');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);

            $objActSheet->setCellValue('A1', '核销时间');
            $objActSheet->setCellValue('B1', '券名称');
            $objActSheet->setCellValue('C1', '券序列码');
            $objActSheet->setCellValue('D1', '状态');
            $objActSheet->setCellValue('E1', '卡券订单号');
            $objActSheet->setCellValue('F1', '券类型');
            $objActSheet->setCellValue('G1', '用户昵称');
            $objActSheet->setCellValue('H1', '手机号');
            $objActSheet->setCellValue('I1', '核销方式');
            $objActSheet->setCellValue('J1', '核销人');


 


            if (!empty($order_list)) {
                $index = 1;
                foreach ($order_list as $value) {

                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['verify_time']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['group_pass']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['status']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['real_orderid']);
                    $objActSheet->setCellValueExplicit('F' . $index, C('config.group_alias_name').'券');
                    $objActSheet->setCellValueExplicit('G' . $index, removeEmojiChar($value['nickname']));
                    $objActSheet->setCellValueExplicit('H' . $index, $value['phone']);
                    $objActSheet->setCellValueExplicit('I' . $index, $value['verify_type']);
                    $objActSheet->setCellValueExplicit('J' . $index, $value['staff_name']);
                }
            }
            sleep(2);

        }


        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

	//景区列表
	public function ticket_order_list(){
		$this->check_scenic();
		$db=D('Scenic_order');
		$where=array();
		$mer_id=$this->store['mer_id'];
        $scenic_id=M('Scenic_list')->field('scenic_id')->where(array('company_id'=>$mer_id))->getField('scenic_id');
		$where['o.scenic_id']=$scenic_id;
		$ticket_id=I('ticket_id','','intval');
		if($ticket_id){
			$where['t.ticket_id']	=	$ticket_id;
    	}

		if($_GET['is_children']>-1){
			$where['t.is_children']	=	$_GET['is_children'];
		}

		if($_GET['is_children']>-1){
			$where['t.is_children']	=	$_GET['is_children'];
		}

		if($_GET['ticket_time_type']>-1){
			$where['t.ticket_time_type']	=	$_GET['ticket_time_type'];
		}

		if($_GET['ticket_group']>-1){
			$where['t.ticket_group']	=	$_GET['ticket_group'];
		}

		if($_GET['ticket_union']>-1){
			$where['t.ticket_union']	=	$_GET['ticket_union'];
		}
		$count=$db->alias('o')->join('left join '.C('DB_PREFIX').'Scenic_ticket t on t.ticket_id=o.ticket_id')->where($where)->count();
		$page=new Page($count,15);
		$list=$db->alias('o')->join('left join '.C('DB_PREFIX').'Scenic_ticket t on t.ticket_id=o.ticket_id')->where($where)->limit($page->firstRow,$page->listRows)->order('order_id desc')->select();
		$where2=array();
		foreach ($list as $k => $v) {
			$uinfo=D('User')->get_user($v['user_id']);
			$v['truename']=$uinfo['nickname'];
			$v['phone']=$uinfo['phone'];

			$ticket_title=M('Scenic_ticket')->where(array('ticket_id'=>$v['ticket_id']))->getField('ticket_title');
			$v['ticket_title']=$ticket_title;
			$where2['order_id']=$v['order_id'];
			$where2['type']=1;
			$v['ticket_count']=M('Scenic_order_com')->where($where2)->count(1);
			$where2['type']=2;
			$v['park_count']=M('Scenic_order_com')->where($where2)->count(1);
			$where2['type']=3;
			$v['guide']=M('Scenic_order_com')->where($where2)->count(1);
			$v['ticket_num_add']=M('Scenic_order_com')->where(array('order_id'=>$v['order_id']))->count(1);
			$v['sms_count']=M('Scenic_order_com')->where(array('order_id'=>$v['order_id'],'send_sms'=>1))->count(1);
			$list[$k]=$v;
		}
		$show=$page->show();
		//项目列表
		$ticket_list=M('Scenic_ticket')->field(true)->where(array('scenic_id'=>$this->merchant_session['scenic_id']))->select();
		$this->assign('list',$list);
		$this->assign('show',$show);
		$this->assign('ticket_list', $ticket_list);
		$this->display();
	}

	//景区门票详情
	public function ticket_order_detail(){
		$this->check_scenic();
		$order_id=I('order_id','','intval');
		if(empty($order_id)){
			exit(L_('缺少参数！'));
		}
		$where=array();
		$where['order_id']=$order_id;
		$mer_id=$this->store['mer_id'];
        $scenic_id=M('Scenic_list')->field('scenic_id')->where(array('company_id'=>$mer_id))->getField('scenic_id');
		$where['scenic_id']=$scenic_id;
		$info=D('Scenic_order')->where($where)->field(true)->find();
		if(empty($info)){
			exit(L_('此订单不存在！'));
		}
		$uinfo=D('User')->get_user($info['user_id']);
		$info['truename']=$uinfo['nickname'];
		$info['phone']=$uinfo['phone'];

		$scenic_ticket=D('Scenic_ticket')->get_scenic_one_ticket(array('ticket_id'=>$info['ticket_id']));
		$info['pay_type']=D('Pay')->get_pay_name($info['pay_type'],$info['is_mobile_pay']);
		$com=M('Scenic_order_com')->field(true)->where(array('order_id'=>$info['order_id']))->select();
		$order_com=array();
		foreach ($com as $k => $v) {
			if($v['type']==1){
				$order_com['ticket'][]=$v;
			}elseif($v['type']==2){
				$order_com['park'][]=$v;
			}elseif($v['type']==3){
				$order_com['guide'][]=$v;
			}
		}
		if($order_com['guide']){
			$guide_name=M('Scenic_guide')->where(array('guide_id'=>$order_com['guide']['0']['type_id']))->getField('guide_name');
			$order_com['guide']['0']['guide_name']=$guide_name;
		}
		$scenic_family=array();
		if($info['family_id']){
			$family_id=explode(',',$info['family_id']);
			foreach ($family_id as $k => $v) {
				$scenic_family[]=M('scenic_family')->field(true)->where(array('family_id'=>$v))->find();
			}
		}
		if($info['is_group']==2){
			if($info['group_traveler_img']){
				$info['group_traveler_img']=explode(';',$info['group_traveler_img']);
			}
			$user_list=M('Scenic_group_user')->where(array('order_id'=>$info['order_id']))->select();
			$info['group_traveler']=$user_list;
		}
		$this->assign('scenic_family', $scenic_family);
        $this->assign('now_order', $info);
        $this->assign('order_com', $order_com);
        $this->assign('scenic_ticket', $scenic_ticket);
        $this->display();
	}

	//景区待核销列表
	public function ticket_verify_list(){
		$this->check_scenic();
		$find_value=I('find_value');
		$find_type=I('find_type','','intval');
		$where=array();
		$mer_id=$this->store['mer_id'];
        $scenic_id=M('Scenic_list')->field('scenic_id')->where(array('company_id'=>$mer_id))->getField('scenic_id');
		$where['scenic_id']=$scenic_id;
		if($find_type==1){
			$order_id=D('Scenic_order_com')->where(array('code'=>array('like','%'.$find_value.'%')))->getField('order_id');
			$where['order_id']=$order_id;
		}elseif($find_type==2){
			$where['order_id']=$find_value;
		}elseif($find_type==3){
			$where['user_id']=$find_value;
		}elseif($find_type==4){
			$user_id=M('user')->where(array('nickname'=>array('like','%'.$find_value.'%')))->getField('uid');
			if($user_id){
				$where['user_id']=$user_id;
			}else{
				$where['user_id']='';
			}
		}elseif($find_type==5){
			$now_user=D('User')->get_user($find_value,'phone');
			if(empty($now_user)){
				$this->error(L_('该手机未在平台注册为会员！'));
			}
			$where['user_id']=$now_user['uid'];
		}
		$this->assign('find_type',$find_type);
		$this->assign('find_value',$find_value);

		$db=D('Scenic_order');
		$where['order_status']=1;
		$date=date('Y-n-j');
		$where['ticket_time']=array('like','%'.$date.'%');

		$ticket_id=I('ticket_id','','intval');
		if($ticket_id){
			$where['ticket_id']=$ticket_id;
		}
		$count=$db->where($where)->count(1);
		$page=new Page($count,15);
		$order_list=$db->field(true)->where($where)->limit($page->firstRow,$page->listRows)->order('order_id desc')->select();
		$where2=array();
		foreach ($order_list as $k => $v) {
			$uinfo=D('User')->get_user($v['user_id']);
			$v['nickname']=$uinfo['nickname'];
			$v['phone']=$uinfo['phone'];
			$ticket_title=M('Scenic_ticket')->where(array('ticket_id'=>$v['ticket_id']))->getField('ticket_title');
			$v['ticket_title']=$ticket_title;
			$where2['order_id']=$v['order_id'];
			$where2['type']=1;
			$v['ticket_count']=M('Scenic_order_com')->where($where2)->count();
			$where2['type']=2;
			$v['park_count']=M('Scenic_order_com')->where($where2)->count();
			$where2['type']=3;
			$v['guide']=M('Scenic_order_com')->where($where2)->count();
			$v['state']=1;
			$order_list[$k]=$v;
		}
		$show=$page->show();
		$this->assign('show',$show);
		$this->assign('order_list',$order_list);
		$this->display();
	}

	//多消费码的景区门票
	public function ticket_pass_array(){
		$order_id=I('order_id','','intval');
		if(empty($order_id)){
			$this->error(L_('缺少参数！'));
		}
		$where=array();
		$where['order_id']=$order_id;
		$info=M('scenic_order')->where($where)->field('order_id,order_imm,paid,order_status')->find();
		$where['type']=1;
		$ticket_list=M('Scenic_order_com')->where($where)->field('com_id,type_id,code,status')->select();
		$where['status']=2;
		$use_num=M('Scenic_order_com')->where($where)->count(1);
		if($info['order_status']==1 && $use_num==0){
			$info['is_verify']=0;
		}else{
			$info['is_verify']=1;
		}
		//获取门票标题
		$ticket_title=M('Scenic_ticket')->where(array('ticket_id'=>$ticket_list['0']['type_id']))->getField('ticket_title');
		$info['ticket_title']=$ticket_title;
		$this->assign('info',$info);
		$this->assign('ticket_list',$ticket_list);
		$this->display();
	}

	//景区门票核销
	public function ajax_verify_ticket(){
		$this->check_scenic();
		$order_id=I('order_id','','intval');
		$result_arr=array();
		$result_arr['errorCode']=1;
		if(empty($order_id)){
			$result_arr['errorMsg']=L_('缺少参数！');
			echo json_encode($result_arr);exit;
		}
		$where=array();
		$where['order_id']=$order_id;
		$info=D("Scenic_order")->get_one_order($where);
		if(empty($info)){
			$result_arr['errorMsg']=L_('订单不存在！');
			echo json_encode($result_arr);exit;
		}elseif($info['paid']!=2 || $info['order_status']!=1){
			$result_arr['errorMsg']=L_('订单状态错误！');
			echo json_encode($result_arr);exit;
		}
		//卡券id
		$com_id=I('com_id',0,'intval');
		$db=M('Scenic_order_com');
		//核销指定票券
		if($com_id>0){
			//该订单所有的票券
			$where2=array();
			$where2['order_id']=$order_id;
			$where2['type']=1;
			$card_info=$db->where($where2)->field("count(*) as count,type_id")->find();
			$count=$card_info['count'];
			//已经核销的票券数量
			$where2['status']=2;
			$done_num=$db->where($where2)->count(1);
			if($count<=$done_num){
				$result_arr['errorMsg']=L_('卡券已全部核销！');
				echo json_encode($result_arr);exit;
			}
			$where2['com_id']=$com_id;
			$where2['status']=1;	//未核销
			$ticket_info=$db->where($where2)->find();
			if(empty($ticket_info)){
				$result_arr['errorMsg']=L_('卡券不存在！');
				echo json_encode($result_arr);exit;
			}
			$time=time();
			//更新票券的状态和记录已使用次数
			$data=array();
			$data['status']=2;
			$data['last_time']=$time;
			$use_limit=M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->getField('use_limit');
            if($use_limit>0){
                $data['use_num']=$use_limit;
            }
			$res=$db->where($where2)->save($data);
			if($res){
				//更新景区收入
                $desc=L_('核销景区门票订单X1的消费码',$info['order_imm']).'<br/>'.$ticket_info['code'].L_('记入收入');
                $res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$ticket_info['price'],$desc,$order_id);
                //全部完成处理
                if($count-$done_num==1){
                    D('Scenic_order')->where(array('order_id'=>$order_id))->setField('order_status',2);
                    //增加票券的销售数量
                    M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->setInc('sale_count',$count);
                    //增加导游服务人数
                    $where3=array();
                    $where3['order_id']=$order_id;
                    $where3['type']=3;
                    $guide_id=$db->where($where3)->getField('type_id');
                    if($guide_id>0){
                        M('Scenic_guide')->where(array('guide_id'=>$guide_id))->setInc('guide_service_number',1);
                    }
                    $where4=array();
                    $where4['order_id']=$order_id;
                    $where4['type']=array('in',array(2,3));
                    //更新导游和停车场
                    $data2=array();
                    $data2['status']=2;
					$data2['last_time']=$time;
                    M('Scenic_order_com')->where($where4)->save($data2);
                    $total_price=M('Scenic_order_com')->where($where4)->sum('price');
                    if($total_price>0){
                        $desc=L_('核销景区门票订单X1的导游、停车场收费记入收入',$info['order_imm']);
                        $res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$total_price,$desc,$order_id);
                    }
                }
				$result_arr['errorCode']=0;
				$result_arr['errorMsg']=L_('操作成功！');
				echo json_encode($result_arr);exit;
			}else{
				$result_arr['errorMsg']=L_('操作失败，请重试！');
				echo json_encode($result_arr);exit;
			}
		}else{
			//更新停车场和导游的信息
			$data=array();
			$data['status']=2;
			$data['last_time']=time();
			$where['type']=array('in',array(2,3));
			$res1=$db->where($where)->save($data);
			//更新票券的信息
            if($info['is_group']==2){
                $num=$info['ticket_num'];
                $data['use_num']=$num;
            }else{
                $type_id=$db->where(array('order_id'=>$order_id,'type'=>1))->getField('type_id');
                $use_limit=M('scenic_ticket')->where(array('ticket_id'=>$type_id))->getField('use_limit');
                if($use_limit>0){
                    $data['use_num']=$use_limit;
                }
            }
			$where['type']=1;
			$res2=$db->where($where)->save($data);
			if($res2){
				$ticket_info=$db->where($where)->field('count(*) as count,type_id')->find();
				D('Scenic_order')->where(array('order_id'=>$order_id))->setField('order_status',2);
				//增加票券的销售数量
				M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->setInc('sale_count',$ticket_info['count']);
				//增加导游服务人数
				$where3=array();
				$where3['order_id']=$order_id;
				$where3['type']=3;
				$guide_id=$db->where($where3)->getField('type_id');
				M('Scenic_guide')->where(array('guide_id'=>$guide_id))->setInc('guide_service_number',1);
				//更新景区收入
				$res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$info['order_total'],L_('用户购买门票记入收入'),$order_id);
				$result_arr['errorCode']=0;
				$result_arr['errorMsg']=L_('操作成功！');
				echo json_encode($result_arr);exit;
			}else{
				$result_arr['errorMsg']=L_('操作失败，请重试！');
				echo json_encode($result_arr);exit;
			}
		}
	}

	//校验店员是否有操作景区门票的权限
	protected function check_scenic(){
		if($this->staff_session['is_verify_ticket']==0){
			if(IS_AJAX){
				echo json_encode(array('errorCode'=>1,'errorMsg'=>L_('您访问的店铺没有开通X1功能！',$this->config['scenic_alias_name'])));exit;
			}else{
				$this->error(L_('您访问的店铺没有开通X1功能！',$this->config['scenic_alias_name']));
			}
		}
	}

    /**
     * 团购优惠组合验证消费
     */
    public function group_combine_verify(){
        $this->check_group();
        $param['order_id'] = $_POST['order_id'];
        $param['mer_id'] = $this->store['mer_id'];
        $param['store_id'] = $this->store['store_id'];
        $param['last_staff'] = $this->staff_session['name'];
        $param['staff_name'] = $this->staff_session['name'];

        $result = D('Group_combine_activity')->groupCombineVerify($param);

        if($result['error']>0){
            $this->error($result['msg']);
        }

        $this->success($result['msg']);
    }

	public function group_find(){
		if(IS_POST){
			$mer_id = $this->store['mer_id'];
			$condition_where = "`o`.`uid`=`u`.`uid` AND `o`.`group_id`=`g`.`group_id` AND `o`.`mer_id`='$mer_id'";
			$find_value = $_POST['find_value'];
			$store_id = $this->store['store_id'];
			if($_POST['find_type'] == 1 && strlen($find_value) == 16){
			    // 团购优惠组合搜索
                $param['store_id'] = $store_id;
                $param['mer_id'] = $mer_id;
                $param['group_pass'] = $find_value;
                $group_combine = D('Group_combine_activity')->getActivityOrderBySearch($param);
                if($group_combine['error'] == 0){
                    $return['search_type'] = 'group_combine';
                    $return['row_count'] = 1;
                    $return['data'] = $group_combine['data'];
                }else{
                    $return['search_type'] = 'group_combine';
                    $return['row_count'] = 0;
                }
                echo json_encode($return);die;
            }elseif($_POST['find_type'] == 1 && (strlen($find_value) == 14 || C('config.baodi_ticket_url')) ){
                $condition_where .= " AND (`o`.`store_id`='$store_id' OR `o`.`store_id` <= 0)";
				$res = D('Group_pass_relation')->get_orderid_by_pass($find_value);
				if(!empty($res)){
					$condition_where .= " AND `o`.`order_id`=".$res['order_id'];
				}else{
					$condition_where .= " AND `o`.`group_pass`='$find_value'";
				}
			}else{
                $condition_where .= " AND `o`.`store_id`='$store_id'";
				if($_POST['find_type'] == 1){
					$condition_where .= " AND `o`.`group_pass` like '%$find_value%'";
				}else if($_POST['find_type'] == 2){
					$condition_where .= " AND `o`.`express_id` like '%$find_value%'";
				}else if($_POST['find_type'] == 3){
					$condition_where .= " AND `o`.`real_orderid`='$find_value'";
				}else if($_POST['find_type'] == 4){
					$condition_where .= " AND `o`.`group_id`='$find_value'";
				}else if($_POST['find_type'] == 5){
					$condition_where .= " AND `o`.`uid`='$find_value'";
				}else if($_POST['find_type'] == 6){
					$condition_where .= " AND `u`.`nickname` like '%$find_value%'";
				}else if($_POST['find_type'] == 7){
                    $condition_where .= " AND (`o`.`phone` like '%$find_value%' OR `u`.`phone` like '%$find_value%')";
				}
			}
			$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o',C('DB_PREFIX').'user'=>'u');
			$order_list = D('')->field('`o`.`phone` AS `group_phone`,`o`.*,`g`.`s_name`,`u`.`uid`,`u`.`nickname`,`u`.`phone`')->where($condition_where)->table($condition_table)->order('`o`.`add_time` DESC')->select();
			if($order_list){
				foreach($order_list as $key=>$value){
//					$value['coupon_price'] = D('Group_order')->get_coupon_info($value['order_id']);
//					$order_list[$key]['un_pay'] =$value['total_money']-$value['wx_cheap']-$value['merchant_balance']-$value['balance_pay']-$value['score_deducte']-$value['coupon_price'];
//					$order_list[$key]['un_pay_num'] = $value['num']-round(($value['total_money']-$order_list[$key]['un_pay'])/$value['price']);
					//$order_list[$key]['un_pay_num'] = D('Group_pass_relation')->get_pass_num($value['order_id'],3);
					$order_list[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
					$order_list[$key]['pay_time'] = date('Y-m-d H:i:s',$value['pay_time']);
					$order_list[$key]['use_time'] = !empty($value['use_time']) ? date('Y-m-d H:i:s',$value['use_time']):'';
					if($value['is_head'] || $value['pin_fid']>0){
						$group_start = D('Group_start')->get_group_start_by_order_id($value['order_id']);
						$order_list[$key]['group_start_status']  =$group_start['status'];
					}else{
						$order_list[$key]['group_start_status']  = 1;

					}
				}
			}

			$return['list'] = $order_list;
			$return['row_count'] = count($order_list);
			echo json_encode($return);
		}else{
			$this->check_group();
			$this->display();
		}
	}

	public function group_verify(){
		$this->check_group();
		$database_group_order = D('Group_order');
		$now_order = $database_group_order->get_order_detail_by_id_and_merId($this->store['mer_id'],$_GET['order_id'],false);
        if(empty($now_order)){
            $this->error(L_('当前订单不存在！'));
        }
		if(empty($now_order['paid'])){
            $this->error(L_('此订单尚未支付！'));
		}
        if($now_order['status']!=0 && $now_order['status']!=7){
            $this->error(L_('此订单尚不是未消费！'));
		}

        if($now_order['is_head'] || $now_order['pin_fid']>0){
            $group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            $group_start_status  = $group_start['status'];
            if ($group_start_status == 0) {
                $this->error(L_('此订单尚未成团！'));
            } elseif ($group_start_status == 2) {
                $this->error(L_('此订单超过有效期，团购作废！'));
            }
        }
        if($now_order['paid'] && ($now_order['status'] == 0 || $now_order['status']==7)){
			$condition_group_order['order_id'] = $now_order['order_id'];
			if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
				$data_group_order['third_id'] = $now_order['order_id'];
			}
			$data_group_order['status'] = '1';
			$data_group_order['store_id'] = $this->store['store_id'];
			$data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
			$data_group_order['last_staff'] = $this->staff_session['name'];
			$data_group_order['staff_name'] = $this->staff_session['name'];
			$data_group_order['verify_time'] = $_SERVER['REQUEST_TIME'];
            $data_group_order['last_random'] = mt_rand(100000000,999999999);
			if($database_group_order->where($condition_group_order)->data($data_group_order)->save()){
                // 计算未验证的验证码数量
                $pass_num = $now_order['pass_num']>1?intval($now_order['pass_num']):1; // 一份团购 对应生成核销券 份数
                $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
                $unconsume_pass_num = $now_order['num']*$pass_num-$consume_num;
                $now_order['unconsume_pass_num'] = $unconsume_pass_num; // 未验证核销码数量

                $staff_info = array(
                    'staff_name' => $this->staff_session['name'],
                    'verify_time' => $_SERVER['REQUEST_TIME'],
                    'verify_type' => 2,
                );
                D('Group_pass_relation')->change_refund_status($now_order['order_id'], 1, $staff_info);
				//验证增加商家余额
				$now_order['order_type'] = 'group';
				$now_order['verify_all'] = 1;
				$now_order['store_id'] =$this->store['store_id'];

				//D('Merchant_money_list')->add_money($this->store['mer_id'],L_('用户购买').$now_order['order_name'].L_('记入收入'),$now_order);
				$now_order['desc']=L_('用户购买X1记入收入',$now_order['order_name']);
				D('SystemBill')->bill_method($now_order['is_own'],$now_order);
				D("System_order")->save_reg_order_info('group',$now_order['order_id'],array(),2);

				$this->group_notice($now_order,1);
				$this->success(L_('验证消费成功！'));

			}else{
                fdump('add_money2','lll');
				$this->error(L_('验证失败！请重试。'));
			}
		}else{
			$this->error(L_('当前订单的状态并不是未消费。'));
		}
	}

	public function group_pass_array(){
		$this->check_group();
		$database_group_order = D('Group_order');
		$now_order = $database_group_order->get_order_detail_by_id_and_merId($this->store['mer_id'],$_GET['order_id'],false);
		$now_order['coupon_price'] = D('Group_order')->get_coupon_info($now_order['order_id']);
		$un_pay =$now_order['total_money']-$now_order['wx_cheap']-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['coupon_price'];
		$has_pay = $now_order['total_money']-$un_pay;
		$pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
        $un_consume_num  = D('Group_pass_relation')->get_pass_num($now_order['order_id'],0);
		foreach($pass_array as &$v){
			$v['need_pay'] = $has_pay>$now_order['price']?0:$now_order['price']-$has_pay;
			$has_pay=($has_pay-$now_order['price'])>0?$has_pay-$now_order['price']:0;
		}
		$this->assign('un_consume_num',$un_consume_num);
		$this->assign('pass_array',$pass_array);
		$this->assign('now_order',$now_order);
		$this->display();
	}

	public function group_array_verify(){
		$this->check_group();
		$database_group_order = D('Group_order');
		$verify_all = false;
		$now_order = $database_group_order->get_order_detail_by_id_and_merId($this->store['mer_id'],$_POST['order_id'],false);
		if(empty($now_order)){
			$this->error(L_('当前订单不存在！'));
		}else{
            if(empty($now_order['paid'])){
                $this->error(L_('此订单尚未支付！'));
            }
            if($now_order['status']!=0 && $now_order['status']!=7){
                $this->error(L_('此订单尚不是未消费！'));
            }
            if($now_order['is_head'] || $now_order['pin_fid']>0){
                $group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
                $group_start_status  = $group_start['status'];
                if ($group_start_status == 0) {
                    $this->error(L_('此订单尚未成团！'));
                } elseif ($group_start_status == 2) {
                    $this->error(L_('此订单超过有效期，团购作废！'));
                }
            }

			$where['order_id'] =$now_order['order_id'];
			$where['group_pass'] =$_POST['group_pass'];
			$group_pass_rela = D('Group_pass_relation');
			$res = $group_pass_rela->where($where)->find();
			if(!empty($res)){
				$date['status']=1;
				$date['staff_name']=$this->staff_session['name'];
				$date['verify_time'] = $_SERVER['REQUEST_TIME'];
				$date['verify_type'] = 2;
				if($group_pass_rela->where($where)->data($date)->save()){
					$count = $group_pass_rela->get_pass_num($where['order_id']);
					$count += $group_pass_rela->get_pass_num($where['order_id'],3);

					if($count==0){
						if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
							$data_group_order['third_id'] = $now_order['order_id'];
						}
						$data_group_order['status'] = '1';
						$verify_all = true;
					}else{
						$now_order['res'] = $res;
					}
					$data_group_order['store_id'] = $this->store['store_id'];
					$now_order['store_id'] = $this->store['store_id'];
					$condition_group_order['order_id'] = $where['order_id'];
					$data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
                    $data_group_order['last_staff'] = $this->staff_session['name'];
                    $data_group_order['last_random'] = mt_rand(100000000,999999999);
                    if ($now_order['status'] == 7 && !$verify_all) {
                        $data_group_order['status'] = 0;
                    }
                    $save_msg = D('Group_order')->where($condition_group_order)->data($data_group_order)->save();
                    if($save_msg){
						//验证增加商家余额
						$now_order['order_type'] = 'group';
						$now_order['verify_all'] = 0;
						$now_order['mer_id'] = $this->store['mer_id'];
						//D('Merchant_money_list')->add_money($this->store['mer_id'],L_('验证团购订单').$now_order['real_orderid'].'的消费码</br>'.$_POST['group_pass'].L_('记入收入'),$now_order);
						$now_order['desc']=L_('验证团购订单X1的消费码',$now_order['real_orderid']).'</br>'.$_POST['group_pass'].L_('记入收入');
						D('SystemBill')->bill_method($now_order['is_own'],$now_order);
						D("System_order")->save_reg_order_info('group',$now_order['order_id'],array(),2);

						$this->group_notice($now_order,$verify_all);
						$this->success(L_('验证消费成功！'));
					}else{
                        $date['status'] = 0;
                        $group_pass_rela->where($where)->data($date)->save();
						$this->error(L_('验证失败！请重试。'));
					}
				}else{
					$this->error(L_("验证消费失败！"));
				}
			}else{
				exit(L_('此消费码不存在！'));
			}
		}
	}

	public function group_edit(){
		$this->check_group();
		$now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'],$_GET['order_id'],false);

		if(empty($now_order)){
			exit(L_('此订单不存在！'));
		}
		if($now_order['tuan_type'] == 2 && $now_order['paid'] == 1){
			$express_list = D('Express')->get_express_list();
			$this->assign('express_list',$express_list);
		}

		if($now_order['is_pick_in_store']){
			$now_order['paytypestr']=L_("到店自提");
		}else{
			$now_order['paytypestr'] = D('Pay')->get_pay_name($now_order['pay_type']);
		}
		if(($now_order['pay_type']=='offline') && !empty($now_order['third_id']) && ($now_order['paid']==1)){
			$now_order['paytypestr'] .='<span style="color:green">&nbsp;'.L_('已支付').'</span>';
		}else if(($now_order['pay_type']!='offline') && ($now_order['paid']==1)){
			$now_order['paytypestr'] .='<span style="color:green">&nbsp; '.L_('已支付').'</span>';
		}else{
			$now_order['paytypestr'] .='<span style="color:red">&nbsp; '.L_('未支付').'</span>';
		}

//		if(!empty($now_order['coupon_id'])) {
//			$system_coupon = D('System_coupon')->get_coupon_info($now_order['coupon_id']);
//			$now_order['coupon_price'] = $system_coupon['price'];
//			$this->assign('system_coupon',$system_coupon);
//		}else if(!empty($now_order['card_id'])) {
//			$card = D('Member_card_coupon')->get_coupon_info($now_order['card_id']);
//			$now_order['coupon_price'] = $card['price'];
//			$this->assign('card', $card);
//		}
        $refund_detail_info = $now_order['refund_detail'];
        $now_order['refund_detail'] = $now_order['refund_detail']?unserialize($now_order['refund_detail']):[];
		if($now_order['refund_detail'] && unserialize($now_order['refund_detail'])){
			if ($now_order['refund_detail']['msg'] && $now_order['refund_detail']['error']) {
                $now_order['refund_detail']['msg'] = "错误码：".$now_order['refund_detail']['error'] . ', '. $now_order['refund_detail']['msg'];
            }
		}elseif($now_order['refund_detail'] && !$now_order['refund_detail']['refund_id']){
            $now_order['refund_detail']['msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']);
        }elseif($now_order['refund_detail'] && $now_order['refund_detail']['msg']){
            $now_order['refund_detail']['msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']['msg']);
        }elseif($now_order['refund_detail'] && $now_order['refund_detail']['err_msg']){
            $now_order['refund_detail']['err_msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']['err_msg']);
        }else{
            $now_order['refund_detail'] = $refund_detail_info;
        }
        // 退款详情
        if ($now_order['refund_detail']) {
            if(is_array($now_order['refund_detail'])){
                $refund_detail = $now_order['refund_detail'];
            }else{
                $refund_detail = ['err_msg' => $now_order['refund_detail']];
                $now_order['refund_detail'] = ['err_msg' => $now_order['refund_detail'], 'msg' => $now_order['refund_detail']];
            }
        } else {
            $refund_detail = array();
        }
        $this->assign('refund_detail',$refund_detail);
		if($now_order['trade_info']){
			$trade_info_arr = unserialize($now_order['trade_info']);
			if($trade_info_arr['type'] == 'hotel'){
				$trade_hotel_info = D('Trade_hotel_category')->format_order_trade_info($now_order['trade_info']);
				foreach ($trade_hotel_info['price_list'] as $k=>$item) {
					$arr[] = $k;
				}
				$trade_hotel_info['price_tmp']  = $arr;
				$trade_hotel_info['book_day'] = (strtotime($trade_hotel_info['end_time']) - strtotime($trade_hotel_info['dep_time']))/86400 ;
				$this->assign('trade_hotel_info',$trade_hotel_info);
			}
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
		$pin_info = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
		$this->assign('pin_info', $pin_info);

		$pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
		foreach ($pass_array as &$v){
		    $v['verify_time'] = !empty($v['verify_time']) ? date('Y-m-d H:i:s',$v['verify_time'] ) : 0;
        }
		$this->assign('pass_array',$pass_array);

		 // 团购优惠组合
        $combineList = D('Group_combine_activity')->where(['combine_id'=> $now_order['combine_id'] ])->find();
        if($combineList){
            $now_order['s_name'] .= '【'.$combineList['title'].'】';
        }
        !empty($now_order['verify_time']) && $now_order['verify_time'] = date('Y-m-d H:i:s',$now_order['verify_time']);

		if ($now_order['paid'] == 1) {
			//增加支付流水号
			$payinfo = M('Pay_order_info')->where(array('business_order_id' => $now_order['order_id'], 'business' => 'group', 'paid' => 1))->find();
			$now_order['orderid'] = $payinfo['orderid'] ?? '';
			$now_order['third_id'] = $payinfo['paid_extra'] ?? '';
		}
        
		$this->assign('now_order',$now_order);
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
                        $write_list[$key]['address'] = $val['address']??'';
                        $write_list[$key]['value'] = $val['value'].' '.$val['value-desc']??'';
                    }
                }
            }
            $this->assign('write_list', $write_list);
        }
        $userinput = [];
        if(C('config.baodi_ticket_url')&&$now_order['userinput']){
            $userinput = $now_order['userinput']?unserialize($now_order['userinput']):[];
        }
        $this->assign('userinput', $userinput);
		$this->display();
	}
	public function group_coupon_edit(){
		$this->check_group();
		$now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'],$_GET['order_id'],false);

		if(empty($now_order)){
			exit(L_('此订单不存在！'));
		}

		if($now_order['is_pick_in_store']){
			$now_order['paytypestr']=L_("到店自提");
		}else{
			$now_order['paytypestr'] = D('Pay')->get_pay_name($now_order['pay_type']);
		}
		if(($now_order['pay_type']=='offline') && !empty($now_order['third_id']) && ($now_order['paid']==1)){
			$now_order['paytypestr'] .='<span style="color:green">&nbsp;'.L_('已支付').'</span>';
		}else if(($now_order['pay_type']!='offline') && ($now_order['paid']==1)){
			$now_order['paytypestr'] .='<span style="color:green">&nbsp; '.L_('已支付').'</span>';
		}else{
			$now_order['paytypestr'] .='<span style="color:red">&nbsp; '.L_('未支付').'</span>';
		}
        $now_order['refund_detail'] = $now_order['refund_detail']?unserialize($now_order['refund_detail']):[];
		if($now_order['refund_detail'] && unserialize($now_order['refund_detail'])){
			if ($now_order['refund_detail']['msg'] && $now_order['refund_detail']['error']) {
                $now_order['refund_detail']['msg'] = "错误码：".$now_order['refund_detail']['error'] . ', '. $now_order['refund_detail']['msg'];
            }
		}elseif($now_order['refund_detail'] && !$now_order['refund_detail']['refund_id']){
            $now_order['refund_detail']['msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']);
        }elseif($now_order['refund_detail'] && $now_order['refund_detail']['msg']){
            $now_order['refund_detail']['msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']['msg']);
        }elseif($now_order['refund_detail'] && $now_order['refund_detail']['err_msg']){
            $now_order['refund_detail']['err_msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']['err_msg']);
        }
        // 退款详情
        if ($now_order['refund_detail']) {
            if(is_array($now_order['refund_detail'])){
                $refund_detail = $now_order['refund_detail'];
            }else{
                $refund_detail = ['err_msg' => $now_order['refund_detail']];
                $now_order['refund_detail'] = ['err_msg' => $now_order['refund_detail'], 'msg' => $now_order['refund_detail']];
            }
        } else {
            $refund_detail = array();
        }
        $this->assign('refund_detail',$refund_detail);
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

		 // 团购优惠组合
        $combineList = D('Group_combine_activity')->where(['combine_id'=> $now_order['combine_id'] ])->find();
        if($combineList){
            $now_order['s_name'] .= '【'.$combineList['title'].'】';
        }
        !empty($now_order['verify_time']) && $now_order['verify_time'] = date('Y-m-d H:i:s',$now_order['verify_time']);
        
        //查询券序列码以及核销信息
        $group_pass = M('Group_pass_relation')->where(['id'=>$_GET['id']])->field('staff_name,verify_time,group_pass,id,status')->find();
        $now_order['staff_name'] = $group_pass['staff_name']??'';
        $now_order['verify_time'] = $group_pass['verify_time']??'';
        $now_order['group_pass'] = $group_pass['group_pass']??'';
        $now_order['status_msg'] = D('Group_pass_relation')->get_status_msg($group_pass['status'],$now_order['deadline_time'],$now_order['effective_type'],$now_order['pay_time']);
        $now_order['deadline'] = $now_order['effective_type'] ? $now_order['deadline_time']*24*60*60+$now_order['pay_time'] : $now_order['deadline_time'];
        $now_order['can_verify'] = 0;
        if($group_pass['status'] == 0){
            $now_order['can_verify'] = 1;
            if(($now_order['effective_type'] == 0 && $now_order['deadline_time'] < time()) || ($now_order['effective_type'] == 1 && $now_order['deadline_time']*24*60*60+$now_order['pay_time'] < time())){
                $now_order['can_verify'] = 0;
            }
        }
        
		$this->assign('now_order',$now_order);
		$this->display();
	}

	public function group_express(){
		$this->check_group();
		$now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'],$_GET['order_id'],false);
		if(empty($now_order)){
			$this->error(L_('此订单不存在！'));
		}
		if(empty($now_order['paid'])){
			$this->error(L_('此订单尚未支付！'));
		}

		if($now_order['status']!=0){
			$this->error(L_('此订单尚不是未消费！'));
		}

		$condition_group_order['order_id'] = $now_order['order_id'];
		$data_group_order['express_type'] = $_POST['express_type'];
		$data_group_order['express_id'] = $_POST['express_id'];
		$data_group_order['last_staff'] = $this->staff_session['name'];
        $data_group_order['last_time'] = $_SERVER['REQUEST_TIME'];
        $data_group_order['send_time'] = $_SERVER['REQUEST_TIME'];
		if($now_order['paid'] == 1 && $now_order['status'] == 0){
			if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
				$data_group_order['third_id'] = $now_order['order_id'];
			}
			$data_group_order['status'] = 0; //原来是1
			$data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
			$data_group_order['store_id'] = $this->store['store_id'];
		}

		if(D('Group_order')->where($condition_group_order)->data($data_group_order)->save()){
			$now_user = D('User')->get_user($now_order['uid'],'uid');
			$express_name = D('Express')->get_express($_POST['express_type']);
			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			$href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
			$model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $this->config['group_alias_name'].L_('快递发货通知'), 'OrderSn' => $now_order['real_orderid'], 'OrderStatus' =>$this->staff_session['name'].L_('已为您发货'), 'remark' =>L_('快递号：').$_POST['express_id'].'('.$express_name['name'].'),'.L_('请尽快确认')), $this->store['mer_id']);

			D('Group_order')->group_app_notice($now_order['order_id'],6);
            /*-----记录系统订单信息 店员已发过 改写状态已发货 19/1/18----*/
            D("System_order")->save_order_status('group',$now_order['order_id'],1);
//			$this->group_notice($now_order,1);
			$this->success(L_('修改成功！'));
		}else{
			$this->error(L_('修改失败！请重试。'));
		}
	}

	public function group_pick(){
		$this->check_group();
		$now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'],$_GET['order_id'],false);
		if(empty($now_order)){
			$this->error(L_('此订单不存在！'));
		}
		if(empty($now_order['paid'])){
			$this->error(L_('此订单尚未支付！'));
		}


		if($now_order['status']!=0){
			$this->error(L_('此订单尚不是未消费！'));
		}

        if($now_order['is_head'] || $now_order['pin_fid']>0){
            $group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            $group_start_status  = $group_start['status'];
            if ($group_start_status == 0) {
                $this->error(L_('此订单尚未成团！'));
            } elseif ($group_start_status == 2) {
                $this->error(L_('此订单超过有效期，团购作废！'));
            }
        }

		$condition_group_order['order_id'] = $now_order['order_id'];
		$date['status']=1;
		$date['paid'] = 1;
		$date['last_staff'] = $this->staff_session['name'];
		$date['use_time'] = $_SERVER['REQUEST_TIME'];
		if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
			$date['third_id'] = $now_order['order_id'];
		}
		if(D('Group_order')->where($condition_group_order)->data($date)->save()){

			//验证增加商家余额
			$now_order['order_type']='group';
			$now_order['verify_all']=1;
			$now_order['store_id'] =$this->store['store_id'];
			//D('Merchant_money_list')->add_money($this->store['mer_id'],L_('用户购买').$now_order['order_name'].L_('记入收入'),$now_order);
			$now_order['desc']=L_('用户购买X1记入收入',$now_order['order_name']);
			D('SystemBill')->bill_method($now_order['is_own'],$now_order);

			$this->group_notice($now_order,1);
			$this->addUserNotice($now_order['order_id'],$now_order['mer_id'],$now_order['store_id'],$now_order['uid'],'核销成功，已经领取商品','您已经领取店铺自提商品，请核对订单','group');
			$this->success(L_('修改成功！'));
		}else{
			$this->error(L_('修改失败！请重试。'));
		}
	}

	public function group_remark(){
		$this->check_group();
		$now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'],$_GET['order_id'],true,false);
		if(empty($now_order)){
			$this->error(L_('此订单不存在！'));
		}
		if(empty($now_order['paid'])){
			$this->error(L_('此订单尚未支付！'));
		}
		if($now_order['store_id']!=$this->staff_session['store_id']){
            $this->error(L_('无权限操作！'));
        }
		$condition_group_order['order_id'] = $now_order['order_id'];
		$data_group_order['merchant_remark'] = $_POST['merchant_remark'];
		if(D('Group_order')->where($condition_group_order)->data($data_group_order)->save()){
			$this->success(L_('修改成功！'));
		}else{
			$this->error(L_('修改失败！请重试。'));
		}
	}

	/*检查是否开启订餐*/
	protected function check_meal(){
		if(empty($this->store['have_meal'])){
			$this->error(L_('您访问的店铺没有开通X1功能！',$this->config['meal_alias_name']));
		}
	}

	public function meal_list()
	{
		$this->check_meal();
		$store_id = intval($this->store['store_id']);
		$where = array();
		if (IS_POST) {
			$order_id = isset($_POST['order_id']) ? htmlspecialchars($_POST['order_id']) : '';
			$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
			$phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
			$meal_pass = isset($_POST['meal_pass']) ? htmlspecialchars($_POST['meal_pass']) : '';
			$table_name = isset($_POST['table_name']) ? htmlspecialchars($_POST['table_name']) : '';
			$order_id && $where['order_id'] = array('like', "%$order_id%");
			$name && $where['name'] = array('like', "%$name%");
			$phone && $where['phone'] = array('like', "%$phone%");
			$meal_pass && $where['meal_pass'] = array('like', "%$meal_pass%");
			if ($table_name) {
				$tables = D('Merchant_store_table')->where(array('name' => array('like', "%$table_name%"), 'store_id' => $store_id))->select();
				$tableids = array();
				foreach ($tables as $table) {
					$tableids[] = $table['pigcms_id'];
				}
				$tableids && $where['tableid'] = array('in', $tableids);
			}
			$this->assign('meal_pass', $meal_pass);
			$this->assign('order_id', $order_id);
			$this->assign('name', $name);
			$this->assign('phone', $phone);
			$this->assign('table_name', $table_name);
		}

		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';
		if ($type && $sort) {
			$order_sort .= $type . ' ' . $sort . ',';
			$order_sort .= 'order_id DESC';
		} else {
			$order_sort .= 'order_id DESC';
		}
		if ($status != -1) {
			$where['status'] = $status;
		}

		$this->assign(D("Meal_order")->get_order_list($this->store['mer_id'], $store_id, $where, $order_sort));
		$this->assign('now_store', $this->store);

		$this->assign('status_list', D('Meal_order')->status_list);
		$this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status));
		$this->display();
	}

	public function order_detial() {
		$now_order = D('Meal_order')->get_order_by_orderid( $_GET['order_id']);
		if (empty($now_order)) {
			exit(L_('此订单不存在！'));
		}
		$now_order['info']=unserialize($now_order['info']);
		if(!empty($now_order['coupon_id'])) {
			$system_coupon = D('System_coupon')->get_coupon_info($now_order['coupon_id']);
			$now_order['coupon_price'] = $system_coupon['price'];
			$this->assign('system_coupon',$system_coupon);
		}else if(!empty($now_order['card_id'])) {
			$card = D('Member_card_coupon')->get_coupon_info($now_order['card_id']);
			$now_order['coupon_price'] = $card['price'];
			$this->assign('card', $card);
		}
		if ($now_order['total_price'] > 0) {
			$now_order['offline_money'] = (floor($now_order['total_price'] * 100) - floor($now_order['minus_price'] * 100) - floor($now_order['balance_pay'] * 100) - floor($now_order['merchant_balance'] * 100) - floor($now_order['coupon_price'] * 100) - floor($now_order['card_price'] * 100) - floor($now_order['score_deducte'] * 100) - floor($now_order['payment_money'] * 100))/100;
		} else {
			$now_order['offline_money'] = (floor($now_order['price'] * 100) - floor($now_order['balance_pay'] * 100) - floor($now_order['merchant_balance'] * 100) - floor($now_order['coupon_price'] * 100) - floor($now_order['card_price'] * 100) - floor($now_order['score_deducte'] * 100) - floor($now_order['payment_money'] * 100))/100;
		}

		$mode = new Model();
		$sql = "SELECT u.name, u.phone FROM " . C('DB_PREFIX') . "deliver_supply AS s INNER JOIN " . C('DB_PREFIX') . "deliver_user AS u ON u.uid=s.uid WHERE s.order_id={$now_order['order_id']} AND s.item=0";
		$res = $mode->query($sql);
		$res = isset($res[0]) && $res[0] ? $res[0] : '';
		$now_order['deliver_user_info'] = $res;
		$this->assign('order',$now_order);
		$this->display();
	}

	public function meal_edit(){
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$store_id = intval($this->store['store_id']);
		if (IS_POST) {
			if (isset($_POST['status'])) {
				$status = intval($_POST['status']);
				if ($order = D("Meal_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->find()) {
					$data = array('store_uid' => $this->staff_session['id'], 'status' => $status);
					$data['last_staff'] = $this->staff_session['name'];
					if ($order['paid'] == 0) $this->error(L_('当前订单的状态并不是未消费。'));
					if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
						$order['paid'] = 0;
						$notOffline = 1;
						if ($this->config['pay_offline_open'] == 1) {
							$now_merchant = D('Merchant')->get_info($order['mer_id']);
							if ($now_merchant) {
								$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
							}
						}
						if ($notOffline) {
							$this->error(L_('当前订单的状态并不是未消费。'));
							exit;
						}
					}

					if ($status && $order['paid'] == 0) {//将未支付的订单，由店员改成已消费，其订单状态则修改成线下已支付！
						$data['third_id'] = $order['order_id'];
						$order['pay_type'] = $data['pay_type'] = 'offline';
						$data['paid'] = 1;
						$price = $order['total_price'] > 0 ? $order['total_price'] : $order['price'];
						$data['pay_money'] = $price - $order['minus_price'];
						$order['pay_time'] = $_SERVER['REQUEST_TIME'];
					}
					$data['use_time'] = $_SERVER['REQUEST_TIME'];
					if (D("Meal_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {
						if ($status && $order['status'] == 0) {
							if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 0))->find()) {
								if ($supply['status'] < 2) {
									D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 0))->delete();
								} else {
									D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 0))->save(array('status' => 5));
								}
							}
							$this->meal_notice($order);
						}

						$this->success(L_("更新成功"), U('Store/meal_list'));
					} else {
						$this->success(L_("更新失败，稍后重试"), U('Store/meal_list'));
					}
				} else {
					$this->error(L_('不合法的请求'));
				}
			} else {
				$this->redirect(U('Store/meal_list'));
			}
		} else {
			$order = D("Meal_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->find();
			$order['info'] = unserialize($order['info']);
			if ($order['store_uid']) {
				$staff = D("Merchant_store_staff")->where(array('id' => $order['store_uid']))->find();
				$order['store_uname'] = $staff['name'];
			}
			if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
				$order['paid'] = 0;
			}

			if (empty($order['tableid'])) {
				$order['tablename'] = L_('不限');
			} else {
				$table = D('Merchant_store_table')->where(array('pigcms_id' => $order['tableid'], 'store_id' => $store_id))->find();
				$order['tablename'] = isset($table['name']) ? $table['name'] : L_('不限');
			}
			if(!empty($order['coupon_id'])) {
				$system_coupon = D('System_coupon')->get_coupon_info($order['coupon_id']);
				$order['coupon_price'] = $system_coupon['price'];
				$this->assign('system_coupon',$system_coupon);
			}else if(!empty($now_order['card_id'])) {
				$card = D('Member_card_coupon')->get_coupon_info($order['card_id']);
				$order['coupon_price'] = $card['price'];
				$this->assign('card', $card);
			}
			$this->assign('order', $order);
			$this->display();
		}
	}

	/*预约订单列表*/
	public function appoint_list(){
		$store_id = $this->store['store_id'];

		$database_order = D('Appoint_order');
		//$database_user = D('User');
		$database_appoint = D('Appoint');
		//$database_store = D('Merchant_store');
		$where['a.store_id'] = $store_id;
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'order_id') {
				$where['a.order_id'] = $_GET['keyword'];
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
				unset($where['orderid']);
				$where['a.order_id'] = $tmp_result['order_id'];
			}elseif ($_GET['searchtype'] == 'name') {
				$where['u.nickname'] = ['like','%'.htmlspecialchars($_GET['keyword'].'%')];
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['u.phone'] = htmlspecialchars($_GET['keyword']);
			}elseif ($_GET['searchtype'] == 'third_id') {
				$where['a.third_id'] =$_GET['keyword'];
			}elseif ($_GET['searchtype'] == 'real_orderid') {
                $where['a.real_orderid'] =$_GET['keyword'];
            }
		}
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if($pay_type){
			if($pay_type=='balance'){
				$where['_string'] = "(`a`.`balance_pay`<>0 OR `a`.`merchant_balance` <> 0 OR a.product_merchant_balance <> 0 OR a.product_balance_pay <> 0 )";
			}else{
				$where['a.pay_type'] = $pay_type;
			}
		}

		$now_appoint = $database_appoint->field(true)->where(array('appoint_id'=>$_GET['appoint_id']))->find();
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));

			$_GET['searchTimeType'] = $_GET['searchTimeType'] ? $_GET['searchTimeType'] : 'order_time';

			$where['_string'] .= (empty($where['_string'])?'':' AND ')." (a.".$_GET['searchTimeType']." BETWEEN ".$period[0].' AND '.$period[1].")";
		}

		// 服务状态
        if (isset($_GET['service_status']) && $_GET['service_status'] !=-1 ) {
			$where['a.service_status'] = $_GET['service_status'];
			if($_GET['service_status'] == 11){
				$where['a.service_status'] = array('in',array(0,1));
				$where['a.is_apply_refund'] = 1;
			}
		}

        // 支付状态
        $paid = isset($_GET['paid']) && $_GET['paid'] ? $_GET['paid'] : -1;
        if ($paid == -10) {
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." (a.paid=1 AND a.service_status<=0)";
        } elseif ($paid == 1) {
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." ((a.paid=1 AND a.payment_status!=1) OR (a.paid=1 AND a.service_status>0))";
        } elseif ($paid==3)  {
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." ((a.paid=3) OR (a.is_del>0))";
        } elseif ($paid==-2)  {
            $where['a.paid'] = 0;
        } elseif ($paid>=0)  {
            $where['a.paid'] = $paid;
        }

		$order_count = $database_order->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid left join '.C('DB_PREFIX').'merchant_store s ON s.store_id = a.store_id ')
				->where($where)->count();

		import('@.ORG.merchant_page');
		$page = new Page($order_count, 20);
		$order_list = $database_order->field('a.*,ap.appoint_name,a.appoint_type,a.mer_id,u.uid,u.nickname,a.order_time,a.pay_time,u.phone,s.name as store_name,s.adress as store_adress')
				->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid left join '.C('DB_PREFIX').'merchant_store s ON s.store_id = a.store_id ')
				->where($where)->order('`order_id` DESC')->limit($page->firstRow.','.$page->listRows)->select();
		foreach ($order_list as &$value) {
			$value['can_change_price'] = 1;
			if($value['service_status']!=0){
           		$value['can_change_price'] = 0;
	       	}
	        if($value['user_pay_time']>0){
           		$value['can_change_price'] = 0;
	        }
            if ($value['product_id']>0) {
                $value['payment_money'] = $value['product_payment_price'];
            }
		}
//         var_dump(D('Appoint_order')->_sql());

//		$order_info = $database_order->field(true)->where($where)->order('`order_id` DESC')->select();
//
//		$uidArr = array();
//		foreach($order_info as $v){
//			array_push($uidArr,$v['uid']);
//		}
//
//		$uidArr = array_unique($uidArr);
//		$user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(array('uid'=>array('in',$uidArr)))->select();
//		$appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->select();
//		$store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
//		$order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
		$pagebar = $page->show();
		$pay_method = D('Config')->get_pay_method('','',1);
		$this->assign('pay_method',$pay_method);
		$this->assign('pay_type',$pay_type);
		$this->assign('pagebar', $pagebar);
		$this->assign('order_list', $order_list);
		$this->display();
	}

	 /*更新预约面议价格*/
    public function change_appoint_order_price(){
        $order_id = $_POST['order_id'];
        $price = $_POST['price'];
        $database_order = D('Appoint_order');
        $where['order_id'] = $order_id;
        // $where['mer_id'] = $this->merchant_session['mer_id'];
        if($this->staff_session['is_change']==0){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('店员没有修改价格的权限')));exit;
        }

        if($price<0){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('金额错误')));exit;
        }

        $now_order = $database_order->where($where)->find();
        if($now_order['service_status']!=0){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('订单状态不是未服务状态')));exit;
        }
        if(!$now_order){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('订单不存在')));exit;
        }
        if($now_order['is_initiative']>0){
            echo json_encode(array('errorCode'=>1,'msg'=>'订单尾款已支付'));exit;
        }

        $old_price = $now_order['appoint_price'];
        if ($now_order['product_id']>0) {
            $old_price = $now_order['product_price'];
        	$update = $database_order->where($where)->setField('product_price',$price);
        }else{
        	$update = $database_order->where($where)->setField('appoint_price',$price);
        }

        if($update){
            M('System_order')
                ->where(['type' => 'appoint', 'order_id' => $order_id])
                ->setField('total_price',$price);
            $save_change_log = [
                'worker_id' => $this->staff_session['id'],
                'order_id' => $order_id,
                'old_price' => $old_price,
                'now_price' => $price,
                'add_time' => time(),
                'product_id' => $now_order['product_id'],
                'type' => 2,
            ];
            D('Merchant_workers_modify_order_log')->add($save_change_log);
            echo json_encode(array('errorCode'=>0,'msg'=>L_('修改成功')));exit;
        }else{
            echo json_encode(array('errorCode'=>1,'msg'=>L_('修改失败')));exit;
        }
    }

	public function change_appoint_order_note(){
		$order_id = $_POST['order_id'];
		$note = $_POST['note'];
		$database_order = D('Appoint_order');
		$where['order_id'] = $order_id;

		$update = $database_order->where($where)->setField('note',$note);

		if($update){
			echo json_encode(array('errorCode'=>0,'msg'=>L_('操作成功')));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'msg'=>L_('操作失败')));exit;
		}
	}


	public function change_shop_order_note()
	{
		$order_id = $_POST['order_id'];
		$note = $_POST['note'];
		$database_order = D('Shop_order');
		$where['order_id'] = $order_id;
		//获取订单信息
        $order_info = $database_order->where($where)->find();
        if(!$order_info){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('订单不存在！')));exit;
        }
        if($order_info['store_id']!=$this->staff_session['store_id']&&$order_info['is_pick_in_store']==2&&!$this->checkPickRule($order_info['pick_id'])){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('无权限操作！')));exit;
        }
		$update = $database_order->where($where)->setField('note',$note);

		if($update){
			echo json_encode(array('errorCode'=>0,'msg'=>L_('操作成功')));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'msg'=>L_('操作失败')));exit;
		}
	}

	public function confirm_pay(){
		$order_id = $_POST['order_id'];
		$database_order = D('Shop_order');
		$where['order_id'] = $order_id;
		$update = $database_order->where($where)->setField('paid',1);

		if($update){
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 1));
			echo json_encode(array('errorCode'=>0,'msg'=>L_('操作成功')));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'msg'=>L_('操作失败')));exit;
		}
	}



	public function change_group_order_note()
	{
		$order_id = $_POST['order_id'];
		$note_info = $_POST['note_info'];
		$database_order = D('Group_order');
		$where['order_id'] = $order_id;

		$update = $database_order->where($where)->setField('note_info',$note_info);

		if($update){
			echo json_encode(array('errorCode'=>0,'msg'=>L_('操作成功')));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'msg'=>L_('操作失败')));exit;
		}
	}




	public function allot_appoint_list(){
		$store_id = $this->store['store_id'];

		$database_order = D('Appoint_order');
		$database_user = D('User');
		$database_appoint = D('Appoint');
		$database_store = D('Merchant_store');
		//$database_merchant_workers = D('Merchant_workers');

		$where['store_id'] = $store_id;
		$where['type'] = array('in',array(1,2));
		//$where['is_del'] = 0;
		$order_info = $database_order->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid ')
            ->field('a.*,ap.appoint_name,a.appoint_type,a.mer_id,u.uid,u.nickname,a.order_time,a.pay_time,u.phone')
            ->where($where)
            ->order('`merchant_allocation_time` desc ,`order_id` DESC')
            ->select();
		$uidArr = array();
		foreach($order_info as $v){
			array_push($uidArr,$v['uid']);
		}

		$uidArr = array_unique($uidArr);
		$user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(array('uid'=>array('in',$uidArr)))->select();
		
		$appoint_info = $database_order->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid ')
            ->field('a.*,ap.appoint_name,a.appoint_type,a.mer_id,u.uid,u.nickname,a.order_time,a.pay_time,u.phone')
            ->select();
		$store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
		$order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);

		$this->assign('order_list', $order_list);
		$this->display();
	}

	/*预约订单查找*/
	public function appoint_find(){
		if(IS_POST){
			$database_order = D('Appoint_order');
			$database_user = D('User');
			$database_appoint = D('Appoint');
			$database_store = D('Merchant_store');

			$appoint_where['a.mer_id'] = $this->store['mer_id'];
			if($_POST['find_type'] == 1 && strlen($_POST['find_value']) == 16){
				$appoint_where['a.appoint_pass'] = $_POST['find_value'];
			} else {
				if($_POST['find_type'] == 1){
					$appoint_where['a.appoint_pass'] = array('LIKE', '%'.$_POST['find_value'].'%');
				} else if($_POST['find_type'] == 2){
					$appoint_where['a.order_id'] = $_POST['find_value'];
				} else if($_POST['find_type'] == 3){
					$appoint_where['a.appoint_id'] = $_POST['find_value'];
				} else if($_POST['find_type'] == 4){
					$appoint_where['a.uid'] = $_POST['find_value'];
				} else if($_POST['find_type'] == 5){
					$appoint_where['u.nickname'] = array('LIKE', '%'.$_POST['find_value'].'%');
				} else if($_POST['find_type'] == 6){
					$appoint_where['u.phone'] = array('LIKE', '%'.$_POST['find_value'].'%');
				}
			}

			$order_info = $database_order->field('a.*,u.nickname,u.phone')->join('as a LEFT JOIN '.C('DB_PREFIX').'user u on u.uid = a.uid')->where($appoint_where)->order('`order_id` DESC')->select();
			//$user_info = $database_user->field('`uid`, `phone`, `nickname`')->where($user_where)->select();
			$appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->select();
			$store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
			$order_list = $this->formatOrderArray($order_info, [], $appoint_info, $store_info);
			if($order_list){
				foreach($order_list as $key=>$val){
					if($_POST['find_type'] == 5){
						if(!isset($val['nickname'])){
							unset($order_list[$key]);
							continue;
						}
					}else if($_POST['find_type'] == 6){
						if(!isset($val['phone'])){
							unset($order_list[$key]);
							continue;
						}
					}
					$order_list[$key]['pay_time'] = $order_list[$key]['pay_time']>0?date('Y-m-d H:i:s', $order_list[$key]['pay_time']):'';
					$order_list[$key]['order_time'] = date('Y-m-d H:i:s', $order_list[$key]['order_time']);
				}
			}

			$return['list'] = array_values($order_list);
			$return['row_count'] = count($order_list);
			echo json_encode($return);
		} else {
			$this->display();
		}
	}

	/*订单详情*/
	public function appoint_detail(){
		$where['order_id'] = $_GET['order_id'] + 0;

		$database_order = D('Appoint_order');
		$database_user = D('User');
		$database_appoint = D('Appoint');
		$database_store = D('Merchant_store');
		$database_appoint_visit_order_info = D('Appoint_visit_order_info');
		$database_merchant_workers = D('Merchant_workers');
		$database_appoint_product = D('Appoint_product');

		$order_info = $database_order->field(true)->where($where)->order('`order_id` DESC')->select();

		$uidArr = array();
		foreach($order_info as $v){
			array_push($uidArr,$v['uid']);
		}

		$uidArr = array_unique($uidArr);
		$user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(array('uid'=>array('in',$uidArr)))->select();

		$appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->select();
		$store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
		$order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
		$now_order = $order_list[0];
		$user = M('User')->where(['uid' => $now_order['uid']])->field('nickname, phone')->find();
        $now_order = array_merge($now_order, $user);
		$cue_info = unserialize($now_order['cue_field']);
		$cue_list = array();
		foreach($cue_info as $key=>$val){
			if(!empty($cue_info[$key]['value'])){
				$cue_list[$key]['name'] = $val['name'];
				$cue_list[$key]['value'] = $val['value'];
				$cue_list[$key]['type'] = $val['type'];
				if($cue_info[$key]['type'] == 2){
					$cue_list[$key]['long'] = $val['long'];
					$cue_list[$key]['lat'] = $val['lat'];
					$cue_list[$key]['address'] = $val['address'];
				}
			}
		}

		$product_detail = $database_appoint_product->get_product_info($now_order['product_id']);
		if($product_detail['status']){
			$now_order['product_detail'] = $product_detail['detail'];
		}

		//上门预约工作人员信息start
		$tmp_order_info=reset($order_info);

		$Map['appoint_order_id'] = $tmp_order_info['order_id'];
		$Map['uid'] = $tmp_order_info['uid'];
		$appoint_visit_order_info = $database_appoint_visit_order_info->where($Map)->find();
		$service_address=  unserialize($appoint_visit_order_info['service_address']);
		if($tmp_order_info['appoint_type'] == 1){
			$service_address_info = array();
			foreach($service_address as $key=>$val){
				if(!empty($service_address[$key]['value'])){
					$service_address_info[$key]['name'] = $val['name'];
					$service_address_info[$key]['value'] = $val['value'];
					$service_address_info[$key]['type'] = $val['type'];
					if($appoint_visit_order_info[$key]['type'] == 2){
						$service_address_info[$key]['long'] = $val['long'];
						$service_address_info[$key]['lat'] = $val['lat'];
						$service_address_info[$key]['address'] = $val['address'];
					}
				}
			}
		}

		if($service_address_info){
			$cue_list = $service_address_info;
		}
		$worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'];
		$worker_field=array('merchant_worker_id','name','mobile');
		$merchant_workers_info = $database_merchant_workers->appoint_worker_info($worker_where,$worker_field);
		$this->assign('merchant_workers_info',$merchant_workers_info);

		//上门预约工作人员信息end

       //退款信息
		$refund = D('Appoint_order_refund')->field(true)->where(array('order_id' => $_GET['order_id'],'status'=>0))->find();

		$imageList = array();
		if(!empty($refund['image'])){
			$image_refund =  explode(',', $refund['image']);
			foreach($image_refund as $k=>$value){
				$imageList[] =  $image_refund[$k];
			}
		}

		$this->assign('imageList',$imageList);
		$status = array('审核中', '已完成退款', '拒绝退货', '审核中', '取消退款申请');
		$refund['showStatus'] = $status[$refund['status']];
		$this->assign('refund',$refund);
		$data['status'] = array('in',[0,1,2]);
		$finishRefunds = D('Appoint_order_refund')->field(true)->where(array('order_id' => $_GET['order_id']))->where($data)->find();

        $finishRefunds['all_refund_money'] =
            $finishRefunds['balance_pay'] +
            $finishRefunds['merchant_balance'] +
            $finishRefunds['card_give_money'] +
            $finishRefunds['payment_money'] +
            $finishRefunds['score_deducte'] +
            $finishRefunds['ecard_pay'] +
            $finishRefunds['balance_payment_money'] +
            $finishRefunds['online_payment_money'];
        
        if($finishRefunds && empty($finishRefunds['status'])){
            $now_order['all_refund_money'] = $finishRefunds['all_refund_money'];
            $finishRefunds = NULL;
        }
        
		$imageLists = array();
		if(!empty($finishRefunds['image'])){
			$image_finishRefunds =  explode(',', $finishRefunds['image']);
			foreach($image_finishRefunds as $k=>$value){
				$imageLists[] =  $image_finishRefunds[$k];
			}
		}
		$this->assign('imageLists',$imageLists);

		$finishRefunds['showStatus'] = $status[$refund['status']];
		$this->assign('finishRefunds',$finishRefunds);

		$this->assign('cue_list', $cue_list);
        empty($now_order['pay_type']) && $now_order['pay_type'] = '余额支付';
        empty($now_order['product_pay_type']) && $now_order['product_pay_type'] = '余额支付';
		$this->assign('now_order', $now_order);
		$this->display();
	}

	public function allot_appoint_detail(){
		$order_id = $_GET['order_id'] + 0;
		$where['order_id'] = $order_id;

		$database_order = D('Appoint_order');
		$database_user = D('User');
		$database_appoint = D('Appoint');
		$database_store = D('Merchant_store');
		$database_appoint_visit_order_info = D('Appoint_visit_order_info');
		$database_merchant_workers = D('Merchant_workers');
		$database_appoint_supply = D('Appoint_supply');

		/*$order_info = $database_order->field(true)->where($where)->order('`order_id` DESC')->select();
		$uidArr = array();
		foreach($order_info as $v){
			array_push($uidArr,$v['uid']);
		}

		$uidArr = array_unique($uidArr);
		$user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(array('uid'=>array('in',$uidArr)))->select();

		$appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->select();
		$store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
		$order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
		$now_order = $order_list[0];*/

		$now_order = $database_order->where($where)->find();
		$where_user['uid'] = $now_order['uid'];
		//$user_info = $database_user->field('`uid`, `phone`, `nickname`')->where($where_user)->find();
		$where_appoint['appoint_id'] = $now_order['appoint_id'];
		$appoint_info = $database_appoint->field(true)->where($where_appoint)->find();
		$cue_info = unserialize($now_order['cue_field']);
		$cue_list = array();
		foreach($cue_info as $key=>$val){
			if(!empty($cue_info[$key]['value'])){
				$cue_list[$key]['name'] = $val['name'];
				$cue_list[$key]['value'] = $val['value'];
				$cue_list[$key]['type'] = $val['type'];
				if($cue_info[$key]['type'] == 2){
					$cue_list[$key]['long'] = $val['long'];
					$cue_list[$key]['lat'] = $val['lat'];
					$cue_list[$key]['address'] = $val['address'];
				}
			}
		}

		//上门预约工作人员信息start
		$tmp_order_info=reset($order_info);

		$Map['appoint_order_id'] = $tmp_order_info['order_id'];
		$Map['uid'] = $tmp_order_info['uid'];
		$appoint_visit_order_info = $database_appoint_visit_order_info->where($Map)->find();
		$service_address=  unserialize($appoint_visit_order_info['service_address']);
		if($tmp_order_info['appoint_type'] == 1){
			$service_address_info = array();
			foreach($service_address as $key=>$val){
				if(!empty($service_address[$key]['value'])){
					$service_address_info[$key]['name'] = $val['name'];
					$service_address_info[$key]['value'] = $val['value'];
					$service_address_info[$key]['type'] = $val['type'];
					if($appoint_visit_order_info[$key]['type'] == 2){
						$service_address_info[$key]['long'] = $val['long'];
						$service_address_info[$key]['lat'] = $val['lat'];
						$service_address_info[$key]['address'] = $val['address'];
					}
				}
			}
		}
		$cue_list = $service_address_info;

		$worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'];
		$worker_field=array('merchant_worker_id','name','mobile');
		$merchant_workers_info = $database_merchant_workers->appoint_worker_info($worker_where,$worker_field);
		$this->assign('merchant_workers_info',$merchant_workers_info);

		//上门预约工作人员信息end


		//技师列表start
		$where['merchant_store_id'] = $now_order['store_id'];
		$where['status'] = 1;
		$worker_list = $database_merchant_workers->where($where)->getField('merchant_worker_id,name');
		$this->assign('worker_list', $worker_list);
		//技师列表end

		$this->assign('cue_list', $cue_list);
		$this->assign('now_order', $now_order);

		$appoint_supply_where['order_id'] = $order_id;
		$appoint_supply_where['worker_id'] = 0;
		$appoint_supply_count = $database_appoint_supply->where($appoint_supply_where)->count();
		$this->assign('appoint_supply_count', $appoint_supply_count);
		$this->display();
	}

	public function ajax_worker_edit(){
		$merchant_worker_id = $_POST['merchant_worker_id'] + 0;
		$order_id = $_POST['order_id'] + 0;
		if(!$merchant_worker_id || !$order_id){
			exit(json_encode(array('status'=>0,'msg'=>L_('传递参数有误！'))));
		}

		$database_appoint_visit_order_info = D('Appoint_visit_order_info');
		$database_appoint_order = D('Appoint_order');
		$_data['merchant_worker_id'] = $merchant_worker_id ? $merchant_worker_id : 0;
		$where['order_id'] = $order_id;
		$appointOrder = $database_appoint_order->where($where)->find();
		if($appointOrder['service_status']){
			exit(json_encode(['status' => 0, 'msg' => L_('当前订单技师已服务')]));
		}
		$database_appoint_order->where($where)->data($_data)->save();
		$insert_id = $database_appoint_visit_order_info->where($where)->data($_data)->save();
		if($insert_id){
            $now_order = $database_appoint_order->where(array('order_id'=>$order_id))->find();
            $worker_where['appoint_order_id'] = $order_id;
            $now_worker = $database_appoint_visit_order_info->appoint_visit_order_detail($worker_where);
            $now_worker = $now_worker['detail'];
            if($now_order['appoint_date']&&$now_order['appoint_time']){
                $tmp_appoint_time = $now_order['appoint_date'] . ' ' . $now_order['appoint_time'];
                $sms_msg = L_('商家为您分配新的预约订单，预约服务时间：X1，订单号：X2，请注意查收！',array('X1'=>$tmp_appoint_time,'X2'=>$order_id));
            }else{
                $sms_msg = L_('商家为您分配新的预约订单，订单号：X1，请注意查收！',array('X1'=>$order_id));
            }
			if(C('config.store_worker_sms_order') && !empty($now_worker)){
				$database_appoint = D('Appoint');
				$appoint_info = $database_appoint->get_appoint_by_appointId($now_order['appoint_id']);

				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'], 'type' => 'appoint');
				$sms_data['uid'] = 0;
				$sms_data['mobile'] = $now_worker['mobile'];
				$sms_data['sendto'] = 'user';
				$sms_data['content'] = L_('有份新的X1被预约，订单号：X2请您注意查看并处理!',array('X1'=>$appoint_info['appoint_name'],'X2'=>$now_order['order_id']));
				Sms::sendSms($sms_data);
			}
            if (!empty($now_worker)&&$now_worker['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                $model->sendTempMsg('TM00017',
                    array(
                        'href' => $href,
                        'wecha_id' => $now_worker['openid'],
                        'first' => $sms_msg,
                        'OrderSn' => $now_order['order_id'],
                        'OrderStatus' => L_('分配新订单提醒'),
                        'remark' => '\n'.L_('请点击查看详细信息！')
                    )
                );
            }

			$database_appoint_supply = D('Appoint_supply');
			$now_supply_order = $database_appoint_supply->where(array('order_id'=>$now_order['order_id']))->find();
			$supply_data['appoint_id'] = $now_order['appoint_id'];
			$supply_data['mer_id'] = $now_order['mer_id'];
			$supply_data['store_id'] = $now_order['store_id'];
			$supply_data['create_time'] = time();
			$supply_data['status'] =  1;
			if($merchant_worker_id > 0){
				$supply_data['worker_id'] = $merchant_worker_id;
				$supply_data['get_type'] = 1;
				$supply_data['status'] =  2;
			}

			$supply_data['start_time'] = $_SERVER['REQUEST_TIME'];
			$supply_data['paid'] = $now_order['paid'];
			$supply_data['pay_type'] = $now_order['pay_type'];
			$supply_data['order_time'] = $now_order['order_time'];
			$supply_data['deliver_cash'] = floatval($now_order['product_price'] - $now_order['product_card_price'] - $now_order['product_merchant_balance'] - $now_order['product_balance_pay'] - $now_order['product_payment_money'] - $now_order['product_score_deducte'] - $now_order['product_coupon_price']);
			$supply_data['uid'] = $now_order['uid'];

			if(!$now_supply_order){
				$supply_data['order_id'] = $now_order['order_id'];
				$database_appoint_supply->data($supply_data)->add();
			}else{
				$supply_where['order_id'] = $now_order['order_id'];
				$database_appoint_supply->where($supply_where)->data($supply_data)->save();
			}
			$recordContent['content'] = [
				[
					'title' => '服务人员名称',
					'value' => $now_worker['name'],
					'type'  => 'text'
				],
				[
					'title' => '服务人员手机号',
					'value' => $now_worker['mobile'],
					'type'  => 'text'
				],
			];
			D('Appoint_order_records')->addOrderRecord($now_order['mer_id'], $now_order['order_id'], '分配服务人员', $recordContent);
			exit(json_encode(array('status'=>1,'msg'=>L_('订单分配成功！'))));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>L_('订单分配失败！'))));
		}
	}

	/*验证预约服务*/
    //修改此方法需同步修改cms/Lib/Model/Appoint_orderModel.class.php appoint_verify
	public function appoint_verify(){
		$database_order = D('Appoint_order');
		$database_appoint_visit_order_info = D('Appoint_visit_order_info');
		$database_merchant_workers = D('Merchant_workers');

		$where['order_id'] = $_GET['order_id'];
		$now_order = $database_order->field(true)->where($where)->find();
		if(empty($now_order)){
			$this->error(L_('当前订单不存在！'));
		} else {
            if($now_order['is_apply_refund']){
                $this->error(L_('当前订单已申请退款！'));
            }
			$condition_group['appoint_id'] = $now_order['appoint_id'];
			D('Appoint')->where($condition_group)->setInc('appoint_sum',1);
			$fields['store_id'] = $this->staff_session['store_id'];
			$fields['last_staff'] = $this->staff_session['name'];
			$fields['last_time'] = time();
            $fields['verify_time'] = time();
			$fields['service_status'] = 1;
			$fields['paid'] = 1;
			if($database_order->where($where)->data($fields)->save()){
				$Map['appoint_order_id'] =  $_GET['order_id'] + 0;
				$appoint_visit_order_info = $database_appoint_visit_order_info->where($Map)->find();

				$worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'];
				$pay_money_count = $database_appoint_visit_order_info->order_appoint_price_sum($worker_where);
				$database_merchant_workers->where($worker_where)->setField('appoint_price',$pay_money_count);
				$database_merchant_workers->where($worker_where)->setInc('order_num');

				$sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'appoint');
				if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
					if (empty($now_order['phone'])) {
						$user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
					}
					$sms_data['uid'] = $now_order['uid'];
					$sms_data['mobile'] = $user['phone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$this->store['name'],'X2'=>$now_order['order_id']));
					Sms::sendSms($sms_data);
				}

				//验证增加商家余额
				$order_info['order_type']='appoint';
				$order_info['order_id'] = $now_order['order_id'];
				$order_info['mer_id'] = $now_order['mer_id'];
				$order_info['store_id'] = $now_order['store_id'];
				$order_info['score_deducte'] = $now_order['score_deducte'] + $now_order['product_score_deducte'];
				$order_info['is_own'] = $now_order['is_own'];
				$order_info['score_used_count'] = $now_order['score_used_count'];
				$order_info['money'] = $now_order['score_deducte']
                    + $now_order['payment_money']
                    + $now_order['merchant_balance']
                    + $now_order['product_score_deducte']
                    + $now_order['product_balance_pay']
                    + $now_order['product_coupon_price']
                    + $now_order['user_pay_money']
                    + $now_order['product_merchant_balance'];

				if($now_order['product_id'] > 0){
					$order_info['total_money'] = $now_order['product_price'];
				}else{
					$order_info['total_money'] = $now_order['appoint_price'];
				}
				if ($order_info['money']>$order_info['total_money']) {
                    $order_info['money'] = $order_info['total_money'];
                }

                $order_info['payment_money'] = $now_order['pay_money'] + $now_order['user_pay_money'];
				$order_info['balance_pay'] = $now_order['balance_pay'] + $now_order['product_balance_pay'];
				$order_info['merchant_balance'] = $now_order['merchant_balance'] + $now_order['product_merchant_balance'];
				$order_info['card_give_money'] = $now_order['card_give_money'] + $now_order['product_card_give_money'];
				$order_info['uid'] = $now_order['uid'];
				$order_info['distributor_uid'] = $now_order['distributor_uid'];

				$appoint_name = M('Appoint')->field('appoint_name,score_percent')->where(array('appoint_id'=>$now_order['appoint_id']))->find();
				//D('Merchant_money_list')->add_money($now_order['mer_id'],L_('用户预约').$appoint_name['appoint_name'].L_('记入收入'),$order_info);
				$order_info['desc']=L_('用户预约X1记入收入',$appoint_name['appoint_name']);
				 $order_info['score_discount_type']=$now_order['score_discount_type'];
				D('SystemBill')->bill_method($order_info['is_own'],$order_info);

				$now_user = D('User')->get_user($order_info['uid']);
				D('Merchant_spread')->add_spread_list($order_info,$now_user,'appoint',$now_user['nickname'].L_("用户购买预约获得佣金"));
				$where['status'] = array('neq',3);
				$database_appoint_supply = D('Appoint_supply');
				$now_supply = $database_appoint_supply->where($where)->find();
				if($now_supply){
					$supply_data['status'] = 3;
					$supply_data['end_time'] = time();
					$supply_data['check_source'] = 2;
					$supply_data['check_time'] = time();
					$database_appoint_supply->where($where)->data($supply_data)->save();
				}
				if(C('config.open_extra_price')==1){
					$score = D('Percent_rate')->get_extra_money($order_info);
					if($score>0){
						D('User')->add_score($order_info['uid'], floor($score), L_('用户预约X1 获得X2',array('X1'=>$appoint_name['appoint_name'],'X2'=>C('config.extra_price_alias_name'))),0,[
						    'order_id'=>$now_order['order_id'],
                            'order_type'=>'appoint'
                        ]);
					}

				}else if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $now_order['score_discount_type']!=2)&& ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
					if($order_info['is_own']&& $this->config['user_own_pay_get_score']!=1){
						$order_info['payment_money'] = 0;
						$this->config['score_get'] = 0;
					}

					if($appoint_name['score_percent'] > 0){
                        $this->config['score_get'] = $appoint_name['score_percent'];
                    }
                    else{
						if(C('config.open_score_get_percent')==1){
							$score_percent = C('config.score_get_percent')/100;
						}else{
							$score_percent = C('config.user_score_get');
						}
						$this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($score_percent,$order_info['uid']);
					}

					D('User')->add_score($now_order['uid'], ($order_info['balance_pay']+$order_info['payment_money']) * $this->config['score_get'] , L_('购买预约商品获得').$this->config['score_name'],0,[
                        'order_id'=>$now_order['order_id'],
                        'order_type'=>'appoint'
                    ]);

				}
				//三级分佣处理
				$spread_total_money = $order_info['total_money'];

				if(C('config.add_spread_by_system_commission')){// 根据平台抽成获得佣金	
					// 获取平台抽成金额							
					$order_info['get_system_take_money'] = true;
                    $spread_total_money = D('SystemBill')->bill_method($order_info['is_own'],$order_info);;
				}

				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
				if(($now_user['can_spread']==1 && !C('config.open_spread_only_once')) &&!empty($now_user['openid'])&&$now_order['pay_type']!='offline' && C('config.open_user_spread')&& (C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)){
                    //上级分享佣金
					$spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],'appoint','',$now_order['appoint_id'],$now_order);
					$spread_users[]=$now_user['uid'];
						$spread_where['_string'] = "uid = '{$now_user['uid']}'";
					$now_user_spread = D('User_spread')->where($spread_where)->find();
					$href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
					if(!empty($now_user_spread)){
						$spread_user = D('User')->get_user($now_user_spread['spread_uid']);
						$user_spread_rate = $spread_rate['first_rate'];//一级分佣金额
						if($spread_user && $user_spread_rate &&!in_array($spread_user['uid'],$spread_users)){
							if ($user_spread_rate ) {
								$spread_money = round($spread_total_money * $user_spread_rate / 100, 2);

								$spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_money,'order_type'=>'appoint','order_id'=>$now_order['order_id'],'third_id'=>$now_order['appoint_id'],'add_time'=>$_SERVER['REQUEST_TIME']);
								if($spread_user['spread_change_uid']!=0){
									$spread_data['change_uid'] = 	$spread_user['spread_change_uid'];
								}
								// 处理长订单id
								if ($now_order['real_orderid']) {
									$spread_data['real_orderid'] = $now_order['real_orderid'];
								}
								D('User_spread_list')->data($spread_data)->add();

								$buy_user = D('User')->get_user($now_user_spread['uid'],'uid');
								if($spread_money>0){
									$money_name = L_('佣金');
									// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => $buy_user['nickname'] . '通过您的分享购买了【' . $now_order['order_name'] . '】，验证消费后您将获得'.$money_name.L_('。'), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
									$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享购买商品，验证消费后您将获得X2。",array("X1" => $buy_user['nickname'],"X2" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
								}
							}
							$spread_users[]=$spread_user['uid'];
							 D('User')->add_money($spread_user['uid'],$spread_money,'推广用户 '.$now_user['nickname'].' 购买 ID为 '.$now_order['appoint_id'].' 的商品 获得的佣金');
						}
						//第二级分享佣金
						$spread_where['_string'] = "uid = '{$spread_user['uid']}' ";
						$second_user_spread = D('User_spread')->where($spread_where )->find();
						if(!empty($second_user_spread)) {
							$second_user = D('User')->get_user($second_user_spread['spread_uid']);
							$sub_user_spread_rate = $spread_rate['second_rate'];//二级分佣的钱
							if ($second_user && ($sub_user_spread_rate)&&!in_array($second_user['uid'],$spread_users)) {
								if ($sub_user_spread_rate) {
									$spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);

									$spread_data =array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'appoint', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['appoint_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
									if($spread_user['spread_change_uid']!=0){
										$spread_data['change_uid'] = 	$second_user['spread_change_uid'];
									}
									// 处理长订单id
									if ($now_order['real_orderid']) {
										$spread_data['real_orderid'] = $now_order['real_orderid'];
									}
									D('User_spread_list')->data($spread_data)->add();
									
									$sec_where['_string'] = "uid = '{$second_user_spread['uid']}' ";
									$sec_user = M('User')->where($sec_where)->find();
									
									if($spread_money>0) {
										$money_name = L_('佣金');
										// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => $sec_user['nickname'] .L_('的子用户').$buy_user['nickname'] . '通过您的分享购买了【' . $now_order['order_name'] . '】，验证消费后您将获得佣金。', 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_("X1的子用户X2通过您的分享购买商品，验证消费后您将获得佣金。",array("X1" => $sec_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
									}
								}

								$spread_users[]=$second_user['uid'];
								 D('User')->add_money($second_user['uid'],$spread_money,'子用户推广用户 '.$now_user['nickname'].' 购买ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
							}

							//顶级分佣
							$spread_where['_string'] = "uid = '{$second_user['uid']}' ";
							$first_user_spread = D('User_spread')->where($spread_where)->find();
							if (!empty($first_user_spread)) {
								$first_spread_user = D('User')->get_user($first_user_spread['spread_uid']);
								$sub_user_spread_rate = $spread_rate['third_rate'];
								if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
									$spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);
									$spread_data =array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'appoint', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['appoint_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
									if($spread_user['spread_change_uid']!=0){
										$spread_data['change_uid'] = 	$first_spread_user['spread_change_uid'];
									}
									// 处理长订单id
									if ($now_order['real_orderid']) {
										$spread_data['real_orderid'] = $now_order['real_orderid'];
									}

									D('User_spread_list')->data($spread_data)->add();
									$fir_where['_string'] = "uid = '{$first_user_spread['uid']}'";
									$fir_user = M('User')->where($fir_where)->find();
								
									if($spread_money>0) {
										// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>$fir_user['nickname'].L_('的子用户的子用户').$buy_user['nickname'] . '通过您的分享购买了【' . $now_order['order_name'] . '】，验证消费后您将获得佣金。', 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>L_("X1的子用户的子用户X2通过您的分享购买了商品，验证消费后您将获得佣金。",array("X1" => $fir_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $now_order['mer_id']);
									}
									 D('User')->add_money($first_spread_user['uid'],$spread_money,'子用户推广用户 '.$now_user['nickname'].' 购买ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
								}
							}
						}
					}
				}
				
				//增加消息通知
                $this->addUserNotice($now_order['order_id'],$now_order['mer_id'],$now_order['store_id'],$now_order['uid'],'核销成功,已完成服务','您预约的服务已经完成，请查看订单','appoint');

				$recordContent['content'] = [[
					'title' => '完成时间',
					'value' => date('Y-m-d H:i:s'),
					'type' => 'text'
				]];
				D('Appoint_order_records')->addOrderRecord($now_order['mer_id'], $now_order['order_id'], '订单完成', $recordContent);
				$this->success(L_('验证成功！'));
			} else {
				$this->error(L_('验证失败！请重试。'));
			}
		}
	}

	/* 格式化订单数据  */
	protected function formatOrderArray($order_info, $user_info, $appoint_info, $store_info){
		if(!empty($user_info)){
			$user_array = array();
			foreach($user_info as $val){
				$user_array[$val['uid']]['phone'] = $val['phone'];
				$user_array[$val['uid']]['nickname'] = $val['nickname'];
			}
		}
		if(!empty($appoint_info)){
			$appoint_array = array();
			foreach($appoint_info as $val){
				$appoint_array[$val['appoint_id']]['appoint_name'] = $val['appoint_name'];
				$appoint_array[$val['appoint_id']]['appoint_type'] = $val['appoint_type'];
				$appoint_array[$val['appoint_id']]['appoint_price'] = $val['appoint_price'];
			}
		}
		if(!empty($store_info)){
			$store_array = array();
			foreach($store_info as $val){
				$store_array[$val['store_id']]['store_name'] = $val['name'];
				$store_array[$val['store_id']]['store_adress'] = $val['adress'];
			}
		}
		if(!empty($order_info)){
			$userMod = M('User');
			foreach($order_info as &$val){
				$thisUser = $userMod->field('`uid`, `phone`, `nickname`')->where(['uid'=>$val['uid']])->find();
				$val['phone'] = $thisUser['phone']??'';
				$val['nickname'] = $thisUser['nickname']??'';
				$val['appoint_name'] = $appoint_array[$val['appoint_id']]['appoint_name'];
				$val['appoint_type'] = $appoint_array[$val['appoint_id']]['appoint_type'];
				$val['appoint_price'] = $val['appoint_price'] ?: $appoint_array[$val['appoint_id']]['appoint_price'];
				$val['store_name'] = $store_array[$val['store_id']]['store_name'];
				$val['store_adress'] = $store_array[$val['store_id']]['store_adress'];
			}
		}
		return $order_info;
	}

	public function logout()
    {
        session('staff_session', null);
        session('staff', null);
		if ($this->merchant_session['store_id']) {
            session('merchant', null);
        }

		setcookie('storestaff_web_session','',$_SERVER['REQUEST_TIME']-1,'/');
		setcookie('storestaff_access_token','',$_SERVER['REQUEST_TIME']-1,'/');

        redirect(U('Store/login'));
    }

	public function bill()
	{
		$mer_id = intval($this->store['mer_id']);
		$this->assign(D("Meal_order")->get_offlineorder_by_mer_id($mer_id, $this->staff_session['name']));
		$this->display();
	}

	private function meal_notice($order)
	{
		//验证增加商家余额
		$order['order_type']='meal';
		$info = unserialize($order['info']);
		$info_str = '';
		foreach($info as $v){
			$info_str.=$v['name'].':'.$v['price'].'*'.$v['num'].'</br>';
		}
		//D('Merchant_money_list')->add_money($this->store['mer_id'],L_('用户购买').$info_str.L_('记入收入'),$order);
		$order['desc']=L_('用户购买X1记入收入',$info_str);
		D('SystemBill')->bill_method($order['is_own'],$order);
		//商家推广分佣
		$now_user = M('User')->where(array('uid' => $order['uid']))->find();
		D('Merchant_spread')->add_spread_list($order, $now_user, 'meal', $now_user['nickname'] . L_('用户购买餐饮商品获得佣金'));

		//积分
		if(C('config.open_extra_price')==1){
			$score = D('Percent_rate')->get_extra_money($order);
			if($score>0){
				D('User')->add_score($order['uid'], floor($score),L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>C('config.extra_price_alias_name'))));
			}

		}else {
			if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2)&& ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
				if($order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
					$order['payment_money'] = 0;
				}

				$this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order['uid']);
                if ($this->config['score_get']>0 && ($order['payment_money'] + $order['balance_pay'])>0) {
                    D('User')->add_score($order['uid'], ($order['payment_money'] + $order['balance_pay']) * $this->config['score_get'], L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])));
                    D('Scroll_msg')->add_msg('meal',$now_user['uid'],L_('用户X1于X2 中消费获得X3',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']). $this->store['name'],'X3'=>$this->config['score_name'])));
                }
			}

			D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])));
		}
		//短信
		$sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'food');
		if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
			if (empty($order['phone'])) {
				$user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
				$order['phone'] = $user['phone'];
			}
			$sms_data['uid'] = $order['uid'];
			$sms_data['mobile'] = $order['phone'];
			$sms_data['sendto'] = 'user';
			$sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$this->store['name'],'X2'=>$order['order_id']));
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_finish_order'] == 2 || $this->config['sms_finish_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $this->store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！',array('X1'=>$order['name'],'X2'=>$order['order_id']));
			Sms::sendSms($sms_data);
		}

		//打印
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($order['order_id'], 'meal_order', 2);
	}

	private function group_notice($order,$verify_all)
	{
		//积分
		$now_user = M('User')->where(array('uid'=>$order['uid']))->find();
		if($verify_all){
			if(C('config.open_extra_price')==1){
				$order['order_type'] = 'group';
				$score = D('Percent_rate')->get_extra_money($order);
				if($score>0){
					D('User')->add_score($order['uid'], floor($score),L_('购买 X1 消费X2 获得X3',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'),'X3'=>C('config.extra_price_alias_name'))));
				}

			}else {
				if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2)&& ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
					if($order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
						$order['payment_money'] = 0;
					}
                    $this->config['score_get'] = D('Percent_rate')->scorePercent('group', $order['group_id'], $order['mer_id']);
                    if( $this->config['score_get']>0) {
                        $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'], $order['uid']);
                        D('User')->add_score($order['uid'], ($order['payment_money'] + $order['balance_pay']) * $this->config['score_get'], L_('购买 X1 消费X2 获得X3',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])));
                    }
					D('Scroll_msg')->add_msg('group',$now_user['uid'],L_('用户X1于X2购买 X3成功并消费获得X4',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$order['order_name'],'X4'=>$this->config['score_name'])));
				}
				D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['total_money'], L_('购买 X1 消费X2 获得积分',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'))));
			}
			//商家推广分佣

			D('Merchant_spread')->add_spread_list($order,$now_user,'group',L_('X1购买X2获得佣金',array('X1'=>$now_user['nickname'],'X2'=>C('config.group_alias_name'))));

		}
		D('Group_order')->group_app_notice($order['order_id'],7);
		//短信
		$sms_data = array('mer_id' => $order['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'group');
		if($now_user['openid']){

			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			$href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$order['order_id'];
			$model->sendTempMsg('TM00017', array('href' => $href,
					'wecha_id' => $now_user['openid'],
					'first' => $this->config['group_alias_name'].L_('团购提醒'),
					'OrderSn' => $order['real_orderid'],
					'OrderStatus' =>L_("您购买的团购已于X1核销成功",date('Y-m-d H:i:s',time())),
					'remark' =>L_('点击查看详情')),
					$this->store['mer_id']);
		}

		if ($this->config['sms_group_finish_order'] == 1 || $this->config['sms_group_finish_order'] == 3) {
			$sms_data['uid'] = $order['uid'];
			$sms_data['mobile'] = $order['phone'];
			$sms_data['sendto'] = 'user';
			if(empty($order['res'])){
				$sms_data['content'] = L_('您购买 X1的订单(订单号：X2)已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$order['order_name'],'X2'=>$order['real_orderid']));
			}else{
				$sms_data['content'] = L_('您购买 X1的订单(消费码：X2)已经完成了消费，如有任何疑意，请您及时联系我们！',array('X1'=>$order['order_name'],'X2'=>$order['res']['group_pass']));
			}
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_group_finish_order'] == 2 || $this->config['sms_group_finish_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $this->store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！',array('X1'=>$order['order_name'],'X2'=>$order['real_orderid']));
			Sms::sendSms($sms_data);
		}

		//打印
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($order['order_id'], 'group_order', 2);
	}

	public function check_confirm()
	{
		$database = D('Meal_order');
		$order_id = $condition['order_id'] = intval($_POST['order_id']);
		$condition['store_id'] = $this->store['store_id'];
		$order = $database->field(true)->where($condition)->find();
		if(empty($order)){
			$this->error(L_('订单不存在！'));
		}
		if ($order['status'] > 2) $this->error(L_('订单已取消'));
		if ($order['paid'] == 0) $this->error(L_('未支付，不能接单。'));
		if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
			$notOffline = 1;
			if ($this->config['pay_offline_open'] == 1) {
				$now_merchant = D('Merchant')->get_info($order['mer_id']);
				if ($now_merchant) {
					$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
				}
			}
			if ($notOffline) {
				$this->error(L('未支付，不能接单。'));
				exit;
			}
		}

		if ($order['meal_type'] == 1) {
			$deliverCondition['store_id'] = $this->store['store_id'];
			$deliverCondition['mer_id'] = $this->store['mer_id'];
			// 商家是否接入配送
// 			if ($deliver = D('Deliver_store')->where($deliverCondition)->find()) {
				$old = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 0))->find();
				if (empty($old)) {
					$deliverType = $deliver['type'];
					$address_id = $order['address_id'];
					$address_info = D('User_adress')->where(array('adress_id' => $address_id))->find();

					$supply['order_id'] = $order_id;
					$supply['paid'] = $order['paid'];
					$supply['real_orderid'] = isset($order['real_orderid']) ? $order['real_orderid'] : '';
					$supply['pay_type'] = $order['pay_type'];
					$supply['money'] = $order['price'];
					$supply['deliver_cash'] = floatval($order['price'] - $order['card_price']-$order['merchant_balance']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-$order['coupon_price']);
					$supply['deliver_cash'] = max(0, $supply['deliver_cash']);
					$supply['store_id'] = $this->store['store_id'];
					$supply['store_name'] = $this->store['name'];
					$supply['mer_id'] = $this->store['mer_id'];
					$supply['from_site'] = $this->store['adress'];
					$supply['from_lnt'] = $this->store['long'];
					$supply['from_lat'] = $this->store['lat'];

					if ($address_info) {
						$supply['aim_site'] =  $address_info['adress'].' '.$address_info['detail'];
						$supply['aim_lnt'] = $address_info['longitude'];
						$supply['aim_lat'] = $address_info['latitude'];
						$supply['name']  = $address_info['name'];
						$supply['phone'] = $address_info['phone'];
					}
					$supply['status'] =  1;
					$supply['type'] = $deliverType;
					$supply['item'] = 0;
					$supply['create_time'] = $_SERVER['REQUEST_TIME'];
					$supply['start_time'] = $_SERVER['REQUEST_TIME'];
					$supply['appoint_time'] = $_SERVER['REQUEST_TIME'];
					if ($addResult = D('Deliver_supply')->add($supply)) {
					} else {
						$this->error(L_('接单失败'));
					}
				}
// 			} else {
// 				$this->error('您还没有接入配送机制');
// 			}
		}
		$data['is_confirm'] = 1;
		$data['order_status'] = 3;
		$data['store_uid'] = $this->staff_session['id'];
		$data['last_staff'] = $this->staff_session['name'];
		if($database->where($condition)->save($data)){
			$this->success(L_('已接单'));
		} else {
			$this->error(L_('接单失败'));
		}

	}

	/***收银台返回处理****/
	public function cashierBack()
	{
		$lgcode=isset($_GET['lgcode']) ? trim($_GET['lgcode']) :'';
		if($lgcode){
			$staff_session = session('staff');
			$database_store_staff = D('Merchant_store_staff');
			$condition_store_staff['username'] = $staff_session['account'];
			$now_staff = $database_store_staff->field(true)->where($condition_store_staff)->find();
			if(!empty($now_staff)){
				$tmplgcode = md5($now_staff['username']);
				if($lgcode == $tmplgcode){
					session('staff',$now_staff);
					session('merchant',null);
					Header('Location:/store.php?g=Merchant&c=Store&a=meal_list');
					exit();
				}
			}
		}
		session('merchant',null);
		$this->error(L_('非法访问登录！'));
	}

	public function cashier()
	{
		$siteurl = $this->config['site_url'];
		$siteurl = rtrim($siteurl,'/');

		if(empty($siteurl)){
			$siteurl=isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
			$siteurl = strtolower($siteurl);
			if(strpos($siteurl,"http:")===false && strpos($siteurl,"https:")===false) $siteurl='http://'.$siteurl;
			$siteurl = rtrim($siteurl,'/');
		}

		$postdata = array('account' => $this->staff_session['username'], 'mer_id' => $this->staff_session['token'], 'store_id' => $this->staff_session['store_id'], 'domain' => ltrim($siteurl, 'http://'));
		$postdata['sign'] = $this->getSign($postdata);
		$postdataStr = json_encode($postdata);
		$postdataStr = $this->Encryptioncode($postdataStr,'ENCODE');
		$postdataStr = base64_encode($postdataStr);

		header('Location: '. $siteurl .'/merchants.php?m=Index&c=auth&a=elogin&code=' . $postdataStr);
	}

	private function getSign($data) {
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$validate[$key] = $this->getSign($value);
			} else {
				$validate[$key] = $value;
			}
		}
		$validate['salt'] = 'pigcmso2oCashier';	//salt
		sort($validate, SORT_STRING);
		return sha1(implode($validate));
	}

	/**
	 * 加密和解密函数
	 *
	 * @access public
	 * @param  string  $string    需要加密或解密的字符串
	 * @param  string  $operation 默认是DECODE即解密 ENCODE是加密
	 * @param  string  $key       加密或解密的密钥 参数为空的情况下取全局配置encryption_key
	 * @param  integer $expiry    加密的有效期(秒)0是永久有效 注意这个参数不需要传时间戳
	 * @return string
	 */
	private function Encryptioncode($string, $operation = 'DECODE', $key = '', $expiry = 0)
	{
		$ckey_length = 4;
		$key = md5($key != '' ? $key : 'lhs_simple_encryption_code_87063');
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

		$cryptkey = $keya . md5($keya . $keyc);
		$key_length = strlen($cryptkey);

		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
		$string_length = strlen($string);

		$result = '';
		$box = range(0, 255);

		$rndkey = array();
		for ($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}

		for ($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}

		for ($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}

		if ($operation == 'DECODE') {
			if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			return $keyc . str_replace('=', '', base64_encode($result));
		}
	}

	public function table()
	{
		$this->assign('now_store', $this->store);

		$database = D('Merchant_store_table');
		$where['store_id'] = $this->store['store_id'];
		$count = $database->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$list = $database->field(true)->where($where)->order('`pigcms_id` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('list', $list);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	public function table_order()
	{
		$tableid = intval($_GET['id']);
		$database = D('Merchant_store_table');
		$condition['pigcms_id'] = $tableid;
		$now_table = $database->field(true)->where($condition)->find();
		if(empty($now_table)){
			$this->error(L_('桌台不存在！'));
		}
		$this->assign('table', $now_table);
		$this->assign('now_store', $this->store);
		$order_list = D('Meal_order')->field(true)->where(array('tableid' => $tableid, 'mer_id' => $this->store['mer_id'], 'status' => 0, 'arrive_time' => array('gt', time() - 10800)))->order('arrive_time ASC')->select();
		$this->assign('order_list', $order_list);
		$this->display();
	}

	/* 分类状态 */
	public function table_status()
	{
		$database = D('Merchant_store_table');
		$condition['pigcms_id'] = intval($_POST['id']);
		$now_table = $database->field(true)->where($condition)->find();
		if(empty($now_table)){
			$this->error(L_('桌台不存在！'));
		}
		$data['status'] = $_POST['type'] == 'open' ? '1' : '0';
		if($database->where($condition)->data($data)->save()){
			exit('1');
		}else{
			exit;
		}
	}

	public function table_check()
	{
		$database = D('Meal_order');
		$order_id = $condition['order_id'] = intval($_POST['id']);
		$condition['store_id'] = $this->store['store_id'];
		$order = $database->field(true)->where($condition)->find();
		if(empty($order)){
			$this->error(L_('订单不存在！'));
		}
		$data['is_confirm'] = $_POST['type'] == 'open' ? '1' : '0';
		if($database->where($condition)->data($data)->save()){
			exit('1');
		}else{
			exit;
		}
	}

	public function ajax_staff_del(){
		$order_id = $this->_post('order_id');
		if(!$order_id){
			exit(json_encode(array('msg'=>L_('传递参数有误！'),'status'=>0)));
		}

		$database_appoint_order = D('Appoint_order');
		$where['order_id'] = $order_id;
		$data['del_time'] = time();
		$data['is_del'] = 4;
		$data['del_staff_id'] = $_SESSION['staff']['id'];
		$result = $database_appoint_order->where($where)->data($data)->save();
		if($result){
            /*-----记录系统订单信息 订单取消成功 改写状态已取消 2020/8/7----*/
            D("System_order")->save_order_status('appoint',$order_id,5);
			exit(json_encode(array('msg'=>L_('取消成功！'),'status'=>1)));
		}else{
			exit(json_encode(array('msg'=>L_('取消失败！'),'status'=>0)));
		}
	}

	public function shop_list()
	{
		$store_id = intval($this->store['store_id']);
		$where = array();
		if (IS_POST) {
			$real_orderid = isset($_POST['real_orderid']) ? htmlspecialchars($_POST['real_orderid']) : '';
			$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
			$phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
			$meal_pass = isset($_POST['meal_pass']) ? htmlspecialchars($_POST['meal_pass']) : '';
			$orderid = isset($_POST['orderid']) ? htmlspecialchars($_POST['orderid']) : '';
			$real_orderid && $where['real_orderid'] = array('like', "%$real_orderid%");
			$name && $where['username'] = array('like', "%$name%");
			$phone && $where['userphone'] = array('like', "%$phone%");
			$orderid && $where['orderid'] = $orderid;
			$this->assign('meal_pass', $meal_pass);
			$this->assign('real_orderid', $real_orderid);
			$this->assign('name', $name);
			$this->assign('phone', $phone);
			$this->assign('orderid', $orderid);
		}
		$where['mer_id'] = $this->store['mer_id'];
		$where['store_id'] = $store_id;
        if($this->staff_session['pick_addr_ids']){
            $where['_string'] = "((`is_pick_in_store` <> 2) OR (`is_pick_in_store` = 2 AND FIND_IN_SET(`pick_id`,'".$this->staff_session['pick_addr_ids']."')))";
        }

		$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';
		if ($type && $sort) {
			$order_sort .= $type . ' ' . $sort . ',';
			$order_sort .= 'pay_time DESC';
		} else {
			if ($status != -1) {
			    //未支付按照下单时间排序
			    if($status == 6){
                    $order_sort .= 'create_time DESC';
                }else{
                    $order_sort .= 'pay_time DESC';
                }
			} else {
				$order_sort .= 'order_id DESC';
			}

		}

		if(!empty($_GET['keyword'])){
		    if ($_GET['searchtype'] == 'real_orderid') {
                $where['real_orderid'] = htmlspecialchars($_GET['keyword']);
                if(strlen($_GET['keyword'])==21 && substr($_GET['keyword'], 0, 1) != 2){
                    $where['real_orderid']=substr($_GET['keyword'],1);
                }
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
				unset($where['orderid']);
				$where['order_id'] = $tmp_result['order_id'];
			} elseif ($_GET['searchtype'] == 'name') {
                $where['username'] = ['LIKE', '%' . htmlspecialchars($_GET['keyword']) . '%'];
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['userphone'] = htmlspecialchars($_GET['keyword']);
			}elseif ($_GET['searchtype'] == 'third_id') {
				$where['third_id'] =$_GET['keyword'];
			}elseif ($_GET['searchtype'] == 'fetch_number') {//取单号
                $where['fetch_number'] =$_GET['keyword'];
            }
		}

		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';

		if ($status == 11) {
            $refund_where_select = 'is_apply_refund = 1 AND status IN (2,3)';
            if(C('customization.yuanjino2o_customized_func')){
                $refund_where_select = 'is_apply_refund = 1';
            }
            $where['_complex'] = [
                $refund_where_select,
                'status=11',
                '_logic' => 'or'
            ];
            unset($where['status']);
		} elseif($status == 100){
			$where['paid'] = 0;
		}elseif($status == 6){
			$where['paid'] = 0;
			unset($where['status']);
		}else if ($status != -1) {
		    $where['status'] = $status;
            if ($status === 0) {
                $where['paid'] = 1;
                $where['status'] = array('in', array(0,7,9));
            }
            if ($status == 2) {
                $where['status'] = array('in', array(2, 3));
            }
            if ($status == 6) {
                $where['paid'] = 0;
            }
            if($status == 12){//已付款未消费
                $where['paid'] = 1;
                $where['status'] = array('in', array(0,1, 9));
            }
		}

		if($pay_type&&$pay_type!='balance'){
			if($pay_type == 'employee_balance_pay'){
				$where['employee_balance_pay'] = ['gt', 0];
			}else if($pay_type == 'employee_score_pay'){
				$where['employee_score_pay'] = ['gt', 0];
			}else{
				$where['pay_type'] = $pay_type;
			}
		}else if($pay_type=='balance'){
			$where['_string'] .= ($where['_string'] ? ' AND ' : '') . "(`balance_pay`<>0 OR `merchant_balance` <> 0 ) AND `pay_type`=''";
		}

		$count_where = "store_id='{$this->store['store_id']}' AND paid=1 AND status<>4 AND status<>5 AND (pay_type<>'offline' OR (pay_type='offline' AND third_id<>''))";

        if (! empty($_GET['begin_time']) && ! empty($_GET['end_time'])) {
            if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}

			$period = array(
				strtotime($_GET['begin_time']." 00:00:00"),
				strtotime($_GET['end_time']." 23:59:59")
			);

			$_GET['searchTimeType'] = $_GET['searchTimeType'] ? $_GET['searchTimeType'] : 'create_time';
            // 退款时间（包括退款，取消，申请退款）
            if ($_GET['searchTimeType']=='refund_time') {
            	$refundOrderIdArray = [];
            	// 查询申请退款订单
            	$refund_condition = [];
            	$refund_condition['status'] = ['in',[0,3]];
            	$refund_condition['applytime'] = ['between',$period];
            	$refund_apply_ids= array_column(M('Shop_order_refund')->field('order_id')->where($refund_condition)->select(), 'order_id');
            	$refund_apply_ids && $refundOrderIdArray = array_merge($refundOrderIdArray,$refund_apply_ids);

            	// 查询退款、取消订单
            	$refund_condition = [];
            	$refund_condition['status'] = ['in',[9,10]];
            	$refund_condition['dateline'] = ['between',$period];
            	$refund_ids = array_column(M('Shop_order_log')->field('order_id')->where($refund_condition)->select(), 'order_id');
            	$refund_ids && $refundOrderIdArray = array_merge($refundOrderIdArray,$refund_ids);
            	$refundOrderIdArray = array_unique($refundOrderIdArray);
            	$where['_string'] .= ($where['_string'] ? ' AND ' : '') . " `order_id` in (".implode(',', $refundOrderIdArray).")";

	            $count_where .= " AND (`order_id` in (".implode(',', $refundOrderIdArray).")";

            }else{
	            $where['_string'] .= ($where['_string'] ? ' AND ' : '') . " (".$_GET['searchTimeType']." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

		    	$count_where .= " AND (".$_GET['searchTimeType']." BETWEEN " . $period[0] .' AND ' . $period[1] . ")";
            }
        }

		$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
		if(C('config.open_mer_owe_money')==0){
			$now_merchant['mch_owe_money'] = 0;
		}
		if($now_merchant['money']<$now_merchant['mch_owe_money']*(-1)){
			$where['_string'] .= ($where['_string'] ? ' AND ' : '') .' from_plat = 0 ';
		}
		//店铺类型，1-快店；2-商城
		$shop_type=I('shop_type',1,'intval');
		if($shop_type==1){
			$where['order_from']=array('neq',1);
		}elseif($shop_type==2){
			$where['order_from']=1;
			//活动类型 act_type 0:所有订单；1：限时购订单；2：拼团订单；3：砍价订单;4周期购订单；5商品券订单
			$act_type=I('act_type',0,'intval');
			if($act_type==2){
				$where_str=' group_id>0';
				$group_order_arr=D('Mall_group_act')->get_success_team($where['mer_id'],$where['store_id']);
				$group_order_str='';
				foreach ($group_order_arr as $k => $v) {
					if($k==0){
						$group_order_str=$v['order_id'];
					}else{
						$group_order_str.=','.$v['order_id'];
					}
				}
				if(!empty($group_order_str)){
                    $where_str.=' and order_id in('.$group_order_str.')';
                }
			}elseif($act_type==3){
				$where_str='bar_id>0';
			}elseif($act_type==4){//周期购订单
				$where_str='periodic_type>0';
			}elseif($act_type==5){//商品券订单
				$where_str='ticket_goods_id<>\'\'';
				$order_sort = 'ticket_time DESC,order_id DESC';
			}else{
				$where_str="group_id=0";
				$group_order_arr=D('Mall_group_act')->get_success_team($where['mer_id'],$where['store_id']);
				if($group_order_arr) {
                    $group_order_str = '';
                    foreach ($group_order_arr as $k => $v) {
                        if ($k == 0) {
                            $group_order_str = $v['order_id'];
                        } else {
                            $group_order_str .= ',' . $v['order_id'];
                        }
                    }

                    if (empty($group_order_str)) {
                        $where_str .= ' or (group_id>0)';
                    } else {
                        $where_str .= ' or (group_id>0 and order_id in(' . $group_order_str . '))';
                    }
                }
			}
			$where['_string'].= ($where['_string'] ? ' AND ' : '') .'('.$where_str.')';
			$this->assign('act_type',$act_type);
		}
		$againCreateDistribution = C('customization.again_create_distribution_order');
		$return=D("Shop_order")->get_order_list($where, $order_sort, 2, false, $againCreateDistribution);

		$this->assign($return);

		$shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
		//获取商家配置的配送方式
		$shop['mall_deliver_type']=D('Merchant_store_shop')->get_mall_deliver_type($shop['deliver_type'],$shop['mall_deliver_type']);
		$shop = array_merge($this->store, $shop);
		$this->assign('now_store', $shop);
		D('Shop_order')->status_list[11] = L_('申请退货');
		$status_list = D('Shop_order')->status_list;

		// 开启多语言
		if (C('config.open_multilingual')) {
			unset($status_list[7],$status_list[8],$status_list[9],$status_list[10]);
		}

		$this->assign('status_list', $status_list);
		//$this->assign($order_lsit);
		$this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status,'pay_type'=>$pay_type));

		// $field = 'sum(price) AS total_price, sum(price+extra_price - card_price - merchant_balance - card_give_money - balance_pay - payment_money - score_deducte - coupon_price - qiye_pay) AS offline_price, sum(card_price + merchant_balance + card_give_money + balance_pay + payment_money + score_deducte + coupon_price) AS online_price';
		$field = [
		    "sum( price ) AS total_price",
            "sum(CASE WHEN pay_type = 'offline' AND third_id <> '' THEN 
                price + extra_price - card_price - merchant_balance - card_give_money - balance_pay - payment_money - score_deducte - coupon_price - qiye_pay ELSE  0 END
            ) AS offline_price",
            "sum(CASE WHEN  pay_type <> 'offline' THEN  price ELSE 0 END) AS online_price "
        ];

		$count_where = "store_id='{$this->store['store_id']}' AND paid=1 AND status<>4 AND status<>5 AND (pay_type<>'offline' OR (pay_type='offline' AND third_id<>''))";
		if ($period) {
		    $count_where .= " AND (".$_GET['searchTimeType']." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
		}

		if($shop_type==1){
			$count_where.=' and order_from!=1';
		}elseif($shop_type==2){
			$count_where.=' and order_from=1';
		}
        if($this->staff_session['pick_addr_ids']){
            $count_where .= " and ((`is_pick_in_store` <> 2) OR (`is_pick_in_store` = 2 AND FIND_IN_SET(`pick_id`,'".$this->staff_session['pick_addr_ids']."')))";
        }
		$result_total = D('Shop_order')->field($field)->where($count_where)->select();
		$result_total = isset($result_total[0]) ? $result_total[0] : '';
		$this->assign($result_total);
		$this->assign('is_change', $this->staff_session['is_change']);
		$pay_method = D('Config')->get_pay_method('','',1);
		$this->assign('pay_method',$pay_method);
		$this->assign('shop_type',$shop_type);

		$this->display();
	}

	public function ajax_shop_neworder(){
		$shop_type=I('get.shop_type',1,'intval');
        //店铺开启了自动接单，则不提示
        $isAutoOrder = M('Merchant_store_shop')->where(['store_id' => $this->store['store_id']])->getField('is_auto_order');
        if ($isAutoOrder == 1) {
            $this->error(time());
        }

		$where=array();
		$where['store_id']=$this->store['store_id'];
		$where['status']=array('in',array(0,9));
		$where['pay_time']=array('egt',$_POST['time']);
		//店铺类型，1-快店；2-商城
		if($shop_type==1){
			$where['order_from']=array('neq',1);
		}else{
			$where['order_from']=1;
		}
		$where['group_id']=array('eq',0);
	    $result = M('Shop_order')->where($where)->find();
	    if($shop_type==2){
		    //拼团成功的订单
		    unset($where['status'],$where['group_id']);
		    $where['ord.status']=array('in',array(0,9));
		    $where['ord.group_id']=array('gt',0);
		    $where['team.status']=1;
		    $res1=M('shop_order')->alias('ord')->join('left join '.C('DB_PREFIX').'mall_group_team team on ord.team_id=team.id')->where($where)->find();
	    }else{
	    	$res1=0;
	    }
	    if($result || $res1){
	        $this->success(time());
	    }else{
	        $this->error(time());
	    }
	}

    public function ajax_shop_refundorder()
    {
    	//店铺类型，1-快店；2-商城
    	$where_str="r.status=0 and r.applytime>".$_POST['time'];
    	$shop_type=I('get.shop_type',1,'intval');
		if($shop_type==1){
			$where_str.=' and o.order_from!=1';
		}else{
			$where_str.=' and o.order_from=1';
		}
        $sql = "SELECT count(1) as cnt FROM " . C('DB_PREFIX') . "shop_order as o INNER JOIN " . C('DB_PREFIX') . "shop_order_refund as r ON r.order_id=o.order_id AND o.store_id=" . $this->store['store_id'] . ' WHERE '.$where_str;
        $count = M()->query($sql);
        $count = isset($count[0]['cnt']) ? intval($count[0]['cnt']) : 0;

        if ($count) {
            $this->success(time());
        } else {
            $this->error(time());
        }
    }

	public function check_shop()
	{

		$database = D('Shop_order');
		$database->startTrans();
		$order_id = $condition['order_id'] = intval($_POST['order_id']);
		$condition['store_id'] = $this->store['store_id'];
		$order = $database->field(true)->where($condition)->find();
		if(empty($order)){
			$this->error(L_('订单不存在！'));
			exit;
		}
		if ($order['status'] > 0) {
			$this->error(L_('该单已接，不要重复接单'));
			exit;
		}
        if($order['is_weight']==1){
            $order_detail = M('Shop_order_detail')->where(array('order_id'=>$order['order_id']))->select();
            $flag = false;
            foreach($order_detail as $o_d){
                if($o_d['sell_type']==2 && $o_d['real_weight']==0){
                    $flag = true;
                }
            }
            if($flag){
                $this->error(L_('该订单含称重商品，请录入重量后接单！'));
            }
        }
		if ($order['status'] == 4 || $order['status'] == 5) {
			$this->error(L_('订单已取消，不能接单！'));
			exit;
		}
		if ($order['is_refund']) {
			$this->error(L_('用户正在退款中~！'));
			exit;
		}
		if ($order['paid'] == 0) {
			$this->error(L_('订单未支付，不能接单！'));
			exit;
		}
		$data['is_taking'] = 1;
		if ($order['platform'] == 1) {//eleme
		    $eleme = D('Eleme_shop')->getElemeObj($order['store_id']);
		    if ($eleme != false) {
		        $result = $eleme->getDataByApi("eleme.order.confirmOrderLite", array('orderId' => $order['platform_id']));
		        if ($result['error']) {
		            $this->error(L_('饿了么返回的错误') . $result['error']['code'] . $result['error']['message']);
		            exit;
		        } else {
		            $data['status'] = 1;
		            $data['order_status'] = 1;
		            $condition['status'] = 0;
		            $data['last_staff'] = $this->staff_session['name'];
		            $data['last_time'] = time();
		            $database->where($condition)->save($data);
		            if ($order['is_pick_in_store'] == 5) {
		                $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
		                if ($result['error_code']) {
		                    D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $order['store_id']))->save(array('status' => 0, 'last_time' => time()));
		                    $this->error($result['msg']);
		                    exit;
		                }
                        $phones = explode(' ', $this->store['phone']);
		                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
		            }

                    //新增消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $param['type'] = 0;
                    $param['business'] = 'shop';
                    $param['order_id'] = $order_id;
                    $param['mer_id'] = $this->store['mer_id'];
                    $param['store_id'] = $order['store_id'];
                    $param['title'] = '外卖商家已接单';
                    $param['content'] = '商家已接单，商品将尽快为你送达';
                    $param['uid'] = $order['uid'];
                    invoke_v20_service($url, [$param]);
					$database->commit();
		            $this->success(L_('已接单'));
		            exit;
		        }
		    } else {
				$database->rollback();
		        $this->error(L_('店铺对接饿了么有问题，请联系商家处理！'));
		        exit;
		    }
		} elseif ($order['platform'] == 2) {//meituan
		    $store = M('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
		    if (empty($store) || empty($store['meituan_token'])) {
		        $this->error(L_('店铺对接美团有问题，请联系商家处理！'));
		        exit;
		    }
		    import('@.ORG.Meituan');
		    $meituan = new Meituan($store['meituan_token']);
		    $result = $meituan->getDataByApi('waimai/order/confirm', array('orderId' => $order['platform_id']));
		    if ($result['error']) {
		        $this->error(L_('美团返回的错误：') . $result['msg']);
		        exit;
		    } else {
		        $data['status'] = 1;
		        $data['order_status'] = 1;
		        $condition['status'] = 0;
		        $data['last_staff'] = $this->staff_session['name'];
		        $data['last_time'] = time();
		        $database->where($condition)->save($data);
		        if ($order['is_pick_in_store'] == 5) {
		            $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
		            if ($result['error_code']) {
		                D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $order['store_id']))->save(array('status' => 0, 'last_time' => time()));
		                $this->error($result['msg']);
		                exit;
		            }
                    $phones = explode(' ', $this->store['phone']);
		            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
		        }

                //新增消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $param['type'] = 0;
                $param['business'] = 'shop';
                $param['order_id'] = $order_id;
                $param['mer_id'] = $this->store['mer_id'];
                $param['store_id'] = $order['store_id'];
                $param['title'] = '外卖商家已接单';
                $param['content'] = '商家已接单，商品将尽快为你送达';
                $param['uid'] = $order['uid'];
                invoke_v20_service($url, [$param]);
				$database->commit();
		        $this->success(L_('已接单'));
		        exit;
		    }
		} else {
    		$data['status'] = 1;
    		$data['order_status'] = 1;
    		$condition['status'] = 0;
    		$data['last_staff'] = $this->staff_session['name'];
    		$data['last_time'] = time();
    		if($database->where($condition)->save($data)){
    			if ($order['is_pick_in_store'] != 2 && $order['is_pick_in_store'] != 3) {
    			    $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
    			    if ($result['error_code']) {
    			        D('Shop_order')->where(array('order_id' => $order_id))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
    			        $this->error($result['msg']);
    			        exit;
    			    }
    			}
    			$phones = explode(' ', $this->store['phone']);
    			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));

                //新增消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $param['type'] = 0;
                $param['business'] = 'shop';
                $param['order_id'] = $order_id;
                $param['mer_id'] = $this->store['mer_id'];
                $param['store_id'] = $order['store_id'];
                $param['title'] = '外卖商家已接单';
                $param['content'] = '商家已接单，商品将尽快为你送达';
                $param['uid'] = $order['uid'];
                invoke_v20_service($url, [$param]);

				$database->commit();
                //接单后打印标签
                $orderDetail = D('Shop_order')->get_order_detail(array('order_id' => $order_id), true);
                D('Label_printer')->shopOrderLabel($orderDetail);
				$this->success(L_('已接单'));
    		} else {
				$database->rollback();
    			$this->error(L_('接单失败'));
    		}
		}
		$database->rollback();
	}

    /**
     * 验证店员是否有操作自提订单权限
     * @param $pick_id
     * @return bool true-有权限，false-无权限
     */
	public function checkPickRule($pick_id){
	    $data = true;
        //验证是否有提交权限
        if($this->staff_session['pick_addr_ids']){
            $pick_addr_ids = explode(',',$this->staff_session['pick_addr_ids']);
            if(!in_array($pick_id,$pick_addr_ids)){
                $data = false;
            }
        }
        return $data;
    }

	//订单详情
	public function shop_order_detail()
	{
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));
        if($order['store_id']!=$this->staff_session['store_id']&&$order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'])){
            $this->error('无权限操作！');
        }
		//获取退款时间
        if ($order['status'] == 4) {
            $order['refund_time'] = M('Shop_order_log')->where(['status' => 9, 'order_id' => intval($_GET['order_id'])])->getField('dateline');	
        }
		//获取取消订单时间
        if ($order['status'] == 5) {
            $order['cancel_time'] = M('Shop_order_log')->where(['status' => 10, 'order_id' => intval($_GET['order_id'])])->getField('dateline');
        }
		$store = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
        
        $merchantStore = D('Merchant_store')->where(['store_id' => $order['store_id']])->find();
        $store['miaomaiyun_erp_storeid'] = $merchantStore['miaomaiyun_erp_storeid'] ?? 0;
        
		$sure = false;
		if($order['is_pick_in_store'] == 3){
			$express_list = D('Express')->get_express_list();
			$this->assign('express_list',$express_list);
		} elseif ($order['is_pick_in_store'] == 0) {
			$supply = D('Deliver_supply')->field('uid')->where(array('order_id' => $order['order_id'], 'item' => 2))->find();
			if (isset($supply['uid']) && $supply['uid']) {
				$sure = true;
			}
		}
		$tempList = array();
		foreach($order['info'] as $v) {
			if($v['give_goods_id']){
				$shop_goods_name = D('Shop_goods')->field('goods_id,name,price')->where(array('goods_id'=>$v['give_goods_id']))->find();
				$v['give_goods_name'] = $shop_goods_name['name'];
				$v['give_goods_price'] = $shop_goods_name['price'];
			}
			if($v['give_coupon_id']){
				$coupon = D('Card_new_coupon')->field('name')->where(array('coupon_id'=>$v['give_coupon_id']))->find();
				$v['give_coupon_name'] = $coupon['name'];
			}
		    $index = isset($v['packname']) && $v['packname'] ? $v['packname'] : 0;
		    if (isset($tempList[$index])) {
		        $tempList[$index]['list'][] = $v;
		    } else {
		        $tempList[$index] = array('name' => $v['packname'], 'list' => array($v));
		    }
		}
		if (count($tempList) == 1) {
		    $tempList[$index]['name'] = '';
		}
		$order['info'] = $tempList;

        //组合附属商品2
        foreach ($order['info'] as $key => $value){
            $order['info'][$key]['list'] = D('Shop_goods')->combination_data2($value['list']);
        }

        $refundList = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'status' => 0))->order('id DESC')->select();
        if(!empty($refundList)){
            $this->assign('refund_id', $refundList[0]['id']);
        }
        $sql = "SELECT g.image, d.goods_id, d.host_goods_id, d.uniqueness_number, d.name, d.price, d.num, d.unit, d.spec, d.id, d.refund_id FROM " . C('DB_PREFIX') . "shop_goods AS g INNER JOIN " . C('DB_PREFIX') . "shop_refund_detail AS d ON g.goods_id=d.goods_id WHERE d.order_id={$order_id}";
        $list = D()->query($sql);
        $data = array();
        $goods_image_class = new goods_image();
        foreach ($list as $row) {
        	$row['price'] = $row['price'] < 0 ? 0 : $row['price'];
            $image = '';
            if(!empty($row['image'])){
                $tmp_pic_arr = explode(';', $row['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    if (empty($image)) {
                        $image = $goods_image_class->get_image_by_path($value, 's');
                        break;
                    }
                }
            }
            $row['image'] = $image;
            $data[$row['refund_id']][] = $row;
        }
        //状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请)
        $status = array(L_('审核中'), L_('已完成退货'), L_('拒绝退货'), L_('审核中'), L_('取消退货申请'));
        foreach ($refundList as &$refund) {
            $refund['image'] = explode(',', $refund['image']);
            $refund['goodsList'] = isset($data[$refund['id']]) ? $data[$refund['id']] : array();
            $refund['showStatus'] = $status[$refund['status']];
            //一个订单的退款有些，直接使用后循环查询
            $refund['logList'] = D('Shop_refund_log')->field(true)->where(array('refund_id' => $refund['id']))->order('id ASC')->select();
        }

        $rlist = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'status' => 1))->order('id DESC')->select();
        $rids = array();
        foreach ($rlist as $rl) {
            $rids[] = $rl['id'];
        }
        $finishRefunds = array();
        if ($rids) {
            $finishRefunds = D('Shop_refund_detail')->field(true)->where(array('refund_id' => array('in', $rids)))->select();
        }

        //组合附属商品2
        $finishRefunds = D('Shop_goods')->combination_data2($finishRefunds);

        //支付手续费
        $payment_money_order_db = M("payment_money_order");
        $payment_money_order = $payment_money_order_db->where("order_id=".$order_id)->find();
        $order['service_charge'] = '';
        if($payment_money_order && $payment_money_order['service_charge'] != '0.00'){
        	$order['service_charge'] = $payment_money_order['service_charge'];
        }

        //周期购数据
        $periodic_list = D('Mall_periodic_deliver')->get_order_deliver($order_id,false);
        $current_periodic = $this->get_current_one($periodic_list);
        $periodic_list = $this->deal_periodic_list($periodic_list);
        $this->assign('periodic_list', $periodic_list);
        $this->assign('current_periodic', $current_periodic);
		if($current_periodic){
			if($order['is_pick_in_store']==2){
				$botton_name = '第'.$current_periodic['deliver_num'].'期自提取货';
			}else{
				$botton_name = '第'.$current_periodic['deliver_num'].'期发货';
			}
		}else{
			$botton_name = '提交';
		}
	
		$this->assign('botton_name', $botton_name);
		$is_authentication = D('Merchant_store')->where(array('store_id' => $order['store_id']))->getField('is_authentication');
		//实名认证
		if($is_authentication&&$order['order_from']==1){
			$authenticationInfo = D('Mall_authentication')->where(array('uid'=>$order['uid']))->find();
			$authenticationInfo['authentication_field'] = unserialize($authenticationInfo['authentication_field']);
			//dump($authenticationInfo['authentication_field']);exit;
			$this->assign('authentication_field',$authenticationInfo['authentication_field']);
		}
        $order['desc'] = empty($order['desc'])?L_('无'):$order['desc'];
		//订单地址修改
		$changeAddressStatus = M('System_order')
            ->where(['type' => 'shop', 'order_id' => $order['order_id']])
            ->field('change_address_status')
            ->find();
		
        $order['change_address_status'] = $changeAddressStatus['change_address_status'];
        $addressChangeRecord = NULL;
		if(!empty($changeAddressStatus['change_address_status'])){
            $addressChangeRecord = M('Order_address_change_record')
                ->where(['type' => 'shop', 'order_id' => $order['order_id']])
                ->find();
        }
        $this->assign('addressChangeRecord', $addressChangeRecord);
        $this->assign('refund_list', $refundList);
        $this->assign('finishRefunds', $finishRefunds);
		$this->assign('sure', $sure);
		$this->assign('store', $store);
		$this->assign('order', $order);
		$this->display();
	}
    //店员端订单地址变更审核
	public function checkChangeAddress()
    {
        $orderId = $_GET['order_id'];
        $status = I('status');
        $type = I('type');
       
        if(empty($orderId) || empty($status) || empty($type)){
            $this->ajaxReturn(['status' => 2, 'info'=>'参数不正确！']);
        }
        $result = D('System_order')->checkChangeAddress($orderId, $status, $type);

        $this->ajaxReturn(['status' => 0, 'info'=>'审核成功！','data' => $result]);
    }
    
	public function deal_ticket_list($data){
		if($data){
			foreach($data as $key => $val){
				switch($val['status']){
					case '0':$data[$key]['status_text'] = '未配送';break;
					case '1':$data[$key]['status_text'] = '已申请';break;
					case '2':$data[$key]['status_text'] = '配送中';break;
					case '3':$data[$key]['status_text'] = '已完成';break;
				}
				$data[$key]['reply_time'] = $val['reply_time']?date('Y-m-d H:i:s',$val['reply_time']):'未申请';
				$data[$key]['accept_time'] = $val['accept_time']?date('Y-m-d H:i:s',$val['accept_time']):'未配送';
				$data[$key]['arrive_time'] = $val['arrive_time']?date('Y-m-d H:i:s',$val['arrive_time']):'未收货';
			}
		}
		return $data;
	}

	//周期购 延期&&取消延期
	public function periodic_delay(){
		$model = D('Mall_periodic_deliver');
		$id = $_POST['id'];
		$type = $_POST['type'];
		$r = $model->delay($id,$type,1);
		echo json_encode($r);
	}

	//周期购列表处理
	protected function deal_periodic_list($list){
		if($list){
			$express = M('express')->field('id,name,url')->select();

			foreach ($list as $key => $val) {
				$info = '第'.$val['deliver_num'].'期，配送时间：'.date('Y-m-d',$val['deliver_time']);
				$express_info = '';
				switch ($val['status']) {
					case '0':
						$list[$key]['status_text'] = '待配送';
						$list[$key]['delay_class'] = 'delay_reply';
						$list[$key]['delay_text']  = '延期';
						break;
					case '1':
						$list[$key]['status_text'] = '配送中';
						break;
					case '2':
						$list[$key]['status_text'] = '已配送';
						break;
					case '3':
						$list[$key]['status_text'] = '配送失败';
						break;
					case '4':
						$list[$key]['status_text'] = '已延期';
						$list[$key]['delay_class'] = 'delay_cancel';
						$list[$key]['delay_text']  = '取消延期';
						break;
					default:
						$list[$key]['status_text'] = '未知状态';
						break;
				}
				if($val['express_id']){
					foreach ($express as $k => $v) {
						if($v['id']==$val['express_id']){
							$express_info = '('.$v['name'].',单号：'.$val['express_number'].')';
						}
					}
				}
				$list[$key]['info'] = $info;
				$list[$key]['express_info'] = $express_info;
				$list[$key]['accept_time_text'] = $val['accept_time']?date('Y-m-d h:i:s',$val['accept_time']):'暂未发货';
				$list[$key]['arrive_time_text'] = $val['arrive_time']?date('Y-m-d h:i:s',$val['arrive_time']):'暂未到货';
			}
		}
		return $list;
	}

	//周期购，获取正在配送中的配送信息
	protected function get_current_one($list){
		$return = array();
		foreach ($list as $key => $val) {
			if($val['status']==0){
				$return = $val;
				break;
			}
		}
		if($return){
			$return['text'] = '(周期购第'.$return['deliver_num'].'期，配送时间：'.date('Y-m-d',$return['deliver_time']).')';
		}
		return $return;
	}

	public function write_weight(){
	    $data['real_weight'] = $_POST['real_weight'];
	    $where['id'] = $_POST['detail_id'];
        if(M('Shop_order_detail')->where($where)->save($data)){
            //称重商品所有都录入了则推送给配送员
            $detail = M('Shop_order_detail')->where(['id' => $_POST['detail_id']])->find();
            if ($detail) {
                $shopOrder = D('Shop_order')->where(['order_id' => $detail['order_id']])->find();
                $isAllWriteWeight = D('Shop_order')->isAllWriteRealWeight($detail['order_id']);
                if ($shopOrder['is_pick_in_store'] != 3 && $isAllWriteWeight) {
                    $store = D('Merchant_store')->field(true)->where(array('store_id' => $shopOrder['store_id']))->find();
                    $result = D('Deliver_supply')->saveOrder($shopOrder['order_id'], $store);
                    if ($result['error_code']) {
                        $this->success(L_('录入重量成功，写入配送表失败！'));
                    }
                }
            }
            $this->success(L_('录入成功！'));
        }else{
            $this->error(L_('录入失败！'));
        }
    }

	public function shop_edit()
	{
		if (IS_POST) {
			// 开启店员权限功能 店小二没权限
			if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
				$this->error(L_('您当前身份为【店小二】，没有修改订单权限，请联系管理员！'));
			}

			$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
			$now_status = isset($_POST['now_status']) ? intval($_POST['now_status']) : 0;
			$store_id = $this->store['store_id'];
			$status = intval($_POST['status']);

			if ($order = D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id,'store_id'=>$store_id))->find()) {
                if($order['store_id']!=$this->staff_session['store_id']&&$order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'])){
                    $this->error(L_('无权限操作!'));
                }
                //赠送满减送优惠卷
				if($status == 2){
					$order_detail = M('Shop_order_detail')->where(array('order_id'=>$order_id))->select();
					if($order_detail){
						foreach($order_detail as $k=>$v){
							if($v['give_coupon_id']){
								$data['coupon_id'] = $v['give_coupon_id'];
								$data['uid'] = $order['uid'];
								$data['num'] = 1;
								$data['receive_time'] = time();
								$data['is_use'] = 0;
								M('Card_new_coupon_hadpull')->data($data)->add();
							}
						}
					}
				}
			    if($now_status != $order['status']){
                    $this->error(L_('检测到该订单已更新状态，将为您刷新最新订单状态！'),$_SERVER['HTTP_REFERER']);
                }
				if($order['group_id']>0){
					$team_info=M('mall_group_team')->where(array('id'=>$order['team_id']))->find();
					if(isset($team_info)){
						if($team_info['status']==0){
							$this->error(L_('该订单为拼团订单，该团尚在拼团中，暂不支持操作！'));
						}elseif($team_info['status']==3){
							$this->error(L_('该订单为拼团订单，该团已失败，暂不支持操作！'));
						}
					}
				}
				if ($status == 1 && $order['status'] > 0) {
					$this->error(L_('该单已接，不要重复接单'));
					exit;
				}
				if($status == 1 && $order['is_weight']==1){
			        $order_detail = M('Shop_order_detail')->where(array('order_id'=>$order['order_id']))->select();
			        $flag = false;
			        foreach($order_detail as $o_d){
			            if($o_d['sell_type']==2 && $o_d['real_weight']==0){
                            $flag = true;
                        }
                    }
                    if($flag){
                        $this->error(L_('该订单含称重商品，请录入重量后接单！'));
                    }
                }
				 if ($status == 5 && $order['status'] == 2) {
                    $this->error(L_('该单已确认消费，不能退款'));
                }
				if ($order['status'] == 4 || $order['status'] == 5) {
//					$this->error(L_('订单已取消，不能再做其他操作。'));
//					exit;
				}
				if ($order['is_refund']) {
					$this->error(L_('用户正在退款中~！'));
					exit;
				}
				if ($status == 4 || $status == 5) {
				    if ($order['paid']) {
				        $status = 4;
				    } else {
				        $status = 5;
				    }
				}
				$data = array('store_uid' => $this->staff_session['id']);
				//dump($status);exit;
				$data['status'] = $status;
				$data['last_staff'] = $this->staff_session['name'];
				$data['post_time']=$_SERVER['REQUEST_TIME'];
				$condition = array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id);
				$database = D('Shop_order');
				if ($order['platform'] == 1) {//eleme
				    $eleme= D('Eleme_shop')->getElemeObj($order['store_id']);
				    if ($eleme != false) {
				        if ($status == 1) {
				            $result = $eleme->getDataByApi("eleme.order.confirmOrderLite", array('orderId' => $order['platform_id']));
				        } elseif ($status == 2 || $status == 3) {
				            $result = $eleme->getDataByApi("eleme.order.receivedOrderLite", array('orderId' => $order['platform_id']));
				        } elseif ($status == 4) {
				            $result = $eleme->getDataByApi("eleme.order.agreeRefundLite", array('orderId' => $order['platform_id']));
				        } elseif ($status == 5) {
				            $result = $eleme->getDataByApi("eleme.order.cancelOrderLite", array('orderId' => $order['platform_id'], 'type' => 'fakeOrder', 'remark' => L_('无法取得联系')));
				        }
						if ($result['error']){
							fdump_sql($result,'elem_storestaff_result_pc');
						}
						if ($result['error'] && $result['error']['message'] != L_('操作失败，订单已确认')) {
				            $this->error(L_('饿了么返回的错误') . $result['error']['code'] . $result['error']['message']);
				            exit;
				        } else {
				            $data['last_time'] = time();
				            $database->where($condition)->save($data);
				            if($status==4){
                                $this->addUserNotice($order_id,$this->store['mer_id'],$store_id,$order['uid']);
                            }
				            if ($status == 1 && $order['is_pick_in_store'] == 5) {
				                $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
				                if ($result['error_code']) {
				                    D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $order['store_id']))->save(array('status' => 0, 'last_time' => time()));
				                    $this->error($result['msg']);
				                    exit;
				                }
				                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
				            } elseif ($status == 0 || $status == 5 || $status == 4) {
			                    D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 0));
				            }
				            $this->success(L_('操作成功'));
				            exit;
				        }
				    } else {
				        $this->error(L_('店铺对接饿了么有问题，请联系商家处理！'));
				        exit;
				    }
				} elseif ($order['platform'] == 2) {//meituan
				    $store = M('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
				    if (empty($store) || empty($store['meituan_token'])) {
				        $this->error(L_('店铺对接美团有问题，请联系商家处理！'));
				        exit;
				    }
				    import('@.ORG.Meituan');
				    $meituan = new Meituan($store['meituan_token']);
				    if ($status == 1) {
				        $result = $meituan->getDataByApi('waimai/order/confirm', array('orderId' => $order['platform_id']));
				    } elseif ($status == 2 || $status == 3) {
				        $this->error(L_('美团不支持确认消费'));
				        exit;
				    } elseif ($status == 4) {
				        $result = $meituan->getDataByApi('waimai/order/agreeRefund', array('orderId' => $order['platform_id'], 'reason' => L_('同意退款')));
				    } elseif ($status == 5) {
				        $result = $meituan->getDataByApi('waimai/order/cancel', array('orderId' => $order['platform_id'], 'reasonCode' => 2001, 'reason' => L_('地址无法配送')));
				    }

				    if ($result['error']) {
				        $this->error(L_('美团返回的错误：') . $result['msg']);
				        exit;
				    } else {
				        $data['last_time'] = time();
				        $database->where($condition)->save($data);
				        if ($status == 1 && $order['is_pick_in_store'] == 5) {
				            $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
				            if ($result['error_code']) {
				                D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $order['store_id']))->save(array('status' => 0, 'last_time' => time()));
				                $this->error($result['msg']);
				                exit;
				            }
				            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
				        } elseif ($status == 0 || $status == 5 || $status == 4) {
				            D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 0));
				        }

                        if($status==4){
                            $this->addUserNotice($order_id,$this->store['mer_id'],$store_id,$order['uid']);
                        }
				        $this->success(L_('操作成功'));
				        exit;
				    }
				}

				if ($order['is_pick_in_store'] == 3) {
					$data['status'] = $status = 1;
					$express_id = isset($_POST['express_id']) ? intval($_POST['express_id']) : 0;
					$express_number = isset($_POST['express_number']) ? htmlspecialchars($_POST['express_number']) : 0;
					if ($status == 2 && (empty($express_id) || empty($express_number))) $this->error(L_('快递公司和快递单号都不能为空。'));
					if ($order['paid'] == 0 && $status != 5 && $status != 4) {
						$this->error(L_('未付款的订单只能进行取消操作。'));
						exit;
					}
					/*只有未使用和商家确认的状态下才可以发货*/
					if($order['status']!=0 && $order['status']!=1){
						$this->error(L_('抱歉，当前订单状态不支持发货操作！'),$_SERVER['HTTP_REFERER']);
					}
					if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
						$order['paid'] = 0;
					}
					if ($order['paid'] == 0 && $status != 5 && $status != 4) {
						$notOffline = 1;
						if ($this->config['pay_offline_open'] == 1) {
							$now_merchant = D('Merchant')->get_info($order['mer_id']);
							if ($now_merchant) {
								$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
							}
						}
						if ($notOffline) {
							$this->error(L_('不支持线下支付。'));
							exit;
						}
						$data['paid'] = 1;
						$data['third_id'] = $order['order_id'];
					}
					$data['last_staff'] = $this->staff_session['name'];
					//if($order['periodic_type']=='0'){//非周期购
						$data['express_id'] = $express_id;
						$data['express_number'] = $express_number;
					//}
					$data['use_time'] = $_SERVER['REQUEST_TIME'];
					$data['last_time'] = $_SERVER['REQUEST_TIME'];
					if($order['periodic_type']){//周期购
						$p_data = array();
						$p_data['express_id']     = $express_id;
						$p_data['express_number'] = $express_number;
						$p_data['status'] = 1;
						$p_data['update_time'] = $_SERVER['REQUEST_TIME'];
						$p_data['accept_time'] = $_SERVER['REQUEST_TIME'];
						$p_data['deliver_uid'] = $this->staff_session['id'];

						$p_where = array();
						$p_where['id'] = intval($_POST['periodic_deliver_id']);
						if(M('Mall_periodic_deliver')->where($p_where)->data($p_data)->save()){
							unset($data['status']);
						}
					}
					if($order['ticket_goods_id']){
						if($_POST['ticket_id']){
							D('Mall_ticket_deliver')->ticket_deliver($_POST['ticket_id'],$this->staff_session['id'],$express_id,$express_number);
						}
					}
					if (D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {
						$phones = explode(' ', $this->store['phone']);
						if ($status == 1) {
							$log_data = [
								'order_id' =>  $order_id, 
								'status'   =>  2, 
								'name'     =>  $this->staff_session['name'], 
								'phone'    =>  $phones[0],
								'ext'      =>  $express_id.'/'.$express_number,
							];
							if(M('Shop_order_log')->where(['order_id' => $order_id, 'status' => 2])->count()>1){//如果之前已存在发货记录后面的状态均为修改
								$log_data['note'] = 'update';//修改操作
							}
							D('Shop_order_log')->add_log($log_data,$order['is_pick_in_store']);

							/*生成erp的销售出库单*/
							if(C('Config.open_yongyou_erp')==1){
								$now_order=M('Shop_order')->where(array('order_id'=>$order_id))->find();
								D('Shop_order')->create_delivery_form($now_order);
							}

                            //-----------------------京东确认订单start 2018-09-03--------------------------------------
                            if($order['jd_order_id']>0 && $order['jd_paid']==0){
                                $url_confirm = "https://bizapi.jd.com/api/order/confirmOrder";
                                $confirm_data['token'] = C('config.jd_access_token');
                                $confirm_data['jdOrderId'] = $order['jd_order_id'];
                                import('ORG.Net.Http');
                                $confirm_return = Http::curlPost($url_confirm,$confirm_data);
                                if($confirm_return['success']==false){
                                    fdump_sql(json_encode($confirm_return),'jd_confirm');
                                    //失败后再发起一次请求
                                    $confirm_return2 = Http::curlPost($url_confirm,$confirm_data);
                                    if($confirm_return2['success']==false){
                                        fdump_sql(json_encode($confirm_return2),'jd_confirm2');
                                        $this->error(L_("京东支付失败，请联系京东运营！订单已确认"));
                                    }else{
                                        D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save(array('jd_paid'=>1));
                                    }
                                }else{
                                    D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save(array('jd_paid'=>1));
                                }
                            }
                            //-----------------------京东确认订单end 2018-09-03--------------------------------------
						} elseif ($status == 2 && $order['status'] != 2 && $order['status'] != 3) {
							$this->shop_notice($order);
							D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 7, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
						} elseif ($status == 4) {
						    D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9, 'name' => $this->staff_session['name'], 'phone' => $phones[0], 'note' => L_('店员把此单修改成已退款')));
						    $database->check_refund($order);


						} elseif ($status == 5) {
						    D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10, 'name' => $this->staff_session['name'], 'phone' => $phones[0], 'note' => L_('店员把此单修改成已取消')));
						}
						/*更新记录的平台订单信息 2019/01/04 */
			            $from_type='shop';
			            if($order['order_from']==1){
			            	$from_type='mall';
			            }
			            D('System_order')->save_order_info($from_type,$order['order_id'],$data);

                        if($status==4){
                            $this->addUserNotice($order_id,$this->store['mer_id'],$store_id,$order['uid']);
                        }
						$this->success(L_("更新成功"));
					} else {
						$this->error(L_("更新失败，稍后重试"));
					}
				} else {
				    if ($order['paid'] == 0 && $status != 5 && $status != 4) {
						$this->error(L_('未付款的订单只能进行取消操作。'));
						exit;
					}
					//0未确认，1已确认，2已消费，3已评价，4已退款，5已取消，
					if ($status == 3) {
						$this->error(L_('您不能将该订单改成已评价状态。'));
						exit;
					}
// 					if ($status == 5 && $order['paid'] == 1) {
// 						$this->error('当前订单已支付，您不能将改成取消状态。');
// 						exit;
// 					}
					$supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 2))->find();

					if ($order['is_pick_in_store'] == 0 && $status == 2 && $supply && $supply['uid']) {//平台配送，当配送员接单后店员就不能把订单修改成已消费状态
						$this->error(L_('您不能将该订单改成已消费状态。'));
					}
					if ($status == 0 && $order['status'] == 1 && $order['is_pick_in_store'] < 2) {
						if ($supply && $supply['status'] > 1) {
							$this->error(L_('当前订单已进入了配送状态，不能修改成未确认状态。'));
							exit;
						}
					}

					if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
						$order['paid'] = 0;
					}
					if ($order['paid'] == 0 && $status != 5 && $status != 4) {
						$notOffline = 1;
						if ($this->config['pay_offline_open'] == 1) {
							$now_merchant = D('Merchant')->get_info($order['mer_id']);
							if ($now_merchant) {
								$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
							}
						}
						if ($notOffline) {
							$this->error(L_('不支持线下支付。'));
							exit;
						}
					}

					if ($status == 2 && $order['paid'] == 0) {//将未支付的订单，由店员改成已消费，其订单状态则修改成线下已支付！
						$data['third_id'] = $order['order_id'];
						$order['pay_type'] = $data['pay_type'] = 'offline';
						$data['paid'] = 1;
						$order['pay_time'] = $_SERVER['REQUEST_TIME'];
					}

					if ($status == 2) {
						$data['order_status'] = 6;//配送完成
						$supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 2))->find();
						if ($supply) {
							if ($supply['status'] < 2) {
								D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->delete();
							} else {
								D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 5));
							}
						}
						//只有完成才有送达时间
						$data['use_time'] = $_SERVER['REQUEST_TIME'];

						//检查含有称重商品的订单，是否需要部分退款
                        if($this->config['is_open_shop_weigh']==1 && $order['is_weight']==1){
                            D('Shop_order')->checkWeightOrder($order);
                        }
					} elseif ($status == 1) {
						$data['order_status'] = 1;
					}
					$data['last_time'] = $_SERVER['REQUEST_TIME'];


					// 周期购信息 已消费
					if($status == 2&&$order['periodic_type']&&$order['is_pick_in_store']==2){//周期购自提
						// 状态改为已自提
		                $p_data = array();
		                $p_data['status'] = 2;
		                $p_data['update_time'] = $_SERVER['REQUEST_TIME'];
		                $p_data['accept_time'] = $_SERVER['REQUEST_TIME'];
		                $p_data['arrive_time'] = $_SERVER['REQUEST_TIME'];
		                $p_data['deliver_uid'] = $this->staff_session['id'];
		                $p_where = array();
		                //获得最近需要配送的周期
		                $periodic_deliver =M('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>0))->order('deliver_num asc')->find();
		                $p_where['id'] = $periodic_deliver['id'];
		                if(M('Mall_periodic_deliver')->where($p_where)->data($p_data)->save()){
		               		unset($data['status']);
		                }
		            }

					if (D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {

					    if (($status == 0 || $status == 4 || $status == 5) && $order['is_pick_in_store'] < 2) {//is_pick_in_store : 配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
							D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 0,'deliver_status'=>0));
						}
						$phones = explode(' ', $this->store['phone']);
						if ($status == 5 || $status == 4) {
						    if ($this->store['dada_shop_id']) {
						        $dada = new Dada();
						        $dadaData = array();
						        $dadaData['order_id'] = $order['real_orderid'];//
						        $dadaData['cancel_reason_id'] = 4;
						        $dadaData['cancel_reason'] = L_('顾客取消订单');
						        $result = $dada->formalCancel($dadaData);
						    }
						    if($order['keloop_trade_no']!=''){
                                $keloop = new Keloop();
                                $Data = array();
                                $Data['trade_no'] = $order['keloop_trade_no'];
                                $Data['reason'] = L_('顾客取消订单');
                                $result = $keloop->cancelOrder($Data);
                                if($result['code']!=200){
                                    $this->error($result['message']);
                                }
                            }
			//顺丰配送取消订单
                            if ($order['sf_order_id'] != '') {
                                $merchantStore = D('Merchant_store')->field('shunfeng_is_open,shunfeng_dev_id,shunfeng_dev_key,shunfeng_shop_id')->where(['store_id' => $order['store_id'], 'shunfeng_is_open' => 1])->find();
                                $sfData = [
                                    'order_id' => $order['sf_order_id'],
                                    'order_type' => 1,
                                    'push_time' => time()
                                ];
                                $result = (new Shunfeng($merchantStore))->cancelorder($sfData);
                                if ($result['error_code'] != 0) {
                                    $this->error($result['message']);
                                }
                            }

                            //饿了么蜂鸟配送取消订单
                            if ($order['is_ele_delivery'] == 1) {
                                $merchantStore = D('Merchant_store')->field('ele_is_open,ele_app_id,ele_secret_key,ele_transport_name,ele_chain_store_code')->where(['store_id' => $order['store_id'], 'ele_is_open' => 1])->find();
                                $ele = new EleDeliveryNew();
                                try {
                                    $ele->setBusinessData('partner_order_code', $order['real_orderid']);
                                    //获取可取消原因
                                    $reasonList = $ele->getCancelReasonList();
                                    //预取消订单
                                    $ele->setBusinessData('order_cancel_code', $reasonList[0]['order_cancel_code'] ?? 0);
                                    $ele->preCancelOrder();
                                    //正式取消
                                    $ele->setBusinessData('order_cancel_role', 1);
                                    $ele->setBusinessData('order_cancel_other_reason', '店员取消订单');
                                    $ele->cancelOrder();
                                } catch (\Exception $e) {
                                    $this->returnCode(1, null, $e->getMessage());
                                }
                            }
							if($order['dingding_trade_no']!=''){
								//处理叮叮取消订单======>郭先森
								$merchantStore = D('Merchant_store')->where(array('store_id'=>$order['store_id'],'is_open_dingding'=>1))->find();
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
									'origin_id'=>$order['real_orderid'],
									'cancel_id'=>10000,
									'cancelReason'=>'其他',
									'timestamp'     => time(),
								);
								$res = $dingDing->orderCancel($postData);

								$res = json_decode($res,true);
								fdump($res,"dingdingorderCancel",true);
							}
						    D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10, 'name' => $this->staff_session['name'], 'phone' => $phones[0], 'note' => L_('店员把此单修改成已取消')));
						    $database->check_refund($order,$this->staff_session);
							//销量回滚reduce_stock_type
							if (($order['paid'] == 1 || $order['reduce_stock_type'] == 1) && $order['is_rollback'] == 0) {
								$goods_obj = D('Shop_goods');
								$details = D('Shop_order_detail')->field(true)->where(array('order_id' => $order_id))->select();
								foreach ($details as $menu) {
									$goods_obj->update_stock($menu, 1);//修改库存
								}
								D('Shop_order')->where(array('order_id' => $order_id))->save(array('is_rollback' => 1));
							}

						}

						if ($status == 2 && $order['status'] != 2 && $order['status'] != 3) {//当订单由未消费修改成已消费时做的通知
						    D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
							D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 7, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
							$this->shop_notice($order);
						}

						if ($status == 1 && $order['status'] == 0 && $order['is_pick_in_store'] != 2) {//接单
						    $insertSupply = true;
                            if ($order['is_pick_in_store'] != 3 && $order['is_weight'] == 1) {
                                //称重订单没有完全录入商品重量不写入到配送表
                                $isAllWriteRealWeight = (new Shop_orderModel())->isAllWriteRealWeight($order['order_id']);
                                !$isAllWriteRealWeight && $insertSupply = false;
                            }

                            if($insertSupply){
                                $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
                                if ($result['error_code']) {
                                    D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save(array('status' => 0, 'last_time' => time()));
                                    $this->error($result['msg']);
                                    exit;
                                }
                            }

							D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));

							/*生成erp的销售出库单*/
							if(C('Config.open_yongyou_erp')==1){
								$now_order=M('Shop_order')->where(array('order_id'=>$order_id))->find();
								D('Shop_order')->create_delivery_form($now_order);
							}
						}

						if ($status == 1 && $order['status'] == 0 ) {
		                    //接单后打印标签
		                    $orderDetail = D('Shop_order')->get_order_detail(array('order_id' => $order['order_id']), true);
		                    D('Label_printer')->shopOrderLabel($orderDetail);
						}

						/*更新记录的平台订单信息 2019/01/04 */
			            $from_type='shop';
			            if($order['order_from']==1){
			            	$from_type='mall';
			            }
			            D('System_order')->save_order_info($from_type,$order['order_id'],$data);
                        $merchant_money=M('Merchant_money_list')->where(array('order_id' => $order['real_orderid'], 'income' => 1))->find();//钱进
                        $merchant_money1=M('Merchant_money_list')->where(array('order_id' => $order['real_orderid'], 'income' => 2))->find();//钱出
                        if (($status == 5 || $status == 4) && $order['paid']==1 && $order['order_from']==1 && !empty($merchant_money) && empty($merchant_money1) && $order['pay_type']!='offline') {
                            $desc="店员取消订单";
                            $returnSystemTake = $returnTakePercent = 0;
                            if($merchant_money['system_take'] > 0){
                                $returnSystemTake = bcmul(bcdiv($order['price'],$merchant_money['money'],2), $merchant_money['system_take'],2);
                                $returnTakePercent = $merchant_money['percent'];
                            }
                            D('Merchant_money_list')->use_money($order['mer_id'],$order['price'],"mall",$desc,$order['real_orderid'],$returnTakePercent,$returnSystemTake);
                        }
                        if($status==4){
                            $this->addUserNotice($order_id,$this->store['mer_id'],$store_id,$order['uid']);
                        }
                        if($status==2 && $order['is_pick_in_store']==2){
                            $this->addUserNotice($order_id,$this->store['mer_id'],$store_id,$order['uid'],'核销成功，已经领取商品','您已经领取店铺自提商品，请核对订单。');
                        }
						$this->success(L_("更新成功"));
					} else {
						$this->error(L_("更新失败，稍后重试"));
					}
				}
			} else {
				$this->error(L_('不合法的请求'));
			}
		}
	}

	public function addUserNotice($order_id,$mer_id,$store_id,$uid,$title='',$content='',$business='shop')
    {
        //新增消息通知
        $url = '\app\common\model\service\user\UserNoticeService/addNotice';
        $param['type'] = 0;
        $param['business'] = $business;
        $param['order_id'] = $order_id;
        $param['mer_id'] = $mer_id;
        $param['store_id'] = $store_id;
        $param['title'] = $title ?: '商家手动取消订单';
        $param['content'] = $content ?: '商家手动取消订单，钱已退回，查看订单';
        $param['uid'] = $uid;
        invoke_v20_service($url, [$param]);
    }
	private function shop_notice($order, $is_staff = false)
	{
	    //验证增加商家余额
	    $order['order_type'] = 'shop';
		if ($is_staff) {
			if($order['card_id']){
				$use_result = D('Card_new_coupon')->user_coupon($order['card_id'], $order['order_id'], 'shop', $order['mer_id'], $order['uid']);
				if($use_result['error_code']){
					return array('error' => 1, 'msg' => $use_result['msg']);
				}
			}

			if($order['coupon_id']){
				$use_result = D('System_coupon')->user_coupon($order['coupon_id'], $order['order_id'], 'shop', $order['mer_id'], $order['uid']);
				if($use_result['error_code']){
					return array('error' => 1, 'msg' => $use_result['msg']);
				}
			}

			// 使用积分
			if ($order['score_used_count'] > 0) {
				$use_result = D('User')->user_score($order['uid'], $order['score_used_count'], L_('购买 X1 扣除X1',array('X1'=>$order['name'],'X2'=>C('config.score_name'))));
				if ($use_result['error_code']) {
					return array('error' => 1, 'msg' => $use_result['msg']);
				}
			}

			if(floatval($order['merchant_balance']) > 0){
				$use_result = D('Card_new')->use_money($order['uid'], $order['mer_id'], $order['merchant_balance'], L_('购买 X1 扣除会员卡余额',$order['real_orderid']));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			if(floatval($order['card_give_money']) > 0){
				$use_result = D('Card_new')->use_give_money($order['uid'], $order['mer_id'], $order['card_give_money'], L_('购买 X1 扣除会员卡赠送余额',$order['real_orderid']));
				if ($use_result['error_code']) {
					return array('error' => 1, 'msg' => $use_result['msg']);
				}
			}

            //使用商家积分
            if($order['mer_score_count']){
                $use_result = D('Card_new')->user_score($order['uid'], $order['mer_id'], $order['mer_score_count'], L_('购买 X1 扣除会员卡积分',$order['real_orderid']));
                fdump_api($use_result,'mer_score_use',1);
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

			$goods = D('Shop_order_detail')->field(true)->where(array('order_id' => $order['order_id']))->select();
			$goods_obj = D("Shop_goods");
			foreach ($goods as $gr) {
				$goods_obj->update_stock($gr);//修改库存
			}
			
			$order['system_status'] = 2;
            D('System_order')->add_order('shop',$order,$order['order_id']);

			$order['order_type'] = 'shop_offline';
		}
		//D('Merchant_money_list')->add_money($this->store['mer_id'],L_('用户在 ') . $this->store['name'] . ' 中消费' . floatval($order['price']) . C('config.Currency_txt').L_('记入收入'),$order);
		if ($order['union_mer_id']) {
			$pre_desc = L_('商家会员卡联盟----');
		}
		$order['desc']=$pre_desc.L_('用户在 X1 中消费X2记入收入',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt')));
		D('SystemBill')->bill_method($order['is_own'],$order);

		//商家推广分佣
		$now_user = M('User')->where(array('uid'=>$order['uid']))->find();
		D('Merchant_spread')->add_spread_list($order,$now_user,'shop',$now_user['nickname'].L_('用户购买X1商品获得佣金',['X1'=>$this->config['shop_alias_name']]));

		//积分
		if(C('config.open_extra_price')==1){
			$score = D('Percent_rate')->get_extra_money($order);
			if($score>0){
                $param['order_type'] = 'shop';
                $param['order_id'] = $order['order_id'];
				D('User')->add_score($order['uid'], floor($score),L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>C('config.extra_price_alias_name'))),0,$param);
			}

		}else{
			if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2)){
				if($order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
					$order['payment_money'] = 0;
				}
				if(C('config.open_score_get_percent')==1){
					$score_percent = C('config.score_get_percent')/100;
				}else{
					$score_percent = C('config.user_score_get');
				}
                if($order['order_from']==1){
                    $score_get = D('Percent_rate')->shop_get_score($order);
                } elseif($this->config['shop_goods_score_edit'] == 1){
					$score_get = D('Percent_rate')->shop_get_score($order);
					$score_get = D('Percent_rate')->get_user_add_score_times($score_get,$order['uid']);
				}else{
					$now_merchant = D('Merchant')->get_info($order['mer_id']);
					if($now_merchant['score_get_percent']>=0){
						$score_percent = $now_merchant['score_get_percent']/100;
					}
					$score_percent = D('Percent_rate')->get_user_add_score_times($score_percent,$order['uid']);
					if ($order['order_type'] = 'shop_offline') {
						$score_get  = round(($order['price']) *$score_percent, 2);
					} else {
						$score_get  = round(($order['payment_money'] + $order['balance_pay']) *$score_percent, 2);
					}
				}
				fdump_api(['no_extra_money' => [$order, $score_percent, $score_get]], 'shop_order/no_extra_money', 1);
                if ($score_get>0) {
	                $param['order_type'] = 'shop';
	                $param['order_id'] = $order['order_id'];
                    D('User')->add_score($order['uid'], $score_get, L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])),0,$param);
                    D('Scroll_msg')->add_msg('shop',$now_user['uid'],L_('用户X1于X2 中消费获得X3',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']). $this->store['name'],'X3'=>$this->config['score_name'])));
                }
			}
			D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])));
		}
		//一卡通
        $internal_card_pay = json_decode($order['internal_card_pay'], true);
        if($internal_card_pay){
            foreach($internal_card_pay as $card_id => $card_pay_money){
                if(empty(floatval($card_pay_money))){
                    continue;
                }
                $card = D('internal_membership_card')->where(array('card_id' => $card_id))->find();
                if(!$card){
                    $this->error(L_('一卡通不存在'));
                }
                if($card['balance_money'] < $card_pay_money){
                    $this->error(L_('一卡通余额不足'));
                }
                $result_balance = round(($card['balance_money'] - $card_pay_money) * 100 / 100, 2);
                D('internal_membership_card')->where(array('card_id' => $card_id))->save(array('balance_money' => $result_balance));
                //插入交易日志
                $condition_card_log['card_id'] = $card_id;
                $condition_card_log['operation_type'] = 2;
                $condition_card_log['mer_id'] = $order['mer_id'];
                $condition_card_log['store_id'] = $order['store_id'];
                $condition_card_log['business_type'] = $order['order_type'];
                $condition_card_log['before_balance'] = $card['balance_money'];
                $condition_card_log['after_balance'] = $result_balance;
                $condition_card_log['money'] = $card_pay_money;
                $condition_card_log['describe'] = '购买线下商品, 扣除余额'. $card_pay_money .'元';
                $condition_card_log['order_id'] = $order['real_orderid'];
                $condition_card_log['uid'] = $card['uid'];
                $condition_card_log['create_time'] = time();
                M('internal_membership_card_log')->add($condition_card_log);
                D('Internal_card')->send_wx_msg($card_id, '-'.getFormatNumber($card_pay_money), '线下购买商品');
            }
        }
		//短信
		$sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'shop');
		if ($this->config['sms_shop_finish_order'] == 1 || $this->config['sms_shop_finish_order'] == 3) {
			if (empty($order['phone'])) {
				$user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
				$order['phone'] = $user['phone'];
			}
			$sms_data['uid'] = $order['uid'];
			$sms_data['mobile'] = $order['userphone'];
			$sms_data['sendto'] = 'user';
			$sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$this->store['name'],'X2'=>$order['real_orderid']));
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_shop_finish_order'] == 2 || $this->config['sms_shop_finish_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $this->store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！',array('X1'=>$order['name'],'X2'=>$order['real_orderid']));
			Sms::sendSms($sms_data);
		}

 		//微信小程序订阅消息提醒 
        $wxapp_message_send = false;
        if($now_user['wxapp_openid'] && $order['order_from']!=1){
            $param['page'] = '/pages/shop_new/orderDetail/orderDetail?order_id='.$order['order_id'];
            $param['wxapp_openid'] = $now_user['wxapp_openid'];
            $param['type'] = 'order_status_change';
            // 获得商品信息
            $goods_name = D('Shop_order_detail')->where(['order_id'=>$order['order_id']])->order('id ASC')->find()['name'];
            $goodsCount = D('Shop_order_detail')->field('sum(num) as total')->where(['order_id'=>$order['order_id']])->find()['total'];
            $order['goods_desc'] = $goodsCount > 1 ? L_('X1等X2个商品',['X1'=>$goods_name,'X2'=>$goodsCount]) : $goods_name;

            $order['status_str'] = L_('已送达');
            $order['note'] = L_('您的订单已送达，请及时关注~');
            $param['data_info'] = $order;
            $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
        }
        
		//打印
		$printHaddle = new PrintHaddle();
		$printType = $is_staff == true ? 1 : 2;
		$printHaddle->printit($order['order_id'], 'shop_order', $printType);

        //配送完成给配送员增加余额
        $supply = D('Deliver_supply')->where(['item' => 2, 'order_id' => $order['order_id'], 'is_settlement' => 0])->find();
        if ($supply && $supply['uid'] > 0) {
            $model = '\app\deliver\model\service\DeliverUserService/addMoney';
            $params = [
                'uid' => $supply['uid'],
                'businessType' => 'shop',
                'businessId' => $supply['order_id'],
                'money' => $supply['deliver_user_fee'] > 0 ? $supply['deliver_user_fee'] : 0,
                'desc' => '配送收入',
                'tipMoney' => $supply['tip_price'] > 0 ? $supply['tip_price'] : 0,
                'tipDesc' => '小费收入',
            ];
            invoke_v20_service($model, $params);
            D('Deliver_supply')->where(['supply_id' => $supply['supply_id']])->save(['is_settlement' => 1]);
        }
	}

	// 餐饮
	private function store_notice($order, $is_staff = false)
	{
		if (!$order['business_type']) {
			$order['business_type'] = 'store';
		}

	    //验证增加商家余额
		if ($is_staff) {
			if($order['card_id']){
				$use_result = D('Card_new_coupon')->user_coupon($order['card_id'], $order['order_id'], $order['business_type'], $order['mer_id'], $order['uid']);
				if($use_result['error_code']){
					$this->error(L_('支付异常：商家优惠券扣除异常'));
				}
			}

			if($order['coupon_id']){
				$use_result = D('System_coupon')->user_coupon($order['coupon_id'], $order['order_id'], $order['business_type'], $order['mer_id'], $order['uid']);
				if($use_result['error_code']){
					$this->error(L_('支付异常：平台优惠券扣除异常'));
				}
			}

			// 使用积分
			if ($order['score_used_count'] > 0) {
				$use_result = D('User')->user_score($order['uid'], $order['score_used_count'], L_('购买 X1 扣除X2' ,array('X1'=>$order['name'],'X2'=>C('config.score_name'))));
				if ($use_result['error_code']) {
					$this->error(L_('支付异常：积分扣除异常'));
				}
			}

			if(floatval($order['merchant_balance']) > 0){
				$use_result = D('Card_new')->use_money($order['uid'], $order['mer_id'], $order['merchant_balance'], L_('购买 X1 扣除会员卡余额',$order['real_orderid']));
				if($use_result['error_code']){
					$this->error(L_('支付异常：扣除会员卡余额异常'));
				}
			}

			if(floatval($order['card_give_money']) > 0){
				$use_result = D('Card_new')->use_give_money($order['uid'], $order['mer_id'], $order['card_give_money'], L_('购买 X1 扣除会员卡赠送余额',$order['real_orderid']));
				if ($use_result['error_code']) {
					$this->error(L_('支付异常：扣除会员卡赠送余额异常'));
				}
			}
		}

        $printHaddle = new PrintHaddle();
        $printHaddle->printit($order['order_id'], 'store_order', 1);
	}

	public function store_order()
	{
		// 开启店员权限功能 店小二没权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您当前身份为【店小二】，没有查看优惠买单权限，请联系管理员！'));
		}

		$store_order = D('Store_order');
		import('@.ORG.merchant_page');
		$where = array('paid' => 1, 'store_id' => $this->store['store_id'], 'from_plat' => 1);

		$count = $store_order->where($where)->count();
		$p = new Page($count, 20);

		$sql = "SELECT s.*, u.nickname, u.phone FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE s.paid=1 AND s.store_id={$this->store['store_id']} AND s.from_plat=1 ORDER BY s.order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
		$order_list = $store_order->query($sql);
		foreach ($order_list as &$l) {
            $l['pay_type_show'] = D('Pay')->get_pay_name($l['pay_type'],$l['is_mobile_pay'], $l['paid'], '',$l);
            $l['pay_money'] = $l['balance_pay']+$l['payment_money']+$l['merchant_balance']+$l['card_give_money'];
			
            //一卡通支付金额
            $l['internal_card_pay'] = $l['internal_card_pay'] ? json_decode($l['internal_card_pay'], true) : [];
            $l['internal_card_pay_money'] = $l['internal_card_pay'] ? array_sum($l['internal_card_pay']) : 0;
		}
		$pagebar = $p->show();

		$this->assign(array('order_list' => $order_list, 'pagebar' => $pagebar));
		$this->display();
	}

	public function pick()
	{
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));
		if(empty($order)){
			$this->error(L_('订单不存在！'));
			exit;
		}
		if($order['store_id']!=$this->staff_session['store_id']){
            $this->error(L_('不是本店铺订单，无法操作！'));
            exit;
        }
        $pick_addr_ids = $this->staff_session['pick_addr_ids']?:'';
		$pick_order = D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->find();
		if (IS_POST) {
			if ($order['status'] == 2 || $order['status'] == 3) {
				$this->error(L_('订单已消费，不能分配！'));
				exit;
			}
			if ($order['status'] == 4 || $order['status'] == 5) {
				$this->error(L_('订单已取消，不能分配！'));
				exit;
			}
			if ($order['is_refund']) {
				$this->error(L_('用户正在退款中~！'));
				exit;
			}
			if ($order['paid'] == 0) {
				$this->error(L_('订单未支付，不能接单！'));
				exit;
			}
			$pick_id = isset($_POST['pick_id']) ? htmlspecialchars($_POST['pick_id']) : '';
			$pick_address = null;
			if ($pick_id) {
				$type = substr($pick_id, 0, 1);
				$type = $type == 's' ? 1 : 0;//0:表示商家添加的自提点，1：默认的店铺
				$pick_id = substr($pick_id, 1);
				$pick_address = D('Pick_address')->field(true)->where(array('id' => $pick_id, 'mer_id' => $this->store['mer_id']))->find();

			}
			if (empty($pick_address)) {
				$this->error(L_('没有分配自提点！'), U('Store/pick', array('order_id' => $order_id)));
				exit;
			}
            //验证是否有提交权限
            if($pick_addr_ids){
                $pick_addr_ids = explode(',',$pick_addr_ids);
                if(!in_array($pick_id,$pick_addr_ids)){
                    $this->error(L_('没有分配当前自提点权限！'), U('Store/pick', array('order_id' => $order_id)));
                }
            }
			if (empty($pick_order)) {
				D('Pick_order')->add(array('store_id' => $order['store_id'], 'order_id' => $order['order_id'], 'type' => $type, 'pick_id' => $pick_id, 'status' => 0, 'dateline' => time()));
				D('Shop_order')->where(array('order_id' => $order_id))->save(array('status' => 7, 'order_status' => 1, 'pick_id' => $pick_id));
				$phones = explode(' ', $this->store['phone']);
				D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 11, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));//分配到自提点
				$this->success(L_('分配成功！'), U('Store/pick', array('order_id' => $order_id)));
				exit;
			} else {
				$this->error(L_('不要重复分配！'), U('Store/pick', array('order_id' => $order_id)));
				exit;
			}
		}

		$user = D('User_adress')->where(array('adress_id' => $order['address_id'], 'uid' => $order['uid']))->find();
		$pick_addr = D('Pick_address')->get_pick_addr_by_merid($this->store['mer_id'],true,0,false,[],1,$pick_addr_ids);
		if ($order['pick_id']) {
			$t_pick = array();
			foreach ($pick_addr as $v) {
				if ('p' . $order['pick_id'] == $v['pick_addr_id']) {
					$t_pick = $v;
				}
			}
			$pick_addr = array($t_pick);
		} else {
			foreach ($pick_addr as &$v) {
				$v['range'] = getRange(getDistance($v['lat'], $v['long'], $user['latitude'], $user['longitude']));
			}
		}
		$this->assign('order', $order);
		$pick_order['pick_id'] = isset($pick_order['pick_id']) && $pick_order['pick_id'] ? ($pick_order['type'] == 1 ? 's' . $pick_order['pick_id'] : 'p' . $pick_order['pick_id']) : '';

		$this->assign('pick_order', $pick_order);
		$this->assign('pick_addr', $pick_addr);
		$this->display();
	}

	public function deliver_goods()
	{
		$database = D('Shop_order');
		$order_id = $condition['order_id'] = intval($_POST['order_id']);
		$condition['store_id'] = $this->store['store_id'];
		$order = $database->field(true)->where($condition)->find();
        //验证是否有提交权限
		if($this->staff_session['pick_addr_ids']&&$order['is_pick_in_store']==2){
            $pick_addr_ids = explode(',',$this->staff_session['pick_addr_ids']);
            if(!in_array($order['pick_id'],$pick_addr_ids)){
                $this->error(L_('没有分配当前自提点权限！'));
            }
        }
		if(empty($order)){
			$this->error(L_('订单不存在！'));
			exit;
		}
		if ($order['status'] == 2 || $order['status'] == 3) {
			$this->error(L_('订单已消费，不能发货！'));
			exit;
		}
		if ($order['status'] == 4 || $order['status'] == 5) {
			$this->error(L_('订单已取消，不能发货！'));
			exit;
		}
		if ($order['is_refund']) {
			$this->error(L_('用户正在退款中~！'));
			exit;
		}
		if ($order['paid'] == 0) {
			$this->error(L_('订单未支付，不能发货！'));
			exit;
		}
		$data = array('status' => 8);
		$data['last_staff'] = $this->staff_session['name'];
		if (D('Shop_order')->where(array('order_id' => $order_id))->save($data)) {//发货
			D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 1));
			$phones = explode(' ', $this->store['phone']);
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 12, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));//发货
			$this->success(L_('已发货'));
		} else {
			$this->error(L_('发货失败，稍后重试！'));
		}
	}

    public function goods()
    {
        $begin_time = strtotime(date('Y-m-d') . ' 00:00:01');
        $end_time = strtotime(date('Y-m-d') . ' 23:59:59');
        if (IS_POST) {
            $begin_time = isset($_POST['begin_time']) ? strtotime($_POST['begin_time']) : $begin_time;
            $end_time = isset($_POST['end_time']) ? strtotime($_POST['end_time']) : $end_time;
        }
        $this->assign(array(
            'end_time' => date('Y-m-d H:i:s', $end_time),
            'begin_time' => date('Y-m-d H:i:s', $begin_time)
        ));
        $data['begin_time'] = $begin_time;
        $data['end_time'] = $end_time;
        $data['store_id'] = $this->staff_session['store_id'];

        //店铺类型，1-快店；2-商城
        $shop_type=I('get.shop_type',1,'intval');
		if($shop_type==1){
			$data['order_from']=array('neq',1);
		}else{
			$data['order_from']=1;
		}
		if($this->staff_session['pick_addr_ids']){
            $data['is_check'] = "((o.is_pick_in_store <> 2) OR (o.is_pick_in_store = 2 AND FIND_IN_SET(o.pick_id,'".$this->staff_session['pick_addr_ids']."')))";
        }
        $this->assign('list', D('Shop_order')->order_count($data));
        $this->assign('shop_type',$shop_type);
        $this->display();
    }

    public function goods_sale()
    {
		// 开启店员权限功能 店小二没权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您当前身份为【店小二】，没有查看商销售统计权限，请联系管理员！'));
		}

        $begin_time = isset($_GET['begin_time']) ? strtotime($_GET['begin_time']) : strtotime(date('Y-m-d', strtotime('-1 day')) . ' 00:00:01');
        $end_time = isset($_GET['end_time']) ? strtotime($_GET['end_time']) : strtotime(date('Y-m-d', strtotime('-1 day')) . ' 23:59:59');
        $sortId = isset($_GET['sort_id']) ? intval($_GET['sort_id']) : 0;
        $orderFrom = isset($_GET['orderFrom']) ? intval($_GET['orderFrom']) : 0;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';


        $this->assign(array(
            'end_time' => date('Y-m-d H:i:s', $end_time),
            'begin_time' => date('Y-m-d H:i:s', $begin_time),
            'keyword' =>  $keyword
        ));

        //------
        $store_id = $this->store['store_id'];

        $shopGoodsSortDB = D('Shop_goods_sort');
        $sortList = D(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id, 'fid' => 0))->select();

        $where = 'o.paid=1 AND (o.status=2 OR o.status=3) AND o.store_id=' . $store_id;
        if ($sort = D(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sortId, 'store_id' => $store_id))->find()) {
            if ($sort['fid'] == 0) {
                $ids = $shopGoodsSortDB->getAllSonIds($sortId, $store_id);
                $ids && $where .= ' AND d.sort_id IN (' . implode(',', $ids) . ')';
            }
        }
        if ($begin_time&& $end_time) {
            $where .= ' AND o.pay_time>' . $begin_time . ' AND o.pay_time<' . $end_time;
        }
        if ($orderFrom) {
            if ($orderFrom == 1) {
                $where .= ' AND o.order_from=1';
            } elseif ($orderFrom == 3) {
                $where .= ' AND o.order_from=6';
            } else {
                $where .= ' AND o.order_from<>1 AND o.order_from<>6';
            }
        }
        if($keyword){
            $where .= " AND d.name like '%".$keyword."%' ";
        }
        //店铺类型，1-快店；2-商城
        $shop_type=I('get.shop_type',1,'intval');
		if($shop_type==1){
			$where.=' and o.order_from!=1';
		}else{
			$where.=' and o.order_from=1';
		}

        $sql = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . 'shop_order AS o INNER JOIN ' . C('DB_PREFIX') . 'shop_order_detail AS d ON d.order_id=o.order_id WHERE ' . $where;
        $count = M()->query($sql);
        $count = isset($count[0]['cnt']) ? intval($count[0]['cnt']) : 0;
        import('@.ORG.merchant_page');
        $p = new Page($count, 20);
        $sql = "SELECT `d`.*, `o`.order_from FROM " . C('DB_PREFIX') . 'shop_order AS o INNER JOIN ' . C('DB_PREFIX') . 'shop_order_detail AS d ON d.order_id=o.order_id WHERE ' . $where . ' LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $orders = M()->query($sql);

        $this->assign('sort_list', $sortList);
        $this->assign('sortId', $sortId);
        $this->assign('orderFrom', $orderFrom);
        $this->assign('list', $orders);
        $this->assign('shop_type', $shop_type);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

	/*
	 *	到店支付  begin
	 */
	public function store_arrival()
	{

		// 开启店员权限功能 店小二没权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您当前身份为【店小二】，没有店内收银权限，请联系管理员！'));
		}

		$store_order = D('Store_order');
		import('@.ORG.merchant_page');

		$where = "s.paid=1 AND s.store_id={$this->store['store_id']} AND s.from_plat in (2,3)";
		$count = $store_order->join(" as s LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid" )->where($where)->count();
		$p = new Page($count, 20);

		$sql = "SELECT s.*, u.nickname, u.phone FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE ".$where." ORDER BY s.order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
		$order_list = $store_order->query($sql);

		$offline_pay_list = M('Store_pay')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		foreach($offline_pay_list as $key=>$value){
			$offline_pay_array[$value['id']] = $value['name'];
		}

		foreach ($order_list as &$l) {
			if($l['pay_type'] == 'offline'){
				$l['pay_type_show'] = L_('线下支付');
				if($l['offline_pay'] && $offline_pay_array[$l['offline_pay']]){
					$l['pay_type_show'].= L_(' ('.$offline_pay_array[$l['offline_pay']].')');
				}
			}else{
				$l['pay_type_show'] = D('Pay')->get_pay_name($l['pay_type'], $l['is_mobile_pay']);
				if($l['from_plat']==3){
					$l['pay_type_show'].=  L_('(扫用户码)');
				}
			}
			$l['pay_money'] = $l['balance_pay']+$l['payment_money']+$l['merchant_balance']+$l['card_give_money']+$l['score_deducte']+$l['card_price']+$l['coupon_price'];

	        if ($l['card_discount']) {
	            $l['card_discount_money'] = round($l['price']-($l['price']-$l['no_discount_money'])*$l['card_discount']/10-$l['no_discount_money'],2);
	        }else{
	            $l['card_discount_money'] = 0;
	        }

        	$offline_money = $l['price'] - $l['card_discount_money'] - ($l['payment_money']+$l['score_deducte']+$l['card_price']+$l['coupon_price']+$l['merchant_balance']+$l['card_give_money']+$l['balance_pay']+$l['qiye_pay']);

	        $l['offline_money'] = $offline_money>=0 ? round($offline_money,2) : 0;

			//一卡通支付金额
			$l['internal_card_pay'] = $l['internal_card_pay'] ? json_decode($l['internal_card_pay'], true) : [];
			$l['internal_card_pay_money'] = $l['internal_card_pay'] ? array_sum($l['internal_card_pay']) : 0;
		}

		$pagebar = $p->show();

		$this->assign(array('order_list' => $order_list, 'pagebar' => $pagebar));
		$this->display();
	}

	//修改桌台服务费
	public function exit_table_service(){
		$table_service_payment = floatval($_POST['table_service_payment']);
		$business_id = intval($_POST['business_id']);
		$model = M('Foodshop_order');
		$where = array();
		$where['order_id'] = $business_id;
		$r = $model->where($where)->save(array('table_service_payment'=>$table_service_payment));
		if($r){
			echo 1;
		}else{
			echo 0;
		}
	}

	public function store_arrival_add(){
		// 开启店员权限功能 店小二没权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您当前身份为【店小二】，没有结账权限，请联系管理员！'));
		}

		if(IS_POST){
			$data = array('store_id' => $this->store['store_id']);
			$data['mer_id'] = $this->store['mer_id'];
			$data['orderid'] = date("YmdHis") . mt_rand(10000000, 99999999);
			$data['name'] = $_POST['pay_title'] ? $_POST['pay_title'] : L_('顾客现场自助支付').'-'. $this->store['name'];
			$data['desc'] = $_POST['txt_info'];
			$data['total_price'] = $_POST['total_price'];
			$data['price'] = $_POST['pay_money'];
			$data['dateline'] = time();
			$data['from_plat'] = 2;
			$data['staff_id'] = $this->staff_session['id'];
			$data['staff_name'] = $this->staff_session['name'];
			$data['extra_price'] = $_POST['extra_price'];
			$data['source'] = 'pc_staff_pay_auto';
            $data['discount_price'] = $_POST['discount_money']; // 买单优惠
            $data['no_discount_money'] = $_POST['no_discount'] ? $_POST['no_discount'] : 0;
            $data['mer_table_discount'] = $_POST['mer_table_discount'] ? $_POST['mer_table_discount'] * 10 : 0;
            $data['mer_table_scale'] = $_POST['mer_table_scale'] ? $_POST['mer_table_scale'] : 0;

            $card_discount = $_POST['card_discount'] ? $_POST['card_discount'] : 0; // 商家会员卡折扣
            $card_discount_money = $_POST['card_discount_money'] ? $_POST['card_discount_money'] : 0;


            if (trim($_POST['phone'])&&$this->config['is_open_pay_bind_user']) { // 绑定用户
				$now_user = D('User')->get_user($_POST['phone'],'phone');
				if ($now_user) {
					$data['uid'] = $now_user['uid'];
            		$data['vip_discount_money'] = $_POST['vip_discount_money']?$_POST['vip_discount_money']:0; // 平台会员等级优惠

            		//检查会员卡的信息余额
            		if ($card_discount_money) { // 使用会员卡折扣
            			if ($card = D('Card_userlist')->field(true)->where(array('id' => $_POST['card_id'], 'mer_id' => $this->store['mer_id']))->find()) {
							$card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
							if ($card['status']==0) $this->error(L_('会员卡已禁用'));
							if (empty($card_source)) $this->error(L_('会员卡不可用'));

		            		$data['card_discount'] = $card_source['discount']; // 会员折扣
		            		$data['price'] += $card_discount_money;
						} else {
							$this->error(L_('会员卡不可用'));
						}
            		}

				}
            }

			if($_POST['from_scan']){
				$data['uid'] = intval($_POST['uid']);
				$data['payid'] = $_POST['payid'];
				//$this->success('SCAN_PAY_SUCCESS');
			}
			$now_user = D('User')->get_user($_POST['user_phone'],'phone');
			if(!empty($_POST['user_phone'])) {
				if (empty($now_user)) {
					if(!$this->config['international_phone'] && !preg_match('/^[0-9]{11}$/',$_POST['user_phone'])){
						$this->error(L_('请输入有效的手机号，无法自动注册'));
					}
					$data_user['phone'] = $_POST['user_phone'];
					D('User')->autoreg($data_user);
				} else {
					$data['uid'] = $now_user['uid'];
				}
			}
            if ($_POST['business_type']) {
                $data['business_type'] = $_POST['business_type'];
                $data['business_id'] = $_POST['business_id'];
                if ($data['business_type'] == 'foodshop') {
					// 开启店员权限功能 修改价格权限
					if (C('config.open_staff_power')&&$this->staff_session['is_change'] == 0) {
						$now_food_order = D('Foodshop_order')->get_order_detail(array('order_id' => $data['business_id'], 'store_id' => $this->store['store_id']), 3);
						$food_money = D('Foodshop_order')->count_price($now_food_order);

						if ($food_money != $_POST['total_price']) {
							$this->error(L_('您没有修改价格权限，请联系管理员！'));
						}
					}

                    // $now_food_order = D('Foodshop_order')->field('order_id, status, book_price')->where(array('order_id' => $data['business_id']))->find();
                    if ($now_food_order && $now_food_order['status'] > 2) {
                        $this->error(L_('此单已完成，不能再支付'));
                    }
                    if (!$data['uid']) {
						$data['uid'] = intval($now_food_order['uid']);
                    }
                }
            }

            //保存优惠券
            $coupon_discount_id = isset($_POST['coupon_discount_id']) ? intval($_POST['coupon_discount_id']) : 0;
            $coupon_discount_money = isset($_POST['coupon_discount_money']) ? floatval($_POST['coupon_discount_money']) : 0;
            $is_use_discount = isset($_POST['is_use_discount']) ? intval($_POST['is_use_discount']) : 0;

            if ($is_use_discount && $coupon_discount_id) {
                M('Plat_order')->where(array('business_id' => $_POST['business_id'], 'business_type' => 'foodshop'))->save(array('system_coupon_id' => $coupon_discount_id, 'system_coupon_price' => $coupon_discount_money));
            }

			if (floatval($data['price']) == 0) {
			    $data['paid'] = '1';
			    $data['pay_time'] = $_SERVER['REQUEST_TIME'];
			    $data['payment_money'] = 0;
			}


			$order_id = M("Store_order")->add($data);
			if($order_id){
				// 订单改变之后调用
				D("Store_order")->after_store_change($order_id,'cash',1);

				if($_POST['from_scan']){
					$this->success('SCAN_PAY_SUCCESS');
				}
			    if(floatval($data['price']) == 0){
			        $now_order = M("Store_order")->where(array('order_id' => $order_id))->find();
			        if($now_order['business_type']){
			            switch($now_order['business_type']){
			                case 'foodshop':
			                    // $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
			                    D('Foodshop_order')->after_pay($now_order['business_id'], array('is_own'=>0), 1);
			                    M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$now_order['pay_type'],'note' => $_POST['txt_info']))->save();
			                    break;
			            }
			        }

			        if (C('config.open_extra_price') == 1 && $now_order['uid'] > 0) {
			            $now_order['order_type'] = 'cash';
			            $score = D('Percent_rate')->get_extra_money($now_order);

			            if ($score > 0) {
			                M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',$score);
			                //到店付给用户积分时需要商家预支积分
			                M('Merchant')->where(array('mer_id'=>$this->store['mer_id']))->setInc('extra_price_pay_for_system',$score);
			                $send_score_data['mer_id'] = $this->store['mer_id'];
			                $send_score_data['score_count'] = $score;
			                $send_score_data['add_time'] = time();
			                M('Merchant_score_send_log')->add($send_score_data);

			                M('Merchant')->where(array('mer_id'=>$this->store['mer_id']))->setInc('extra_price_pay_for_system',$score);
			                D('User')->add_score($now_order['uid'], floor($score),$this->store['name'] . ' 中使用到店消费支付了' . floatval($now_order['price']) . C('config.Currency_txt').' 获得'.C('config.extra_price_alias_name'));
			            }

			        }
			        $this->success('SUCCESS');
			    } else {
			    	$now_order = M("Store_order")->where(array('order_id' => $order_id))->find();
			        if($now_order['business_type']){
			            switch($now_order['business_type']){
			                case 'foodshop': //保存备注
			                    M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('note' => $_POST['txt_info']))->save();
			                    break;
			            }
			        }
			        $this->success($order_id);
			    }
			}else{
				$this->error(L_('订单创建失败，请重试'));
			}
		}else{
			$now_store['discount_txt'] = unserialize($this->store['discount_txt']);
			$this->assign('has_discount',$now_store['discount_txt'] ? true : false);
			$this->assign('vip_discount_type', $this->store['vip_discount_type']);
			$this->assign('discount_type', isset($now_store['discount_txt']['discount_type']) ? $now_store['discount_txt']['discount_type'] : 0);
			$this->assign('discount_percent', isset($now_store['discount_txt']['discount_percent']) ? $now_store['discount_txt']['discount_percent'] : 0);
			$this->assign('condition_price', isset($now_store['discount_txt']['condition_price']) ? $now_store['discount_txt']['condition_price'] : 0);
			$this->assign('minus_price', isset($now_store['discount_txt']['minus_price']) ? $now_store['discount_txt']['minus_price'] : 0);

			$this->assign('platform_score', isset($this->now_merchant['platform_score']) ? $this->now_merchant['platform_score'] : 0);

			if($_GET['business_type']){
				switch($_GET['business_type']){
					case 'foodshop':
						$now_order = D('Foodshop_order')->get_order_detail(array('order_id'=>$_GET['business_id']));
						//定制桌次折扣优惠券
                        if(1 == $this->config['is_open_merchant_foodshop_discount']){
                            $tablesDiscount = D('Merchant_store_foodshop')->getTableDiscount($this->store['mer_id'],$this->store['store_id'],$_GET['business_id'],$now_order);
                            if($tablesDiscount!=false && $tablesDiscount['mer_discount']!=0){
                                $this->assign('tablesDiscount',$tablesDiscount);
                            }
                        }
						if ($now_order && $now_order['status'] > 2) {
						    $this->error(L_('订单已完成，不能再支付了'));
						}
						$pay_money = D('Foodshop_order')->count_price($now_order);
                        //注释原商家折扣功能---------start
						//$can_discount_money = D('Foodshop_order')->count_price($now_order, 1);
						//可以打折的金额
						//$now_order['total_money'] = $can_discount_money;
                        //注释原商家折扣功能---------end
						$now_coupon = D('System_coupon')->get_noworder_coupon_list($now_order, 'plat', '', $now_order['uid'], 'wap', 'foodshop');
						$coupon_discount = $now_coupon[0];

						// $data_foodshop_order['system_coupon_id']=  $coupon_discount['id'];
						// $data_foodshop_order['system_coupon_price'] = $coupon_discount['discount_money'];
						// //保存优惠信息到订单
						// if($coupon_discount['id'] ){
						// 	M('Plat_order')->where(array('business_id'=>$_GET['business_id'],'business_type'=>'foodshop'))->save($data_foodshop_order);
						// 	//$pay_money-=$coupon_discount['discount_money'];
						// }

						$this->assign('now_order',$now_order);
						$this->assign('coupon_discount',$coupon_discount);
						$pay_title = L_('餐饮订单：').$now_order['real_orderid'];
						break;
				}
				$this->assign('pay_money',$pay_money);
				$this->assign('pay_title',$pay_title);
			}


			if($this->now_merchant['status']!=1){
				if($this->now_merchant['merchant_end_time']>0 && $this->now_merchant['merchant_end_time']<time()){
					$this->error(L_('商家已过期，暂不可使用该功能！'));
				}
				$this->error(L_('商家已X1，暂不可使用该功能！',$this->merchant_status[$this->now_merchant['status']]));
			}

			// 使用老版本
			// 是否开启到店付，线下零售绑定用户支付
			if ($this->config['is_open_pay_bind_user'] == 1) {
				$this->display();
			}else{
				$this->display('store_arrival_add_old');
			}
		}
	}

	public function store_arrival_order(){
		$order_id  = $_GET['order_id'];
		$card_id  = $_GET['card_id'];

		$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
		switch($now_order['business_type']){
            case 'foodshop':
                $foodshop_order = M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
            	$type = 'meal';
                break;
            default:// 到店付（优惠买单）
            	$type = 'store';
        }

        // 减去会员卡折扣
		if ($now_order['card_discount']) {
		    $card_discount_money = round($now_order['price']-($now_order['price']-$now_order['no_discount_money'])*$now_order['card_discount']/10-$now_order['no_discount_money'],2);
        	$now_order['price'] = $now_order['price'] - $card_discount_money;
		}

		$this->assign('now_order',$now_order);

		// 会员卡信息
		$now_card = M("Card_userlist")->where(array('uid'=>$now_order['uid'],'mer_id'=>$this->store['mer_id'],'status'=>1))->find();
		$this->assign('now_card',$now_card);

		$orderprinter = M("Orderprinter")->where(array('store_id'=>$this->store['store_id']))->order('`is_main` DESC')->find();
		$this->assign('orderprinter',$orderprinter);

		// 线下支付方式
		$offline_pay_list = M('Store_pay')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		$this->assign('offline_pay_list',$offline_pay_list);
		$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
		$is_sub_alipay = $this->config['pay_alipay_sp_appid']!=''&& $this->config['pay_alipay_sp_prikey']!='' && $now_merchant['alipay_auth_code']!='';
		$this->assign('is_sub_alipay',$is_sub_alipay);

		if ($now_order['uid']) {
			$now_user = D('User')->get_user($now_order['uid']);
		}
		$this->assign('phone',$now_user ? $now_user['phone'] : '');
        //商家优惠券
        $mer_coupon = D('Card_new')->get_use_coupon_by_params($now_order['uid'], $this->store['mer_id'], $type);
        if ($mer_coupon) {
        	foreach ($mer_coupon as &$value) {
        		$value['platform'] = unserialize($value['platform']);
        	}
        }

	    //平台优惠券
	    $now_order['total_money'] =  $now_order['price'];

        if (!empty($now_order['business_type'])) {
            $now_coupon = D('System_coupon')->get_noworder_coupon_list($now_order, $type, $now_order['phone'], $now_order['uid'], '', $now_order['business_type'],'',false);
        } else {
            $now_coupon = D('System_coupon')->get_noworder_coupon_list($now_order, $type, $now_order['phone'], $now_order['uid'], '','','',false);
        }
        // $system_coupon = reset($now_coupon);

        //使用积分
        $score_can_use_count=0;
        $score_deducte=0;
        $user_score_use_percent = (float)$this->config['user_score_use_percent'];
        $user_score_use_condition = $this->config['user_score_use_condition'];
        $user_score_max_use = D('Percent_rate')->get_max_core_use($order_info['mer_id'], $type,$now_order['total_money'],$now_order);//不同业务不同积分

        $user_score_use_percent = (float)$this->config['user_score_use_percent'];

        $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);
        $now_user = D('User')->get_user($now_order['uid']);
        if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 ) {   //如果设置没有错误
            $total_ = $now_order['total_money'];

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

        $this->assign('score_deducte', $score_deducte);
        $this->assign('score_can_use_count', $score_can_use_count);
        $this->assign('mer_coupon', json_encode($mer_coupon));
        $this->assign('system_coupon', json_encode($now_coupon));

		// 使用老版本
		// 是否开启到店付，线下零售绑定用户支付
		if ($this->config['is_open_pay_bind_user'] != 1) {
			$this->display('store_arrival_order_old');
		}else{
			$this->display();
		}
	}

	public function ajax_get_score_discount(){
		$order_id  = $_REQUEST['order_id'];
		$type_  = $_POST['type'];
		$merc_id  = $_POST['merc_id'];
		$sysc_id  = $_POST['sysc_id'];
		$card_id  = $_POST['card_id'];
		$total_money  = floatval($_POST['total_money']);

		if ($type_ == 'shop') { // 快店
			$now_order = D("Shop_order")->get_order_by_orderid($order_id);
			$order_content = D('Shop_order_detail')->join('as o LEFT JOIN '.C('DB_PREFIX').'shop_goods g ON g.goods_id = o.goods_id')->field('o.*,g.score_max,score_percent')->where(array('order_id' => $order_id))->select();
			foreach ($order_content as &$trow) {
				$trow['name'] = $trow['spec'] ? $trow['name'] . ' (' . $trow['spec'] . ')' : $trow['name'];
				$trow['money'] = floatval($trow['price'] * $trow['num']);
			}

			foreach ($order_content as $val) {
				if(count($order_content)>1) {
					if($is_app){
						$str = $val['name'] . ' '.C('config.Currency_symbol') . $val['price'] . ' *' . $val['num'] . ' 等';
					}else{
						$str = $val['name'] . ' '.C('config.Currency_symbol') . $val['price'] . ' *' . $val['num'] . '  <b style="color:#BEBFBF;">等</b>';
					}
				}else{
					$str = $val['name'] . ' '.C('config.Currency_symbol') . $val['price'] . ' *' . $val['num'] ;
				}
				break;
			}
			$now_order['order_content'] = $order_content ? $order_content :array();
		}else{
			$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
			switch($now_order['business_type']){
	            case 'foodshop':
	                $foodshop_order = M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
	            	$type_ = 'meal';
	                break;
	            default:// 到店付（优惠买单）
	            	$type_ = 'store';
	        }
		}

        $now_user = D('User')->get_user($now_order['uid']);
        if (!$now_user&&intval($_POST['uid'])) {
        	$now_user = D('User')->get_user($_POST['uid']);
        }

        if ($total_money) {
        	$now_order['total_money']  = $total_money;
        }else{
        	$now_order['total_money'] =  $now_order['price'];
	        if ($card_id) { // 会员卡折扣
				$card = D('Card_userlist')->field(true)->where(array('id' => $card_id,'status'=>1))->find();
				if ($card) {
					$card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
	        		$now_order['total_money'] -= empty($card_source['discount']) ? 0 : $card_source['discount']*0.1*$now_order['total_money'];
				}
	        }
	        if ($merc_id) {
	            $mer_coupon = D('Card_new_coupon')->get_coupon_info($merc_id);
	        	$now_order['total_money'] -= empty($mer_coupon['discount']) ? 0 : $mer_coupon['discount'];
	        }

	        if ($sysc_id) {
	        	$system_coupon = D('System_coupon')->get_coupon_info($sysc_id);
	        	$now_order['total_money'] -= empty($system_coupon['discount']) ? 0 : $system_coupon['discount'];
	        }
        }

        //使用积分
        $score_can_use_count=0;
        $score_deducte=0;
        $user_score_use_percent = (float)$this->config['user_score_use_percent'];


        $user_score_use_condition = $this->config['user_score_use_condition'];
        $user_score_max_use = D('Percent_rate')->get_max_core_use($order_info['mer_id'], $type_,$now_order['total_money'],$now_order);//不同业务不同积分

        $user_score_use_percent = (float)$this->config['user_score_use_percent'];
        $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

        if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 ) {   //如果设置没有错误
            $total_ = $now_order['total_money'];

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
        exit(json_encode(array('error'=>false,'score_deducte'=>$score_deducte,'score_can_use_count'=>$score_can_use_count)));
	}

	public function store_arrival_print()
	{
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($_POST['order_id'], 'store_order', -1);
	}

	public function store_arrival_alipay_pay($order_id,$now_order){
		$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
		if($now_merchant['open_sub_mchid']==1 && $this->config['pay_alipay_sp_appid']!=''&& $this->config['pay_alipay_sp_prikey']!='' && $now_merchant['alipay_auth_code']!=''){
			$this->store_alipay_face_to_face($order_id,$now_order,$now_merchant['alipay_auth_code']);
			die;
		}
		if(empty($this->config['arrival_alipay_open'])){
			$this->error(L_('平台未开启支付宝收银'));
		}
		$param['app_id'] = $this->config['arrival_alipay_app_id'];
		$param['method'] = 'alipay.trade.pay';
		$param['charset'] = 'utf-8';
		$param['sign_type'] = $this->config['alipay_arrival_encrypt_type'] ? $this->config['alipay_arrival_encrypt_type'] : 'RSA';
		$param['timestamp'] = date('Y-m-d H:i:s');
		$param['version'] = '1.0';
		$biz_content = array(
				'out_trade_no' => 'store_'.$now_order['order_id'],
				'scene' => 'bar_code',
				'auth_code' => $_POST['auth_code'],
				'total_amount' => $now_order['price'],
				'subject' => $now_order['name'],
		);
		$param['biz_content'] = json_encode($biz_content,JSON_UNESCAPED_UNICODE);

		ksort($param);
		$stringToBeSigned = "";
		$i = 0;
		foreach ($param as $k => $v) {
			if (!empty($v) && "@" != substr($v, 0, 1)) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		$priKey = $this->config['arrival_alipay_app_prikey'];;
		$res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

		if(!function_exists('openssl_sign')){
			$this->error(L_('服务器不支持SSL，请联系管理员安装该插件。'));
		}

		if($param['sign_type'] == 'RSA2'){
			openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($stringToBeSigned, $sign, $res);
		}

		if(empty($sign)){
			$this->error(L_('支付宝收银商户密钥错误，请联系管理员解决。'));
		}

		$sign = base64_encode($sign);

		$param['sign'] = $sign;
		$requestUrl = "https://openapi.alipay.com/gateway.do?";
		foreach ($param as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);
		// echo $requestUrl;die;
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlGet($requestUrl);
		$returnArr = json_decode($return,true);
		$data['from_plat'] = 3; //扫用户支付码
		if(!empty($returnArr['alipay_trade_pay_response']) && "10000" == $returnArr['alipay_trade_pay_response']['code']){
			$now_user = D('User')->get_user($returnArr['alipay_trade_pay_response']['buyer_user_id'],'alipay_uid');
			$data['uid'] = $now_user ? $now_user['uid'] : '0';
			$data['paid'] = '1';
			$data['pay_time'] = strtotime($returnArr['alipay_trade_pay_response']['gmt_payment']);
			$data['pay_type'] = 'alipay';
			$data['third_id'] = $returnArr['alipay_trade_pay_response']['trade_no'];
			$data['payment_money'] = $returnArr['alipay_trade_pay_response']['receipt_amount'];
			if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
				$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                    foreach ($data as $kData=>$vData){
                        $now_order[$kData]=$vData;
                    }
                }
				$now_order['order_type']='cash';
				//商家余额增加
				//D('Merchant_money_list')->add_money($now_order['mer_id'],L_('用户到店支付宝支付记入收入'),$now_order);
				$now_order['desc']='用户到店支付宝支付记入收入';
				D('SystemBill')->bill_method($now_order['is_own'],$now_order);

				if($now_order['business_type']){
					switch($now_order['business_type']){
						case 'foodshop':
							$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
							D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
							M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
							break;
					}
				}

				D('Store_order')->store_score_get($now_order); // 增加积分

				// 订单改变之后调用
				D("Store_order")->after_store_change($order_id,'cash',2);

				$this->success(L_('支付成功！'));
			}else{
				$this->error(L_('支付失败！请联系管理员处理。'));
			}
		}else if(!empty($returnArr['alipay_trade_pay_response']) && "10003" == $returnArr['alipay_trade_pay_response']['code']){	//需要用户处理，下次查询订单
			$this->error(L_('需要用户确认支付，请在用户成功支付后再点击支付按钮。'));
		}else{
			if($returnArr['alipay_trade_pay_response']['sub_code'] == 'ACQ.TRADE_HAS_SUCCESS' && $now_order['paid'] != 1){
				$data['paid'] = '1';
				$data['pay_time'] = strtotime($returnArr['alipay_trade_pay_response']['gmt_payment']);
				$data['pay_type'] = 'alipay';
				$data['third_id'] = $returnArr['alipay_trade_pay_response']['trade_no'];
				$data['payment_money'] = $returnArr['alipay_trade_pay_response']['receipt_amount'];
				if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
					$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                    if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                        foreach ($data as $kData=>$vData){
                            $now_order[$kData]=$vData;
                        }
                    }
					$now_order['order_type']='cash';
					//商家余额增加
					//D('Merchant_money_list')->add_money($now_order['mer_id'],L_('用户到店支付宝支付记入收入'),$now_order);
					$now_order['desc']='用户到店支付宝支付记入收入';
					D('SystemBill')->bill_method($now_order['is_own'],$now_order);

					if($now_order['business_type']){
						switch($now_order['business_type']){
							case 'foodshop':
								$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
								D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
								M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
								break;
						}
					}

					D('Store_order')->store_score_get($now_order); // 增加积分

					$this->success(L_('支付成功！'));
				}else{
					$this->error(L_('支付失败！请联系管理员处理。'));
				}
			}else{
				$this->error(L_('支付失败！支付宝返回：').$returnArr['alipay_trade_pay_response']['sub_msg']);
			}
		}
	}

	public function store_arrival_offline_pay($order_id,$now_order){
		$data['paid'] = '1';
		$data['pay_time'] = $_SERVER['REQUEST_TIME'];
		$data['pay_type'] = 'offline';
		$data['offline_pay'] = $_POST['offline_pay'];
		if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
			$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
            if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                foreach ($data as $kData=>$vData){
                    $now_order[$kData]=$vData;
                }
            }
			if($now_order['business_type']){
				switch($now_order['business_type']){
					case 'foodshop':
						$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
						D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
						M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['price']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
						break;
				}
			}

			if(C('config.open_extra_price')==1&&$now_order['uid']>0){
				$now_order['order_type'] ='cash';
				$score = D('Percent_rate')->get_extra_money($now_order);

				if($score>0){
					M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',$score);
					//到店付给用户积分时需要商家预支积分
					M('Merchant')->where(array('mer_id'=>$this->store['mer_id']))->setInc('extra_price_pay_for_system',$score);
					$send_score_data['mer_id'] = $this->store['mer_id'];
					$send_score_data['score_count'] = $score;
					$send_score_data['add_time'] = time();
					M('Merchant_score_send_log')->add($send_score_data);

					M('Merchant')->where(array('mer_id'=>$this->store['mer_id']))->setInc('extra_price_pay_for_system',$score);
					D('User')->add_score($now_order['uid'], floor($score),$this->store['name'] . ' 中使用到店消费支付了' . floatval($now_order['price']) . C('config.Currency_txt').' 获得'.C('config.extra_price_alias_name'));

				}

			}

			D('Store_order')->store_score_get($now_order); // 增加积分

			// 订单改变之后调用
			D("Store_order")->after_store_change($order_id,'cash',2);

			$this->success(L_('支付成功！'));
		}else{
			$this->error(L_('支付失败！请联系管理员处理。'));
		}

	}

	public function store_arrival_pay(){
		// 开启店员权限功能 店小二没权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您没有权限结算，请联系管理员！'));
		}

		$order_id  = $_POST['order_id'];
		$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
        fdump_api(['store_arrival_pay' => [$_POST, $now_order, $this->config]], 'pay/storeArrivalPay',1);
        // 减去会员卡折扣
		if ($now_order['card_discount']) {
		    $card_discount_money = round($now_order['price']-($now_order['price']-$now_order['no_discount_money'])*$now_order['card_discount']/10-$now_order['no_discount_money'],2);
        	$now_order['price'] = $now_order['price'] - $card_discount_money;
		}

        $_POST['card_id'] && $card_id = $_POST['card_id']; // 商家优惠券id
        $_POST['card_price'] && $card_price = floatval($_POST['card_price']); // 商家优惠券金额
        $_POST['coupon_id'] && $coupon_id = $_POST['coupon_id']; // 平台优惠券id
        $_POST['coupon_price'] && $coupon_price = floatval($_POST['coupon_price']); // 平台优惠券金额
        $_POST['card_money'] && $card_money = floatval($_POST['card_money']); // 使用会员卡赠送余额
        ($_POST['is_use_score'] && $_POST['score_used_count']) && $score_used_count = $_POST['score_used_count']; // 积分使用数量
        ($_POST['is_use_score'] && $_POST['score_used_count']) && $score_deducte = floatval($_POST['score_deducte']); // 积分抵扣金钱
        $_POST['user_card_id'] && $user_card_id = $_POST['user_card_id']; // 使用会员卡id
        $price = floatval($_POST['price']); // 还需支付金额
        $_POST['mer_physical_card_no'] && $mer_physical_card_no = $_POST['mer_physical_card_no']; // 使用商家实体卡号

        //查询商家实体卡是否存在
        if($mer_physical_card_no){
            $mer_p_card = D('Merchant_physical_card')->where(['card_no'=>$mer_physical_card_no])->find();
            if(!$mer_p_card){
                $this->error(L_('卡号错误！'));
            }
            if($mer_p_card['mer_id']!=$now_order['mer_id']){
                $this->error(L_('卡号不是当前商家实体卡，无法使用！'));
            }
            if(($mer_p_card['money']-$mer_p_card['use_money'])<$now_order['price']){
                $this->error(L_('商家实体卡余额不足！'));
            }
            $data['mer_physical_card_no'] = $mer_physical_card_no;
        }
        
		// 优惠买单
		if (isset($_POST['price']) && $this->config['is_open_pay_bind_user']) {
			switch($now_order['business_type']){
	            case 'foodshop':
	                $foodshop_order = M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
	            	$type = 'meal';
	                break;
	            default:// 到店付（优惠买单）
	            	$type = 'store';
	        }

			//检查会员卡的信息余额
			$data['merchant_balance'] = 0;//商家余额
			$data['card_give_money'] = 0;//会员卡赠送余额
			$data['coupon_id'] = 0;//平台优惠券金额
			$data['card_id'] = 0;//商家优惠券的金额
			$coupon_price = 0;
			$sys_coupon_price = 0;
			$score_deducte_price = 0;

			$card_money = $card_money ? $card_money : 0;
			if ($card = D('Card_userlist')->field(true)->where(array('uid' => $now_order['uid'], 'mer_id' => $this->store['mer_id']))->find()) {
				$card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
				// if (empty($card_source)) $this->error('会员卡不可用');

				$user_card_total_moeny = $card['card_money'] + $card['card_money_give'];
				if ($user_card_total_moeny < $card_money) {
					$this->error(L_('会员卡余额不足'));
				}
				if ($card_money <= $card['card_money']) {
					$data['merchant_balance'] = $card_money;
					$data['card_give_money'] = 0;
				} else {
					$data['merchant_balance'] = $card['card_money'];
					$data['card_give_money'] = $card_money - $card['card_money'];
				}

				$price = $now_order['price'];

				// 商家优惠券
				if ($user_coupon = M('Card_new_coupon_hadpull')->field(true)->where(array('id' => $card_id, 'is_use' => 0))->find()) {
					if ($new_coupon = M(_view('card_new_coupon'))->field(true)->where(array('coupon_id' => $user_coupon['coupon_id']))->find()) {
						$now_time = time();
						if (($new_coupon['cate_name'] == 'all' || $new_coupon['cate_name'] == $type) && $new_coupon['end_time'] > $now_time && $price >= $new_coupon['order_money']) {
							$data['card_id'] = $card_id;
							$coupon_price = $data['card_price'] = $new_coupon['discount'];
						}
					}
				}
			} else {
				$card_money = 0;
			}

			// 平台优惠券
			if ($user_coupon = D('System_coupon_hadpull')->field(true)->where(array('id' => $coupon_id, 'is_use' => 0))->find()) {
				if ($new_coupon = M('System_coupon')->field(true)->where(array('coupon_id' => $user_coupon['coupon_id']))->find()) {
					if (($new_coupon['cate_name'] == 'all' || $new_coupon['cate_name'] == $type) && $new_coupon['end_time'] > $now_time && ($price-$coupon_price) >= $new_coupon['order_money']) {
						 //查看使用期限是否到期
			            // 用户可使用的天数
						if ($new_coupon['valid_time']) {
			                if ($user_coupon['receive_time']<$new_coupon['start_time']) {
			                    $end_time = $new_coupon['start_time'] + $new_coupon['valid_time']*86400;
			                }else{
			                    $end_time = $user_coupon['receive_time'] + $new_coupon['valid_time']*86400;
			                }
			                if ($end_time>time()) {
								$data['coupon_id'] = $coupon_id;
								$sys_coupon_price = $data['coupon_price'] = $new_coupon['discount'];
			                }
			            }else{
							$data['coupon_id'] = $coupon_id;
							$sys_coupon_price = $data['coupon_price'] = $new_coupon['discount'];
			            }
					}
				}
			}

			// 使用积分
	        $score_can_use_count=0;
	        $score_deducte=0;
			if ($_POST['is_use_score']) {
				// $data['score_used_count'] = $score_used_count;
				// $data['score_deducte'] = $score_deducte;
				// $score_deducte_price = $score_deducte;
				 //使用积分
		        $user_score_use_percent = (float)$this->config['user_score_use_percent'];

		        $now_order['total_money'] = $price - $coupon_price - $sys_coupon_price;
		        $user_score_use_condition = $this->config['user_score_use_condition'];
		        $user_score_max_use = D('Percent_rate')->get_max_core_use($this->store['mer_id'], $type,$now_order['total_money'],$now_order);//不同业务不同积分
		        $uid = $now_order['uid'] ? $now_order['uid'] : $card['uid'];
				$now_user = D('User')->get_user($uid);
		        $user_score_use_percent = (float)$this->config['user_score_use_percent'];
		        $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

		        if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 ) {   //如果设置没有错误
		            $total_ = $now_order['total_money'];

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
				$data['score_used_count'] = $score_can_use_count;
				$data['score_deducte'] = $score_deducte;
			}

			M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save();
		  	$now_order = M("Store_order")->where(array('order_id' => $order_id))->find();
            if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                foreach ($data as $kData=>$vData){
                    $now_order[$kData]=$vData;
                }
            }

			// 订单改变之后调用
			D("Store_order")->after_store_change($order_id,'cash',2);

			$now_order['price'] = floatval($price - $card_money - $coupon_price - $sys_coupon_price - $score_deducte);
			// $data['price']=0;
			// 应付金额为0
			if ($now_order['price'] == 0) {
				// 开启到店付，线下零售绑定用户，使用优惠
				$this->store_notice($now_order, true);

			    $data['paid'] = '1';
			    $data['pay_time'] = $_SERVER['REQUEST_TIME'];
			    $data['payment_money'] = 0;
			    if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
					// 订单改变之后调用
					D("Store_order")->after_store_change($order_id,'cash',2);

			        if($now_order['business_type']){
			            switch($now_order['business_type']){
			                case 'foodshop':
			                    D('Foodshop_order')->after_pay($now_order['business_id'], array('is_own'=>0), 1);
			                    M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$now_order['pay_type']))->save();
			                    break;
			            }
			        }

			        if (C('config.open_extra_price') == 1 && $now_order['uid'] > 0) {
			            $now_order['order_type'] = 'cash';
			            $score = D('Percent_rate')->get_extra_money($now_order);

			            if ($score > 0) {
			                M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',$score);
			                //到店付给用户积分时需要商家预支积分
			                M('Merchant')->where(array('mer_id'=>$this->store['mer_id']))->setInc('extra_price_pay_for_system',$score);
			                $send_score_data['mer_id'] = $this->store['mer_id'];
			                $send_score_data['score_count'] = $score;
			                $send_score_data['add_time'] = time();
			                M('Merchant_score_send_log')->add($send_score_data);

			                M('Merchant')->where(array('mer_id'=>$this->store['mer_id']))->setInc('extra_price_pay_for_system',$score);
			                D('User')->add_score($now_order['uid'], floor($score),$this->store['name'] . ' 中使用到店消费支付了' . floatval($now_order['price']) . C('config.Currency_txt').' 获得'.C('config.extra_price_alias_name'));
			            }

			        }
			    }
				$this->success('SUCCESS');
			}
		}

		if($_POST['balance_pay']){
			$data['smscode'] = $_POST['smscode'];
			$now_user = D('User')->get_user($_POST['phone'],'phone');
			$data['uid'] = $now_user['uid'];
			$data['name'] = $now_user['nickname'];
			$data['phone'] = $_POST['phone'];
			if ($now_user['now_money']<$now_order['price']) {
				$this->error(L_('余额不足！'));
			}
			// 开启到店付，线下零售绑定用户，使用优惠
			$this->store_notice($now_order, true);
			$this->store_arrival_balance_pay($order_id,$now_order,'Store_order',$data);
			die;
		}
        if($_POST['auth_type']=='ecard'){
            fdump_api($_POST['auth_type'], 'ecard_pay', 1);
            $post_internal_card_pay = I('post.internal_card_pay', [], 'trim,strip_tags');

            $internal_card_con = [];
            $internal_card_con['status'] = 1;
            if ($post_internal_card_pay) {
                $internal_card_con['card_id'] = array_keys($post_internal_card_pay);
            } else {
                $internal_card_con['phone'] = $_POST['phone'];
            }
            $staff_store_info = D('Merchant_store')->where(['store_id' => $now_order['store_id']])->find();
            if ($staff_store_info['is_internal_card_pay'] != 1) {
                $this->error(L_('店铺不支持一卡通支付'));
            }
            $card_list = M('internal_membership_card')->where($internal_card_con)->select();
            if(empty($card_list)){
                $this->returnCode(1,'',L_('没有一卡通信息！'));

            }
            $total_money = 0;
            foreach($card_list as $card){
                $total_money += $card['balance_money'];
            }
            fdump_api([$total_money, $card_list, $now_order['price']], 'ecard_pay', 1);

            $total_money = getFormatNumber($total_money);
            $now_user = D('User')->get_user($_POST['phone'],'phone');
            $data['uid'] = $now_user['uid'];
            $data['name'] = $now_user['nickname'];
            $data['phone'] = $_POST['phone'];
            try{
                if ($total_money<$now_order['price']) {
                    $this->returnCode(1,'',L_('余额不足！'));
                }else{
                    //计算一卡通扣钱
                    $money = $now_order['price'];
                    $internal_card_pay = array();
                    fdump_api('else', 'ecard_pay', 1);
                    foreach($card_list as $card){
                        if ($card['balance_money'] >= $money){
                            $internal_card_pay[$card['card_id']] = $money;
                            $money = 0;
                        }else{
                            $internal_card_pay[$card['card_id']] = $card['balance_money'];
                            $money -= $card['balance_money'];
                        }
                        fdump_api([$card, $money], 'ecard_pay', 1);

                        if ($money = 0){
                            break;
                        }
                    }
                }
            }catch (Exception $e) {
                fdump_api($e->getMessage(), 'ecard_pay', 1);
                $this->returnCode(1, '', $e->getMessage());
            }

            // 开启到店付，线下零售绑定用户，使用优惠
            $this->store_notice($now_order, true);
            $data['internal_card_pay'] = $internal_card_pay;
            fdump_api([$card_list, $internal_card_pay], 'ecard_pay', 1);
            $this->store_arrival_ecard_pay($order_id,$now_order,'Store_order',$data);
            die;
        }
		if($_POST['offline_pay']){
			// 开启到店付，线下零售绑定用户，使用优惠
			$this->store_notice($now_order, true);
			$this->store_arrival_offline_pay($order_id,$now_order);
			die;
		}

        if($mer_physical_card_no){
            // 开启到店付，线下零售绑定用户，使用优惠
            $this->store_notice($now_order, true);
            $this->store_arrival_mer_physical_card_pay($order_id,$mer_p_card);
            die;
        }

		if($this->config['open_juhepay']==1){
			// 开启到店付，线下零售绑定用户，使用优惠
			$this->store_notice($now_order, true);
			import('@.ORG.LowFeePay');

			$order_info['pay_type'] = $_POST['auth_type']=='alipay'?'alipay':'scan_weixin';
			$order_info['order_id'] =   'store_'.$order_id;

			$order_info['order_total_money'] =  $now_order['price'];
			$order_info['order_name'] =  $now_order['name'];
			$pay_info['auth_code'] =  $_POST['auth_code'];
			$lowfeepay = new LowFeePay('juhepay');

			$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$this->store['mer_id']))->find();
            if(!empty($mer_juhe)){
                $lowfeepay->userId =$mer_juhe['userid'];
                $order_info['is_own'] =1 ;
                $order_info['order_id'] =  $order_info['order_id'].'_1';
            }
			$go_pay_param= $lowfeepay->pay($order_info,$pay_info);

			if($go_pay_param['error']==1){
				$this->error($go_pay_param['msg']);
			}else{
				$data['paid'] = '1';
				$data['pay_time'] = time();
				$data['pay_type'] = $_POST['auth_type']=='alipay'?'alipay':'weixin';
				$data['third_id'] = $go_pay_param['thirdid'];
				if(!empty($mer_juhe)) {
					$data['is_own'] = 1;
				}

				$data['from_plat'] = 3; //扫用户支付码
				$data['payment_money'] = $now_order['price'];

				if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
					$now_order['order_type']='cash';
					$now_order['desc']=L_('用户到店扫码支付记入收入');
					//商家余额增加

					D('SystemBill')->bill_method($now_order['is_own'],$now_order);

					D('Store_order')->store_score_get($now_order); // 增加积分

					// 订单改变之后调用
					D("Store_order")->after_store_change($order_id,'cash',2);
					$this->success($go_pay_param['msg']);
				}
			}
			die;
		}

		//付呗
		if($this->config['pay_fubei_open']==1 && !empty($this->config['pay_fubei_mchid']) && !empty($this->config['pay_fubei_secret'])){
			// 开启到店付，线下零售绑定用户，使用优惠
			$this->store_notice($now_order, true);
			import('@.ORG.LowFeePay');

			$order_info['pay_type'] = $_POST['auth_type'];
			$order_info['order_id'] =   'store_'.$order_id;

			$order_info['order_total_money'] =  $now_order['price'];
			$order_info['order_name'] =  $now_order['name'];
			$pay_info['auth_code'] =  $_POST['auth_code'];
			$pay_info['repay'] =  $_POST['repay'];
			$lowfeepay = new LowFeePay('fubei');
			$go_pay_param= $lowfeepay->pay($order_info,$pay_info);

			if($go_pay_param['error']==1){
				if($go_pay_param['repay'] == 1){
					$this->ajaxReturn(['status' => 2, 'info'=>$go_pay_param['msg']]);
					exit;
				}
				$this->error($go_pay_param['msg']);
			}else{
				$data['paid'] = '1';
				$data['pay_time'] = time();
				$data['pay_type'] = $_POST['auth_type']=='alipay'?'alipay':'weixin';
				$data['third_id'] = $go_pay_param['thirdid'];

				$data['from_plat'] = 3; //扫用户支付码
				$data['payment_money'] = $now_order['price'];

				if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
					$now_order['order_type']='cash';
					$now_order['desc']=L_('用户到店扫码支付记入收入');
					//商家余额增加

					D('SystemBill')->bill_method($now_order['is_own'],$now_order);

					D('Store_order')->store_score_get($now_order); // 增加积分

					// 订单改变之后调用
					D("Store_order")->after_store_change($order_id,'cash',2);
					$this->success($go_pay_param['msg']);
				}
			}
			die;
		}

		if($_POST['auth_type'] == 'yzfpay'){
			// 开启到店付，线下零售绑定用户，使用优惠
			$this->store_notice($now_order, true);
			$this->store_arrival_yzfpay_pay($order_id,$now_order);
			die;
		}

        if ($_POST['auth_type'] == 'qiye') {
            $authCode = $_POST['auth_code'];

            $checkRs = D('Qiye')->checkPayAuthCode($authCode);
            if ($checkRs['error_no']) {
                $this->error($checkRs['error_msg']);
            }
            $payCode = $checkRs['retval'];
            M('Qiye_pay_code_record')->where(['id' => $payCode['id']])->save(['status' => 1, 'order_id' => $order_id, 'type' => 'store']);

            $data['uid'] = $payCode['uid'];
            $this->store_notice($now_order, true);
            $this->store_arrival_qiye_pay($order_id, $now_order, 'Store_order', $data);
            die;
        }

		import('ORG.Net.Http');
		$http = new Http();

		//开通威富通低费率支付
		$is_own = 0;
		$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
		if ($this->config['open_wftpay'] == 1) {
			$lowfeepay = new LowFeePay('wftpay');
			$order_info['auth_code'] = $_POST['auth_code'];
			$order_info['is_own'] = 0;
			$order_info['mer_id'] = $now_order['mer_id'];
			$order_info['out_trade_no'] = 'store_' . $now_order['order_id'];
			$order_info['total_fee'] = $now_order['price'];
			$order_info['order_name'] = $now_order['name'] ? $now_order['name'] : $this->store['name'].' - 线下收款';
			$return = $lowfeepay->scan_pay($order_info);

			if ($return['return_code'] == 'FAIL') {
				$this->error(L_('支付失败！支付返回：') . $return['return_msg']);
			}

			if ($return['err_code'] == 'USERPAYING') {
				$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
			}
			if ($return['result_code'] == 'FAIL') {
				$this->error(L_('支付失败！支付返回：') . $return['err_code_des']);
			}

			if ($return['trade_state'] == 'USERPAYING') {
				$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
			}

			$data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';

			if($data['pay_type'] == 'weixin' && $return['openid']){
				$now_user = D('User')->get_user($return['openid'],'openid');
			}else if($data['pay_type'] == 'alipay' && $return['alipay_uid']){
				$now_user = D('User')->get_user($return['alipay_uid'],'alipay_uid');
			}
		}elseif($this->config['open_weikebaba'] == 1){
			$lowfeepay = new LowFeePay('weikebaba');
			$order_info['auth_code'] = $_POST['auth_code'];
			$order_info['is_own'] = 0;
			$order_info['mer_id'] = $now_order['mer_id'];
			$order_info['out_trade_no'] = 'store_' . $now_order['order_id'];
			$order_info['total_fee'] = $now_order['price'];
			$order_info['order_name'] = $now_order['name'] ? $now_order['name'] : $this->store['name'].' - 线下收款';
			$order_info['pay_type'] = $_POST['auth_type']=='alipay' ? 2 : 1;
			$return = $lowfeepay->scan_pay($order_info);

			if ($return['return_code'] == 'FAIL') {
				$this->error(L_('支付失败！支付返回：') . $return['return_msg']);
			}

			if ($return['err_code'] == 'USERPAYING') {
				$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
			}
			if ($return['result_code'] == 'FAIL') {
				$this->error(L_('支付失败！支付返回：') . $return['err_code_des']);
			}

			if ($return['trade_state'] == 'USERPAYING') {
				$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
			}

			$data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';

			if($data['pay_type'] == 'weixin' && $return['openid']){
				$now_user = D('User')->get_user($return['openid'],'openid');
			}else if($data['pay_type'] == 'alipay' && $return['alipay_uid']){
				$now_user = D('User')->get_user($return['alipay_uid'],'alipay_uid');
			}
		}elseif($this->config['pay_scrcu_cash_open'] == 1 && $this->config['scrcu_mid']){
        	// 四川农信银行支付
			if($now_merchant['scrcu_mer_no']){
				$is_own = 1;
			}
        	import("@.ORG.pay.Scrcu");
            $pay_moodel = new Scrcu();
			$session_key = 'store_order_userpaying_' . $now_order['order_id'];
            if($_SESSION[$session_key]){
	            $return = $pay_moodel->scanQuery($now_order['order_id'], $now_merchant['scrcu_mer_no']);
	            if ($return['error']) {// 正在输入密码
					$_SESSION[$session_key] = '1';
					$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
	            }
			}else{
				// 付款
	            $return = $pay_moodel->scanPay($_POST['order_type'], $now_order['order_id'], $now_order['price'], $_POST['auth_code'], $now_merchant['scrcu_mer_no'] );
	            if ($return['error']) {
					$this->error(L_('支付失败！支付返回：') . $return['msg']);
	            }

	            sleep(1);
	            // 查询
	            $return = $pay_moodel->scanQuery($now_order['order_id'], $now_merchant['scrcu_mer_no']); 
	            if ($return['error']) {// 正在输入密码
					$_SESSION[$session_key] = '1';
					$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
	            }
			}

			if($_SESSION[$session_key] ){
				unset($_SESSION[$session_key]);
			}

            $data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
        }else{
			if($_POST['auth_type'] == 'alipay'){
				// 开启到店付，线下零售绑定用户，使用优惠
				$this->store_notice($now_order, true);
				$this->store_arrival_alipay_pay($order_id,$now_order);
				die;
			}

			$sub_mch_pay = false;

			if($this->config['open_sub_mchid']){
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

					$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
					$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
					$sub_mch_pay = true;
					$sub_mch_id = $pay_method['weixin']['sub_mch_id'];
					$is_own = 6 ;
				}
				if($this->store['open_sub_mchid'] && $this->store['sub_mch_id']>0){
					$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
					$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
					$sub_mch_pay = true;
					$sub_mch_id = $this->store['sub_mch_id'];
					$is_own = 3 ;
				}else if ( $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
					$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
					$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
					$sub_mch_pay = true;
					$sub_mch_id = $now_merchant['sub_mch_id'];
					$is_own = 2 ;
				}
			}

			import('ORG.Net.Http');
			$http = new Http();
			//auth_code 获取openid
			$user_openid_session_key = 'user_openid_userpaying_' . $order_id;
			if ($_SESSION[$user_openid_session_key]){
				$tmp_user_openid = $_SESSION[$user_openid_session_key];
			}else{
				$param = array();
				$param['appid'] = $this->config['pay_weixin_appid'];
				$param['mch_id'] = $this->config['pay_weixin_mchid'];
				$param['nonce_str'] = $this->createNoncestr();
				$param['auth_code'] = $_POST['auth_code'];
				$param['sign'] = $this->getWxSign($param);
				$return_openid_from_auth_code = Http::curlPostXml('https://api.mch.weixin.qq.com/tools/authcodetoopenid', $this->arrayToXml($param));
				if($return_openid_from_auth_code['return_code']=='SUCCESS' && $return_openid_from_auth_code['result_code'] =='SUCCESS'){
					$tmp_user_openid = $return_openid_from_auth_code['openid'];
					$_SESSION[$user_openid_session_key] = $tmp_user_openid;
				}
			}
			$now_user = D('User')->get_user($tmp_user_openid,'openid');
			$now_order['order_type'] = 'store';
			if($now_user){
				if($this->config['san_pay_score_coupon']==1 && $now_merchant['san_pay_score_coupon']) {
					$check_result = D('Store_order')->check_coupon_score($now_user, $now_order);
					$now_order['card_id'] = $check_result['card_id'];
					$now_order['coupon_id'] = $check_result['coupon_id'];
					$now_order['score_used_count'] = $check_result['score_used_count'];
					$now_order['card_price'] = $check_result['card_price'];
					$now_order['coupon_price'] = $check_result['coupon_price'];
					$now_order['score_deducte'] = $check_result['score_deducte'];
					if ($now_order['card_id']) {
						$now_coupon = D('Card_new_coupon')->get_coupon_by_id($now_order['card_id']);
						if (empty($now_coupon)) {
							$this->error(L_('支付异常：卡券不存在'));
						}
					}

					//判断平台优惠券
					if ($now_order['coupon_id']) {
						$now_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
						if (empty($now_coupon)) {
							$this->error(L_('支付异常：系统卡券错误'));
						}
					}

					$score_used_count = $now_order['score_used_count'];
					if ($now_user['score_count'] < $score_used_count) {
						$this->error(L_('支付异常：积分异常'));
					}
					$now_order['price'] = $check_result['total_money'];
				}
			}else{
				$access_token_array = D('Access_token_expires')->get_access_token();
				if (!$access_token_array['errcode']) {
					$result_user = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$tmp_user_openid.'&lang=zh_CN');
					$userinfo = json_decode($result_user,true);
					if(empty($userinfo['errcode'])) {
						//商家推广绑定关系
						D('Merchant_spread')->spread_add($now_order['mer_id'], $userinfo['openid'], 'storepay');
						$data_user = array(
							'openid' => $userinfo['openid'],
							'union_id' => ($userinfo['unionid'] ? $userinfo['unionid'] : ''),
							'nickname' => $userinfo['nickname'],
							'sex' => $userinfo['sex'],
							'province' => $userinfo['province'],
							'city' => $userinfo['city'],
							'avatar' => $userinfo['headimgurl'],
							'is_follow' => $userinfo['subscribe'],
							'source' => 'pc_staff_pay_auto2',
						);
						$reg_result = D('User')->autoreg($data_user);
						if ($reg_result['error_code']) {
							$now_user['uid'] = '0';
						} else {
							$now_user = D('User')->get_user($userinfo['openid'], 'openid');
						}
					}
				}else{
					$now_user['uid'] = '0';
				}
			}
			$success_pay_order = false;
			if($now_order['price']>0) {
				$session_key = 'store_order_userpaying_' . $order_id;
				if ($_SESSION[$session_key]) {
					$param = array();
					$param['appid'] = $this->config['pay_weixin_appid'];
					$param['mch_id'] = $this->config['pay_weixin_mchid'];
					$param['nonce_str'] = $this->createNoncestr();
					$param['out_trade_no'] = 'store_' . $now_order['order_id'];
					if ($sub_mch_pay) {
						$param['out_trade_no'] = 'store_' . $now_order['order_id'] . '_1';
						$param['sub_mch_id'] = $sub_mch_id;
					}
					$param['sign'] = $this->getWxSign($param);

					$return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/orderquery', $this->arrayToXml($param));
				} else {
					$param = array();
					$param['appid'] = $this->config['pay_weixin_appid'];
					$param['mch_id'] = $this->config['pay_weixin_mchid'];
					$param['nonce_str'] = $this->createNoncestr();
					$param['body'] = $now_order['name'];
					$param['out_trade_no'] = 'store_' . $now_order['order_id'];
					$param['total_fee'] = floatval($now_order['price'] * 100);
					$param['spbill_create_ip'] = get_client_ip();
					$param['auth_code'] = $_POST['auth_code'];
					if ($sub_mch_pay) {
						$param['out_trade_no'] = 'store_' . $now_order['order_id'] . '_1';
						$param['sub_mch_id'] = $sub_mch_id;
					}
					$param['sign'] = $this->getWxSign($param);
					$return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/micropay', $this->arrayToXml($param));
				}
				if ($return['return_code'] == 'FAIL') {
					$this->error((L_('支付失败！微信返回：') . $return['return_msg']));
				}

				if ($return['err_code'] == 'USERPAYING') {
					$_SESSION[$session_key] = '1';
					$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
				}
				if ($return['result_code'] == 'FAIL') {
					$this->error(L_('支付失败！微信返回：') . $return['err_code_des']);
				}

				if ($return['trade_state'] == 'USERPAYING') {
					$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
				}

				if($session_key){
					unset($_SESSION[$session_key]);
				}

				if(isset($return['trade_state']) && $return['trade_state'] != 'SUCCESS') {
					$this->error('支付失败！微信返回：'. $return['trade_state_desc']);
				}

				$data['pay_type'] = 'weixin';
				$success_pay_order = true;
			}else{
				$success_pay_order = true;
				$data['pay_type'] = '';
			}
			unset($_SESSION[$session_key]);

			if($success_pay_order) {
				if ($now_order['card_id']) {
					$use_result = D('Card_new_coupon')->user_coupon($now_order['card_id'], $now_order['order_id'], 'store', $now_order['mer_id'], $now_user['uid']);
					if ($use_result['error_code']) {
						$this->error(L_('支付异常：卡券抵扣错误'));
					} else {
						$data['card_id'] = $check_result['card_id'];
						$data['card_price'] = $check_result['card_price'];
					}
				}

				//如果使用了平台优惠券
				if ($now_order['coupon_id']) {
					$use_result = D('System_coupon')->user_coupon($now_order['coupon_id'], $now_order['order_id'], 'store', $now_order['mer_id'], $now_user['uid']);
					if ($use_result['error_code']) {
						$this->error(L_('支付异常：系统卡券抵扣错误'));
					} else {
						$data['coupon_id'] = $check_result['coupon_id'];
						$data['coupon_price'] = $check_result['coupon_price'];
					}
				}

				if ($score_used_count > 0) {
					$use_result = D('User')->user_score($now_user['uid'], $score_used_count, L_('购买 X1 扣除X2' ,array('X1'=>$now_order['name'],'X2'=>C('config.score_name'))));
					if ($use_result['error_code']) {
						$this->error(L_('支付异常：积分扣除异常'));
					} else {
						$data['score_used_count'] = $check_result['score_used_count'];
						$data['score_deducte'] = $check_result['score_deducte'];
					}
				}

				if(floatval($now_order['merchant_balance']) > 0){
					$use_result = D('Card_new')->use_money($now_order['uid'], $now_order['mer_id'], $now_order['merchant_balance'], L_('购买 X1 扣除会员卡余额',$now_order['real_orderid']));
					if($use_result['error_code']){
						$this->error(L_('支付异常：会员卡余额扣除异常'));
					}
				}

				if(floatval($now_order['card_give_money']) > 0){
					$use_result = D('Card_new')->use_give_money($now_order['uid'], $now_order['mer_id'], $now_order['card_give_money'], L_('购买 X1 扣除会员卡赠送余额',$now_order['real_orderid']));
					if ($use_result['error_code']) {
						$this->error(L_('支付异常：会员卡赠送余额扣除异常'));
					}
				}
			}
		}

		$data['uid'] = $now_user ? $now_user['uid'] : 0;
		$data['paid'] = '1';
		$data['pay_time'] = time();
		$data['third_id'] = $return['transaction_id'];
		$data['is_own'] = $return['is_own'] ? $return['is_own'] : $is_own;
		$data['from_plat'] = 3; //扫用户支付码
		$data['payment_money'] = $return['total_fee']/100;
		$now_order['price'] = $data['payment_money'] + $data['score_deducte'] + $data['card_price'] + $data['coupon_price'];
		if(M("Store_order")->where(array('order_id'=>$order_id, 'paid' => 0))->data($data)->save()){
			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			$href = C('config.site_url').'/wap.php?c=My&a=store_order_list';
			$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $this->store['name'], 'keyword1' => L_('店内收银支付提醒'), 'keyword2' => $now_order['orderid'], 'keyword3' => $now_order['price'], 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('付款成功，感谢您的使用')), $this->store['mer_id']);
			if($now_user['uid']){
				$order = M("Store_order")->where(array('order_id'=>$order_id))->find();
				if(C('config.open_extra_price')==1){
					$order['order_type'] ='cash';
					$score = D('Percent_rate')->get_extra_money($order);
					if($score>0){
						M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',$score);
						//到店付给用户积分时需要商家预支积分
						D('User')->add_score($order['uid'], floor($score),L_('X1 中使用到店消费支付了X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($now_order['price']) . C('config.Currency_txt'),'X3'=>C('config.extra_price_alias_name'))));
					}
				}else{
	            	if(C('config.merchant_buy_points_switch')!=1){ // 没有开启商家积分
						if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
							if($is_own && $this->config['user_own_pay_get_score']!=1){
								$order['payment_money'] = 0;
							}
							if(isset($this->config['store_score_get_percent'])){
								if($this->config['store_score_get_percent']==1){
									$this->config['score_get'] = $this->config['store_score_percent']/100;
								}else{
									$this->config['score_get'] =  $this->config['user_store_score_get'];
								}
							}
							$this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order['uid']);
							if($this->config['score_round_two']==1){
								M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',round(($order['payment_money'] + $order['balance_pay']) * $this->config['score_get'],2));
							}else{
								M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',round(($order['payment_money'] + $order['balance_pay']) * $this->config['score_get']));
							}

                            if ($this->config['score_get']>0 && ($order['payment_money'] + $order['balance_pay'])>0) {
                                D('User')->add_score($now_user['uid'], ($order['payment_money'] + $order['balance_pay']) * $this->config['score_get'], L_('在X1 中使用到店消费支付了X2 获得X3', array('X1' => $this->store['name'], 'X2' => floatval($now_order['price']) . C('config.Currency_txt'), 'X3' => $this->config['score_name'])));
                                D('Scroll_msg')->add_msg('store', $now_user['uid'], L_('用户X1于X2 中消费获得X3', array('X1' => str_replace_name($now_user['nickname']), 'X2' => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']) . $this->store['name'], 'X3' => $this->config['score_name'])));
                            }
                        }
						D('Userinfo')->add_score($now_user['uid'], $now_order['mer_id'], $now_order['price'], L_('在 X1 中使用到店消费支付了X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($now_order['price']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])));
					}
				}
			}

            D('Store_order')->store_score_get($now_order); // 增加积分

			// 订单改变之后调用
			D("Store_order")->after_store_change($order_id,'cash',2);

			$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
			$now_order['order_type']='cash';
			$now_order['desc']=L_('用户到店微信支付记入收入');
            fdump($now_order,'store',true);
			//商家余额增加
			//D('Merchant_money_list')->add_money($now_order['mer_id'],'用户到店微信支付记入收入',$now_order);
			D('SystemBill')->bill_method($now_order['is_own'],$now_order);

			D('Merchant_spread')->add_spread_list($now_order,$now_user,$now_order['order_type'],$now_user['nickname'].L_('用户到店支付获得佣金'));

			if($now_order['business_type']){
				switch($now_order['business_type']){
					case 'foodshop':
						$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
						D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
						M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','from_plat'=>3,'payment_type'=>$now_order['pay_type']))->save();
						break;
				}
			}

			$this->success(L_('支付成功！'));
		}else{
			$this->error(L_('支付失败！请联系管理员处理。'));
		}
	}

	public function store_arrival_yzfpay_pay($order_id,$now_order){
		$mer_id =$this->store['mer_id'];
        $now_merchant = D('Merchant')->get_info($this->store['mer_id']);

		$this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
		switch($this->config['merchant_ownpay']){
			case '0':
				$pay_method = D('Config')->get_pay_method(0, 0, true);
				break;
			case '1':
				$pay_method = D('Config')->get_pay_method(0, 0, true);
				if($mer_id){
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
					foreach($merchant_ownpay as $ownKey=>$ownValue){
						$ownValueArr = unserialize($ownValue);
						if($ownValueArr['open']){
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name'=>D('Pay')->getPayName($ownKey),'config'=>$ownValueArr);
						}
					}
				}
				break;
			case '2':
				$pay_method = array();
				if($mer_id){
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
					foreach($merchant_ownpay as $ownKey=>$ownValue){
						$ownValueArr = unserialize($ownValue);
						if($ownValueArr['open']){
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name'=>D('Pay')->getPayName($ownKey),'config'=>$ownValueArr);
						}
					}
				}
				if(empty($pay_method)){
					$pay_method = D('Config')->get_pay_method(0, 0, true);
				}
				break;
		}
		import('@.ORG.pay.Yzfpay');

		$now_order['order_name'] = $now_order['name']?$now_order['name']:$now_merchant['name'];
		$now_order['staff_id'] = $this->staff_session['id'];
		$now_order['platform'] ="APP";
		$now_order['order_trade_no'] ="store_".$now_order['order_id'].($pay_method['yzfpay']['config']['is_own']?'_1':'');
		$pay_class = new Yzfpay($now_order,$now_order['price'], 'yzfpay', $pay_method['yzfpay']['config'],0,1);
		$go_query_param = $pay_class->scan_barcode_pay($_POST['auth_code']);
		fdump_sql($go_query_param,'yzf_scan');
		if($go_query_param['success']==true && $go_query_param['result']['tradeStatus']=='SUCCESS'){
			$data['paid'] = '1';
			$data['pay_time'] = strtotime($go_query_param['result']['tradeFinishedDate']);
			$data['pay_type'] = 'yzfpay_offline';
			$data['third_id'] = $go_query_param['result']['tradeNo'];
			$data['payment_money'] = $go_query_param['result']['payAmt']/100;
			$data['is_own'] =$pay_method['yzfpay']['config']['is_own']?1:0 ;
			if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
				$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                    foreach ($data as $kData=>$vData){
                        $now_order[$kData]=$vData;
                    }
                }
				$now_order['order_type']='cash';
				//商家余额增加
				$now_order['desc']=L_('用户到店支付宝支付记入收入');
				D('SystemBill')->bill_method($now_order['is_own'],$now_order);

				if($now_order['business_type']){
					switch($now_order['business_type']){
						case 'foodshop':
							$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
							D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
							M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
							break;
					}
				}

				D('Store_order')->store_score_get($now_order); // 增加积分

				// 订单改变之后调用
				D("Store_order")->after_store_change($order_id,'cash',2);

				$this->success(L_('支付成功！'));
			}else{
				$this->error(L_('支付失败！请联系管理员处理。'));
			}
		}else if($go_query_param['success']==true && $go_query_param['result']['tradeStatus']=='WAITFORPAY'){

			sleep(2);
			$go_query_param = $pay_class->query_order($go_query_param['result']['outTradeNo'],  date("Y-m-d H:i:s",strtotime($go_query_param['result']['tradeFinishedDate'])),3);
			if($go_query_param['error']==0){
				$data['paid'] = '1';
				$data['pay_time'] = strtotime($go_query_param['pay_time']);
				$data['pay_type'] = 'yzfpay_offline';
				$data['third_id'] = $go_query_param['third_id'];
				$data['payment_money'] =$go_query_param['pay_money'];
				$data['is_own'] =$go_query_param['is_own'];

				if(M("Store_order")->where(array('order_id'=>$go_query_param['order_id']))->data($data)->save()){
					$now_order = M("Store_order")->where(array('order_id'=>$go_query_param['order_id']))->find();
                    if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                        foreach ($data as $kData=>$vData){
                            $now_order[$kData]=$vData;
                        }
                    }
					$now_order['order_type']='cash';

					$now_order['desc']=L_('用户到店翼支付记入收入');
					D('SystemBill')->bill_method($now_order['is_own'],$now_order);
					if($now_order['business_type']){
						switch($now_order['business_type']){
							case 'foodshop':
								$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
								D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
								M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
								break;
						}
					}
					// D('Store_order')->store_score_get($now_order); // 增加积分
					$this->success(L_('支付成功！'));
				}else{
					$this->error(L_('支付失败！请联系管理员处理。'));
				}
			}else{
				$this->error(L_('等待支付中'));
			}

		}else{	//需要用户处理，下次查询订单
			$this->error($go_query_param['errorMsg']);
		}
	}

    public function store_arrival_ecard_pay($order_id, $now_order, $table_name, $data = array())
    {
        if($now_order['price']>0){
            $business = 'store';
            foreach($data['internal_card_pay'] as $card_id => $money){
                $res = D('Internal_card')->use_money($card_id, $money,$business,$now_order);
            }
            if(!$res['error_code']){
                $data['paid'] = 1;
                $data['pay_time'] = time();
                $data['use_time'] = time();
                $data['balance_pay'] = $now_order['price'];

                if (M($table_name)->where(array('order_id' => $order_id))->data($data)->save()) {
                    $now_order = M($table_name)->where(array('order_id' => $order_id))->find();
                    if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                        foreach ($data as $kData=>$vData){
                            $now_order[$kData]=$vData;
                        }
                    }
                    $now_order['order_type'] = 'cash';

                    D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                    if($now_order['business_type']){
                        switch($now_order['business_type']){
                            case 'foodshop':
                                $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                                D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                                M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('name'=>$data['name'],'phone'=>$data['phone'],'status'=>3,'uid'=>$data['uid'],'price'=>$now_order['price']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>''))->save();
                                break;
                        }
                    }

                    if($this->config['merchant_buy_points_switch']!=1){
                        if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $now_order['score_discount_type']!=2) && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
                            $now_user  = D('User')->get_user($now_order['uid']);
                            if($this->config['store_score_get_percent']==1){
                                $score_get =$this->config['store_score_percent']/100;
                            }else{
                                $score_get = $this->config['user_store_score_get'];
                            }

                            if($now_order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
                                $now_order['payment_money'] = 0;
                            }
                            $score_get = D('Percent_rate')->get_user_add_score_times($score_get,$now_order['uid']);

                            D('User')->add_score(
                                $now_order['uid'],
                                (($now_order['payment_money'] + $now_order['balance_pay'])* $score_get),
                                L_('在X1使用X2消费X3获得X4',array('X1'=>$this->store['name'],'X2'=>$this->config['cash_alias_name'],'X3'=>floatval($now_order['price']) . C('config.Currency_txt'),'X4'=>$this->config['score_name']))
                            );
                            D('Scroll_msg')->add_msg(
                                'meal',
                                $now_user['uid'],
                                L_('用户X1于X2购买 X3成功并消费获得X4',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$now_order['order_name'],'X4'=>$this->config['score_name']))
                            );
                        }
                    }

                    D('Store_order')->store_score_get($now_order); // 增加积分

                    // 订单改变之后调用
                    D("Store_order")->after_store_change($order_id,'cash',2);

                    $this->returnCode(0);
                }else{
                    fdump(M(),'mmm');
                }
            }else {
                $this->returnCode(1,'',$res['msg']);
            }
        }
    }

	public function arrival_yzfpay_pay($order_id,$now_order,$table_name='',$data=array()){
		$mer_id =$this->store['mer_id'];
		$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
//		if(empty($this->config['arrival_alipay_open'])){
//			$this->error('平台未开启支付宝收银');
//		}
		$this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
		switch($this->config['merchant_ownpay']){
			case '0':
				$pay_method = D('Config')->get_pay_method(0, 0, true);
				break;
			case '1':
				$pay_method = D('Config')->get_pay_method(0, 0, true);
				if($mer_id){
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
					foreach($merchant_ownpay as $ownKey=>$ownValue){
						$ownValueArr = unserialize($ownValue);
						if($ownValueArr['open']){
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name'=>D('Pay')->getPayName($ownKey),'config'=>$ownValueArr);
						}
					}
				}
				break;
			case '2':
				$pay_method = array();
				if($mer_id){
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
					foreach($merchant_ownpay as $ownKey=>$ownValue){
						$ownValueArr = unserialize($ownValue);
						if($ownValueArr['open']){
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name'=>D('Pay')->getPayName($ownKey),'config'=>$ownValueArr);
						}
					}
				}
				if(empty($pay_method)){
					$pay_method = D('Config')->get_pay_method(0, 0, true);
				}
				break;
		}
		import('@.ORG.pay.Yzfpay');


		$now_order['order_name'] = $now_order['name']?$now_order['name']: $now_merchant['name'];
		$now_order['staff_id'] = $this->staff_session['id'];
		$now_order['platform'] ="APP";
		$now_order['order_trade_no'] ="shop_".$now_order['order_id'].($pay_method['yzfpay']['config']['is_own']?'_1':'');
		$pay_class = new Yzfpay($now_order,$now_order['price'], 'yzfpay', $pay_method['yzfpay']['config'],0,1);
		$go_query_param = $pay_class->scan_barcode_pay($_POST['auth_code']);
		fdump_sql($go_query_param,'yzf_scan');
		if($go_query_param['success']==true && $go_query_param['result']['tradeStatus']=='SUCCESS'){
			$data['paid'] = '1';
			$data['status'] = 2;
			$data['order_status'] = 5;
			$data['pay_time'] = strtotime($go_query_param['result']['tradeFinishedDate']);
			$data['pay_type'] = 'yzfpay_offline';
			$data['third_id'] = $go_query_param['result']['tradeNo'];
			$data['payment_money'] = $go_query_param['result']['payAmt']/100;
			$data['is_own'] =$pay_method['yzfpay']['config']['is_own']?1:0 ;
			if (M($table_name)->where(array('order_id'=>$order_id))->data($data)->save()) {
				$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
				$this->shop_notice($now_order, true);
				// D('Shop_order')->shop_score_get($now_order);
				$this->success(L_('支付成功！'));
			} else {
				$this->error(L_('支付失败！请联系管理员处理。'));
			}
		}else if($go_query_param['success']==true && $go_query_param['result']['tradeStatus']=='WAITFORPAY'){//需要用户处理，下次查询订单
			sleep(2);
			$go_query_param = $pay_class->query_order($go_query_param['result']['outTradeNo'],  date("Y-m-d H:i:s",strtotime($go_query_param['result']['tradeFinishedDate'])),3);

			if($go_query_param['error']==0) {
				$data['paid'] = '1';
				$data['status'] = 2;
				$data['order_status'] = 5;
				$data['pay_time'] = strtotime($go_query_param['pay_time']);
				$data['pay_type'] = 'yzfpay_offline';
				$data['third_id'] = $go_query_param['third_id'];
				$data['payment_money'] = $go_query_param['pay_money'];
				$data['is_own'] = $go_query_param['is_own'];
				if (M('Shop_order')->where(array('order_id' => $go_query_param['order_id']))->data($data)->save()) {
					$now_order = M('Shop_order')->where(array('order_id' => $order_id))->find();
					$this->shop_notice($now_order, true);
					// D('Shop_order')->shop_score_get($now_order);

					$this->success(L_('支付成功！'));
				} else {
					$this->error(L_('支付失败！请联系管理员处理。'));
				}
			}else{
				$this->error(L_('支付等待中'));
			}
		}else{
			//需要用户处理，下次查询订单
			$this->error($go_query_param['errorMsg']);
		}
	}

	public function store_alipay_face_to_face($order_id, $now_order,$auth_token){
		import('@.ORG.pay.Aop.AopClient');
		import('@.ORG.pay.Aop.SignData');
		import('@.ORG.pay.Aop.AlipayTradePayRequest');
		$aop = new AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $this->config['pay_alipay_sp_appid'];
		$aop->rsaPrivateKey = $this->config['pay_alipay_sp_prikey'];
		$aop->alipayrsaPublicKey=$this->config['pay_alipay_sp_pubkey'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='UTF-8';
		$aop->format='json';
		$request = new AlipayTradePayRequest();
		$biz_content = array(
				'out_trade_no' => 'store_'.$now_order['order_id'],
				'scene' => 'bar_code',
				'auth_code' => $_POST['auth_code'],
				'product_code' => 'FACE_TO_FACE_PAYMENT',
				'total_amount' => $now_order['price'],
				'subject' => $now_order['name'] ? $now_order['name'] : L_('现场自助支付'),
				'extend_params' => array(
					'sys_service_provider_id'=>$this->config['pay_alipay_pid'],
				),
		);

		$request->setBizContent(json_encode($biz_content,JSON_UNESCAPED_UNICODE));
		$result = $aop->execute ( $request,$_POST['auth_code'],$auth_token);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
			$data['paid'] = '1';
			$data['pay_time'] = strtotime($result->$responseNode->gmt_payment);
			$data['pay_type'] = 'alipay';
			$data['third_id'] = $result->$responseNode->trade_no;
			$data['payment_money'] = $result->$responseNode->receipt_amount;
			if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
				$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                    foreach ($data as $kData=>$vData){
                        $now_order[$kData]=$vData;
                    }
                }
				$now_order['is_own']=2;
				$now_order['order_type']='cash';
				if($auth_token){
					$now_order['payment_money']=0;
				}
//				商家余额增加
//				D('Merchant_money_list')->add_money($now_order['mer_id'],'用户到店支付宝支付记入收入',$now_order);
				$now_order['desc']='用户到店支付宝支付记入收入(支付宝服务商子商户)';
				D('SystemBill')->bill_method($now_order['is_own'],$now_order);

				if($now_order['business_type']){
					switch($now_order['business_type']){
						case 'foodshop':
							$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
							D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
							M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
							break;
					}
				}

				// 订单改变之后调用
				D("Store_order")->after_store_change($order_id,'cash',2);

				$this->success(L_('支付成功！'));
			}else{
				$this->error(L_('支付失败！请联系管理员处理。支付宝返回信息：').$result->$responseNode->sub_msg);
			}
		} elseif($resultCode == 10003) {
			$this->error(L_("等待用户验证支付"));
		}else{
			if($result->$responseNode->sub_code=='ACQ.TRADE_HAS_SUCCESS'){

				$data['paid'] = '1';
				$data['pay_time'] = strtotime($result->$responseNode->gmt_payment);
				$data['pay_type'] = 'alipay';
				$data['third_id'] = $result->$responseNode->trade_no;
				$data['payment_money'] = $result->$responseNode->receipt_amount;
				if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
					$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                    if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                        foreach ($data as $kData=>$vData){
                            $now_order[$kData]=$vData;
                        }
                    }
					$now_order['is_own']=2;
					$now_order['order_type']='cash';
					//商家余额增加
					//D('Merchant_money_list')->add_money($now_order['mer_id'],L_('用户到店支付宝支付记入收入'),$now_order);
					$now_order['desc']='用户到店支付宝支付记入收入';
					if($auth_token){
						$now_order['payment_money']=0;
					}
					D('SystemBill')->bill_method($now_order['is_own'],$now_order);

					if($now_order['business_type']){
						switch($now_order['business_type']){
							case 'foodshop':
								$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
								D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
								M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
								break;
						}
					}

					// 订单改变之后调用
					D("Store_order")->after_store_change($order_id,'cash',2);

					$this->success(L_('支付成功！'));
				}else{
					$this->error((L_('支付失败！请联系管理员处理。支付宝返回信息：').$result->$responseNode->sub_msg));
				}
			}else{
				$this->error(L_('支付失败！请联系管理员处理。支付宝返回信息：').$result->$responseNode->sub_msg);
			}
		}
	}

	public function arrival_alipay_face_to_face($order_id, $now_order,$auth_token){
		import('@.ORG.pay.Aop.AopClient');
		import('@.ORG.pay.Aop.SignData');
		import('@.ORG.pay.Aop.AlipayTradePayRequest');
		$aop = new AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $this->config['pay_alipay_sp_appid'];
		$aop->rsaPrivateKey = $this->config['pay_alipay_sp_prikey'];
		$aop->alipayrsaPublicKey=$this->config['pay_alipay_sp_pubkey'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='UTF-8';
		$aop->format='json';
		$request = new AlipayTradePayRequest();
		$biz_content = array(
				'out_trade_no' => 'shop_'.$now_order['real_orderid'],
				'scene' => 'bar_code',
				'auth_code' => $_POST['auth_code'],
				'product_code' => 'FACE_TO_FACE_PAYMENT',
				'total_amount' => $now_order['price'],
				'subject' => $now_order['name'] ? $now_order['name'] : L_('现场自助支付'),
		);

		$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
		$request->setBizContent(json_encode($biz_content,JSON_UNESCAPED_UNICODE));
		$result = $aop->execute ( $request,$_POST['auth_code'],$now_merchant['alipay_auth_code']);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
			$data['paid'] = 1;
			$data['status'] = 2;
			$data['order_status'] = 5;
			$data['pay_time'] = strtotime($result->$responseNode->gmt_payment);
			$data['use_time'] = time();
			$data['pay_type'] = 'alipay';
			$data['third_id'] =  $result->$responseNode->trade_no;
			$data['payment_money'] =$result->$responseNode->receipt_amount;
			if (M('Shop_order')->where(array('order_id' => $order_id))->data($data)->save()) {
				$now_order = M('Shop_order')->where(array('order_id' => $order_id))->find();
				if($auth_token){
					$now_order['payment_money']=0;
				}
				$this->shop_notice($now_order, true);
				$this->success((L_('支付成功！')));
			} else {
				$this->error(L_('支付失败！请联系管理员处理。'));
			}
		} elseif($resultCode == 10003) {
			$this->error(L_("等待用户验证支付"));
		}else{
			if($result->$responseNode->sub_code=='ACQ.TRADE_HAS_SUCCESS'){

				$data['paid'] = 1;
				$data['status'] = 2;
				$data['order_status'] = 5;
				$data['pay_time'] = strtotime($result->$responseNode->gmt_payment);
				$data['use_time'] = time();
				$data['pay_type'] = 'alipay';
				$data['third_id'] =  $result->$responseNode->trade_no;
				$data['payment_money'] = $result->$responseNode->receipt_amount;
				if (M('Shop_order')->where(array('order_id' => $order_id))->data($data)->save()) {
					$now_order = M('Shop_order')->where(array('order_id' => $order_id))->find();
					if($auth_token){
						$now_order['payment_money']=0;
					}
					$this->shop_notice($now_order, true);
					$this->success(L_('支付成功！'));
				} else {
					$this->error(L_('支付失败！请联系管理员处理。'));
				}
			}else{
				$this->error(L_('支付失败！请联系管理员处理。支付宝返回信息：').$result->$responseNode->sub_msg);
			}
		}
	}

	public function store_arrival_check(){
		if($this->config['open_juhepay']==1){
			import('@.ORG.LowFeePay');
			$order['orderNo'] = 'store' . '_' . $_POST['order_id'];
			$lowfeepay = new LowFeePay('juhepay');
			$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$this->store['mer_id']))->find();
			if(!empty($mer_juhe)){
				$lowfeepay->userId =$mer_juhe['userid'];
				$order_info['is_own'] =1 ;
				$order_info['order_id'] =  $order['orderNo'].'_1';
			}
			$go_pay_param = $lowfeepay->check($order);

			if($go_pay_param['error']==0){
				$date['pay_type'] = $go_pay_param['pay_type'];
				$date['payment_money'] = $go_pay_param['pay_money'];
				$date['third_id'] = $go_pay_param['third_id'];
				$date['is_own'] = $go_pay_param['is_own'];
				$date['paid'] = 1;
				$date['pay_time'] = $go_pay_param['pay_time'];

				if(M("Store_order")->where(array('order_id'=>$_POST['order_id']))->data($date)->save()){
					$now_order = M('Store_order')->where(array('order_id'=>$_POST['order_id']))->find();
                    if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                        foreach ($data as $kData=>$vData){
                            $now_order[$kData]=$vData;
                        }
                    }
					$now_order['order_type']='cash';
					$now_order['desc']=L_('用户到店扫码支付记入收入');
					//商家余额增加

					D('SystemBill')->bill_method($now_order['is_own'],$now_order);
				}
			}
		}

		$now_order = M('Store_order')->where(array('order_id'=>$_POST['order_id']))->find();
		if($now_order['paid']){
			$this->success(L_('支付成功！'));
		}else{
			$this->error(L_('还未支付'));
		}
	}

	//作用：产生随机字符串，不长于32位
	public function createNoncestr( $length = 32 ){
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}

	//微信支付密钥
	public function getWxSign($Obj)
	{
		foreach ($Obj as $k => $v)
		{
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
		$String = $String."&key=".$this->config['pay_weixin_key'];
		$String = md5($String);
		$result_ = strtoupper($String);
		return $result_;
	}

	//格式化参数，签名过程需要使用
	function formatBizQueryParaMap($paraMap, $urlencode){
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v)
		{
			if($urlencode)
			{
				$v = urlencode($v);
			}
			//$buff .= strtolower($k) . "=" . $v . "&";
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar='';
		if (strlen($buff) > 0)
		{
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}

	//数组转XML
	function arrayToXml($arr){
		$xml = "<xml>";
		foreach ($arr as $key=>$val){
			if (is_numeric($val)){
				$xml.="<".$key.">".$val."</".$key.">";
			}else{
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml.="</xml>";
		return $xml;
	}

	/*
	 *
	 *
	 *	到店支付  end
	 *
	 *
	 */
	public function ajax_foodshop_storestaff_order(){
		$where['store_id'] = $this->store['store_id'];
		$where['status'] = array('lt',3);
		$where['running_state'] = '1';
		$where['running_time'] = array('egt',$_POST['time']);
		$count = M("Foodshop_order")->where($where)->count();
		if($count > 0){
			$this->success(L_('您有新订单'));
		}else{
			$this->error($_SERVER['REQUEST_TIME']);
		}
	}

	public function foodshop()
	{
		$store_id = intval($this->store['store_id']);
		$where = array();
		if (IS_POST) {
			$real_orderid = isset($_POST['real_orderid']) ? htmlspecialchars($_POST['real_orderid']) : '';
			$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
			$phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
			$real_orderid && $where['real_orderid'] = array('like', "%$real_orderid%");
			$name && $where['name'] = array('like', "%$name%");
			$phone && $where['phone'] = array('like', "%$phone%");
			$orderid && $where['orderid'] = $orderid;

			$this->assign('meal_pass', $meal_pass);
			$this->assign('real_orderid', $real_orderid);
			$this->assign('name', $name);
			$this->assign('phone', $phone);
			$this->assign('orderid', $orderid);
		}
		$where['store_id'] = $store_id;

		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		$is_foodshop_new = isset($_GET['is_foodshop_new']) ? intval($_GET['is_foodshop_new']) : 0;
		$operate_status = isset($_GET['operate_status']) ? intval($_GET['operate_status']) : 88;
		$this->assign('operate_status', $operate_status);
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';

		$condition_where = 'WHERE o.mer_id = '.$this->store['mer_id'].' AND o.store_id = '.$this->store['store_id'];
		// 新餐饮订单
		if (C('config.open_meal_electronic_menu') && $this->store['open_from_shop']) {
            $condition_where .= ' AND o.is_foodshop_new =1';
            $condition_where .= ' AND o.status >0';
            // 新餐饮订单状态
			$order_sort = 'operate_status ASC ,pay_time DESC';
	        // 状态
	        if ($operate_status>=0) {
	            switch ($operate_status) {
	                case 0:
	            		$condition_where .= ' AND o.operate_status =' . $operate_status;
	            		$operate_status_where['f.operate_status'] = $operate_status;
	                    $order_sort = 'operate_status ASC ,pay_time ASC';
	                    break;
	                case 1:
	            		$condition_where .= ' AND o.operate_status =' . $operate_status;
	            		$operate_status_where['f.operate_status'] = $operate_status;
	                    $order_sort = 'operate_status ASC ,pay_time ASC';
	                    break;
	                case 2:
	            		$condition_where .= ' AND o.operate_status =' . $operate_status;
	            		$operate_status_where['f.operate_status'] = $operate_status;
	                    $order_sort = 'operate_status ASC ,pay_time DESC';
	                    break;
	                case 3:
	            		$condition_where .= ' AND o.operate_status =' . $operate_status;
	            		$operate_status_where['f.operate_status'] = $operate_status;
	                    $order_sort = 'operate_status ASC ,pay_time DESC';
	                    break;
	                case 88://未接单已接单
	            		$condition_where .= ' AND o.operate_status in (0,1)';
	            		$operate_status_where['f.operate_status'] = array('in','0,1');
	                    $order_sort = 'operate_status ASC ,pay_time ASC';
	                    break;
	            }
	        }
		}else{
            $condition_where .= ' AND o.is_foodshop_new =0';
			if ($type && $sort) {
				$order_sort .= $type . ' ' . $sort . ',';
				$order_sort .= 'order_id DESC';
			} else {
				$order_sort .= 'order_id DESC';
			}
		}

		if ($status != -1 && $status <= 10) {
			$condition_where .= ' AND o.status ='.$status;
		}else if($status == 11){
			$condition_where .= ' AND o.status <3 AND o.running_state=1';
		}
		$foodshop = M('Merchant_store_foodshop')->where(array('store_id'=>$this->store['store_id']))->field('account_type')->find();
		$account_type = $foodshop['account_type'];
		if($account_type['account_type']==2&&$status<3){
			//$condition_where .= ' AND o.status =-1';//此时不能查到任何订单
		}
        if (! empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'real_orderid') {
                $condition_where .= ' AND o.real_orderid =' . $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'orderid') {
                $condition_where .= ' AND o.orderid =' . $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'name') {
                $condition_where .= ' AND o.name ="' . $_GET['keyword'].'"';
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= ' AND o.phone =' . $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'third_id') {
                $condition_where .= ' AND p.third_id =' . $_GET['keyword'];
            }
        }

		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if($pay_type&&$pay_type!='balance'){
			$condition_where .=' AND p.pay_type ="'.$pay_type.'"';
		}else if($pay_type=='balance'){
			$condition_where .= "  AND  (`p`.`system_balance`<>0 OR `p`.`merchant_balance_pay` <> 0 )";
		}

		$period = array();
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}
			$period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
			$condition_where .=" AND  (o.create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
		}

		// 必须绑定桌台或者不绑定桌台已支付才显示
		$condition_where .= ' AND (o.unbind_table=0 OR o.status>=3)';

		$sql_cont = 'SELECT count(o.order_id) as count from pigcms_foodshop_order o LEFT JOIN (select a.order_id,a.business_id from pigcms_plat_order a,(select max(order_id) as order_id,business_id from pigcms_plat_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id and a.business_type="foodshop") p on o.order_id  = p.business_id '.$condition_where.' ORDER BY o.order_id DESC';
		$count  = M()->query($sql_cont);
		$count = $count[0]['count'];

		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		//$list = D("Foodshop_order")->where($where)->order($order_sort)->limit($p->firstRow . ',' . $p->listRows)->select();

		$sql = 'SELECT o.*,p.pay_type as pay_method from pigcms_foodshop_order o LEFT JOIN (select a.order_id,a.business_id,a.pay_type,a.third_id,a.system_balance,a.merchant_balance_pay from pigcms_plat_order a,(select max(order_id) as order_id,business_id from pigcms_plat_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id and a.business_type="foodshop") p on o.order_id  = p.business_id '.$condition_where.' ORDER BY '.$order_sort.' limit '.$p->firstRow.','.$p->listRows;

		$list = M('')->query($sql);

		$mer_ids = $store_ids = array();
		foreach ($list as $l) {
			$mer_ids[] = $l['mer_id'];
			$store_ids[] = $l['store_id'];
			$table_types[] = $l['table_type'];
			$tids[] = $l['table_id'];


		}


		$type_list = array();
		if ($table_types) {
			$temp_type_list = M('Foodshop_table_type')->field(true)->where(array('id' => array('in', $table_types)))->select();
			foreach ($temp_type_list as $tmp) {
				$type_list[$tmp['id']] = $tmp;
			}
		}
		$table_list = array();
		if ($tids) {
			$temp_table_list = M('Foodshop_table')->field(true)->where(array('id' => array('in', $tids)))->select();
			foreach ($temp_table_list as $temp) {
				$table_list[$temp['id']] = $temp;
			}
		}


		$store_temp = $mer_temp = array();
		if ($mer_ids) {
			$merchants = D("Merchant")->where(array('mer_id' => array('in', $mer_ids)))->select();
			foreach ($merchants as $m) {
				$mer_temp[$m['mer_id']] = $m;
			}
		}
		if ($store_ids) {
			$merchant_stores = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_stores as $ms) {
				$store_temp[$ms['store_id']] = $ms;
			}
		}
		foreach ($list as &$li) {
			$li['merchant_name'] = isset($mer_temp[$li['mer_id']]['name']) ? $mer_temp[$li['mer_id']]['name'] : '';
			$li['store_name'] = isset($store_temp[$li['store_id']]['name']) ? $store_temp[$li['store_id']]['name'] : '';

			$li['table_type_name'] = isset($type_list[$li['table_type']]) ? $type_list[$table_list[$li['table_id']]['tid']]['name'] . '(' . $type_list[$table_list[$li['table_id']]['tid']]['min_people'] . '-' . $type_list[$table_list[$li['table_id']]['tid']]['max_people'] . '人)' : '';
			$li['table_name'] = isset($table_list[$li['table_id']]) ? $table_list[$li['table_id']]['name'] : '';
			$li['show_status'] = D('Foodshop_order')->status_list[$li['status']];

			if ($li['status'] < 3) {
				if ($li['status'] < 1) {
				    $li['pay_type_show'] = L_('暂未支付');
				} else {
				    $li['pay_type_show'] = D('Pay')->get_pay_name($li['pay_method'], $li['is_mobile_pay']);
				}
			} elseif ($li['pay_type'] == 1) {//店员买单
				if ($store_order = D('Store_order')->field(true)->where(array('business_type' => 'foodshop', 'business_id' => $li['order_id'], 'paid' => 1))->find()) {
					if ($store_order['pay_type'] == 'offline') {
						if ($store_order['offline_pay'] && ($store_pay = D('Store_pay')->field('name')->where(array('id' => $store_order['offline_pay']))->find())) {
							$li['pay_type_show'] = L_('线下支付【').$store_pay['name'].L_('】');
						} else {
							$li['pay_type_show'] = L_('线下支付');
						}
					} else {
						$li['pay_type_show'] = D('Pay')->get_pay_name($store_order['pay_type'], 0, 1);
					}
				} else {
				    $li['pay_type_show'] = D('Pay')->get_pay_name($li['pay_method'], $li['is_mobile_pay']);
				}
			}else {
				$li['pay_type_show'] = D('Pay')->get_pay_name($li['pay_method'], $li['is_mobile_pay']);
			}

			if ($li['from_plat'] == 3) {
				$li['pay_type_show'] .= L_(' (扫用户码)');
			}

			// 堂扫实付金额
			if ($li['tax_rate_price']) {
				$li['price'] = $li['price'] + $li['tax_rate_price'];
			}
			// 在线支付详情
            $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$li['order_id'],'order_type'=>'plat','pay_type'=>$li['pay_type'],'paid_money'=>array('gt',0)))->find();
            if($payment_money_order['payment_money']){
                //可退金额包括用户实际支付的支付手续费
                $li['price'] = $payment_money_order['paid_money'];
                $li['service_charge'] = $payment_money_order['service_charge'];
            }
		}
		$this->assign('order_list', $list);

		$pagebar = $p->show();
		$this->assign('status_list', D('Foodshop_order')->status_list);
		$this->assign('operate_status_list', D('Foodshop_order')->operate_status_list);
		$this->assign('pagebar', $pagebar);

		//$this->assign(D("Foodshop_order")->get_order_list($where, $order_sort, 1));
		$shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
		$shop = array_merge($this->store, $shop);
		$this->assign('now_store', $shop);

		$this->assign('status_list', D('Foodshop_order')->status_list);
		$this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status,'pay_type'=>$pay_type));

		// 堂扫
		$pay_method_new['alipay']['name'] = L_('支付宝');
		$this->assign('pay_method_new_js',json_encode($pay_method_new));

		$pay_method = D('Config')->get_pay_method('','',1);
		$pay_method['balance']['name'] = L_('余额支付');
		$this->assign('pay_method_js',json_encode($pay_method));

		// 堂扫
		if (C('config.open_meal_electronic_menu') && $this->store['open_from_shop']) {
			$pay_method = $pay_method_new;
		}
		$this->assign('pay_method',$pay_method);
		$where = array('p.store_id' => $this->store['store_id'], 'p.paid' => 1);
		if ($period) {
		    $where['p.pay_time'] = array(array('gt', $period[0]), array('lt', $period[1]));
		}
		$where['p.business_type'] = 'foodshop';
		// 新餐饮订单
		if (C('config.open_meal_electronic_menu') && $this->store['open_from_shop']) {        
			$where['f.is_foodshop_new'] = '1';
			// 新餐饮订单状态
			if ($operate_status>=0) {
				$where = array_merge($where,$operate_status_where);
			}
        }else{
			if ($status != -1) {
				$where['f.status'] = $status;
			}
			$where['f.is_foodshop_new'] = '0';
        }
		$data = array('total_price' => 0, 'online_price' => 0, 'offline_price' => 0);
		// $field = 'sum(total_money - wx_cheap + pay_rate_price) AS total_price, sum(total_money + pay_rate_price - system_balance - system_coupon_price - system_score_money - merchant_balance_pay - merchant_balance_give - merchant_discount_money - merchant_coupon_price - pay_money) AS offline_price, sum(pay_money+system_balance + system_coupon_price + system_score_money + merchant_balance_pay + merchant_balance_give + merchant_discount_money + merchant_coupon_price) as online_price';
		// $order = D('Plat_order')->field($field)->where($where)->find();


		$field = 'sum(p.total_money - p.wx_cheap + p.service_charge) AS total_price, sum(p.total_money + p.service_charge - p.system_balance - p.system_coupon_price - p.system_score_money - p.merchant_balance_pay - p.merchant_balance_give - p.merchant_discount_money - p.merchant_coupon_price - p.pay_money) AS offline_price, sum(p.pay_money+p.system_balance + p.system_coupon_price + p.system_score_money + p.merchant_balance_pay + p.merchant_balance_give + p.merchant_discount_money + p.merchant_coupon_price) as online_price';

		$order = D('Plat_order')->field($field)->join(' as p left join '.C('DB_PREFIX').'foodshop_order as f on f.order_id = p.business_id')->where($where)->find();

		$data['total_price'] += floatval($order['total_price']);
		$data['online_price'] += floatval($order['online_price']);
		$data['offline_price'] += floatval($order['offline_price']);

		// 店员结账	
		// $field = 'sum(price) AS total_price, sum(price - balance_pay - card_price - coupon_price - payment_money - merchant_balance - card_give_money - score_deducte) AS offline_price, sum(payment_money+balance_pay + card_price + coupon_price + merchant_balance + card_give_money + score_deducte) as online_price';
		// $order = D('Store_order')->field($field)->where($where)->find();
		$result_total_list = D('Store_order')->field('p.balance_pay , p.card_price, p.coupon_price , p.payment_money , p.merchant_balance, p.card_give_money, p.score_deducte, p.price')->join(' as p left join '.C('DB_PREFIX').'foodshop_order as f on f.order_id = p.business_id')->where($where)->select();
		$order = array();
        foreach ($result_total_list as $key => $value) {
        	$total_online_temp = $value['balance_pay'] + $value['card_price'] + $value['coupon_price'] + $value['payment_money'] + $value['merchant_balance'] + $value['card_give_money'] + $value['score_deducte'];
        	$total_online_temp = $total_online_temp > $value['price'] ? $value['price'] : $total_online_temp;
        	$order['total_price'] = getFormatNumber($order['total_price']+$value['price']);
        	$order['offline_price'] = getFormatNumber($order['offline_price']+$value['price']-$total_online_temp);
        	$order['online_price'] = getFormatNumber($order['online_price']+$total_online_temp);
        }

		$data['total_price'] += floatval($order['total_price']);
		$data['online_price'] += floatval($order['online_price']);
		$data['offline_price'] += floatval($order['offline_price']);
		$this->assign($data);
		$this->display();
	}

	// 堂扫接单,拒单，完成订单
    public function change_foodshop_status(){
        $order_id = isset($_POST['order_id']) && $_POST['order_id'] ? intval($_POST['order_id']) : 0;
        $status = isset($_POST['status']) && $_POST['status'] ? intval($_POST['status']) : 0;
        $where = array('order_id' => $order_id);
        // $where = array('store_id' => $this->store['store_id'], 'table_id' => $table_id);
        $now_order = D('Foodshop_order')->field(true)->where($where)->find();
        
        switch ($status) {
            case '1'://接单
                if ($now_order['operate_status']==1) {
					exit(json_encode(array('err_code' => true, 'msg' => L_('已接单'))));
                }
                $res = D('Foodshop_order')->field(true)->where($where)->setField('operate_status',1);
                break;
            case '2': // 完成订单
                if ($now_order['operate_status']==2) {
					exit(json_encode(array('err_code' => true, 'msg' => L_('订单已完成'))));
                }
                $res = D('Foodshop_order')->field(true)->where($where)->setField('operate_status',2);
                break;
            case '3':// 拒单
                if ($now_order['operate_status']==3 || $now_order['status']==5) {
					exit(json_encode(array('err_code' => true, 'msg' => L_('订单已取消'))));
                }
                $res = D('Foodshop_order')->where($where)->save(array('cancel_time' => time(), 'status' => 5,'operate_status' => 3, 'cancel_reason' => L_('店员手动取消！')));
                // 退款
                if ($res) {
                    // 更新库存
                    $goods_list = D('Foodshop_order_detail')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'],'ishide'=>0))->order('id ASC')->select();
                    $goodsDB = D('Foodshop_goods_new');
                    foreach ($goods_list as $index => $new_row) {
                        //更新库存
                        $goodsDB->update_stock($new_row,1);
                    }

                    // 修改桌台状态
                    if ($now_order['table_id']) {
                        $order = M('Foodshop_order')->where(array('table_id'=>$now_order['table_id'],'status'=>array('in',array(1,2))))->order('`order_id` ASC')->find();
                        if (!$order) {
                            M('Foodshop_table')->where(array('id'=>$now_order['table_id']))->data(array('status'=>0))->save();
                        }
                    }

                    /* 增加系统订单记录 2019/01/29 */
                    D('Foodshop_order')->add_system_order($order_id,2);

                    $this->refundMoney($order_id,$now_order['order_from']);
               
                }else{
					exit(json_encode(array('err_code' => true, 'msg' => L_('操作失败'))));
                }
                break;
        }
        if ($res) {
			exit(json_encode(array('err_code' => false, 'data' => $return)));
        }else{
			exit(json_encode(array('err_code' => true, 'msg' => L_('操作失败'))));
        }
        
    }  

    //修改桌台号
    public function change_table(){
        $table_id = intval($_POST['table_id']);
        $order_id = intval($_POST['order_id']);
        if(empty($order_id)){
            $this->returnCode(1,null,L_('订单id不能为空'));
        }

        $now_order = M('Foodshop_order')->where(array('order_id'=>$order_id))->find();
        if (!$now_order) {
			exit(json_encode(array('err_code' => true, 'msg' => L_('订单不存在'))));
        }

        $table = M('Foodshop_table')->where(array('id'=>$table_id))->find();
        if(empty($table)){
			exit(json_encode(array('err_code' => true, 'msg' => L_('桌台不存在'))));
        }

        $table_type = M('Foodshop_table_type')->where(array('id'=>$table['tid']))->find();
        if ($table_id==$now_order['table_id']) {
			exit(json_encode(array('err_code' => true, 'msg' => L_('您已是当前桌台，请选择其他桌台'))));
        }

        $model = M('Foodshop_order');
        $where = array();
        $where['order_id'] = $order_id;
        $r = $model->where($where)->save(array('table_id'=>$table_id,'table_type'=>$table['tid']));
        if($r){
            $return = array(
                'table_type_name' => $table_type['name'],
                'table_name' => $table['name'],
                'min_max' => $table_type['min_people'].'-'.$table_type['max_people'].'人',
            );
			exit(json_encode(array('err_code' => false, 'data' => $return)));
        }else{
			exit(json_encode(array('err_code' => true, 'msg' => L_('修改失败'))));
        }
    }


    // 获得餐饮可预约列表详情
	public function get_appoint_time()
	{
		$foodshop = D('Merchant_store')->now_store($this->store['store_id']);
		if ($foodshop['err_code']) exit(json_encode($foodshop));
		$foodshop = $foodshop['data'];

		$book_time = isset($_POST['book_time']) ? htmlspecialchars($_POST['book_time']) : 0;
		if (empty($book_time)) {
			exit(json_encode(array('err_code' => true, 'msg' => L_('预订时间不能为空'))));
		}

		$book_time = strtotime($book_time);

		$table_type = intval($_POST['table_type']);
		$table_id = intval($_POST['table_id']);
		$return = D('Foodshop_order')->format_data($foodshop, $book_time, $table_type, $table_id);
		exit(json_encode($return));
	}

	// 根据桌台获取当前桌台最近可预约时间
	public function ajax_get_now_appoint_time()
	{
		$foodshop = D('Merchant_store')->now_store($this->store['store_id']);
		if ($foodshop['err_code']) exit(json_encode($foodshop));
		$foodshop = $foodshop['data'];

		$table_type = intval($_POST['table_type']);
		$table_id = intval($_POST['table_id']);


		$book_time = strtotime($book_time);

		$table_type = intval($_POST['table_type']);
		$table_id = intval($_POST['table_id']);
		$book_time = D('Foodshop_order')->get_now_appoint_time($table_type,$foodshop,$table_id);
		if ($book_time) {
			$return = D('Foodshop_order')->format_data($foodshop, strtotime($book_time), $table_type,$table_id);
			exit(json_encode($return));
		}else{
			exit(json_encode(array('err_code' => true, 'msg' => L_('当前桌台没有可预约时间'))));
		}
	}

	public function foodshop_order_before(){
		if($this->now_merchant['status']!=1){
			if($this->now_merchant['merchant_end_time']>0 && $this->now_merchant['merchant_end_time']<time()){
				$this->error(L_('商家已过期，暂不可使用该功能！'));
			}
			$this->error(L_('商家已X1，暂不可使用该功能！',$this->merchant_status[$this->now_merchant['status']]));
		}
		$table_type = M('Foodshop_table_type')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();

		if(empty($table_type)){
			$this->error(L_('该店铺没有设置桌台分类'));
		}

		$this->assign('table_type',$table_type);

		if(intval($_GET['table_type'])){
			$now_table_type = intval($_GET['table_type']);
		}
		$table_id = intval($_GET['table_id']);
		$this->assign('table_id',$table_id);
		$this->assign('now_table_type',$now_table_type);

		if (C('config.store_open_table_bind_time') == 1) {
			// 是否开启线下锁定桌台绑定时间段
			$table_list = M('Foodshop_table')->where(array('tid'=>$now_table_type ? $now_table_type : $table_type[0]['id']))->order('`id` ASC')->select();
		}else{
			$table_list = M('Foodshop_table')->where(array('tid'=>$now_table_type ? $now_table_type : $table_type[0]['id'],'status'=>'0'))->order('`id` ASC')->select();
		}
		// var_dump($table_list);
		$this->assign('table_list',$table_list);

		if (C('config.store_open_table_bind_time') == 1) {
			// 是否开启线下锁定桌台绑定时间段
			$foodshop = D('Merchant_store')->now_store($this->store['store_id']);
			if (!$foodshop['err_code']) {
				$foodshop = $foodshop['data'];

				$table_type_id = $now_table_type ? $now_table_type : $table_type[0]['id'];
				$book_time = D('Foodshop_order')->get_now_appoint_time($table_type_id,$foodshop,$table_id);

				$this->assign('book_time',$book_time);
				$return = D('Foodshop_order')->format_data($foodshop, strtotime($book_time), $table_type);
				unset($return['table_type']);
				unset($return['table_list']);
				$this->assign($return);
			}
		}
		$this->display();
	}

	public function get_table_list(){
		$order_id = intval($_POST['order_id']);
        if($order_id){ 
        	$now_order = M('Foodshop_order')->where(array('order_id'=>$order_id))->find();
	        // if (!$now_order) {
	        //     $this->returnCode(1,null,L_('订单不存在'));
	        // }

	        $tables = M('Foodshop_table')->where(array('store_id' => $now_order['store_id'], 'tid' => $now_order['table_type']))->select(); 
			echo json_encode($tables?$tables:array()); exit;
        }

		if (C('config.store_open_table_type')) { //是否开启餐饮桌台列表形式的创建订单
			$table_list = M('Foodshop_table')->where(array('tid'=>$_GET['table_type'],'store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
			foreach ($table_list as $key=>$value) {
				$table_order = M('Foodshop_order')->where(array('table_id'=>$value['id'],'status'=>2))->order('`order_id` ASC')->find();
				if ($table_order) {
					unset($table_list[$key]);
				}
			}
		}else{
			$table_list = M('Foodshop_table')->where(array('tid'=>$_GET['table_type'],'store_id'=>$this->store['store_id'],'status'=>'0'))->order('`id` ASC')->select();
		}
		if (C('config.store_open_table_bind_time') == 1) {
			$table_list = M('Foodshop_table')->where(array('tid'=>$_GET['table_type'],'store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		}
		echo json_encode($table_list);
	}

	public function foodshop_add_order(){
		$data['real_orderid'] = date('ymdhis').substr(microtime(),2,8-strlen($this->store['store_id'])).$this->store['store_id'];
		$data['mer_id'] = $this->store['mer_id'];
		$data['store_id'] = $this->store['store_id'];
		$data['book_num'] = $_POST['book_num'];
		$data['table_type'] = $_POST['table_type'];
		$data['table_id'] = $_POST['table_id'];
		$data['create_time'] = $_SERVER['REQUEST_TIME'];
		$data['status'] = 1;
		$data['order_from'] = 2;

		if (C('config.store_open_table_bind_time') == 1 && isset($_POST['book_time'])) {
			// 是否开启线下锁定桌台绑定时间段
			$foodshop = D('Merchant_store')->now_store($this->store['store_id']);
			$foodshop = $foodshop['data'];

			$now_time = time() + $foodshop['advance_time'] * 60;
			$book_time = isset($_POST['book_time']) ? htmlspecialchars($_POST['book_time']) : 0;
			if (empty($book_time)) {
				$this->error(L_('预订时间不能为空'));
			}
			$book_time = strtotime($book_time);
			if ($now_time > $book_time) {
				$this->error(L_('至少提前X1分钟预定',$foodshop['advance_time']));
			}
			$data['book_time'] = $book_time;

			$order = M('Foodshop_order')->field(true)->where(array('store_id' => $this->store['store_id'], 'book_time' => $book_time, 'table_type' => $_POST['table_type'],'table_id' => $_POST['table_id'], 'status' => array('in', array(1, 2))))->find();
			if ($order) {
				$this->error(L_('该时段该桌台已订满'));
			}
		}

		if($order_id = D('Foodshop_order')->save_order($data)){
			M('Foodshop_table')->where(array('id'=>$data['table_id']))->data(array('status'=>'1'))->save();

			// 插入必点菜
			$data['order_id'] = $order_id;
			D('Foodshop_order')->count_price($data,0,true);

            /* 增加系统订单记录 2019/01/29 */
			D('Foodshop_order')->add_system_order($order_id,1);

			$this->success($order_id);
		}else{
			$this->error(L_('创建订单失败，请重试'));
		}
	}

	public function foodshop_order(){
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']), 3);

		$price = D('Foodshop_order')->count_price($order);
		if($this->config['open_extra_price']){
			$extra_price = D('Foodshop_order')->count_extra_price($order);
		}else{
			$extra_price = 0;
		}
		//备注
		$store_order = D('Store_order')->where(array('business_id'=>$order_id,'business_type'=>'foodshop','paid'=>1))->find();
		$plat_order = D('Plat_order')->where(array('business_id'=>$order_id,'business_type'=>'foodshop','paid'=>1))->find();
		$store_order['desc'] && $order['note'] = $store_order['desc'];

        // 实付金额
        $discount_money = 0;
        if ($store_order) {
            $discount_money += $store_order['card_price'] + $store_order['coupon_price']+ $store_order['score_deducte'];
        }
        if ($plat_order) {
            $discount_money +=  $plat_order['system_coupon_price'] + $plat_order['system_score_money'] + $plat_order['merchant_coupon_price'];
        }

        if ($store_order['card_discount']) {
            $discount_money = round($store_order['price']-($store_order['price']-$store_order['no_discount_money'])*$store_order['card_discount']/10-$store_order['no_discount_money'],2);
        }
        $plat_order['merchant_discount_money'] && $discount_money += $plat_order['merchant_discount_money'];
        $order['price'] = $order['price'] - $discount_money;
        
		// 堂扫实付金额
		if ($order['tax_rate_price']) {
			$order['price'] = $order['price'] + $order['tax_rate_price'];
		}
		// 在线支付详情
        $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['order_id'],'order_type'=>'plat','pay_type'=>$order['online_pay'],'paid_money'=>array('gt',0)))->find();
        if($payment_money_order['payment_money']){
            //可退金额包括用户实际支付的支付手续费
            $order['price'] = $payment_money_order['paid_money'];
            $order['service_charge'] = $payment_money_order['service_charge'];
        }
		$this->assign('order', $order);
		$this->assign('price', $price);
		$this->assign('total_price', $price + $order['book_price']);
		$this->assign('extra_price', $extra_price);
		$account_type = D('Merchant_store_foodshop')->get_account_type($this->store['store_id']);
		$this->assign('account_type',$account_type);
		$goods_package = D('Foodshop_goods_package')->get_list_by_store_id($this->store['store_id']);
		$this->assign('goods_package', $goods_package);
		if ($order['status'] < 3) {
			$this->display();
		} else {
			$this->display('foodshop_order_detail');
		}
	}

	public function foodshop_getmenu(){
		$search	=	$_GET['search'];
		$tmpList = D('Foodshop_goods')->get_list_by_storeid($this->store['store_id']);
 		$return['lists'] = $tmpList;

		// foreach($tmpList as $value){
		// 	$return['lists'][$value['sort_id']] = $value;
		// }

		$return['package'] = D('Foodshop_goods_package')->get_list_by_store_id($this->store['store_id']);
		if(empty($return['package'])){
			$return['package'] = array();
		}

		$uids = array();
		$tmp_order = M('Foodshop_order_temp')->where(array('store_id'=>$this->store['store_id'],'order_id'=>$_GET['order_id'],'ishide' => 0))->order('`id` ASC')->select();
        if ($tmp_order) {
            foreach ($tmp_order as $tmp_value) {
                if (! in_array($tmp_value['uid'], $uids)) {
                    $uids[] = $tmp_value['uid'];
                }
                $tmp_value['price'] = floatval($tmp_value['price']);
                if ($this->config['open_extra_price'] == 0) {
                    $tmp_value['extra_pay_price'] = 0;
                }
                $return['tmp_order'][$tmp_value['uid']][] = $tmp_value;
            }
        } else {
            $return['tmp_order'] = array();
        }
        $return['order_detail'] = array();
        $order_details = M('Foodshop_order_detail')->where(array('store_id' => $this->store['store_id'], 'order_id' => $_GET['order_id'], 'ishide' => 0))->order('`id` ASC')->select();
        foreach ($order_details as $detail_value) {
            if (! in_array($detail_value['uid'], $uids)) {
                $uids[] = $detail_value['uid'];
            }
            $detail_value['price'] = floatval($detail_value['price']);
            $detail_value['num'] = floatval($detail_value['num']);

            $return['order_detail'][$detail_value['uid']][] = $detail_value;
        }
        $userTemp = D('User')->field('nickname, uid')->where(array('uid' => array('in', $uids)))->select();
        $userList = array();
        foreach ($userTemp as $user) {
            $userList[$user['uid']] = $user['nickname'];
        }
        $return['userList'] = $userList;

        $order_info = M('Foodshop_order')->where(array('store_id'=>$this->store['store_id'],'order_id'=>$_GET['order_id']))->find();
        $return['order_info'] = $order_info;

        echo json_encode($return);
	}

	public function foodshop_getgroup_detail(){
		echo json_encode(D('Foodshop_goods_package_detail')->get_detail_by_pid($_GET['group_id']));
	}

	public function foodshop_change_order(){
		$condition_where['store_id'] = $this->store['store_id'];
		$condition_where['order_id'] = $_GET['order_id'];
		$condition_where['id'] = $_POST['detail_id'];
		$order_id = intval($_GET['order_id']);
		$detail = M('Foodshop_order_detail')->where($condition_where)->find();
		if($_POST['number'] > 0){
			if(M('Foodshop_order_detail')->where($condition_where)->data(array('num'=>$_POST['number']))->save()){
				$order = D('Foodshop_order')->get_order_detail(array('order_id' => intval($_GET['order_id']), 'store_id' => $this->store['store_id']), 2);
				$price = D('Foodshop_order')->count_price($order);
				if($this->config['open_extra_price']){
					$extra_price = D('Foodshop_order')->count_extra_price($order);
				}else{
					$extra_price = 0;
				}
				$detail['num'] = $_POST['number'] - $detail['num'];
				D('Foodshop_goods')->update_stock($detail);
				 // 是否启用餐饮打印模式 新
				if (C('config.open_foodshop_print_type')) {
					if ($detail['num']>0) { // 加菜
						// 打印加菜
	                    D('Foodshop_order')->order_notice($order_id, array($detail), 0, 1, 0,$this->staff_session['name']);
					}else{
						// 打印退菜
			    		$detail['num'] = 0-$detail['num'];
	                    D('Foodshop_order')->order_notice($order_id, array($detail), 0, 1, 1,$this->staff_session['name']);
					}
				}

	            /* 增加系统订单记录 2019/01/29 */
				D('Foodshop_order')->add_system_order($order_id,2);
				//计算桌台服务费
                $service_fee = D('Foodshop_order')->create_table_service_fee($order_id,1);
				header('Content-Type:application/json; charset=utf-8');
				exit(json_encode(array('status' => 1, 'info' => L_('保存成功！'), 'total_price' => floatval($price + $order['book_price']), 'book_price' => floatval($order['book_price']), 'unpaid_price' => floatval($price),'extra_price'=>$extra_price,'service_fee'=>$service_fee)));
				$this->success(L_('保存成功！'));
			}else{
				$this->error(L_('保存失败！'));
			}
		}else{
			if(M('Foodshop_order_detail')->where($condition_where)->delete()){
				$order = D('Foodshop_order')->get_order_detail(array('order_id' => intval($_GET['order_id']), 'store_id' => $this->store['store_id']), 2);
				$price = D('Foodshop_order')->count_price($order);
				if($this->config['open_extra_price']){
					$extra_price = D('Foodshop_order')->count_extra_price($order);
				}else{
					$extra_price = 0;
				}
				D('Foodshop_goods')->update_stock($detail, 1);

				 // 是否启用餐饮打印模式 新
				if (C('config.open_foodshop_print_type')) {
					// 打印退菜
	                D('Foodshop_order')->order_notice($order_id, array($detail), 0, 1, 1,$this->staff_session['name']);
	            }

	            /* 增加系统订单记录 2019/01/29 */
				D('Foodshop_order')->add_system_order($order_id,2);
				//计算桌台服务费
                $service_fee = D('Foodshop_order')->create_table_service_fee($order_id,1);
				header('Content-Type:application/json; charset=utf-8');
				exit(json_encode(array('status' => 1, 'info' => L_('保存成功！'), 'total_price' => floatval($price + $order['book_price']), 'book_price' => floatval($order['book_price']), 'unpaid_price' => floatval($price),'extra_price'=>$extra_price,'service_fee'=>$service_fee)));
				$this->success(L_('删除成功！'));
			}else{
				$this->error(L_('删除失败！'));
			}
		}
	}

	public function foodshop_save_order()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
		$carts = isset($_POST['cart']) ? $_POST['cart'] : null;
		if (empty($carts)) {
			$this->error(L_('没有点菜'));
		}
		if ($order = M('Foodshop_order')->field(true)->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->find()) {
			$goods_ids = array_column($carts, 'productId');
			$goods_list = M('Foodshop_goods')->where(array('goods_id'=>array('in',$goods_ids)))->select();
			$goods_list_fomart = array();
			foreach ($goods_list as $key => $value) {
				$goods_list_fomart[$value['goods_id']] = $value;
			}
			$productCart = array();
			foreach ($carts as $row) {
				$params = array();
				$tmp_id = 0;
				if (isset($row['tmpOrderId']) && $row['tmpOrderId']) { // 临时表中已存在，用户待确认菜品
					if ($goods_temp = D('Foodshop_order_temp')->field(true)->where(array('order_id' => $order_id, 'id' => $row['tmpOrderId']))->find()) {
					    $t_cookie = array('goods_id' => $goods_temp['goods_id'], 'tempId' => $goods_temp['id'], 'uid' => $goods_temp['uid'], 'fid' => $goods_temp['fid'], 'package_id' => $goods_temp['package_id'], 'num' => $row['count'], 'note' => $row['note'], 'name' => $goods_temp['name'], 'price' => floatval($goods_temp['price']),'extra_price'=>$goods_temp['extra_price'],'is_must'=>intval($goods_list_fomart[$goods_temp['goods_id']]['is_must']),'operate_must_goods'=>intval($goods_list_fomart[$goods_temp['goods_id']]['operate_must_goods']));
						$temp_params = array();
						if ($goods_temp['spec_id']) {
							$temp_params = D('Foodshop_goods')->format_spec_ids($goods_temp, $temp_params);
						}
						if ($goods_temp['spec']) {
							$temp_params = D('Foodshop_goods')->format_properties_ids($goods_temp, $temp_params);
						}
						$t_cookie['params'] = $temp_params;
						$productCart[] = $t_cookie;
						continue;
					}
				} elseif (isset($row['productParam'])) {  // 店员新增
					foreach ($row['productParam'] as $r) {
						if ($r['type'] == 'spec') {
							$t_data = array('id' => $r['spec_id'], 'name' => '', 'type' => $r['type'], 'data' => array(array('id' => $r['id'], 'name' => $r['name'])));
						} else {
							$t_data = array('id' => 0, 'name' => '', 'type' => $r['type'], 'data' => '');
							$td = array();
							foreach ($r['data'] as $i => $d) {
								$t_data['id'] = $d['list_id'];
								$td[] = array('id' => $i, 'name' => $d['name']);
							}
							$t_data['data'] = $td;
						}
						$params[] = $t_data;
					}
				}
				$productCart[] = array('name' => $row['productName'], 'tempId' => 0, 'uid' => 0, 'goods_id' => $row['productId'], 'package_id' => 0, 'note' => $row['note'], 'num' => $row['count'], 'params' => $params,'extra_price'=>$row['extra_price'],'staff_id'=>$this->staff_session['id'],'is_must'=>intval($goods_list_fomart[$row['productId']]['is_must']),'operate_must_goods'=>intval($goods_list_fomart[$row['productId']]['operate_must_goods']));
			}

			$cart_data = D('Foodshop_goods')->format_cart($productCart, $this->store['store_id'], $order_id, false);

			$diff_order_temp = array(); // 店员新增或减少的菜品
			foreach($cart_data['data'] as $vd){
				unset($vd['tempId']);
				$where_tmp['order_id'] =$vd['order_id'] ;
				$where_tmp['goods_id'] =$vd['goods_id'] ;
				if($order_temp = M('Foodshop_order_temp')->where($where_tmp)->find()){
					if ($order_temp['num'] != $vd['num']) {
						$_diff_order_temp = $vd;
						$_diff_order_temp['num'] = $vd['num']-$order_temp['num'];
						$diff_order_temp[] = $_diff_order_temp;
					}
					M('Foodshop_order_temp')->where($where_tmp)->save($vd);
				}else{
					$diff_order_temp[] = $vd;
					M('Foodshop_order_temp')->add($vd);
				}
				// M('Foodshop_goods')->where(array('goods_id'=>$vd['goods_id']))->setInc('sell_count',$vd['num']);
				// M('Foodshop_goods')->where(array('goods_id'=>$vd['goods_id']))->setDec('stock_num',$vd['num']);
			}
			if ($cart_data['err_code']) {
				$this->error($cart_data['msg']);
			}
			$new_goods_list = $cart_data['data'];

			$total = $cart_data['total'];
			$price = $cart_data['price'];
			$now_time = time();
			if ($package_data = M('Foodshop_goods_package')->field(true)->where(array('id' => $package_id, 'store_id' => $this->store['store_id']))->find()) {
				$price = $package_data['price'];

				$hideData = array();
				$hideData['order_id'] = $order_id;
				$hideData['goods_id'] = $package_id;
				$hideData['name'] = $package_data['name'];
				$hideData['price'] = $package_data['price'];
				$hideData['unit'] = L_('份');
				$hideData['create_time'] = $now_time;
				$hideData['package_id'] = $package_id;
				$hideData['uid'] = 0;
				$hideData['staff_id'] = $this->staff_session['id'];
				$hideData['ishide'] = 1;
				$hideData['is_discount'] = 1;
				$hideData['num'] = 1;
				$hideData['store_id'] = $this->store['store_id'];
				$hideData['extra_price'] = 0;
				if ($fid = D('Foodshop_order_detail')->add($hideData)) {
    				foreach ($new_goods_list as $index => $new_row) {
    					$new_row['order_id'] = $order_id;
    					$new_row['create_time'] = $now_time;
    					$new_row['package_id'] = $package_id;
    					$new_row['uid'] = 0;
    					$new_row['fid'] = $fid;
    					$new_row['store_id'] = $this->store['store_id'];
    					$new_row['extra_price'] = empty($new_row['extra_price'])?0:$new_row['extra_price'];
    					D('Foodshop_order_detail')->add($new_row);
    				}
				} else {
				    $this->error(L_('套餐保存失败，稍后重试！'));
				}
			} else {
			    $detail_list = D('Foodshop_order_detail')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'], 'package_id' => 0))->select();
				$detailList = array();
				foreach ($detail_list as $_row) {
				    $_t_index = $_row['uid'] . '_' . $_row['goods_id'];
					if (strlen($_row['spec']) > 0) {
						$_t_index .= '_' . md5($_row['spec']);
					}
					$detailList[$_t_index] = $_row;
				}

				$tempTableList = D('Foodshop_order_temp')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'], 'package_id' => 0))->select();
				$temp_table_list = array();
				foreach ($tempTableList as $t_row) {
				    $t_t_index = $t_row['uid'] . '_' . $t_row['goods_id'];
				    if (strlen($t_row['spec']) > 0) {
				        $t_t_index .= '_' . md5($t_row['spec']);
				    }
				    $temp_table_list[$t_t_index] = $t_row;
				}
				$newPackageIDs = array();
				$detailDB = D('Foodshop_order_detail');
				$foodshopGoodsDB = D('Foodshop_goods');
				foreach ($new_goods_list as $index => $new_row) {
				    if ($new_row['package_id']) {//用户点的套餐
				        if (isset($newPackageIDs[$new_row['package_id']]) && $newPackageIDs[$new_row['package_id']]) {
				            $fid = $newPackageIDs[$new_row['package_id']];
				        } else {
				            if ($pack = D('Foodshop_goods_package')->field(true)->where(array('id' => $new_row['package_id']))->find()) {
    				            $tmp = array();
    				            $tmp['order_id'] = $order_id;
    				            $tmp['store_id'] = $this->store['store_id'];
    				            $tmp['package_id'] = $new_row['package_id'];
    				            $tmp['goods_id'] = $new_row['package_id'];
    				            $tmp['note'] = $new_row['note'];
    				            $tmp['ishide'] = 1;
    				            $tmp['is_discount'] = 1;
    				            $tmp['name'] = $pack['name'];
    				            $tmp['price'] = $pack['price'];
    				            $tmp['extra_price'] = 0;
    				            $tmp['uid'] = $new_row['uid'];
    				            $tmp['staff_id'] = intval($new_row['staff_id']);
    				            $tmp['create_time'] = $now_time;
    				            $tmp['num'] = $new_row['num'];
    				            if ($fid = $detailDB->add($tmp)) {
    				                $newPackageIDs[$new_row['package_id']] = $fid;
    				            } else {
    				                $this->error(L_('用户点的套餐保存失败，稍后重试！'));
    				            }
				            } else {
				                $this->error(L_('套餐信息不存在！'));
				            }
				        }
				        if ($new_row['fid']) {
    				        $new_row['fid'] = $fid;
    				        $new_row['create_time'] = $now_time;
    				        $new_row['order_id'] = $order_id;
    				        $new_row['store_id'] = $this->store['store_id'];
    				        $new_row['extra_price'] = empty($new_row['extra_price']) ? 0 : $new_row['extra_price'];
    				        $detailDB->add($new_row);
				        }
				    } elseif (isset($detailList[$index])) {//这个用户已经点了这个菜品的时候 只是在已点菜品中增加数量
				        $detailDB->where(array('id' => $detailList[$index]['id']))->save(array('num' => $new_row['num'] + $detailList[$index]['num']));
				        unset($detailList[$new_row['uid']][$index]);
					} else {//新增的菜品
						$new_row['fid'] = 0;
						$new_row['package_id'] = 0;
						$new_row['create_time'] = $now_time;
						$new_row['order_id'] = $order_id;
						$new_row['store_id'] = $this->store['store_id'];
						$new_row['extra_price'] = empty($new_row['extra_price']) ? 0 : $new_row['extra_price'];
						$detailDB->add($new_row);
					}

					if (isset($temp_table_list[$index])) {
					    $diffNum = $new_row['num'] - $temp_table_list[$index]['num'];
					    if ($diffNum != 0) {
					        $temp_table_list[$index]['num'] = $diffNum;
					        // $foodshopGoodsDB->update_stock($temp_table_list[$index]);
					    }
					    unset($temp_table_list[$index]);
					}
				}
				// fdump('回滚','htm',true);
				// fdump('$temp_table_list','htm',true);
				// fdump($temp_table_list,'htm',true);
				//将用户点的菜品，但是被店员给取消掉的回滚库存
				foreach ($temp_table_list as $i => $rowList) {
				    // foreach ($rowList as $val) {
				        $foodshopGoodsDB->update_stock($rowList, 1);
				    // }
				}
				foreach ($diff_order_temp as $i => $rowList) {
				    $foodshopGoodsDB->update_stock($rowList);
				}
				//清空用户点餐的零时表
				D('Foodshop_order_temp')->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->delete();
			}
			if ($order['status'] < 2) {
				$save_order_data['status'] = 2;
			}
			$save_order_data['running_state'] = 0;
			$save_order_data['last_time'] = $_SERVER['REQUEST_TIME'];
			$save_order_data['running_time'] = $_SERVER['REQUEST_TIME'];

			if (M('Foodshop_order')->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->save($save_order_data)) {
				//配置打印
				// 是否启用餐饮打印模式 新
				if (C('config.open_foodshop_print_type')) {
	                D('Foodshop_order')->order_notice($order_id, $new_goods_list, 6, 1, 0,$this->staff_session['name'],$order['status']);
	            }else{
					D('Foodshop_order')->order_notice($order_id, $new_goods_list);
	            }

	            /* 增加系统订单记录 2019/01/29 */
				D('Foodshop_order')->add_system_order($order_id,2);

                // 增加流水号
                D('Foodshop_order')->get_fetch_number($order);

				$service_fee = D('Foodshop_order')->create_table_service_fee($order_id,1);
				$this->success(L_('订单保存成功'));
			} else {
				$this->error(L_('订单保存失败，稍后重试！'));
			}
		} else {
			$this->error(L_('订单信息不存在！'));
		}
	}

	public function foodshop_edit_order(){
		$now_order = M('Foodshop_order')->where(array('order_id'=>$_GET['order_id'],'store_id'=>$this->store['store_id']))->find();
		$this->assign('now_order',$now_order);

		$table_type = M('Foodshop_table_type')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();

		if(empty($table_type)){
			$this->error(L_('该店铺没有设置桌台分类'));
		}

		$this->assign('table_type',$table_type);

		if (C('config.store_open_table_type')) { //是否开启餐饮桌台列表形式的创建订单
			$table_list = M('Foodshop_table')->where(array('tid'=>$now_order['table_type'],'store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
			foreach ($table_list as $key=>$value) {
				$table_order = M('Foodshop_order')->where(array('table_id'=>$value['id'],'status'=>2))->order('`order_id` ASC')->find();
				if ($table_order) {
					unset($table_list[$key]);
				}
			}
		}else{
			if (C('config.store_open_table_bind_time') == 1) {
				// 是否开启线下锁定桌台绑定时间段
				$table_list = M('Foodshop_table')->where(array('tid'=>$table_type[0]['id']))->order('`id` ASC')->select();
			}else{
				// $table_list = M('Foodshop_table')->where(array('tid'=>$table_type[0]['id'],'status'=>'0'))->order('`id` ASC')->select();
				$table_list = M('Foodshop_table')->where(array('tid'=>$now_order['table_type'],'store_id'=>$this->store['store_id'],'_string'=>'(status=0 OR id ='.$now_order['table_id'].')'))->order('`id` ASC')->select();
			}
			// $table_list = M('Foodshop_table')->where(array('tid'=>$now_order['table_type'],'store_id'=>$this->store['store_id'],'status'=>'0'))->order('`id` ASC')->select();
		}
		$this->assign('table_list',$table_list);

		if (C('config.store_open_table_bind_time') == 1) {
			// 是否开启线下锁定桌台绑定时间段
			$foodshop = D('Merchant_store')->now_store($this->store['store_id']);
			if (!$foodshop['err_code']) {
				$foodshop = $foodshop['data'];

				$table_type_id = $table_type[0]['id'];
				$book_time = D('Foodshop_order')->get_now_appoint_time($table_type_id,$foodshop,$table_list[0]['id']);

				$this->assign('book_time',$book_time);
				$return = D('Foodshop_order')->format_data($foodshop, strtotime($book_time), $table_type,$table_list[0]['id']);
				unset($return['table_type']);
				unset($return['table_list']);
				$this->assign($return);
			}
		}

		$this->display();
	}

	public function foodshop_edit_order_save(){
		$now_order = M('Foodshop_order')->where(array('order_id'=>$_POST['order_id'],'store_id'=>$this->store['store_id']))->find();

		$condition_order['order_id'] = $_POST['order_id'];
		$condition_order['store_id'] = $this->store['store_id'];
		$data['book_num'] = $_POST['book_num'];
		$data['table_type'] = $_POST['table_type'];
		$data['table_id'] = $_POST['table_id'];

		if (C('config.store_open_table_bind_time') == 1) {
			// 是否开启线下锁定桌台绑定时间段
			$foodshop = D('Merchant_store')->now_store($this->store['store_id']);
			$foodshop = $foodshop['data'];

			$now_time = time() + $foodshop['advance_time'] * 60;
			$book_time = isset($_POST['book_time']) ? htmlspecialchars($_POST['book_time']) : 0;
			if (empty($book_time)) {
				$this->error(L_('预订时间不能为空'));
			}
			$book_time = strtotime($book_time);
			if ($now_time > $book_time) {
				$this->error(L_('至少提前X1分钟预定',$foodshop['advance_time']));
			}
			$data['book_time'] = $book_time;

			$order = M('Foodshop_order')->field(true)->where(array('store_id' => $this->store['store_id'], 'book_time' => $book_time, 'table_type' => $_POST['table_type'],'table_id' => $_POST['table_id'], 'status' => array('in', array(1, 2))))->find();
			if ($order) {
				$this->error(L_('该时段该桌台已订满'));
			}
		}
		//计算桌台服务费
        $service_fee = D('Foodshop_order')->create_table_service_fee($_POST['order_id'],1);
		if(M('Foodshop_order')->where($condition_order)->data($data)->save()){
			M('Foodshop_table')->where(array('id'=>$now_order['table_id']))->data(array('status'=>'0'))->save();
			M('Foodshop_table')->where(array('id'=>$data['table_id']))->data(array('status'=>'1'))->save();
			$this->success(L_('编辑成功'));
		}else{
			$this->error(L_('编辑订单失败，请重试'));
		}
	}

	//只打印订单里面的商品，而且只用主打印机打印，一般用于用户结算前的打印。
	public function foodshop_print_order()
	{
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$is_print_menu = isset($_GET['is_print_menu']) ? intval($_GET['is_print_menu']) : 0;
        $order = M('Foodshop_order')->field(true)->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->find();

		$printHaddle = new PrintHaddle();

        if ($order['is_foodshop_new']) {
            // 堂扫打印
            $printHaddle->printit_new($order_id, 'foodshop_order', 7, 0,$this->staff_session['name'],1);// 结账单
        }elseif (C('config.open_foodshop_print_type')) {
	    	// 是否启用餐饮打印模式 新
	    	if ($is_print_menu) { // 后厨补打
                $printHaddle->printit($order_id, 'foodshop_order', -1, 0, $this->staff_session['name']);
            }else{   // 打印预结单，结账单
                $printHaddle->printit($order_id, 'foodshop_order', -1, 1, $this->staff_session['name']);
            }
		}else{
			$printHaddle->printit($order_id, 'foodshop_order', -1, 1);
		}
		$this->success(L_('打印成功'));
	}

	public function tmp_table(){

		$table_type = M('Foodshop_table_type')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		if(empty($table_type)){
			$this->error(L_('该店铺没有设置桌台分类'));
		}

		$this->assign('table_type',$table_type);

		if($_GET['type_id']){
			$now_table = M('Foodshop_table_type')->where(array('store_id'=>$this->store['store_id'],'id'=>$_GET['type_id']))->find();
		}
		if(empty($now_table)){
			$now_table = $table_type[0];
		}
		$this->assign('now_table',$now_table);

		// $table_list = M('Foodshop_table')->field('t.*,o.order_id')->join('as t left join '.C('DB_PREFIX').'Foodshop_order as o on o.table_id=t.id')->where('t.tid='.$now_table['id'].' AND o.status in(1,2)')->group('t.id')->order('`t`.`id` ASC')->select();
		$table_list = M('Foodshop_table')->where(array('tid'=>$now_table['id']))->order('`id` ASC')->select();
		foreach ($table_list as &$value) {
			$table_order = M('Foodshop_order')->where(array('table_id'=>$value['id'],'status'=>array('in',array(1,2)),'account_type'=>1,'is_foodshop_new'=>0))->order('`order_id` ASC')->find();
			$value['order_id'] = $table_order? $table_order['order_id'] : 0;
		}
		// var_dump($table_list);
		$this->assign('table_list',$table_list);

		$this->display();
	}

	public function ajax_tmp_table(){

		$table_type = M('Foodshop_table_type')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		if(empty($table_type)){
			exit(json_encode(array('err_code'=>1,'msg'=>L_('该店铺没有设置桌台分类'))));
		}

		if($_GET['type_id']){
			$now_table = M('Foodshop_table_type')->where(array('store_id'=>$this->store['store_id'],'id'=>$_GET['type_id']))->find();
		}
		if(empty($now_table)){
			$now_table = $table_type[0];
		}

		$table_list = M('Foodshop_table')->where(array('tid'=>$now_table['id']))->order('`id` ASC')->select();
		foreach ($table_list as &$value) {
			$table_order = M('Foodshop_order')->where(array('table_id'=>$value['id'],'status'=>array('in',array(1,2)),'is_foodshop_new'=>0,'account_type'=>1))->order('`order_id` ASC')->find();
			$value['order_id'] = $table_order? $table_order['order_id'] : 0;
		}
		foreach ($table_list as &$value) {
			$value['url'] = U('book_list',array('table_id' => $value['id']));
			$value['store_open_table_type'] = C('config.store_open_table_type');
		}
		exit(json_encode(array('err_code'=>1,'data'=>$table_list)));
	}

	// 验证桌台是否预定中、就餐中
	public function ajax_get_table_order(){

		$table_id = $_POST['table_id'];

		$table = M('Foodshop_table')->where(array('id'=>$table_id))->find();
		$table_order = M('Foodshop_order')->where(array('table_id'=>$table_id,'status'=>array('in',array(1,2)),'account_type'=>1))->order('`order_id` ASC')->find();
		$order_id = $table_order? $table_order['order_id'] : 0;

		exit(json_encode(array('err_code'=>0,'order_id'=>$order_id)));
	}

	public function book_list()
	{
		$table_id = isset($_GET['table_id']) && $_GET['table_id'] ? intval($_GET['table_id']) : 0;
		$where = array('store_id' => $this->store['store_id'], 'table_id' => $table_id, 'status' => array('in', array(1, 2)), 'book_time' => array('gt', time()),'is_foodshop_new'=>0);
		$list = D('Foodshop_order')->field(true)->where($where)->order('book_time ASC')->select();
		$this->assign('list', $list);
		$this->display();
	}

	public function tmp_table_lock(){
		if(M('Foodshop_table')->where(array('store_id'=>$this->store['store_id'],'id'=>$_POST['id']))->setField('status',$_POST['lock'])){
			$this->success(L_('操作成功'));
		}else{
			$this->error(L_('操作失败'));
		}
	}

	/**
	 * 餐饮的排号管理
	 */
	public function queue()
	{
		$foodshop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
		if ($foodshop['is_queue'] == 0) {
			$this->error(L_('店铺没有排号功能'));
		}

		$this->assign('queue_list', $this->queue_list(1));
		$store = array_merge($this->store, $foodshop);
		$this->assign('store', $store);
		$this->display();
	}

	public function change_queue()
	{
		$where = array('store_id' => $this->store['store_id']);
		$foodshop = M('Merchant_store_foodshop')->field(true)->where($where)->find();
		if (empty($foodshop)) {
			$this->error(L_('店铺信息有问题'));
		}
		if (M('Merchant_store_foodshop')->where($where)->save(array('queue_is_open' => 1 - intval($foodshop['queue_is_open']), 'queue_open_time' => time()))) {
			if ($foodshop['queue_is_open'] == 0) {
				//开启排号的时候清空以前的排号记录
				M('Foodshop_queue')->where($where)->delete();
				$msg = L_('点击关闭排号');
			} else {
				$msg = L_('点击开启排号');
			}
			$this->success($msg);
		} else {
			$this->error(L_('状态修改失败'));
		}
	}

	public function queue_list($param = 0)
	{
		$where = array('store_id' => $this->store['store_id']);
		$foodshop = M('Merchant_store_foodshop')->field(true)->where($where)->find();
		if ($foodshop['is_queue'] == 0) {
			if ($param) {
				$this->error(L_('店铺没有排号功能'));
				exit;
			} else {
				exit(json_encode(array('status' => 0)));
			}
		}
		if ($foodshop['queue_is_open'] == 0) {
			if ($param) {
// 				$this->error('店铺没有排号功能');
// 				exit;
				return false;
			} else {
				exit(json_encode(array('status' => 0)));
			}
		}

		//排队例表
		$queue_list = M('Foodshop_queue')->field(true)->where(array('store_id' => $this->store['store_id'], 'status' => 0))->order('id ASC')->select();
		$now_number_ids = null;
		$next_number_ids = null;
		$wait_number_list = array();
		foreach ($queue_list as $row) {
			if (!isset($now_number_ids[$row['table_type']])) {
				$now_number_ids[$row['table_type']] = $row['number'];
			} elseif (!isset($next_number_ids[$row['table_type']])) {
				$next_number_ids[$row['table_type']] = $row['number'];
			}
			if (isset($wait_number_list[$row['table_type']])) {
				$wait_number_list[$row['table_type']] ++;
			} else {
				$wait_number_list[$row['table_type']] = 1;
			}
		}
		$table_total = M('Foodshop_table')->field('count(tid) AS cnt, tid')->where(array('store_id' => $this->store['store_id'], 'status' => 0))->group('tid')->select();

		$temp = array();
		foreach ($table_total as $v) {
			$temp[$v['tid']] = $v['cnt'];
		}

		$table_type_list = M('Foodshop_table_type')->field(true)->where($where)->select();
		foreach ($table_type_list as &$t_row) {
			$t_row['now_number'] = '';
			$t_row['next_number'] = '';
			$t_row['wait'] = 0;
			$t_row['free'] = 0;
			if (isset($now_number_ids[$t_row['id']])) {
				$t_row['now_number'] = $now_number_ids[$t_row['id']];
			}
			if (isset($next_number_ids[$t_row['id']])) {
				$t_row['next_number'] = $next_number_ids[$t_row['id']];
			}
			if (isset($wait_number_list[$t_row['id']])) {
				$t_row['wait'] = $wait_number_list[$t_row['id']];
			}
			if (isset($temp[$t_row['id']])) {
				$t_row['free'] = $temp[$t_row['id']];
			}
		}
		if ($param) {
			return $table_type_list;
		} else {
			exit(json_encode(array('status' => 1, 'data' => $table_type_list)));
		}
	}

	public function queue_call()
	{
		$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
		$where = array('store_id' => $this->store['store_id'], 'table_type' => $tid, 'status' => 0);
		if ($queue = M('Foodshop_queue')->where($where)->order('id ASC')->limit(1)->find()) {
			//TODO 发送模板消息

			if ($user = D('User')->where(array('uid' => $queue['uid']))->find()) {
				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
				$href = C('config.site_url').'/wap.php?c=Foodshop&a=queue&store_id=' . $this->store['store_id'];
				$model->sendTempMsg('OPENTM205984119', array('href' => $href, 'wecha_id' => $user['openid'], 'first' => L_('尊敬的用户您好，已经排到您的号了'), 'keyword1' => $queue['number'], 'keyword2' => date('Y.m.d H:i', $queue['create_time']), 'keyword3' => 1, 'remark' => L_('【X1】通知您进店用餐！',$this->store['name'])), $this->store['mer_id']);
			}


			$voic_baidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
			import('ORG.Net.Http');
			$http = new Http();
			//$return = Http::curlGet($voic_baidu);

			//$voice_return = json_decode($return, true);
			//$voice_access_token = $voice_return['access_token'];
			$msg = L_('请X1号准备就餐',$queue['number']);
			//$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex='.$msg.'&lan=zh&tok='.$voice_access_token.'&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
            $voice_mp3 = text2audio($msg);


			exit(json_encode(array('err_code' => false, 'msg' => L_('已叫号'), 'mp3' => $voice_mp3)));
		} else {
			exit(json_encode(array('err_code' => true, 'msg' => L_('没有排号了'))));
		}
	}

	public function queue_cancel()
	{
		$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
		$where = array('store_id' => $this->store['store_id'], 'status' => 0, 'table_type' => $tid);
		if ($queue = M('Foodshop_queue')->where($where)->order('id ASC')->limit(1)->find()) {
			$where['id'] = $queue['id'];
			if (M('Foodshop_queue')->where($where)->save(array('status' => 1))) {
				exit(json_encode(array('err_code' => false, 'msg' => 'ok')));
			} else {
				exit(json_encode(array('err_code' => true, 'msg' => L_('跳号失败，稍后重试'))));
			}
		} else {
			exit(json_encode(array('err_code' => true, 'msg' => L_('不存在的信息'))));
		}
	}

	public function queue_table()
	{
		$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
		$queue = M('Foodshop_queue')->where(array('table_type' => $tid, 'store_id' => $this->store['store_id'], 'status' => 1))->find();
		if (empty($queue)) {
			$this->error(L_('号码不存在'), 'javascript:parent.location.reload();');
		}
		$now_table = M('Foodshop_table_type')->where(array('store_id' => $this->store['store_id'], 'id' => $tid))->find();
		if (empty($now_table)) {
			$this->error(L_('该店铺没有设置桌台分类'), 'javascript:parent.location.reload();');
		}
		$this->assign('now_table', $now_table);
		$table_list = M('Foodshop_table')->where(array('tid' => $now_table['id'], 'status' => 0))->order('`id` ASC')->select();
		if (empty($table_list)) {
			$this->error(L_('暂无空闲桌台'), 'javascript:parent.location.reload();');
		}
		$this->assign('table_list', $table_list);
		$this->assign('tid', $tid);
		$this->display();
	}

	public function queue_save()
	{
		$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
		$queue = M('Foodshop_queue')->where(array('table_type' => $tid, 'store_id' => $this->store['store_id'], 'status' => 1))->find();
		if (empty($queue)) {
			$this->error(L_('号码不存在'));
		}
		if(M('Foodshop_table')->where(array('store_id'=>$this->store['store_id'],'id'=>$_POST['id']))->setField('status',$_POST['lock'])){
			$where = array('store_id' => $this->store['store_id'], 'id' => $queue['id'], 'table_type' => $tid);
			M('Foodshop_queue')->where($where)->save(array('status' => 2));
			$this->success(L_('操作成功'));
		}else{
			$this->error(L_('操作失败'));
		}
	}

	public function shop_export()
	{
		$param = $_POST;
		$param['type'] = 'shop';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		//$param['store_session'] = $this->store;
		$param['store_session']['store_id'] = $this->store['store_id'];
		$param['lang'] = C('config.system_lang');
		C('config.tmp_system_lang',$param['lang']);
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
		die;
		set_time_limit(0);
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = $this->store['name'] . L_('订单信息');
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);

		// 设置当前的sheet
		$condition_where = 'WHERE o.store_id = '.$this->store['store_id'];
		$where['store_id'] =$this->store['store_id'];


		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$where['real_orderid'] = htmlspecialchars($_GET['keyword']);
				$condition_where .= ' AND o.real_orderid = "'. htmlspecialchars($_GET['keyword']).'"';
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
				unset($where['orderid']);
				$where['order_id'] = $tmp_result['order_id'];
				$condition_where .= ' AND o.order_id = '. $tmp_result['order_id'];
			} elseif ($_GET['searchtype'] == 'name') {
				$where['username'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=  ' AND o.username = "'.  htmlspecialchars($_GET['keyword']).'"';
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['userphone'] = htmlspecialchars($_GET['keyword']);
				$condition_where .= ' AND o.userphone = "'.  htmlspecialchars($_GET['keyword']).'"';
			}elseif ($_GET['searchtype'] == 'third_id') {
				$where['third_id'] =$_GET['keyword'];
			}

		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';

		if($status == 100){
			$where['paid'] = 0;
			$condition_where .= ' AND o.paid=0';
		}else if ($status != -1) {
			$where['status'] = $status;
			$condition_where .= ' AND o.status='.$status;
		}

		if($pay_type&&$pay_type!='balance'){
			$where['pay_type'] = $pay_type;
			$condition_where .= ' AND o.pay_type="'.$pay_type.'"';
		}else if($pay_type=='balance'){
			$where['_string'] = "(`balance_pay`<>0 OR `merchant_balance` <> 0 )";
			$condition_where .= ' AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 )';
		}

		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$where['_string'] =( $where['_string']?' AND ':''). " (create_time BETWEEN ".$period[0].' AND '.$period[1].")";
			$condition_where .=  " AND (o.create_time BETWEEN ".$period[0].' AND '.$period[1].")";
		}

		$count = D('Shop_order')->where($where)->count();
		//$count = D('Shop_order')->where(array('store_id' => $this->store['store_id']))->count();

		$length = ceil($count / 1000);
		for ($i = 0; $i < $length; $i++) {
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);
			$objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息',($i+1)));
			$objActSheet = $objExcel->getActiveSheet();
			$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(40);
			$objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);

			$objActSheet->setCellValue('A1', L_('订单编号'));
			$objActSheet->setCellValue('B1', L_('商品名称'));
			$objActSheet->setCellValue('C1', L_('商品进价'));
			$objActSheet->setCellValue('D1', L_('单价'));
			$objActSheet->setCellValue('E1', L_('单位'));
			$objActSheet->setCellValue('F1', '规格/属性');
			$objActSheet->setCellValue('G1', L_('数量'));
			$objActSheet->setCellValue('H1', L_('商家名称'));
			$objActSheet->setCellValue('I1', L_('店铺名称'));
			$objActSheet->setCellValue('J1', L_('客户姓名'));
			$objActSheet->setCellValue('K1', L_('客户电话'));
			$objActSheet->setCellValue('L1', L_('客户地址'));
			$objActSheet->setCellValue('M1', L_('订单总价'));
			$objActSheet->setCellValue('N1', L_('平台优惠'));
			$objActSheet->setCellValue('O1', L_('商家优惠'));
			$objActSheet->setCellValue('P1', L_('实付总价'));
			$objActSheet->setCellValue('Q1', L_('在线支付金额'));
			$objActSheet->setCellValue('R1', L_('支付时间'));
			$objActSheet->setCellValue('S1', L_('送达时间'));
			$objActSheet->setCellValue('T1', L_('订单状态'));
			$objActSheet->setCellValue('U1', L_('支付情况'));
			//$objActSheet->setCellValue('R1', 支付情况'支付情况');

			$sql = "SELECT  o.*, m.name AS merchant_name,d.name as good_name,d.price as good_price ,d.spec,d.unit,d.cost_price, d.num as good_num, s.name AS store_name FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=o.store_id INNER JOIN " . C('DB_PREFIX') . "merchant AS m ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "shop_order_detail AS d ON `d`.`order_id`=`o`.`order_id` ".$condition_where." ORDER BY o.order_id DESC LIMIT " . $i * 1000 . ",1000";

			$result_list = D()->query($sql);

			$tmp_id = 0;
			if (!empty($result_list)) {
				$index = 1;
				foreach ($result_list as $value) {
					if($tmp_id == $value['real_orderid']){
						$objActSheet->setCellValueExplicit('A' . $index, '');
						$objActSheet->setCellValueExplicit('B' . $index, $value['good_name']);
						$objActSheet->setCellValueExplicit('C' . $index, $value['cost_price']);
						$objActSheet->setCellValueExplicit('D' . $index, $value['good_price']);
						$objActSheet->setCellValueExplicit('E' . $index, $value['unit']);
						$objActSheet->setCellValueExplicit('F' . $index, $value['spec']);
						$objActSheet->setCellValueExplicit('G' . $index, $value['good_num']);
						$objActSheet->setCellValueExplicit('H' . $index,'');
						$objActSheet->setCellValueExplicit('I' . $index,'');
						$objActSheet->setCellValueExplicit('J' . $index, '');
						$objActSheet->setCellValueExplicit('K' . $index, '');
						$objActSheet->setCellValueExplicit('L' . $index, '');
						$objActSheet->setCellValueExplicit('M' . $index, '');
						$objActSheet->setCellValueExplicit('N' . $index, '');
						$objActSheet->setCellValueExplicit('O' . $index, '');
						$objActSheet->setCellValueExplicit('P' . $index, '');
						$objActSheet->setCellValueExplicit('Q' . $index, '');
						$objActSheet->setCellValueExplicit('R' . $index, '');
						$objActSheet->setCellValueExplicit('S' . $index, '');
						$objActSheet->setCellValueExplicit('T' . $index, '');
						$objActSheet->setCellValueExplicit('U' . $index, '');
						$index++;
					}else{
						$index++;
						$objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
						$objActSheet->setCellValueExplicit('B' . $index, $value['good_name']);
						$objActSheet->setCellValueExplicit('C' . $index, $value['cost_price']);
						$objActSheet->setCellValueExplicit('D' . $index, $value['good_price']);
						$objActSheet->setCellValueExplicit('E' . $index, $value['unit']);
						$objActSheet->setCellValueExplicit('F' . $index, $value['spec']);
						$objActSheet->setCellValueExplicit('G' . $index, $value['good_num']);
						$objActSheet->setCellValueExplicit('H' . $index, $value['merchant_name']);
						$objActSheet->setCellValueExplicit('I' . $index, $value['store_name']);
						$objActSheet->setCellValueExplicit('J' . $index, $value['username']);
						$objActSheet->setCellValueExplicit('K' . $index, $value['userphone'] . ' ');
						$objActSheet->setCellValueExplicit('L' . $index, $value['address'] . ' ');
						$objActSheet->setCellValueExplicit('M' . $index, floatval($value['total_price']));
						$objActSheet->setCellValueExplicit('N' . $index, floatval($value['balance_reduce']));
						$objActSheet->setCellValueExplicit('O' . $index, floatval($value['merchant_reduce']));
						$objActSheet->setCellValueExplicit('P' . $index, floatval($value['price']));
						$objActSheet->setCellValueExplicit('Q' . $index, floatval($value['payment_money']));
						$objActSheet->setCellValueExplicit('R' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
						$objActSheet->setCellValueExplicit('S' . $index, $value['use_time'] ? date('Y-m-d H:i:s', $value['use_time']) : '');
						$objActSheet->setCellValueExplicit('T' . $index, D('Shop_order')->status_list[$value['status']]);
						$objActSheet->setCellValueExplicit('U' . $index, D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']));
						$index++;
					}
					$tmp_id = $value['real_orderid'];

				}
			}
			sleep(2);
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
	}

	public function shop_change_price()
	{
		if (empty($this->staff_session['is_change'])) {
			$this->error(L_('您没有修改价格的权限!'));
		}
		if (IS_POST || IS_AJAX) {
			$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
			$where = array('order_id' => $order_id, 'store_id' => $this->store['store_id']);
			$order = D('Shop_order')->get_order_detail($where);
			if (empty($order)) {
				$this->error(L_('不存在的订单信息!'));
			}
			if (!($order['paid'] == 0 && $order['status'] == 0)) {
				$this->error(L_('该订单已经不能修改支付价格了!'));
			}
			$change_price = isset($_POST['change_price']) ? floatval($_POST['change_price']) : $order['price'];
			$change_reason = isset($_POST['change_reason']) ? htmlspecialchars($_POST['change_reason']) : '';
			if ($change_price == $order['price']) {
				$this->error(L_('您没有修改价格!'));
			}
			if ($change_price <= 0) {
				$this->error(L_('您不能把价格改成小于等于0的数'));
			}
			$data = array('price' => $change_price);
			$data['last_staff'] = $this->staff_session['name'];
			$data['last_time'] = $_SERVER['REQUEST_TIME'];
			if (floatval($order['change_price']) == 0) {
				$data['change_price'] = $order['price'];
			}
			$data['change_price_reason'] = $change_reason;

			if (D('Shop_order')->where($where)->save($data)) {
				$phones = explode(' ', $this->store['phone']);
				D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 30, 'name' => $this->staff_session['name'], 'phone' => $phones[0], 'note' => $change_price));
				$this->success(L_('修改成功'));
			} else {
				$this->error(L_('修改出错，稍后重试！'));
			}
		} else {
			$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
			$order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));
			$this->assign('order', $order);

			$this->display();
		}
	}

	public function mall_order_detail()
	{
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));

		if($order['group_id']>0){
			$team_info=M('mall_group_team')->where(array('id'=>$order['team_id']))->find();
			if(isset($team_info)){
				if($team_info['status']==0){
					exit(L_('该订单为拼团订单，该团尚在拼团中，暂不支持操作！'));
				}
				if($team_info['status']==2){
					exit(L_('该订单为拼团订单，该团已失败，暂不支持操作！'));
				}
			}
		}
		$store_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();

		//计算配送距离
		$distance = 0;
		if (C('config.is_riding_distance')) {
		    import('@.ORG.longlat');
		    $longlat_class = new longlat();
		    $distance = $longlat_class->getRidingDistance($this->store['lat'], $this->store['long'], $order['lat'], $order['lng']);
		}
		$distance || $distance = getDistance($this->store['lat'], $this->store['long'], $order['lat'], $order['lng']);

		//获取配送的优惠金额信息
		$delivery_fee_reduce = 0;
		$discounts = D('Shop_discount')->getDiscounts($this->store['mer_id'], $this->store['store_id']);

		if ($d_tmp = D('Shop_goods')->getReduce($discounts, 2, $order['price'])) {
		    $delivery_fee_reduce = $d_tmp['reduce_money'];
		}
		$order['distance'] = $distance;

		//平台配送的相关设置信息的处理
		$distance = $distance / 1000;
		$deliverReturn = D('Deliver_set')->getDeliverInfo($store_shop, $order['price']);

		$pass_distance = $distance > $deliverReturn['basic_distance'] ? floatval($distance - $deliverReturn['basic_distance']) : 0;
		$deliverReturn['delivery_fee'] += round($pass_distance * $deliverReturn['per_km_price'], 2);
		$deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] - $delivery_fee_reduce;
		$deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] > 0 ? $deliverReturn['delivery_fee'] : 0;
		$delivery_fee = $deliverReturn['delivery_fee'];

		$delivery_fee2 = 0;
		$delivery_fee3 = 0;
		$timeList = array('time_select_1' => substr($deliverReturn['delivertime_start'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop'], 0, -3));
		if (!($deliverReturn['delivertime_start2'] == $deliverReturn['delivertime_stop2'] && $deliverReturn['delivertime_start2'] == '00:00:00')) {
		    $pass_distance = $distance > $deliverReturn['basic_distance2'] ? floatval($distance - $deliverReturn['basic_distance2']) : 0;
		    $deliverReturn['delivery_fee2'] += round($pass_distance * $deliverReturn['per_km_price2'], 2);
		    $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] - $delivery_fee_reduce;
		    $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] > 0 ? $deliverReturn['delivery_fee2'] : 0;
		    $delivery_fee2 = $deliverReturn['delivery_fee2'];
		    $timeList['time_select_2'] = substr($deliverReturn['delivertime_start2'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop2'], 0, -3);
		}
		if (!($deliverReturn['delivertime_start3'] == $deliverReturn['delivertime_stop3'] && $deliverReturn['delivertime_start3'] == '00:00:00')) {
		    $pass_distance = $distance > $deliverReturn['basic_distance3'] ? floatval($distance - $deliverReturn['basic_distance3']) : 0;
		    $deliverReturn['delivery_fee3'] += round($pass_distance * $deliverReturn['per_km_price3'], 2);
		    $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] - $delivery_fee_reduce;
		    $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] > 0 ? $deliverReturn['delivery_fee3'] : 0;
		    $delivery_fee3 = $deliverReturn['delivery_fee3'];
		    $timeList['time_select_3'] = substr($deliverReturn['delivertime_start3'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop3'], 0, -3);
		}

		switch (intval(C('config.count_freight_charge_method'))) {
		    case 0:
		        break;
		    case 1:
		        $delivery_fee = ceil($delivery_fee * 10) / 10;
		        $delivery_fee2 = ceil($delivery_fee2 * 10) / 10;
		        $delivery_fee3 = ceil($delivery_fee3 * 10) / 10;
		        break;
		    case 2:
		        $delivery_fee = floor($delivery_fee * 10) / 10;
		        $delivery_fee2 = floor($delivery_fee2 * 10) / 10;
		        $delivery_fee3 = floor($delivery_fee3 * 10) / 10;
		        break;
		    case 3:
		        $delivery_fee = round($delivery_fee * 10) / 10;
		        $delivery_fee2 = round($delivery_fee2 * 10) / 10;
		        $delivery_fee3 = round($delivery_fee3 * 10) / 10;
		        break;
		    case 4:
		        $delivery_fee = ceil($delivery_fee);
		        $delivery_fee2 = ceil($delivery_fee2);
		        $delivery_fee3 = ceil($delivery_fee3);
		        break;
		    case 5:
		        $delivery_fee = floor($delivery_fee);
		        $delivery_fee2 = floor($delivery_fee2);
		        $delivery_fee3 = floor($delivery_fee3);
		        break;
		    case 6:
		        $delivery_fee = round($delivery_fee);
		        $delivery_fee2 = round($delivery_fee2);
		        $delivery_fee3 = round($delivery_fee3);
		        break;
		}

// 		if ($have_two_time) {
// 		    $this->assign(array('time_select_1' => substr($deliverReturn['delivertime_start'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop'], 0, -3), 'time_select_2' => substr($deliverReturn['delivertime_start2'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop2'], 0, -3)));
// 		} else {
// 		    $this->assign(array('time_select_1' => substr($deliverReturn['delivertime_start'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop'], 0, -3)));
// 		}
		$this->assign($timeList);

		$diffTime = 60;
		if ($store_shop['send_time_type'] == 1) {
		    $diffTime = 3600;
		} elseif ($store_shop['send_time_type'] == 2) {
		    $diffTime = 86400;
		} elseif ($store_shop['send_time_type'] == 3) {
		    $diffTime = 86400 * 7;
		} elseif ($store_shop['send_time_type'] == 4) {
		    $diffTime = 86400 * 30;
		}
		$time = time() + $store_shop['work_time'] * $diffTime;

		$this->assign(array('delivery_fee' => $delivery_fee, 'delivery_fee2' => $delivery_fee2, 'delivery_fee3' => $delivery_fee3));
		$this->assign('arrive_datetime', date('Y-m-d H:i', $time));

		$this->assign('distance', round($distance, 2));
		$this->assign('store', $store_shop);
		$this->assign('order', $order);
		$this->display();

	}

	/**
	 * 商城订单更改配送方式  将快递 更改成 其他配送方式
	 */
	public function check_deliver()
	{
		$database = D('Shop_order');
		$order_id = $condition['order_id'] = intval($_POST['order_id']);
		$expect_use_time = isset($_POST['expect_use_time']) ? strtotime(htmlspecialchars($_POST['expect_use_time'])) : 0;
		$condition['store_id'] = $this->store['store_id'];
		$order = $database->field(true)->where($condition)->find();
		if(empty($order)){
			$this->error(L_('订单不存在！'));
			exit;
		}

		if ($order['is_refund']) {
			$this->error(L_('用户正在退款中~！'));
			exit;
		}
		if ($order['paid'] == 0) {
			$this->error(L_('订单未支付，不能接单！'));
			exit;
		}
		if ($order['status'] > 0) {
			$this->error(L_('该订单已处理，不能更改！'));
			exit;
		}

		if ($order['is_pick_in_store'] != 3) {
			$this->error(L_('不是快递配送，不能修改配送方式！'));
			exit;
		}
		$d_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
		if($order['order_from']==1){	/*商家店铺*/
			$d_shop['mall_deliver_type']=D('Merchant_store_shop')->get_mall_deliver_type($d_shop['deliver_type'],$d_shop['mall_deliver_type']);
		}

		if (in_array($d_shop['deliver_type'], array(2, 5))) {
			$this->error(L_('店铺不支持快递以外的配送，不能修改配送方式！'));
			exit;
		}
		$is_pick_in_store = $d_shop['deliver_type'] == 0 || $d_shop['deliver_type'] == 3 ? 0 : 1;

		$deliverReturn = D('Deliver_set')->getDeliverInfo($d_shop, $order['price'] - $order['freight_charge']);

		$start_time = $deliverReturn['delivertime_start'];
		$stop_time = $deliverReturn['delivertime_stop'];

		$start_time2 = $deliverReturn['delivertime_start2'];
		$stop_time2 = $deliverReturn['delivertime_stop2'];

		$start_time3 = $deliverReturn['delivertime_start3'];
		$stop_time3 = $deliverReturn['delivertime_stop3'];

		$distance = getDistance($order['lat'], $order['lng'], $this->store['lat'], $this->store['long']);
		$distance = $distance / 1000;

		$delivery_fee_reduce = 0;
		$discounts = D('Shop_discount')->getDiscounts($this->store['mer_id'], $this->store['store_id']);
		if ($d_tmp = D('Shop_goods')->getReduce($discounts, 2, $order['goods_price'])) {
		    $delivery_fee_reduce = $d_tmp['reduce_money'];
		}

		fdump($delivery_fee_reduce, 1, 1);
		$expect_use_time_temp = $expect_use_time ? $expect_use_time : (time() + $d_shop['send_time'] * 60);//默认的期望送达时间

		if (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time, $stop_time, 1)) {
		    //时间段一
		    $pass_distance = $distance > $deliverReturn['basic_distance'] ? floatval($distance - $deliverReturn['basic_distance']) : 0;
		    $deliverReturn['delivery_fee'] += round($pass_distance * $deliverReturn['per_km_price'], 2);
		    $deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] - $delivery_fee_reduce;
		    $deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] > 0 ? $deliverReturn['delivery_fee'] : 0;
		    $delivery_fee = $order_data['freight_charge'] = $deliverReturn['delivery_fee'];//运费
		} elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time2, $stop_time2, 2)) {
		    //时间段二
		    $pass_distance = $distance > $deliverReturn['basic_distance2'] ? floatval($distance - $deliverReturn['basic_distance2']) : 0;
		    $deliverReturn['delivery_fee2'] += round($pass_distance * $deliverReturn['per_km_price2'], 2);
		    $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] - $delivery_fee_reduce;
		    $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] > 0 ? $deliverReturn['delivery_fee2'] : 0;
		    $delivery_fee = $order_data['freight_charge'] = $deliverReturn['delivery_fee2'];//运费
		} elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time3, $stop_time3, 3)) {
		    //时间段三
		    $pass_distance = $distance > $deliverReturn['basic_distance3'] ? floatval($distance - $deliverReturn['basic_distance3']) : 0;
		    $deliverReturn['delivery_fee3'] += round($pass_distance * $deliverReturn['per_km_price3'], 2);
		    $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] - $delivery_fee_reduce;
		    $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] > 0 ? $deliverReturn['delivery_fee3'] : 0;
		    $delivery_fee = $order_data['freight_charge'] = $deliverReturn['delivery_fee3'];//运费
		} else {
		    $this->error(L_('您选择的时间不在配送时间段内！'));
		}

		$data['status'] = 1;
		$condition['status'] = 0;
		$data['order_status'] = 1;
		$data['is_pick_in_store'] = $is_pick_in_store;
		$data['last_staff'] = $this->staff_session['name'];
		$data['last_time'] = time();
		$data['expect_use_time'] = $expect_use_time_temp;
		$data['last_staff'] = $this->staff_session['name'];
		if ($d_shop['deliver_type'] == 0 || $d_shop['deliver_type'] == 3) {
			$data['no_bill_money'] = $delivery_fee;
            if ($order['other_money']>0) {
                $data['no_bill_money'] += $order['other_money'];
            }
		}
		$data['distance']=$distance*1000;
		if ($database->where($condition)->save($data)) {
		    $result = D('Deliver_supply')->saveOrder($order_id, $this->store, 2, 'shop_order', $delivery_fee);
		    if ($result['error_code']) {
		        D('Shop_order')->where(array('order_id' => $order_id))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
		        $this->error($result['msg']);
		        exit;
		    }

			$phones = explode(' ', $this->store['phone']);
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
			$this->success(L_('已接单'));
		} else {
			$this->error(L_('接单失败'));
		}
	}

	public function check_shop_goods_stock()
	{
		$now_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
		//店铺类别；1-快店；2-商城
		$shop_type=I('get.shop_type',1,'intval');
		if ($result = D('Shop_goods')->check_stock_list($this->store['store_id'], $now_shop['stock_type'],10,$shop_type)) {
			exit(json_encode(array('status' => 1)));
		} else {
			exit(json_encode(array('status' => 0)));
		}
	}

	public function shop_goods_stock()
	{
		//店铺类别；1-快店；2-商城
		$shop_type=I('get.shop_type',1,'intval');

		$now_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
		$this->assign('goods_list', D('Shop_goods')->check_stock_list($this->store['store_id'], $now_shop['stock_type'],10,$shop_type));
		$this->assign('shop_type',$shop_type);
		$this->display();
	}

	public function goods_stock_export()
	{
	    set_time_limit(0);

	    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
	    $title = $this->store['name'] . L_('库存警报提醒');
	    $objExcel = new PHPExcel();
	    $objProps = $objExcel->getProperties();
	    // 设置文档基本属性
	    $objProps->setCreator($title);
	    $objProps->setTitle($title);
	    $objProps->setSubject($title);
	    $objProps->setDescription($title);
	    $objExcel->setActiveSheetIndex(0);
	    $objActSheet = $objExcel->getActiveSheet();
	    $objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
	    $objExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
	    $objExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
	    $objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);

	    $objActSheet->setCellValue('A1', L_('商品编号'));
	    $objActSheet->setCellValue('B1', L_('商品名称'));
	    $objActSheet->setCellValue('C1', L_('商品单价'));
	    $objActSheet->setCellValue('D1', L_('剩余库存'));

	    $now_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
	    //店铺类别；1-快店；2-商城
		$shop_type=I('get.shop_type',1,'intval');

	    $result_list = D('Shop_goods')->check_stock_list($this->store['store_id'], $now_shop['stock_type'],10,$shop_type);
	    if (!empty($result_list)) {
	        $index = 2;
	        foreach ($result_list as $value) {
	            $objActSheet->setCellValueExplicit('A' . $index, $value['number']);
	            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
	            $objActSheet->setCellValueExplicit('C' . $index, $value['price']);
	            $objActSheet->setCellValueExplicit('D' . $index, $value['stock_num']);
	            $index ++;
	        }
	    }
	    //输出
	    $objWriter = new PHPExcel_Writer_Excel5($objExcel);
	    ob_end_clean();
	    header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
	    header("Content-Type:application/force-download");
	    header("Content-Type:application/vnd.ms-execl");
	    header("Content-Type:application/octet-stream");
	    header("Content-Type:application/download");
	    header('Content-Disposition:attachment;filename="' . $title . ':' . date("Y-m-d H:i:s") . '.xls"');
	    header("Content-Transfer-Encoding:binary");
	    $objWriter->save('php://output');
	    exit();
	}

	public function foodshop_goods_stock_export()
	{
	    set_time_limit(0);

	    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
	    $title = $this->store['name'] . L_('库存警报提醒');
	    $objExcel = new PHPExcel();
	    $objProps = $objExcel->getProperties();
	    // 设置文档基本属性
	    $objProps->setCreator($title);
	    $objProps->setTitle($title);
	    $objProps->setSubject($title);
	    $objProps->setDescription($title);
	    $objExcel->setActiveSheetIndex(0);
	    $objActSheet = $objExcel->getActiveSheet();
	    $objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
	    $objExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
	    $objExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
	    $objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);

	    $objActSheet->setCellValue('A1', L_('商品编号'));
	    $objActSheet->setCellValue('B1', L_('商品名称'));
	    $objActSheet->setCellValue('C1', L_('商品单价'));
	    $objActSheet->setCellValue('D1', L_('剩余库存'));

	    $now_shop = D('Merchnat_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
	    $result_list= D('Foodshop_goods')->check_stock_list($this->store['store_id']);
	    //$result_list = D('Shop_goods')->check_stock_list($this->store['store_id'], $now_shop['stock_type']);
	    if (!empty($result_list)) {
	        $index = 2;
	        foreach ($result_list as $value) {
	            $objActSheet->setCellValueExplicit('A' . $index, $value['number']);
	            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
	            $objActSheet->setCellValueExplicit('C' . $index, $value['price']);
	            $objActSheet->setCellValueExplicit('D' . $index, $value['stock_num']);
	            $index ++;
	        }
	    }
	    //输出
	    $objWriter = new PHPExcel_Writer_Excel5($objExcel);
	    ob_end_clean();
	    header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
	    header("Content-Type:application/force-download");
	    header("Content-Type:application/vnd.ms-execl");
	    header("Content-Type:application/octet-stream");
	    header("Content-Type:application/download");
	    header('Content-Disposition:attachment;filename="' . $title . ':' . date("Y-m-d H:i:s") . '.xls"');
	    header("Content-Transfer-Encoding:binary");
	    $objWriter->save('php://output');
	    exit();
	}

	public function foodshop_report()
	{
		if ($this->staff_session['type'] != 2) {
			$this->error(L_('对不起，您没有权限'));
		}
		$this->assign('start_time',date('Y-m-d 00:00:00'));
		$this->assign('end_time',date('Y-m-d 23:59:59'));
		$this->display();
	}

	// 餐饮日结账单统计
	public function ajax_foodshop_report()
	{
		$stime = strtotime(date('Y-m-d 00:00:00'));
		$etime = strtotime(date('Y-m-d 23:59:59'));
		$where = array('paid' => 1, 'store_id' => $this->store['store_id']);
		if ($stime && $etime) {
			$where['pay_time'] = array(array('gt', $stime), array('lt', $etime));
		}

		$return = D('Foodshop_order')->foodshop_report($where);
		exit(json_encode(array('error_code' => false, 'data' => $return, 'count' => count($return))));
	}

	public function ajax_foodshop_report_print()
	{
		$stime = strtotime(date('Y-m-d 00:00:00'));
		$etime = strtotime(date('Y-m-d 23:59:59'));
		$where = array('paid' => 1, 'store_id' => $this->store['store_id']);
		if ($stime && $etime) {
			$where['pay_time'] = array(array('gt', $stime), array('lt', $etime));
		}

		$return = D('Foodshop_order')->foodshop_report($where);

		$printHaddle = new PrintHaddle();
		$printHaddle->printit($return, 'foodshop_order_report', $this->staff_session['name']);// 日结单

		exit(json_encode(array('error_code' => true, 'info' => L_('打印成功'))));

	}

	public function report()
	{
		// 开启店员权限功能 店小二没权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您当前身份为【店小二】，没有报表统计权限，请联系管理员！'));
		}

		$this->assign('type', isset($_GET['type']) ? intval($_GET['type']) : 0);
		$this->display();
	}

	public function ajax_report()
	{
		$type = isset($_POST['type']) ? intval($_POST['type']) : 0;
		$day = isset($_POST['day']) ? intval($_POST['day']) : 0;
		$period = isset($_POST['period']) ? htmlspecialchars($_POST['period']) : '';
		$stime = $etime = 0;
		if ($day) {
		    $day --;
		    $stime = strtotime(date('Y-m-d', strtotime("-{$day} day")));
			$etime = time();
		}
		if ($period) {
			$time_array = explode('-', $period);
			$stime = strtotime($time_array[0]);
			$etime = strtotime($time_array[1]) + 86400 - 1;
		}
		$where = array('paid' => 1, 'store_id' => $this->store['store_id']);
		if ($stime && $etime) {
			$where['pay_time'] = array(array('gt', $stime), array('lt', $etime));
		}

		$store_pays = D('Store_pay')->field(true)->where(array('store_id' => $this->store['store_id']))->select();
		$offline_pay_type = array(0 => array('name' => L_('现金支付'), 'id' => 0, 'store_id' => $this->store['store_id']));
		foreach ($store_pays as $pay) {
			$offline_pay_type[$pay['id']] = $pay;
		}
		if ($type == 0) {//餐饮
			$field = 'sum(total_money - wx_cheap) AS total_price, sum(total_money - system_balance - system_coupon_price - system_score_money - merchant_balance_pay - merchant_balance_give - merchant_discount_money - merchant_coupon_price - pay_money) AS offline_price, sum(pay_money) AS online_price, sum(system_balance + system_coupon_price + system_score_money + merchant_balance_pay + merchant_balance_give + merchant_discount_money + merchant_coupon_price) as balance, 0 as offline_pay';
			$where['business_type'] = 'foodshop';
			$order = D('Plat_order')->field($field)->where($where)->find();

			$field = 'sum(price) AS total_price, sum(price - balance_pay - card_price - payment_money - merchant_balance - card_give_money - score_deducte) AS offline_price, sum(payment_money) AS online_price, sum(balance_pay + card_price + merchant_balance + card_give_money + score_deducte) as balance, offline_pay';
			$where['business_type'] = 'foodshop';

			$order_list = D('Store_order')->field($field)->where($where)->group('offline_pay')->select();
			if (empty($order_list)) $order_list = array();
			if ($order) $order_list[] = $order;
		} elseif ($type == 1) {
// 		    $field = 'sum(price) AS total_price, sum(price - balance_pay - card_price - payment_money - merchant_balance - card_give_money - score_deducte-coupon_price) AS offline_price, sum(payment_money) AS online_price, sum(balance_pay + card_price + merchant_balance + card_give_money + score_deducte+coupon_price) as balance, offline_pay';
		    $field = 'price, card_discount, payment_money, (balance_pay + card_price + merchant_balance + card_give_money + score_deducte+coupon_price) as balance, offline_pay, pay_type';
		    $order_list = D('Store_order')->field($field)->where($where)->select();

		    $newList = array();
		    foreach ($order_list as $order) {
		        if (isset($newList[$order['offline_pay']]) && $newList[$order['offline_pay']]) {
		            $new = $newList[$order['offline_pay']];
		        } else {
		            $new = array('total_price' => 0, 'offline_price' => 0, 'online_price' => 0, 'offline_pay' => 0);
		        }

		        $new['total_price'] += $order['price'];
		        if ($order['pay_type'] == 'offline') {
		            if ($order['card_discount'] != 0) {
		                $new['offline_price'] += round($order['price'] * $order['card_discount'] * 0.1- $order['balance'] - $order['payment_money'], 2);
		            } else {
		                $new['offline_price'] += $order['price'] - $order['balance'] - $order['payment_money'];
		            }
		        }
		        $new['online_price'] += ($order['payment_money']+$order['balance']);
		        $new['offline_pay'] = $order['offline_pay'];
		        $newList[$order['offline_pay']] = $new;
		    }
		    $order_list = $newList;
		} else {
		    $where['order_from'] = 6;
		    $field = 'sum(price) AS total_price, sum(price - balance_pay - card_price - payment_money - merchant_balance - card_give_money - score_deducte) AS offline_price, sum(payment_money) AS online_price, sum(balance_pay + card_price + merchant_balance + card_give_money + score_deducte) as balance, offline_pay';
		    $order_list = D('Shop_order')->field($field)->where($where)->group('pay_type, offline_pay')->select();
		}
		$money_list = array();
		foreach ($order_list as $order) {

			if (isset($money_list['total_price'])) {
				$money_list['total_price']['money'] += round(floatval($order['total_price']),2);
				$money_list['total_price']['money'] = round($money_list['total_price']['money'],2);
			} else {
				$money_list['total_price'] = array('pay_type' => L_('支付总金额'), 'money' =>round( floatval($order['total_price']),2), 'report_export' => U('report_export', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => 'all')), 'report_detail' => U('report_detail', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => 'all')));
			}

			if (isset($money_list['online_price'])) {
				$money_list['online_price']['money'] += floatval($order['balance']) + floatval($order['online_price']);
				$money_list['online_price']['money'] = round($money_list['online_price']['money'],2);
			} else {
				$money_list['online_price'] = array('pay_type' => L_('在线支付'), 'money' => round(floatval($order['balance']) + floatval($order['online_price']),2), 'report_export' => U('report_export', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => 'online')), 'report_detail' => U('report_detail', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => 'online')));
			}

			if (isset($money_list['offline_' . $order['offline_pay']])) {
				$money_list['offline_' . $order['offline_pay']]['money'] += floatval(round($order['offline_price'],2));
				$money_list['offline_' . $order['offline_pay']]['money'] = round($money_list['offline_' . $order['offline_pay']]['money'],2);
			} else {
			    if ($order['offline_pay'] == 0 && $order['offline_price'] > 0) {
			        $money_list['offline_' . $order['offline_pay']] = array('pay_type' => $offline_pay_type[$order['offline_pay']]['name']??L_('线下支付'), 'money' => round(floatval($order['offline_price']),2), 'report_export' => U('report_export', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => $order['offline_pay'])), 'report_detail' => U('report_detail', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => $order['offline_pay'])));
			    } elseif ($order['offline_pay'] > 0) {
			        $money_list['offline_' . $order['offline_pay']] = array('pay_type' => $offline_pay_type[$order['offline_pay']]['name']??L_('线下支付'), 'money' => round(floatval($order['offline_price']),2), 'report_export' => U('report_export', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => $order['offline_pay'])), 'report_detail' => U('report_detail', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => $order['offline_pay'])));
			    }
			}
		}
		exit(json_encode(array('error_code' => false, 'data' => $money_list, 'count' => count($money_list))));
	}

	public function report_detail()
	{
		$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
		$stime = isset($_GET['stime']) ? intval($_GET['stime']) : 0;
		$etime = isset($_GET['etime']) ? intval($_GET['etime']) : 0;
		$pay_type = isset($_GET['pay_type']) ? htmlspecialchars($_GET['pay_type']) : 'all';

		$store_pays = D('Store_pay')->field(true)->where(array('store_id' => $this->store['store_id']))->select();
		$offline_pay_type = array(0 => array('name' => L_('现金支付'), 'id' => 0, 'store_id' => $this->store['store_id']));
		foreach ($store_pays as $pay) {
			$offline_pay_type[$pay['id']] = $pay;
		}
		if ($pay_type == 'all') {
			$pay_type_title = L_('总的支付');
		} elseif ($pay_type == 'online') {
			$pay_type_title = L_('在线支付');
		} else {
			$pay_type_title = L_($offline_pay_type[$pay_type]['name']);
		}
		$mode = new Model();
		if ($type == 0) {
			$sql = "SELECT f.order_id, f.real_orderid, f.name as username, 0 as offline_pay, f.phone as userphone,f.mer_table_discount,f.table_or_vipcard_discount, (p.total_money - p.system_balance - p.system_coupon_price - p.system_score_money - p.merchant_balance_pay - p.merchant_balance_give - p.merchant_discount_money - p.merchant_coupon_price - p.pay_money-p.wx_cheap) AS offline_price, f.price, f.total_price, (p.pay_money+p.system_balance + p.system_coupon_price + p.system_score_money + p.merchant_balance_pay + p.merchant_balance_give + p.merchant_discount_money + p.merchant_coupon_price) as online_price, (f.total_price-f.price+p.system_coupon_price+p.system_score_money+p.merchant_coupon_price+p.merchant_discount_money) as discount_price, p.pay_time, p.pay_type, p.system_coupon_price, p.system_score_money, p.merchant_coupon_price, p.merchant_discount_money FROM " . C('DB_PREFIX') . "foodshop_order AS f INNER JOIN " . C('DB_PREFIX') . "plat_order AS p ON p.business_id=f.order_id WHERE p.paid=1 AND p.business_type='foodshop' AND p.pay_time>'{$stime}' AND p.pay_time<'{$etime}' AND p.store_id='{$this->store['store_id']}'";
			if ($pay_type != 'all' && $pay_type != 'online' && $pay_type == 0) {
				$sql .= " AND p.pay_type='offline'";
			} elseif ($pay_type == 'online') {
				$sql .= " AND (p.system_balance>0 OR p.system_coupon_price>0 OR p.system_score_money>0 OR p.merchant_balance_pay>0 OR p.merchant_balance_give>0 OR p.merchant_discount_money>0 OR p.merchant_coupon_price>0 OR p.pay_money>0)";
			} elseif ($pay_type > 0) {
			    $sql .= " AND false";
			}
			$sql .= ' ORDER BY f.order_id DESC';
			$temp_list = $mode->query($sql);
			foreach ($temp_list as $row) {
			    $row['discount_price'] = max(0, $row['discount_price']);
				$row['price'] = $row['price'] - $row['system_coupon_price'] - $row['system_score_money'] - $row['merchant_coupon_price'] - $row['merchant_discount_money']; // 实付金额（减去优惠）
				if (isset($order_list[$row['order_id']])) {
					$order_list[$row['order_id']]['online_price'] += $row['online_price'];
					$order_list[$row['order_id']]['offline_price'] += $row['offline_price'];
                    if($row['table_or_vipcard_discount']==1)$row['mer_table_discount'] = 0;
					$order_list[$row['order_id']]['mer_table_discount'] = $row['mer_table_discount'];
// 					$order_list[$row['order_id']]['price'] += $row['price'];
// 					$order_list[$row['order_id']]['total_price'] += $row['total_price'];
// 					$order_list[$row['order_id']]['discount_price'] += $row['discount_price'];
				} else {
					$order_list[$row['order_id']] = $row;
				}
			}
			$sql = "SELECT f.order_id, f.real_orderid, f.name as username, s.offline_pay, f.phone as userphone, (s.price - s.balance_pay - s.card_price - s.payment_money - s.merchant_balance - s.card_give_money - s.score_deducte) AS offline_price, (s.balance_pay + s.card_price + s.payment_money + s.merchant_balance + s.card_give_money + s.score_deducte) AS online_price, f.total_price, f.price, (f.total_price-f.price+s.card_price+s.coupon_price+s.score_deducte) as discount_price, s.pay_time, s.pay_type, s.no_discount_money,s.card_price, s.coupon_price,s.score_deducte FROM " . C('DB_PREFIX') . "foodshop_order AS f INNER JOIN " . C('DB_PREFIX') . "store_order AS s ON s.business_id=f.order_id WHERE s.paid=1 AND s.business_type='foodshop' AND s.pay_time>'{$stime}' AND s.pay_time<'{$etime}' AND s.store_id='{$this->store['store_id']}'";
			if ($pay_type != 'all' && $pay_type != 'online') {
				$sql .= " AND s.offline_pay='{$pay_type}'";
			} elseif ($pay_type == 'online') {
				$sql .= " AND (s.balance_pay>0 OR s.card_price OR s.payment_money>0 OR s.merchant_balance>0 OR s.card_give_money>0 OR s.score_deducte>0)";
			}
			$sql .= ' ORDER BY f.order_id DESC';
			$temp_list = $mode->query($sql);
			foreach ($temp_list as $row) {
				$discount_money = 0;
				if ($row['card_discount']) {
		            $discount_money = round($row['price']-($row['price']-$row['no_discount_money'])*$row['card_discount']/10-$row['no_discount_money'],2);
		            $row['discount_price'] += $discount_money;
		        }
			    $row['discount_price'] = max(0, $row['discount_price']);
				$row['price'] = $row['price'] - $row['card_price'] - $row['coupon_price'] - $row['score_deducte'] - $discount_money; // 实付金额（减去优惠）
				if (isset($order_list[$row['order_id']])) {
					$order_list[$row['order_id']]['online_price'] += $row['online_price'];
					$order_list[$row['order_id']]['offline_price'] += $row['offline_price'];
					$order_list[$row['order_id']]['offline_pay'] = $row['offline_pay'];
					if($row['table_or_vipcard_discount']==1)$row['mer_table_discount'] = 0;
					$order_list[$row['order_id']]['mer_table_discount'] = $row['mer_table_discount'];
				} else {
					$order_list[$row['order_id']] = $row;
				}
			}
		} elseif ($type == 1) {
		    $where = "s.paid=1 AND s.store_id={$this->store['store_id']}";
		    if ($stime && $etime) {
		        $where .= " AND s.pay_time>'{$stime}' AND s.pay_time<'{$etime}'";
		    }
		    if ($pay_type != 'all' && $pay_type != 'online') {
		        $where .= " AND s.offline_pay='{$pay_type}'";
		    } elseif ($pay_type == 'online') {
		        $where .= " AND (s.balance_pay>0 OR s.card_price OR s.payment_money>0 OR s.merchant_balance>0 OR s.card_give_money>0 OR s.score_deducte>0)";
		    }
		    // (s.price - s.balance_pay - s.card_price - s.payment_money - s.merchant_balance - s.card_give_money - s.score_deducte - s.coupon_price) AS offline_price
		    $sql = "SELECT s.total_price, s.price, s.discount_price, s.card_discount, (s.balance_pay + s.card_price + s.payment_money + s.merchant_balance + s.card_give_money + s.score_deducte + s.coupon_price) AS online_price, u.nickname as username, u.phone as userphone, s.order_id as real_orderid, s.pay_time, s.pay_type, s.offline_pay,s.no_discount_money FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN " . C('DB_PREFIX') . "user as u ON s.uid=u.uid WHERE {$where}";
			$sql .= ' ORDER BY s.order_id DESC';
		    $order_list = $mode->query($sql);
		    foreach ($order_list as &$order) {
		        if ($order['card_discount'] != 0 && $order['card_discount'] != 10) {
		        	$discount_money =  ($order['price']-$order['no_discount_money']) * (10-$order['card_discount']) * 0.1 ;
		            $order['offline_price'] = max(0,getFormatNumber($order['price']-$discount_money - $order['online_price']));
		        } else {
		            $order['offline_price'] = $order['price'] - $order['online_price'];
		        }
		    }
		} else {
		    $where = "s.paid=1 AND s.store_id={$this->store['store_id']} AND s.order_from=6";
		    if ($stime && $etime) {
		        $where .= " AND s.pay_time>'{$stime}' AND s.pay_time<'{$etime}'";
		    }
		    if ($pay_type != 'all' && $pay_type != 'online') {
		        $where .= " AND s.pay_type='offline' AND s.offline_pay='{$pay_type}'";
		    } elseif ($pay_type == 'online') {
		        $where .= " AND (s.balance_pay>0 OR s.card_price OR s.payment_money>0 OR s.merchant_balance>0 OR s.card_give_money>0 OR s.score_deducte>0)";
		    }
		    $sql = "SELECT s.total_price, s.price, (s.total_price-s.price) as discount_price, (s.price - s.balance_pay - s.card_price - s.coupon_price - s.payment_money - s.merchant_balance - s.card_give_money - s.score_deducte) AS offline_price, (s.balance_pay + s.card_price + s.coupon_price + s.payment_money + s.merchant_balance + s.card_give_money + s.score_deducte) AS online_price, u.nickname as username, u.phone as userphone, s.order_id as real_orderid, s.pay_time, s.pay_type, s.offline_pay FROM " . C('DB_PREFIX') . "shop_order AS s LEFT JOIN " . C('DB_PREFIX') . "user as u ON s.uid=u.uid WHERE {$where}";
			$sql .= ' ORDER BY s.order_id DESC';
		    $order_list = $mode->query($sql);
		}
		$total_money = 0;
		foreach ($order_list as $index => &$order) {
			if ($pay_type == 'all') {
				$total_money += $order['price'];
			} elseif ($pay_type == 'online') {
				$total_money += $order['online_price'];
			} else {
				$total_money += $order['offline_price'];
			}
			if ($pay_type === 0) {
			    if (floatval($order['offline_price']) > 0) {
			        $order['pay_type'] = $offline_pay_type[$order['offline_pay']]['name'];
			    } else {
			        unset($order_list[$index]);
			    }
			} else {
			    if ($order['pay_type']) {
			        if ($order['pay_type'] == 'offline') {
			            if ($order['offline_pay']) {
			                $order['pay_type'] = L_($offline_pay_type[$order['offline_pay']]['name']);
			            } else {
			                $order['pay_type'] = L_('现金支付');
			            }
			        } else {
			            $order['pay_type'] = D('Pay')->get_pay_name($order['pay_type'], 0);
			        }
    			} elseif (floatval($order['offline_price']) > 0) {
    				$order['pay_type'] = L_($offline_pay_type[$order['offline_pay']]['name']);
    			} else {
    			    $order['pay_type'] = L_('余额支付');
    			}
			}
			$order['online_price'] = floatval($order['online_price']);
		}
		$this->assign(array('order_list' => $order_list, 'stime' => date('Y-m-d', $stime), 'etime' => date('Y-m-d', $etime), 'pay_type_title' => $pay_type_title, 'total_money' => $total_money, 'report_export' => U('report_export', array('type' => $type, 'stime' => $stime, 'etime' => $etime, 'pay_type' => $pay_type))));
		$this->display();
	}

	public function report_export()
	{
		set_time_limit(0);

		$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
		$stime = isset($_GET['stime']) ? intval($_GET['stime']) : 0;
		$etime = isset($_GET['etime']) ? intval($_GET['etime']) : 0;
		$pay_type = isset($_GET['pay_type']) ? htmlspecialchars($_GET['pay_type']) : 'all';

		$store_pays = D('Store_pay')->field(true)->where(array('store_id' => $this->store['store_id']))->select();
		$offline_pay_type = array(0 => array('name' => L_('现金支付'), 'id' => 0, 'store_id' => $this->store['store_id']));
		foreach ($store_pays as $pay) {
			$offline_pay_type[$pay['id']] = $pay;
		}
		if ($pay_type == 'all') {
			$pay_type_title = L_('总的支付');
		} elseif ($pay_type == 'online') {
			$pay_type_title = L_('在线支付');
		} else {
			$pay_type_title = $offline_pay_type[$pay_type]['name'];
		}
		$mode = new Model();
		if ($type == 0) {
			$sql = "SELECT f.order_id, f.real_orderid, f.name as username, 0 as offline_pay, f.phone as userphone, (p.total_money - p.system_balance - p.system_coupon_price - p.system_score_money - p.merchant_balance_pay - p.merchant_balance_give - p.merchant_discount_money - p.merchant_coupon_price - p.pay_money-p.wx_cheap) AS offline_price, f.price, f.total_price, (p.pay_money+p.system_balance + p.system_coupon_price + p.system_score_money + p.merchant_balance_pay + p.merchant_balance_give + p.merchant_discount_money + p.merchant_coupon_price) as online_price, (f.total_price-f.price+p.system_coupon_price+p.system_score_money+p.merchant_coupon_price+p.merchant_discount_money) as discount_price, p.pay_time, p.pay_type, p.system_coupon_price, p.system_score_money, p.merchant_coupon_price, p.merchant_discount_money FROM " . C('DB_PREFIX') . "foodshop_order AS f INNER JOIN " . C('DB_PREFIX') . "plat_order AS p ON p.business_id=f.order_id WHERE p.paid=1 AND p.business_type='foodshop' AND p.pay_time>'{$stime}' AND p.pay_time<'{$etime}' AND p.store_id='{$this->store['store_id']}'";
			if ($pay_type != 'all' && $pay_type != 'online' && $pay_type == 0) {
				$sql .= " AND p.pay_type='offline'";
			} elseif ($pay_type == 'online') {
				$sql .= " AND (p.system_balance>0 OR p.system_coupon_price>0 OR p.system_score_money>0 OR p.merchant_balance_pay>0 OR p.merchant_balance_give>0 OR p.merchant_discount_money>0 OR p.merchant_coupon_price>0 OR p.pay_money>0)";
			} elseif ($pay_type > 0) {
			    $sql .= " AND false";
			}
			$sql .= ' ORDER BY f.order_id DESC';
			$temp_list = $mode->query($sql);
			foreach ($temp_list as $row) {
			    $row['discount_price'] = max(0, $row['discount_price']);
				$row['price'] = $row['price'] - $row['system_coupon_price'] - $row['system_score_money'] - $row['merchant_coupon_price'] - $row['merchant_discount_money']; // 实付金额（减去优惠）
				if (isset($order_list[$row['order_id']])) {
					$order_list[$row['order_id']]['online_price'] += $row['online_price'];
					$order_list[$row['order_id']]['offline_price'] += $row['offline_price'];
// 					$order_list[$row['order_id']]['price'] += $row['price'];
// 					$order_list[$row['order_id']]['total_price'] += $row['total_price'];
// 					$order_list[$row['order_id']]['discount_price'] += $row['discount_price'];
				} else {
					$order_list[$row['order_id']] = $row;
				}
			}
			$sql = "SELECT f.order_id, f.real_orderid, f.name as username, s.offline_pay, f.phone as userphone, (s.price - s.balance_pay - s.card_price - s.payment_money - s.merchant_balance - s.card_give_money - s.score_deducte) AS offline_price, (s.balance_pay + s.card_price + s.payment_money + s.merchant_balance + s.card_give_money + s.score_deducte) AS online_price, f.total_price, f.price, (f.total_price-f.price+s.card_price+s.coupon_price+s.score_deducte) as discount_price, s.pay_time, s.pay_type, s.no_discount_money,s.card_price, s.coupon_price,s.score_deducte FROM " . C('DB_PREFIX') . "foodshop_order AS f INNER JOIN " . C('DB_PREFIX') . "store_order AS s ON s.business_id=f.order_id WHERE s.paid=1 AND s.business_type='foodshop' AND s.pay_time>'{$stime}' AND s.pay_time<'{$etime}' AND s.store_id='{$this->store['store_id']}'";
			if ($pay_type != 'all' && $pay_type != 'online') {
				$sql .= " AND s.offline_pay='{$pay_type}'";
			} elseif ($pay_type == 'online') {
				$sql .= " AND (s.balance_pay>0 OR s.card_price OR s.payment_money>0 OR s.merchant_balance>0 OR s.card_give_money>0 OR s.score_deducte>0)";
			}
			$sql .= ' ORDER BY f.order_id DESC';
			$temp_list = $mode->query($sql);
			foreach ($temp_list as $row) {
				$discount_money = 0;
				if ($row['card_discount']) {
		            $discount_money = round($row['price']-($row['price']-$row['no_discount_money'])*$row['card_discount']/10-$row['no_discount_money'],2);
		            $row['discount_price'] += $discount_money;
		        }
			    $row['discount_price'] = max(0, $row['discount_price']);
				$row['price'] = $row['price'] - $row['card_price'] - $row['coupon_price'] - $row['score_deducte'] - $discount_money; // 实付金额（减去优惠）
				if (isset($order_list[$row['order_id']])) {
					$order_list[$row['order_id']]['online_price'] += $row['online_price'];
					$order_list[$row['order_id']]['offline_price'] += $row['offline_price'];
// 					$order_list[$row['order_id']]['price'] += $row['price'];
// 					$order_list[$row['order_id']]['total_price'] += $row['total_price'];
// 					$order_list[$row['order_id']]['discount_price'] += $row['discount_price'];
					$order_list[$row['order_id']]['offline_pay'] = $row['offline_pay'];
				} else {
					$order_list[$row['order_id']] = $row;
				}
			}
		} elseif ($type == 1) {
		    $where = "s.paid=1 AND s.store_id={$this->store['store_id']}";
		    if ($stime && $etime) {
		        $where .= " AND s.pay_time>'{$stime}' AND s.pay_time<'{$etime}'";
		    }
		    if ($pay_type != 'all' && $pay_type != 'online') {
		        $where .= " AND s.offline_pay='{$pay_type}'";
		    } elseif ($pay_type == 'online') {
		        $where .= " AND (s.balance_pay>0 OR s.card_price OR s.payment_money>0 OR s.merchant_balance>0 OR s.card_give_money>0 OR s.score_deducte>0)";
		    }
		    // (s.price - s.balance_pay - s.card_price - s.payment_money - s.merchant_balance - s.card_give_money - s.score_deducte - s.coupon_price) AS offline_price
		    $sql = "SELECT s.total_price, s.price, s.discount_price, s.card_discount, (s.balance_pay + s.card_price + s.payment_money + s.merchant_balance + s.card_give_money + s.score_deducte + s.coupon_price) AS online_price, u.nickname as username, u.phone as userphone, s.order_id as real_orderid, s.pay_time, s.pay_type, s.offline_pay FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN " . C('DB_PREFIX') . "user as u ON s.uid=u.uid WHERE {$where}";
			$sql .= ' ORDER BY s.order_id DESC';
		    $order_list = $mode->query($sql);
		    foreach ($order_list as &$order) {
		        if ($order['card_discount'] != 0) {
		            $order['offline_price'] = bcmul($order['price'], bcmul($order['card_discount'] , '0.1',2),2) - $order['online_price'];
		        } else {
		            $order['offline_price'] = $order['price'] - $order['online_price'];
		        }
		    }
		} else {
		    $where = "s.paid=1 AND s.store_id={$this->store['store_id']} AND s.order_from=6";
		    if ($stime && $etime) {
		        $where .= " AND s.pay_time>'{$stime}' AND s.pay_time<'{$etime}'";
		    }
		    if ($pay_type != 'all' && $pay_type != 'online') {
		        $where .= " AND s.pay_type='offline' AND s.offline_pay='{$pay_type}'";
		    } elseif ($pay_type == 'online') {
		        $where .= " AND (s.balance_pay>0 OR s.card_price OR s.payment_money>0 OR s.merchant_balance>0 OR s.card_give_money>0 OR s.score_deducte>0)";
		    }
		    $sql = "SELECT s.total_price, s.price, (s.total_price-s.price) as discount_price, (s.price - s.balance_pay - s.card_price - s.payment_money - s.merchant_balance - s.card_give_money - s.score_deducte) AS offline_price, (s.balance_pay + s.card_price + s.payment_money + s.merchant_balance + s.card_give_money + s.score_deducte) AS online_price, u.nickname as username, u.phone as userphone, s.order_id as real_orderid, s.pay_time, s.pay_type, s.offline_pay FROM " . C('DB_PREFIX') . "shop_order AS s LEFT JOIN " . C('DB_PREFIX') . "user as u ON s.uid=u.uid WHERE {$where}";
			$sql .= ' ORDER BY s.order_id DESC';
		    $order_list = $mode->query($sql);
		}
		$total_money = 0;
		foreach ($order_list as $index => &$order) {
			if ($pay_type == 'all') {
				$total_money += $order['price'];
			} elseif ($pay_type == 'online') {
				$total_money += $order['online_price'];
			} else {
				$total_money += $order['offline_price'];
			}
			if ($pay_type === 0) {
			    if (floatval($order['offline_price']) > 0) {
			        $order['pay_type'] = $offline_pay_type[$order['offline_pay']]['name'];
			    } else {
			        unset($order_list[$index]);
			    }
			} else {
			    if ($order['pay_type']) {
			        if ($order['pay_type'] == 'offline') {
			            if ($order['offline_pay']) {
			                $order['pay_type'] = $offline_pay_type[$order['offline_pay']]['name'];
			            } else {
			                $order['pay_type'] = L_('现金支付');
			            }
			        } else {
			            $order['pay_type'] = D('Pay')->get_pay_name($order['pay_type'], 0);
			        }
    			} elseif (floatval($order['offline_price']) > 0) {
    				$order['pay_type'] = $offline_pay_type[$order['offline_pay']]['name'];
    			} else {
    			    $order['pay_type'] = L_('余额支付');
    			}
			}
			$order['online_price'] = floatval($order['online_price']);
		}

		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = $this->store['name'] . $pay_type_title . L_('统计报表');
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
		$objExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);

		$objActSheet->setCellValue('A1', L_('订单号'));
		$objActSheet->setCellValue('B1', L_('用户姓名'));
		$objActSheet->setCellValue('C1', L_('用户电话'));
		$objActSheet->setCellValue('D1', L_('订单总价'));
		$objActSheet->setCellValue('E1', L_('优惠金额'));
		$objActSheet->setCellValue('F1', L_('实付金额'));
		$objActSheet->setCellValue('G1', L_('在线支付金额'));
		$objActSheet->setCellValue('H1', L_('线下支付金额'));
		$objActSheet->setCellValue('I1', L_('支付时间'));
		$objActSheet->setCellValue('J1', L_('支付类型'));
		if (!empty($order_list)) {
			$index = 2;
			foreach ($order_list as $value) {
				$objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
				$objActSheet->setCellValueExplicit('B' . $index, $value['username']);
				$objActSheet->setCellValueExplicit('C' . $index, $value['userphone']);
				$objActSheet->setCellValueExplicit('D' . $index, getFormatNumber($value['total_price']));
				$objActSheet->setCellValueExplicit('E' . $index, getFormatNumber($value['discount_price']));
				$objActSheet->setCellValueExplicit('F' . $index, getFormatNumber($value['price']));
				$objActSheet->setCellValueExplicit('G' . $index, getFormatNumber($value['online_price']));
				$objActSheet->setCellValueExplicit('H' . $index, getFormatNumber($value['offline_price']));
				$objActSheet->setCellValueExplicit('I' . $index, date('Y-m-d H:i:s', $value['pay_time']));
				$objActSheet->setCellValueExplicit('J' . $index, $value['pay_type']);
				$index ++;
			}
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="' . $title . ':' . date("Y-m-d H:i:s") . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
	}

	public function market()
	{
		if($this->now_merchant['status']!=1){
			if($this->now_merchant['merchant_end_time']>0 && $this->now_merchant['merchant_end_time']<time()){
				$this->error(L_('商家已过期，暂不可使用该功能！'));
			}
			$this->error(L_('商家已X1，暂不可使用该功能！',$this->merchant_status[$this->now_merchant['status']]));
		}

		// 开启店员权限功能 店小二无线下零售权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您当前身份为【店小二】，暂无线下零售权限，请联系管理员！'));
		}

		//店铺类别；1-快店；2-商城
		$shop_type=I('shop_type',1,'intval');
		$this->assign('shop_type',$shop_type);

		$this->assign(array('name' => $this->staff_session['name'], 'date' => date('Y-m-d')));
		$offline_pay_list = M('Store_pay')->where(array('store_id' => $this->store['store_id']))->order('`id` ASC')->select();
		$this->assign('offline_pay_list', $offline_pay_list);
		$this->assign('store_id', $this->store['store_id']);
		$this->assign('is_change', $this->staff_session['is_change']);

		$now_store['discount_txt'] = unserialize($this->store['discount_txt']);
		$this->assign('has_discount',$now_store['discount_txt'] ? true : false);
		$this->assign('vip_discount_type', $this->store['vip_discount_type']);
		$this->assign('discount_type', isset($now_store['discount_txt']['discount_type']) ? $now_store['discount_txt']['discount_type'] : 0);
		$this->assign('discount_percent', isset($now_store['discount_txt']['discount_percent']) ? $now_store['discount_txt']['discount_percent'] : 0);
		$this->assign('condition_price', isset($now_store['discount_txt']['condition_price']) ? $now_store['discount_txt']['condition_price'] : 0);
		$this->assign('minus_price', isset($now_store['discount_txt']['minus_price']) ? $now_store['discount_txt']['minus_price'] : 0);
		$this->assign('platform_score', isset($this->now_merchant['platform_score']) ? $this->now_merchant['platform_score'] : 0);
		$this->assign('mer_id', isset($this->now_merchant['mer_id']) ? $this->now_merchant['mer_id'] : 0);

		// 使用老版本
		// 是否开启到店付，线下零售绑定用户支付
		/*if ($this->config['is_open_pay_bind_user'] != 1) {
			$this->display('market_old');
		}else{
			$this->display();
		}*/
        $this->display();
	}

	public function print_prev_order(){
	    $where['order_from'] = 6;
	    $where['paid'] = 1;
	    $order = M('Shop_order')->where($where)->order('order_id DESC')->find();
        $printHaddle = new PrintHaddle();
        $res = $printHaddle->printit($order['order_id'], 'shop_order', 0);
        exit(json_encode(array('err_code' => false, 'msg' => L_('打印上单成功！'))));
    }

	public function ajax_shop_goods()
	{
		ini_set('memory_limit', '2048M');
		$is_refresh = isset($_POST['refresh']) ? intval($_POST['refresh']) : 1;
        if(!empty($_POST['staff_offline'])){
            $product_list = D('Shop_goods')->get_list($this->store['store_id'], $is_refresh, [],['sort_status'=>1]);
        }else{
		    $product_list = D('Shop_goods')->get_list($this->store['store_id'], $is_refresh, ['status' => 1],['sort_status'=>1]);
        }
		header('Content-Type:application/json');
		if ($product_list) {
			foreach($product_list as $key => $_sort){
				foreach($_sort['goods_list'] as $key2 => $_goods){
					if($_goods['sell_type'] == 2){// 称重
						$product_list[$key]['goods_list'][$key2]['price'] = $_goods['unit_price'];
					}
				}
			}
			exit(json_encode(array('err_code' => false, 'data' => $product_list)));
		} else {
			exit(json_encode(array('err_code' => true, 'data' => L_('暂无商品'))));
		}
	}

	/**
	 * 通过手机号获取会员信息
	 */
	public function ajax_user_by_phone()
	{
		$phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
		if ($this->config['scan_from_merchang_card']) {
            if (trim(I('auth_code'))) {
                // 扫商家会员卡二维码
                $key = trim(I('auth_code'));
                $condition_user['mer_id'] = $this->store['mer_id'];
                $condition_user['status'] = array('eq',1);
                $condition_user['_string'] = "id={$key}";
                $card = D('Card_userlist')->field(true)->where($condition_user)->find();
                if($card){
                    $user = D('User')->field(true)->where(array('uid' => $card['uid']))->find();
                }
            }else{
                if(strlen($phone) == 11) {
                    // 手机号搜索
                    $user = D('User')->field(true)->where(array('phone' => $phone))->find();
                }else{
                    // 输入实体卡号
                    $condition_user['mer_id'] = $this->store['mer_id'];
                    $condition_user['status'] = array('eq',1);
                    $condition_user['_string'] = "physical_id = {$phone}";
                    $card = D('Card_userlist')->field(true)->where($condition_user)->find();
                    if($card){
                        $user = D('User')->field(true)->where(array('uid' => $card['uid']))->find();
                    }
                }
            }
        }else{
            if ($_POST['auth_code']) {
				$phone_info = D('User_payid')->get($_POST['auth_code']);
				if ($phone_info['error']) {
					exit(json_encode(array('err_code' => true, 'data' => $phone_info['msg'])));
				}else{
					$phone = $phone_info['phone'];
				}
			}
			if (!$phone) {
				exit(json_encode(array('err_code' => true, 'data' => L_('请输入正确手机号'))));
			}

			//开启客户导入生成真实用户
			if(C('config.open_user_import_create_user') == 1){
				// 使用实体卡号搜索
				$user = D('User')->field(true)->where('phone ="'.$phone.'" OR cardid="'.$phone.'"')->find();
			}else{
				$user = D('User')->field(true)->where('phone ="'.$phone.'"')->find();
			}
        }

		if( $user ) {
			$card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id'],'status'=>1))->find();

			$card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();


			$return = array('name' => $user['truename'] ? $user['truename'] : $user['nickname'], 'sex' => $user['sex'] == 1 ? L_('男') : L_('女'), 'card_id' => $card['id'], 'phone' => $user['phone']);
			$return['card_money'] = $card['card_money'] + $card['card_money_give'];
			$return['card_score'] = $card['card_score'];
			$return['physical_id'] = $card['physical_id'];
			$return['uid'] = $user['uid'];
			$return['discount'] = $card_source['discount'];

			// 会员等级优惠
			$level_off=false;
			if( !empty($user) && isset($user['level']) ){
				$leveloff=!empty($this->store['leveloff']) ? unserialize($this->store['leveloff']) :'';
				/****type:0无优惠 1百分比 2立减*******/
				if(!empty($leveloff) && isset($leveloff[$user['level']]) ){
					$level_off=$leveloff[$user['level']];
					if($level_off['type']==1){
						$level_off['offstr']=L_('单价按原价X1%来结算',$level_off['vv']);
					}elseif($level_off['type']==2){
						$level_off['offstr']=L_('单价立减X1',$level_off['vv'].C('config.Currency_txt'));
					}

				}
			}
			$return['leveloff'] = $level_off;;

			exit(json_encode(array('err_code' => false, 'data' => $return)));
		}else{
			exit(json_encode(array('err_code' => true, 'data' => L_('用户不存在'))));
		}
	}

	/**
	 * 获取会员卡信息
	 */
    public function ajax_card_new()
    {
        //获取登录店员信息
        $staff_info = session('staff');
        if (!$staff_info) {
            exit(json_encode(array('err_code' => true, 'data' => L_('店员未登录'))));
        }
        //获取店员绑定店铺信息
        $store_info = M('merchant_store')
            ->field('store_id, area_id, is_internal_card_pay')
            ->where(['store_id' => $staff_info['store_id']])
            ->find();
        if (!$store_info) {
            exit(json_encode(array('err_code' => true, 'data' => L_('未找到店员店铺信息'))));
        }

        $key = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
        $type = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : '';
        !isset($_POST['auth_code']) && $_POST['auth_code'] = $key;

        // 一卡通查询
        if ($type == 'ecard'){
            try{
                $cardDta = D('InternalMembershipCard')->getInternalCard($store_info, $key, $_POST['password']);
            }catch (\Exception $e){
                exit(json_encode(array('err_code' => true, 'data' => $e->getMessage())));
            }
            exit(json_encode($cardDta));
        }

        if ($this->config['scan_from_merchang_card']) {
            if (trim(I('auth_code'))) {
                // 扫商家会员卡二维码
                $key = trim(I('auth_code'));
                $condition_user['mer_id'] = $this->store['mer_id'];
                $condition_user['status'] = array('eq',1);
                $condition_user['_string'] = "(id={$key} OR wx_card_code={$key})";
                $card = D('Card_userlist')->field(true)->where($condition_user)->find();
                if($card){
                    $user = D('User')->field(true)->where(array('uid' => $card['uid']))->find();
                }elseif(strlen($key) == 11){
                    $user = D('User')->field(true)->where(array('phone' => $key))->find();
                }
            }else{
                if(strlen($key) == 11) {
                    // 手机号搜索
                    $user = D('User')->field(true)->where(array('phone' => $key))->find();
                }else{
                    // 输入实体卡号
                    $condition_user['mer_id'] = $this->store['mer_id'];
                    $condition_user['status'] = array('eq',1);
                    $condition_user['_string'] = "physical_id = {$key}";
                    $card = D('Card_userlist')->field(true)->where($condition_user)->find();
                    if($card){
                        $user = D('User')->field(true)->where(array('uid' => $card['uid']))->find();
                    }
                }
            }
        }else{
			if ($_POST['auth_code']) {
				$phone_info = D('User_payid')->get($_POST['auth_code']);
				if ($phone_info['error']) {
					exit(json_encode(array('err_code' => true, 'data' => $phone_info['msg'])));
				}else{
                    $phone_info['phone'] && $key = $phone_info['phone'];
				}
			}

			//开启客户导入生成真实用户
			if(C('config.open_user_import_create_user') == 1){
				// 使用实体卡号搜索
				$user = D('User')->field(true)->where('phone ="'.$key.'" OR cardid="'.$key.'"')->find();
			}else{
				$user = D('User')->field(true)->where('phone ="'.$key.'"')->find();
			}
		}
        if($user) {
            $card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id'],'status'=>1))->find();
            $card_data = $this->format_ajax_card($card);
            if ($card_data['err_code']) {
                $card_data['err_code'] = false;
                $card_data['data'] = array(
                    'name' => $user['truename'] ? $user['truename'] : $user['nickname'],
                    'sex' => $user['sex'] == 1 ? L_('男') : L_('女'),
                    'phone' => $user['phone']
                );
                $card_data['data']['card_new'] = D('Card_new')->get_use_coupon_by_params($user['uid'], $this->store['mer_id'], 'shop');
                $card_data['data']['system_card'] = D('System_coupon')->get_use_coupon_by_params($user['uid'], 'shop');;
            }

            // 会员等级优惠
            $level_off=false;
            if( !empty($user) && isset($user['level']) ){
                $leveloff=!empty($this->store['leveloff']) ? unserialize($this->store['leveloff']) :'';
                /****type:0无优惠 1百分比 2立减*******/
                if(!empty($leveloff) && isset($leveloff[$user['level']]) ){
                    $level_off=$leveloff[$user['level']];
                    if($level_off['type']==1){
                        $level_off['offstr']=L_('单价按原价X1%来结算',$level_off['vv']);
                    }elseif($level_off['type']==2){
                        $level_off['offstr']=L_('单价立减X1',$level_off['vv'].C('config.Currency_txt'));
                    }
                }
            }
            $card_data['data']['leveloff'] = $level_off;;
            $card_data['data']['birthday'] = $user['birthday']?:'';
            exit(json_encode($card_data));

        } else {
            exit(json_encode(array('err_code' => true, 'data' => L_('没有用户信息'))));
        }
    }

	/**
	 * 获取会员卡信息
	 */
	public function ajax_card()
	{
		$key = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
		if(strlen($key) == 11 && $user = D('User')->field(true)->where(array('phone' => $key,'status'=>array('eq',1)))->find()) {
			$card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id'], 'status'=>array('eq',1)))->find();
			exit(json_encode($this->format_ajax_card($card)));
		}else{
			$condition_user['mer_id'] = $this->store['mer_id'];
			$condition_user['status'] = array('eq',1);
			$condition_user['_string'] = "id='{$key}' OR wx_card_code='{$key}' OR physical_id = '{$key}'";
			$card = D('Card_userlist')->field(true)->where($condition_user)->find();

			if(empty($card) &&(($user = D('User')->field(true)->where(array('truename' =>  $key))->find()) || ($user = D('User')->field(true)->where(array('nickname' =>  array('like','%'.$key.'%')))->find())) ){
				$card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id']))->find();
			}
			exit(json_encode($this->format_ajax_card($card)));
		}
	}

	public function format_ajax_card($card){
		if(empty($card)){
			return array('err_code' => true, 'data' => L_('没有会员卡信息'));
		}

		$card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
		if (empty($card_source)) {
			return array('err_code' => true, 'data' => L_('没有会员卡信息'));
		}
		if ($user = D('User')->field(true)->where(array('uid' => $card['uid']))->find()) {
			$return = array('name' => $user['truename'] ? $user['truename'] : $user['nickname'], 'sex' => $user['sex'] == 1 ? L_('男') : L_('女'), 'card_id' => $card['id'], 'phone' => $user['phone']);
			$return['card_money'] = $card['card_money'] + $card['card_money_give'];
			$return['card_score'] = $card['card_score'];
			$return['physical_id'] = $card['physical_id'];
			$return['uid'] = $user['uid'];
			$return['discount'] = $card_source['discount'];

			$return['card_new'] = D('Card_new')->get_use_coupon_by_params($user['uid'], $this->store['mer_id'], 'shop', $this->store['store_id']);
			$return['system_card'] = D('System_coupon')->get_use_coupon_by_params($user['uid'], 'shop');;

			return array('err_code' => false, 'data' => $return);
		} else {
			return array('err_code' => true, 'data' => L_('此卡找不到相应的用户信息'));
		}
	}

	//预约
	public function pay_appoint_order(){
//        echo json_encode($_POST['order_id']);die;
        $appoint_order = M('Appoint_order')->where(array('order_id'=>$_POST['order_id']))->find();
		if(empty($appoint_order)){
			return array('err_code' => true, 'data' => L_('订单不存在!'));
		}

		$now_pay_money = $appoint_order['product_price'] - $appoint_order['product_payment_price'];

		if(!$now_pay_money){
			$now_pay_money = $appoint_order['appoint_price'] - $appoint_order['payment_money'];
		}

		if($now_pay_money <= 0){
			exit(json_encode(array('status' => 0,'msg' => '所需支付金额为0！')));
		}
		if($this->config['cash_pay_qrcode']){
			$pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id=' . (3100000000 + $_POST['order_id']);
		}else{
			$pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($this->config['site_url'].'/wap.php?c=My&a=appoint_order_before&order_id='.$_POST['order_id']);
		}

		$now_pay_money = getFormatNumber($now_pay_money);

		exit(json_encode(array('error_code' => false, 'real_orderid' => $appoint_order['real_orderid'], 'order_id' => $_POST['order_id'], 'price' => $now_pay_money, 'pay_qrcode_url' => $pay_qrcode_url, 'discount_msg' => trim('余款支付'))));

	}

	public function shop_order_save()
	{
		// 开启店员权限功能 店小二无线下零售权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			exit(json_encode(array('error_code' => true, 'msg' => L_('您当前身份为【店小二】，暂无线下零售权限，请联系管理员！'))));
		}

		//order_from = 6;
		$data = isset($_POST['data']) ? ($_POST['data']) : '';
		$uid = isset($data['card_data']['uid']) && $data['card_data']['uid'] ? intval($data['card_data']['uid']) : 0;
		$store_id = $this->store['store_id'];
		$return = D('Shop_goods')->checkCart($store_id, $uid, $data['goods_data'], 2, 0, true);
		fdump_api($return,'shop/shop_order_save',true);
		if ($return['error_code']) exit(json_encode($return));
		if (IS_POST) {
			if (!($user = D('User')->field(true)->where(array('uid' => $uid))->find())) {
				$uid = 0;
			}
			D('Shop_order')->where(array('staff_id' => $this->staff_session['id'], 'order_from' => 6, 'paid' => 0, 'is_del' => 0))->save(array('is_del' => 1));
			$now_time = time();
			$order_data = array();
			$order_data['mer_id'] = $return['mer_id'];
			$order_data['store_id'] = $return['store_id'];
			$order_data['uid'] = $uid;//TODO
			$order_data['staff_id'] = $this->staff_session['id'];
			$order_data['last_staff'] = $this->staff_session['name'];

			$order_data['desc'] = '';
			$order_data['create_time'] = $now_time;
			$order_data['last_time'] = $now_time;
			$order_data['invoice_head'] = '';
			$order_data['village_id'] = 0;

			$order_data['num'] = $return['total'];
			$order_data['packing_charge'] = $return['packing_charge'];//打包费
			$order_data['merchant_reduce'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//店铺优惠
			$order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
			$orderid  = date('ymdhis').substr(microtime(), 2, 8 - strlen($uid)). $uid;
			$order_data['real_orderid'] = $orderid;
			$order_data['no_bill_money'] = 0;//无需跟平台对账的金额

			$order_data['is_pick_in_store'] = 2;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
			$delivery_fee = $order_data['freight_charge'] = 0;//运费
			$order_data['username'] = isset($user['nickname']) && $user['nickname'] ? $user['nickname'] : '';
			$order_data['userphone'] = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';
			$order_data['address'] = '';
			$order_data['address_id'] = 0;
			$order_data['pick_id'] = 0;
			$order_data['status'] = 0;
			$order_data['order_from'] = 6;
			$order_data['expect_use_time'] = 0;//客户期望使用时间
			$order_data['can_discount_money'] =  $return['can_discount_money'];//可用商家优惠券的总价

            $hasReduceMoney = 0;
            if(C('config.vip_level_discount_sequence')==1) { //满减后计算
                if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
                    if ($return['sorts_discount_type'] == 0) { //折上折
                        $tempMoney = round($return['vip_discount_money'] - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                        $hasReduceMoney = $tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2);
                        foreach ($return['goods'] as &$goods) {
                            $rate = sprintf('%.2f', ($goods['price'] * $goods['num']) / $return['price']);
                            $rate = $rate ? $rate : 1;
                            $goods['discount_price'] = floatval(round($goods['discount_price'] - (($hasReduceMoney * $rate) / $goods['num']), 2));
                        }
                    } else { //折扣最优
                        if ($return['optimalDiscountMoney'] > 0) {
                            $totalOptimalMoney = $return['optimalDiscountMoney'];
                            $return['vip_discount_money'] = $totalOptimalMoney;
                            $tempMoney = round($totalOptimalMoney - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                            $hasReduceMoney = $tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2);
                            foreach ($return['goods'] as &$goods) {
                                $rate = sprintf('%.2f', ($goods['price'] * $goods['num']) / $return['price']);
                                $rate = $rate ? $rate : 1;
                                $goods['discount_price'] = floatval(round($goods['price'] - (($hasReduceMoney * $rate) / $goods['num']), 2));
                            }
                        }
                    }
                }
                $return['vip_discount_money']  = round($return['vip_discount_money'] - $hasReduceMoney,2);
                $return['discount_price']  = $return['vip_discount_money'];
                $return['can_discount_money'] = round($return['can_discount_money'] - $hasReduceMoney,2);
                $order_data['vip_level_after'] = 1;//会员等级优惠满减后计算
            }else{ //正常计算
                $hasReduceMoney = $return['vip_level_reduce_money'] ? $return['vip_level_reduce_money'] : 0;
            }

            $order_data['vip_level_reduce_money'] = $hasReduceMoney;//会员等级折扣优惠金额
            $order_data['vip_level_discount'] = $return['vip_discount'];//会员等级折扣

			$order_data['goods_price'] = $return['price'];//商品的价格
			$order_data['extra_price'] = $return['extra_price'];//另外要支付的金额
			$order_data['discount_price'] = $return['vip_discount_money'];//商品折扣后的总价
			$order_data['total_price'] = $return['price'] + $delivery_fee + $return['packing_charge'];//订单总价  商品价格+打包费+配送费
			$order_data['price'] = $order_data['discount_price'] + $delivery_fee + $return['packing_charge'] - $order_data['merchant_reduce'] - $order_data['balance_reduce'];//实际要支付的价格
			$order_data['discount_detail'] = $return['discount_list'] ? serialize($return['discount_list']) : '';//优惠详情

			$discountMsg = '';
			foreach($return['discount_list'] as $row) {
			    switch ($row['discount_type']) {
			        case 1:
			            $discountMsg .= '平台首单优惠,';
			            break;
			        case 2:
			            $discountMsg .= '平台满减优惠,';
			            break;
			        case 3:
			            $discountMsg .= '店铺首单优惠,';
			            break;
			        case 4:
			            $discountMsg .= '店铺满减优惠,';
			            break;
			    }
			}

			foreach ($return['goods'] as $goods) {
			    switch ($goods['discount_type']) {
			        case 1:
			            $discountMsgDP = '店铺折扣优惠,';
			            break;
			        case 2:
			            $discountMsgFL = '分类折扣优惠,';
			            break;
                    case 3:
                        $discountMsgVP = '会员等级优惠,';
                        break;
                    case 4:
                        $discountMsgDVP = '店铺折扣优惠,会员等级优惠,';
                        break;
                    case 5:
                        $discountMsgFVP = '分类折扣优惠,会员等级优惠,';
                        break;
			    }
			}
			if(isset($discountMsgDP) ){
                $discountMsg.=$discountMsgDP;
            }
            if(isset($discountMsgFL) ){
                $discountMsg.=$discountMsgFL;
            }
            if(isset($discountMsgVP) ){
                $discountMsg.=$discountMsgVP;
            }
            if(isset($discountMsgDVP) ){
                $discountMsg.=$discountMsgDVP;
            }
            if(isset($discountMsgFVP) ){
                $discountMsg.=$discountMsgFVP;
            }
			if($return['packing_charge']>0){
			    $discountMsg = trim($discountMsg,',');
			    if($discountMsg!=''){
                    $discountMsg .= L_('之后且含打包费的价格');
                }else{
                    $discountMsg .= L_('含打包费的价格');
                }
            }else{
                $discountMsg = trim($discountMsg,',');
                if($discountMsg!='') {
                    $discountMsg .= L_('之后的价格');
                }
            }
			// if ($return['price'] - $return['store_discount_money'] > 0) {
			// 	$order_data['discount_detail'] = '店铺折扣优惠：' . floatval($return['price'] - $return['store_discount_money']);
			// }
			// if ($return['store_discount_money'] - $return['vip_discount_money'] > 0) {
			// 	$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';VIP优惠：' . floatval($return['store_discount_money'] - $return['vip_discount_money']) : 'VIP优惠：' . floatval($return['store_discount_money'] - $return['vip_discount_money']);
			// }
			// if ($return['sys_first_reduce']> 0) {
			// 	$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';平台首单减：' . $return['sys_first_reduce'] : '平台首单减：' . $return['sys_first_reduce'];
			// }
			// if ($return['sys_full_reduce'] > 0) {
			// 	$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';平台满减：' . $return['sys_full_reduce'] : '平台满减：' . $return['sys_full_reduce'];
			// }
			// if ($return['sto_first_reduce']> 0) {
			// 	$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';店铺首单减：' . $return['sto_first_reduce'] : '店铺首单减：' . $return['sto_first_reduce'];
			// }
			// if ($return['sto_full_reduce'] > 0) {
			// 	$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';店铺满减：' . $return['sto_full_reduce'] : '店铺满减：' . $return['sto_full_reduce'];
			// }

			$order_data['reduce_stock_type'] = $return['store']['reduce_stock_type'];//'减库存类型（0：支付后，1：下单后）'

			if ($order_id = D('Shop_order')->add($order_data)) {
				D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 0));
				// if ($order_data['is_pick_in_store'] == 2 && $order_data['status'] == 7) {
				// 	D('Pick_order')->add(array('store_id' => $order_data['store_id'], 'order_id' => $order_id, 'pick_id' => $pick_id, 'status' => 0, 'dateline' => time()));
				// 	//D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 11, 'name' => $this->staff_session['name'], 'phone' => $this->store['phone']));//分配到自提点
				// }
                $mer_goods_can_use_score_max = 0; //商家加分商品最高可使用数量
				$detail_obj = D('Shop_order_detail');
				$goods_obj = D("Shop_goods");
				foreach ($return['goods'] as $grow) {
					$detail_data = array('store_id' => $return['store_id'], 'order_id' => $order_id, 'number' => isset($grow['number']) && $grow['number'] ? $grow['number'] : '', 'cost_price' => $grow['cost_price'], 'unit' => $grow['unit'], 'goods_id' => $grow['goods_id'], 'name' => $grow['name'], 'price' => $grow['price'], 'num' => $grow['num'], 'spec' => $grow['str'], 'spec_id' => $grow['spec_id'], 'create_time' => time(),'extra_price'=>$grow['extra_price'] ? $grow['extra_price'] : 0);
					$detail_data['is_seckill'] = intval($grow['is_seckill_price']);
					$detail_data['discount_type'] = intval($grow['discount_type']);
					$detail_data['discount_rate'] = $grow['discount_rate'];
					$detail_data['sort_id'] = $grow['sort_id'];
					$detail_data['old_price'] = floatval($grow['old_price']);
					$detail_data['discount_price'] = $grow['discount_price'];
					$detail_data['pay_price'] = $grow['discount_price'];
					$detail_data['goods_weight'] = $grow['goods_weight'];
					$detail_data['sell_type'] = $grow['sell_type'];
					$detail_data['code_weight'] = isset($grow['code_weight']) ? $grow['code_weight'] : 0;
					$detail_data['code_price'] =  isset($grow['code_price']) ? $grow['code_price'] : 0;
					$detail_data['real_weight'] = $grow['goods_weight'] * $grow['num'] + $grow['code_weight'];
					D('Shop_order_detail')->add($detail_data);
					$order_data['reduce_stock_type'] && $goods_obj->update_stock($grow);//修改库存
                    //计算商品最大需要商家会员卡积分数量
                    if(!empty($grow['mer_score_rate'])&&!empty($data['card_data']['card_id'])){
                        $mer_goods_can_use_score_money += $grow['price']*$grow['num']*$goods['mer_score_rate']/100;
                    }
				}
				
                $mer_can_use_score = [
                    'max_score' => 0, //积分最大使用数量
                    'one_score_money' => '0', //一元所需积分数量
                    'score_money' => 0, //积分最大抵扣金额
                ];
                if($mer_goods_can_use_score_money>=1&&$uid>0){
                    $mer_use_score = $this->user_can_use_mer_score($return['mer_id'],$mer_goods_can_use_score_money,$uid);
                    $mer_can_use_score['max_score'] = $mer_use_score['mer_score_count'];
                    $mer_can_use_score['one_score_money'] = $mer_use_score['money_need_score'];
                    $mer_can_use_score['score_money'] = $mer_use_score['mer_score_money'];
                }

                if(!empty($mer_can_use_score['max_score'])&&$order_data['price']<$mer_can_use_score['score_money']){
                    $mer_can_use_score['max_score'] = floor($mer_can_use_score['one_score_money']*$order_data['price']);
                    $mer_can_use_score['score_money'] = $order_data['price'];
                }
				
				if ($user['openid']) {
					$keyword2 = '';
					$pre = '';
					foreach ($return['goods'] as $menu) {
						$keyword2 .= $pre . $menu['name'] . ':' . $menu['price'] . '*' . $menu['num'];
						$pre = '\n\t\t\t';
					}
					$href = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='. $order_id . '&mer_id=' . $order_data['mer_id'] . '&store_id=' . $order_data['store_id'];
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $user['openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $orderid, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $keyword2, 'remark' => L_('您的该次X1下单成功，感谢您的使用！',$this->config['shop_alias_name'])), $this->store['mer_id']);
				}

				D('System_order')->add_order('shop',$order_data,$order_id);


				$printHaddle = new PrintHaddle();
				$printHaddle->printit($order_id, 'shop_order', 0);

				$sms_data = array('mer_id' => $return['mer_id'], 'store_id' => $return['store_id'], 'type' => 'shop');
				if ($this->config['sms_shop_place_order'] == 1 || $this->config['sms_shop_place_order'] == 3) {
					$sms_data['uid'] = $user['uid'];
					$sms_data['mobile'] = $order_data['userphone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('您X1在【X2】中下了一个订单，订单号：X3',array('X1'=>date("H时i分"),'X2'=>$return['store']['name'],'X3'=>$orderid));
					Sms::sendSms($sms_data);
				}
				if ($this->config['sms_shop_place_order'] == 2 || $this->config['sms_shop_place_order'] == 3) {
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $return['store']['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_('顾客【X1】在X2时下了一个订单，订单号：X3请您注意查看并处理!',array('X1'=>$order_data['username'],'X2'=>date("Y-m-d H:i:s"),'X3'=>$orderid));
					Sms::sendSms($sms_data);
				}

				if($this->config['cash_pay_qrcode']){
					$pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id=' . (3700000000 + $order_id);
				}else{
					$pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($this->config['site_url'].'/wap.php?c=My&a=shop_order_before&order_id='.$order_id);
				}

				$order_data['price'] = getFormatNumber($order_data['price']);

				exit(json_encode(array('error_code' => false, 'real_orderid' => $order_data['real_orderid'], 'order_id' => $order_id, 'price' => $order_data['price'], 'pay_qrcode_url' => $pay_qrcode_url, 'discount_msg' => trim($discountMsg, ','),'mer_can_use_score'=>$mer_can_use_score)));
			} else {
				exit(json_encode(array('error_code' => true, 'msg' => L_('订单保存失败'))));
			}
		} else {
			exit(json_encode(array('error_code' => true, 'msg' => L_('不合法的提交'))));
		}
	}

    /**
     * 用户可使用最大商家会员卡积分数量
     * @param $mer_id
     * @param $mer_goods_can_use_score_money //商品最大积分抵扣金额
     * @param $uid
     * @return array
     */
	private function user_can_use_mer_score($mer_id,$mer_goods_can_use_score_money,$uid){
        $mer_user_score = $mer_score_count = $mer_score_money = 0; //商家会员卡积分数量
        //获取商家会员卡
        $mercaht_card = M('Card_new')->where(['mer_id'=>$mer_id,'status'=>1])->find();
        if($mercaht_card&&$mercaht_card['money_need_score']){
            $money_need_score = $mercaht_card['money_need_score'];
            $mer_goods_can_use_score_max = $mer_goods_can_use_score_money*$money_need_score;
            //查询用户是否是该商家会员
            $user_card = M('Card_userlist')->where(['mer_id'=>$mer_id,'status'=>1,'card_id'=>$mercaht_card['card_id'],'uid'=>$uid])->find();
            if($user_card&&$user_card['card_score']>=1){
                $mer_score_count = $mer_goods_can_use_score_max<$user_card['card_score']?$mer_goods_can_use_score_max:$user_card['card_score'];
                $mer_score_count = floor($mer_score_count);
                $mer_score_money = floor($mer_score_count*100/$mercaht_card['money_need_score'])/100;
                $mer_user_score = $user_card['card_score'];
            }
        }
        return [
            'mer_score_count' => $mer_score_count, //最大使用积分数量
            'money_need_score' => $money_need_score, //一元兑换积分数量
            'mer_score_money' => $mer_score_money, //积分兑换最大金额
            'mer_user_score' => $mer_user_score, //商家会员卡积分数量
        ];
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

	public function shop_arrival_check()
	{
		$now_order = M('Shop_order')->where(array('order_id'=>$_POST['order_id']))->find();
		if ($now_order['paid']) {
			$this->success(L_('支付成功！'));
		} else {
			$this->error(L_('还未支付'));
		}
	}

	public function arrival_pay()
	{
		// 开启店员权限功能 店小二无线下零售权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您当前身份为【店小二】，暂无线下零售权限，请联系管理员！'));
		}
        $_POST['auth_code'] = trim($_POST['auth_code']);
        if(!empty($_POST['auth_code']) && mb_strlen($_POST['auth_code']) > 20){
            $this->error(L_('付款码不正确，请核实后确认支付！'));
        }
		$table = isset($_POST['table']) ? htmlspecialchars($_POST['table']) : 'shop';
		$change_price_reason = isset($_POST['change_reason']) ? htmlspecialchars($_POST['change_reason']) : '';
// 		$discount = isset($_POST['discount']) ? intval($_POST['discount']) : 10;
		$coupon = isset($_POST['coupon']) ? intval($_POST['coupon']) : 0;
		$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
		$card_id = isset($_POST['card_id']) ? intval($_POST['card_id']) : 0;
		$card_money = isset($_POST['card_money']) ? floatval($_POST['card_money']) : 0;
		$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $internal_card_pay = $_POST['internal_card_pay'];
		$sys_coupon = isset($_POST['sys_coupon']) ? floatval($_POST['sys_coupon']) : 0; // 平台优惠券id
		$is_use_score = isset($_POST['is_use_score']) ? floatval($_POST['is_use_score']) : 0; // 是否使用积分
		$score_used_count = isset($_POST['score_used_count']) ? floatval($_POST['score_used_count']) : 0; // 使用积分数量
		$score_deducte = isset($_POST['score_deducte']) ? floatval($_POST['score_deducte']) : 0;// 使用积分抵扣金额
        $mer_physical_card_no = $_POST['mer_physical_card_no']??'';
        $mer_user_card_score_use_count = $_POST['mer_user_card_score_use_count']??0; //使用商家积分数量

		$table = strtolower($table);
		$table_name = ucfirst($table) . '_order';
		$order_id  = intval($_POST['order_id']);
		$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
		$now_order['order_name'] = $this->store['name'].L_('线下零售').$now_order['name'];
		if (empty($now_order)) $this->error(L_('订单信息错误'));

        //查询商家实体卡是否存在
        if($mer_physical_card_no){
            $mer_p_card = D('Merchant_physical_card')->where(['card_no'=>$mer_physical_card_no])->find();
            if(!$mer_p_card){
                $this->error(L_('卡号错误！'));
            }
            if($mer_p_card['mer_id']!=$now_order['mer_id']){
                $this->error(L_('卡号不是当前商家实体卡，无法使用！'));
            }
            if(($mer_p_card['money']-$mer_p_card['use_money'])<$price){
                $this->error(L_('商家实体卡余额不足！'));
            }
            $data['mer_physical_card_no'] = $mer_physical_card_no;
        }

		$cacheKey = $table_name.$order_id;
		if(S($cacheKey)){//防止重复请求扣款
            $this->error(L_('当前订单正在处理中，请稍后继续！'));
        }
		S($cacheKey,1,3);
		// 商家优惠(到店付买单优惠)
		$discount_money = isset($_POST['discount_money']) ? floatval($_POST['discount_money']) : 0;
		$no_discount_money = isset($_POST['no_discount_money']) ? floatval($_POST['no_discount_money']) : 0;
		$data['no_discount_money'] = $no_discount_money;//不可优惠金额
		if ($discount_money) {
			$data['merchant_discount_price'] = $discount_money;
			$discount_price = $now_order['price'] - $discount_money;
			$data['price'] = $discount_price;
		}else{
			$discount_price = $now_order['price'];
			$data['price'] = $discount_price;
		}

		//检查会员卡的信息已经余额
		$discount = 10;
		$data['merchant_balance'] = 0;//商家余额
		$data['card_give_money'] = 0;//会员卡赠送余额
		$data['card_id'] = 0;//优惠券ID
		$data['card_price'] = 0;//优惠券的金额
		$coupon_price = 0;
		$sys_coupon_price = 0;
		$score_deducte_price = 0;
		$card_money = $card_money ? $card_money : 0;
        $mer_score_money = 0; //商家会员卡积分抵扣金额
		if ($card = D('Card_userlist')->field(true)->where(array('id' => $card_id, 'uid' => $uid, 'mer_id' => $this->store['mer_id']))->find()) {
			$card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
			// if (empty($card_source)) $this->error('会员卡不可用');
            if($card_source['discount'] == 0) $card_source['discount'] = 10;
			$discount = floatval($card_source['discount']);
			$user_card_total_moeny = $card['card_money'] + $card['card_money_give'];
			if ($user_card_total_moeny < $card_money) {
				$this->error(L_('会员卡余额不足'));
			}
			if ($card_money <= $card['card_money']) {
				$data['merchant_balance'] = $card_money;
				$data['card_give_money'] = 0;
			} else {
				$data['merchant_balance'] = $card['card_money'];
				$data['card_give_money'] = $card_money - $card['card_money'];
			}

			$discount_price = floatval(round(($discount_price-$no_discount_money) * $discount * 0.1+$no_discount_money, 2));// 折扣后的金额
			$data['price'] = $discount_price;
			if ($user_coupon = M('Card_new_coupon_hadpull')->field(true)->where(array('id' => $coupon, 'is_use' => 0))->find()) {
				if ($new_coupon = M(_view('card_new_coupon'))->field(true)->where(array('coupon_id' => $user_coupon['coupon_id'], 'use_with_card' => 1))->find()) {
					$now_time = time();
					if (($new_coupon['cate_name'] == 'all' || $new_coupon['cate_name'] == 'shop') && $new_coupon['end_time'] > $now_time && $discount_price >= $new_coupon['order_money']) {
						$data['card_id'] = $coupon;
						$coupon_price = $data['card_price'] = $new_coupon['discount'];
					}
				}
			}
			$discount = floatval($card_source['discount']);
			
			//会员卡积分抵扣
            if($mer_user_card_score_use_count>0){
                $mer_goods_can_use_score_money = 0;
                $order_content = D('Shop_order_detail')->join('as o LEFT JOIN '.C('DB_PREFIX').'shop_goods g ON g.goods_id = o.goods_id')->field('o.*,g.score_max,score_percent,g.mer_score_rate')->where(array('order_id' => $order_id,'g.mer_score_rate'=>array('gt',0)))->select();
                foreach ($order_content as $item_detail){
                    $mer_goods_can_use_score_money += $item_detail['price']*$item_detail['num']*$item_detail['mer_score_rate']/100;
                }
                if($mer_goods_can_use_score_money>0){
                    $mer_use_score = $this->user_can_use_mer_score($this->store['mer_id'],$mer_goods_can_use_score_money,$uid);
                    if($mer_use_score['mer_user_score']<$mer_user_card_score_use_count){
                        $this->error(L_('商家会员卡积分不足！'));
                    }
                    $data['mer_score_count'] = $mer_use_score['mer_score_count'];
                    $data['mer_score_money'] = $mer_score_money = $mer_use_score['mer_score_money'];
                }
            }
		} else {
			$card_money = 0;
			$discount = 10;
		}

		// 平台优惠券
		if ($user_coupon = D('System_coupon_hadpull')->field(true)->where(array('id' => $sys_coupon, 'is_use' => 0))->find()) {
			if ($new_coupon = M('System_coupon')->field(true)->where(array('coupon_id' => $user_coupon['coupon_id']))->find()) {
				if (($new_coupon['cate_name'] == 'all' || $new_coupon['cate_name'] == 'shop') && $new_coupon['end_time'] > $now_time && ($discount_price-$coupon_price) >= $new_coupon['order_money']) {
		            //查看使用期限是否到期
		            // 用户可使用的天数
					if ($new_coupon['valid_time']) {
		                if ($user_coupon['receive_time']<$new_coupon['start_time']) {
		                    $end_time = $new_coupon['start_time'] + $new_coupon['valid_time']*86400;
		                }else{
		                    $end_time = $user_coupon['receive_time'] + $new_coupon['valid_time']*86400;
		                }
		                if ($end_time>time()) {
							$data['coupon_id'] = $sys_coupon;
							if($new_coupon['is_discount'] == 1){
								//折扣券
								$sys_coupon_price = $data['coupon_price'] = $discount_price*(10-$new_coupon['discount_value'])/10;
							}else{
								$sys_coupon_price = $data['coupon_price'] = $new_coupon['discount'];
							}
						
		                }
		            }else{
						$data['coupon_id'] = $sys_coupon;
						if($new_coupon['is_discount'] == 1){
							//折扣券
							$sys_coupon_price = $data['coupon_price'] = $discount_price*(10-$new_coupon['discount_value'])/10;
						}else{
							$sys_coupon_price = $data['coupon_price'] = $new_coupon['discount'];
						}
		            }
				}
			}
		}

		// 使用积分
        $score_can_use_count=0;
        $score_deducte=0;
		if ($_POST['is_use_score']) {
			$now_user = D('User')->get_user($now_order['uid']);
			 //使用积分
	        $user_score_use_percent = (float)$this->config['user_score_use_percent'];

	        $now_order['total_money'] = $discount_price - $coupon_price - $sys_coupon_price;
	        $user_score_use_condition = $this->config['user_score_use_condition'];

            $order_content = D('Shop_order_detail')->join('as o LEFT JOIN '.C('DB_PREFIX').'shop_goods g ON g.goods_id = o.goods_id')->field('o.*,g.score_max,score_percent')->where(array('order_id' => $order_id))->select();
            $now_order['order_content'] = $order_content ? $order_content :array();

	        $user_score_max_use = D('Percent_rate')->get_max_core_use($this->store['mer_id'], 'shop',$now_order['total_money'],$now_order);//不同业务不同积分

	        $user_score_use_percent = (float)$this->config['user_score_use_percent'];
	        $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

	        if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 ) {   //如果设置没有错误
	            $total_ = $now_order['total_money'];

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
			$data['score_used_count'] = $score_can_use_count;
			$data['score_deducte'] = $score_deducte;
		}

		// 使用一卡通
        $internal_card_pay_money = 0;
        if($internal_card_pay){
            $staff_store_info = D('Merchant_store')->where(['store_id' => $now_order['store_id']])->find();
            if ($staff_store_info['is_internal_card_pay'] != 1) {
                $this->error(L_('店铺不支持一卡通支付'));
            }
            foreach($internal_card_pay as $card_id => $card_pay_money){
                $internal_card_pay_money += $card_pay_money;
                $card = D('internal_membership_card')->where(['card_id' => $card_id])->find();
                if(!$card){
                    $this->error(L_('一卡通不存在'));
                }
                if($card['balance_money'] < $card_pay_money){
                    $this->error(L_('一卡通余额不足'));
                }
            }
            $data['internal_card_pay'] = json_encode($internal_card_pay);
            $save_data['internal_card_pay'] = $data['internal_card_pay'];
            M($table_name)->where(array('order_id' => $order_id))->save($save_data);
        }

		if ($this->staff_session['is_change']) {
			$true_price = floatval(round($discount_price - $coupon_price - $sys_coupon_price, 2));//订单会员卡折扣和优惠券后的钱
			if ($true_price != $price) {
				$data['change_price'] = $discount_price;
				$data['price'] = $price;
				$data['change_price_reason'] = $change_price_reason;//修改价格的理由
			} else {
				$price = $true_price;
			}
			$now_order['price'] = floatval(round($price  - $coupon_price - $sys_coupon_price - $card_money - $score_deducte - $mer_score_money - $internal_card_pay_money, 2));
		} else {
			$now_order['price'] = floatval(round($discount_price - $card_money - $coupon_price - $sys_coupon_price - $score_deducte - $mer_score_money - $internal_card_pay_money, 2));
		}
		
		if(!empty($data['mer_score_count'])&&$price<$data['mer_score_money']){
            $data['mer_score_count'] = floor($mer_use_score['money_need_score']*$price);
            $data['mer_score_money'] = $price;
        }

		$data['card_discount'] = $discount;
		$data['last_staff'] = $this->staff_session['name'];
		if ($now_order['price'] > 0) {
			if($_POST['auth_type'] == 'balance'){
				$data['smscode'] = $_POST['auth_code'];
				$now_user = D('User')->get_user($_POST['phone'],'phone');
				$data['uid'] = $now_user['uid'];
				if ($now_user['now_money']<$now_order['price']) {
					$this->error(L_('余额不足！'));
				}
				$this->arrival_balance_pay($order_id, $now_order, $table_name, $data);
				die;
			}

            if ($_POST['auth_type'] == 'qiye') {
                $authCode = $_POST['auth_code'];

                $checkRs = D('Qiye')->checkPayAuthCode($authCode);
                if ($checkRs['error_no']) {
                    $this->error($checkRs['error_msg']);
                }
                $payCode = $checkRs['retval'];
                $data['uid'] = $payCode['uid'];
                M('Qiye_pay_code_record')->where(['id' => $payCode['id']])->save(['status' => 1, 'order_id' => $order_id, 'type' => 'store']);

                $this->arrival_qiye_pay($order_id, $now_order, $table_name, $data);
                die;
            }

            //商家实体卡支付
            if($mer_physical_card_no){
                $this->arrival_mer_physical_card_pay($order_id, $mer_p_card, $table_name, $data);
                die;
            }

			$offline_pay = isset($_POST['offline_pay']) ? intval($_POST['offline_pay']) : -1;
			if($offline_pay >= 0){
				$this->arrival_offline_pay($order_id, $now_order, $table_name, $data);
				die;
			}

			//翼支付
			if($_POST['auth_type'] == 'yzfpay'){
				$this->arrival_yzfpay_pay($order_id, $now_order, $table_name, $data);
				die;
			}

			import('ORG.Net.Http');
			$http = new Http();
			$is_own = 0;

			$now_merchant = D('Merchant')->get_info($now_order['mer_id']);

			//开通威富通低费率支付
			if ($this->config['open_wftpay'] == 1) {
				$lowfeepay = new LowFeePay('wftpay');
				$order_info['auth_code'] = $_POST['auth_code'];
				$order_info['is_own'] = 0;
				$order_info['mer_id'] = $now_order['mer_id'];
				$order_info['out_trade_no'] = $table . '_' . $now_order['order_id'];
				$order_info['total_fee'] = $now_order['price'];
				$order_info['order_name'] = $this->store['name'].' - 线下零售';
				$return = $lowfeepay->scan_pay($order_info);
				fdump_sql([$order_info,$return],'wft_store_pay'); 
				if ($return['return_code'] == 'FAIL') {
					$this->error(L_('支付失败！支付返回：') . $return['return_msg']);
				}

				if ($return['err_code'] == 'USERPAYING') {
					$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'), '', array('errcode' => 'USERPAYING'));
				}
				if ($return['result_code'] == 'FAIL') {
					$this->error(L_('支付失败！支付返回：') . $return['err_code_des']);
				}

				if ($return['trade_state'] == 'USERPAYING') {
					$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'), '', array('errcode' => 'USERPAYING'));
				}
				if($return['trade_state'] && $return['trade_state'] != 'SUCCESS'){
					$this->error(L_('支付失败！支付返回：') . $return['trade_state_desc']);
				}
				
				fdump($return, 'shop_offline_wftpay');
				
				$data['third_id'] = $return['transaction_id'];
				$data['payment_money'] = $return['total_fee']/100;

				$data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';

				if($data['pay_type'] == 'weixin' && $return['openid']){
					$now_user = D('User')->get_user($return['openid'],'openid');
				}else if($data['pay_type'] == 'alipay' && $return['alipay_uid']){
					$now_user = D('User')->get_user($return['alipay_uid'],'alipay_uid');
				}
			}elseif($this->config['open_weikebaba'] == 1){
				$lowfeepay = new LowFeePay('weikebaba');
				$order_info['auth_code'] = $_POST['auth_code'];
				$order_info['is_own'] = 0;
				$order_info['mer_id'] = $now_order['mer_id'];
				$order_info['out_trade_no'] = $table . '_' . $now_order['order_id'];
				$order_info['total_fee'] = $now_order['price'];
				$order_info['order_name'] = $this->store['name'].' - 线下零售';
				$order_info['pay_type'] = $_POST['auth_type']=='alipay' ? 2 : 1;
				$return = $lowfeepay->scan_pay($order_info);
				if ($return['return_code'] == 'FAIL') {
					$this->error(L_('支付失败！支付返回：') . $return['return_msg']);
				}

				if ($return['err_code'] == 'USERPAYING') {
					$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
				}
				if ($return['result_code'] == 'FAIL') {
					$this->error(L_('支付失败！支付返回：') . $return['err_code_des']);
				}

				if ($return['trade_state'] == 'USERPAYING') {
					$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
				}

				$data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';

				if($data['pay_type'] == 'weixin' && $return['openid']){
					$now_user = D('User')->get_user($return['openid'],'openid');
				}else if($data['pay_type'] == 'alipay' && $return['alipay_uid']){
					$now_user = D('User')->get_user($return['alipay_uid'],'alipay_uid');
				}

			}elseif($this->config['pay_fubei_open']==1 && !empty($this->config['pay_fubei_mchid']) && !empty($this->config['pay_fubei_secret'])){
				import('@.ORG.LowFeePay');

				$order_info['pay_type'] = $_POST['auth_type'];
				$order_info['order_id'] =   $table . '_' . $now_order['order_id'];

				$order_info['order_total_money'] =  $now_order['price'];
				$order_info['order_name'] =  $this->store['name'].' - 线下零售';
				$pay_info['auth_code'] =  $_POST['auth_code'];
				$pay_info['repay'] =  $_POST['repay'];
				$lowfeepay = new LowFeePay('fubei');
				$go_pay_param= $lowfeepay->pay($order_info,$pay_info);

				if($go_pay_param['error']==1){
					if($go_pay_param['repay'] == 1){
						$this->ajaxReturn(['status' => 2, 'info'=>$go_pay_param['msg']]);
						exit;
					}
					$this->error($go_pay_param['msg']);
				}
			}elseif($this->config['pay_scrcu_cash_open'] == 1 && $this->config['scrcu_mid']){
				// 四川农信银行支付
				if($now_merchant['scrcu_mer_no']){
					$is_own = 1;
				}
				import("@.ORG.pay.Scrcu");
				$pay_moodel = new Scrcu();
				$session_key = 'store_order_userpaying_' . $now_order['order_id'];
				if($_SESSION[$session_key]){
		            $return = $pay_moodel->scanQuery($now_order['order_id'], $now_merchant['scrcu_mer_no']);
		            if ($return['error']) {// 正在输入密码
						$_SESSION[$session_key] = '1';
						$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
		            }
				}else{
					// 付款
		            $return = $pay_moodel->scanPay($_POST['order_type'], $now_order['order_id'], $now_order['price'], $_POST['auth_code'], $now_merchant['scrcu_mer_no'] );
		            if ($return['error']) {
						$this->error(L_('支付失败！支付返回：') . $return['msg']);
		            }

		            sleep(1);
		            // 查询
		            $return = $pay_moodel->scanQuery($now_order['order_id'], $now_merchant['scrcu_mer_no']); 
		            if ($return['error']) {// 正在输入密码
						$_SESSION[$session_key] = '1';
						$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
		            }
				}

				if($_SESSION[$session_key] ){
					unset($_SESSION[$session_key]);
				}
	
				$data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
			}
			else{
				if($_POST['auth_type'] == 'alipay'){
					$this->arrival_alipay_pay($order_id, $now_order, $table_name, $data);
					die;
				}
				$sub_mch_pay = false;

				if($this->config['open_sub_mchid']){
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
									$wx_sub = true;
								}else{
									$ownValueArr['is_own'] = true;
								}
								$pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
							}
						}
						if($wx_sub){
							$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
							$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
							$sub_mch_pay = true;
							$sub_mch_id = $pay_method['weixin']['config']['sub_mch_id'];
							$is_own = 6 ;
						}
					}

					if($this->store['open_sub_mchid'] && $this->store['sub_mch_id']>0){
						$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
						$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
						$sub_mch_pay = true;
						$sub_mch_id = $this->store['sub_mch_id'];
						$is_own = 3 ;
					}else if ( $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
						$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
						$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
						$sub_mch_pay = true;
						$sub_mch_id = $now_merchant['sub_mch_id'];
						$is_own = 2 ;
					}
				}

                //付款码转openid缓存,因为付款码多次转换会报付款码过期
                $authCodeOpenidCache = $_POST['auth_code'] . '_openid';
                if (S($authCodeOpenidCache)) {
                    $tmp_user_openid = S($authCodeOpenidCache);
                } else {
                    $param = array();
                    $param['appid'] = $this->config['pay_weixin_appid'];
                    $param['mch_id'] = $this->config['pay_weixin_mchid'];
                    if ($sub_mch_id) {
                        $param['sub_mch_id'] = $sub_mch_id;
                    }
                    $param['nonce_str'] = $this->createNoncestr();
                    $param['auth_code'] = $_POST['auth_code'];
                    $param['sign'] = $this->getWxSign($param);
                    $return_openid_from_auth_code = Http::curlPostXml('https://api.mch.weixin.qq.com/tools/authcodetoopenid', $this->arrayToXml($param));
                    if ($return_openid_from_auth_code['return_code'] == 'SUCCESS' && $return_openid_from_auth_code['result_code'] == 'SUCCESS') {
                        $tmp_user_openid = $return_openid_from_auth_code['openid'];
                        S($authCodeOpenidCache, $tmp_user_openid, 300);
                    } else {
                        $_msg = $return_openid_from_auth_code['return_msg'] ?: ($return_openid_from_auth_code['err_code_des'] ?: "未知错误！");
                        $this->error($_msg);
                    }
                }

				$now_user = D('User')->get_user($tmp_user_openid,'openid');
				$now_order['order_type'] = 'shop';
				
				if(($data['pay_type'] == 'weixin' || $_POST['auth_type'] == 'weixin') && $tmp_user_openid){
					$now_user = D('User')->get_user($tmp_user_openid,'openid');
					if(empty($now_user)){
						$access_token_array = D('Access_token_expires')->get_access_token();
						if (!$access_token_array['errcode']) {
							$result_user = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$tmp_user_openid.'&lang=zh_CN');
							$userinfo = json_decode($result_user,true);
							if(empty($userinfo['errcode'])) {
								//商家推广绑定关系
								D('Merchant_spread')->spread_add($now_order['mer_id'], $userinfo['openid'], 'storepay');
								$data_user = array(
										'openid' => $userinfo['openid'],
										'union_id' => ($userinfo['unionid'] ? $userinfo['unionid'] : ''),
										'nickname' => $userinfo['nickname'],
										'sex' => $userinfo['sex'],
										'province' => $userinfo['province'],
										'city' => $userinfo['city'],
										'avatar' => $userinfo['headimgurl'],
										'is_follow' => $userinfo['subscribe'],
										'source' => 'pc_staff_pay_auto3',
								);
								$reg_result = D('User')->autoreg($data_user);
								if ($reg_result['error_code']) {
									$now_user['uid'] = '0';
								} else {
									$now_user = D('User')->get_user($userinfo['openid'], 'openid');
									$uid = $now_user['uid'];
								}
							}
						}
					} else {
						$uid = $now_user['uid'];
						if($this->config['san_pay_score_coupon']==1 && $now_merchant['san_pay_score_coupon'] && !$this->config['is_open_pay_bind_user']) {
							$check_result = D('Shop_order')->check_coupon_score($now_user, $now_order);
							$now_order['score_used_count'] = $check_result['score_used_count'];
							$now_order['score_deducte'] = $check_result['score_deducte'];
							$now_order['coupon_id'] = $check_result['coupon_id'];
							$now_order['coupon_price'] = $check_result['coupon_price'];

							//判断平台优惠券
							if ($now_order['coupon_id']) {
								$now_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
								if (empty($now_coupon)) {
									$this->error(L_('支付异常：系统卡券错误'));
								}
							}

							$score_used_count = $now_order['score_used_count'];
							if ($now_user['score_count'] < $score_used_count) {
								$this->error(L_('支付异常：积分异常'));
							}
							$now_order['price'] = $check_result['total_money'];

							$data['coupon_id'] = $check_result['coupon_id'];
							$data['coupon_price'] = $check_result['coupon_price'];
							$data['score_used_count'] = $check_result['score_used_count'];
							$data['score_deducte'] = $check_result['score_deducte'];
						}
					}

					if($now_order['price']>0) {
						$session_key = $table . '_order_userpaying_'.$order_id;
						if($_SESSION[$session_key]){
							$param = array();
							$param['appid'] = $this->config['pay_weixin_appid'];
							$param['mch_id'] = $this->config['pay_weixin_mchid'];
							$param['nonce_str'] = $this->createNoncestr();
							$param['out_trade_no'] = $table . '_' . $now_order['order_id'];
							if($sub_mch_pay){
								$param['out_trade_no'] = 'store_'.$now_order['order_id'].'_1';
								$param['sub_mch_id'] = $sub_mch_id;
							}
							$param['sign'] = $this->getWxSign($param);
							$return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/orderquery', $this->arrayToXml($param));
						} else {
							$param = array();
							$param['appid'] = $this->config['pay_weixin_appid'];
							$param['mch_id'] = $this->config['pay_weixin_mchid'];
							$param['nonce_str'] = $this->createNoncestr();
							$param['body'] = $now_order['real_orderid'];
							$param['out_trade_no'] = $table . '_' . $now_order['order_id'];
							$param['total_fee'] = floatval($now_order['price']*100);
							$param['spbill_create_ip'] = get_client_ip();
							$param['auth_code'] = $_POST['auth_code'];
							if($sub_mch_pay){
								$param['out_trade_no'] = 'store_'.$now_order['order_id'].'_1';
								$param['sub_mch_id'] = $sub_mch_id;
							}
							$param['sign'] = $this->getWxSign($param);

							$return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/micropay', $this->arrayToXml($param));
						}

						if ($return['return_code'] == 'FAIL') {
						    $this->error(L_('支付失败！支付返回：'). $return['return_msg']);
						}
						if ($return['result_code'] == 'FAIL') {
							if ($return['err_code'] == 'USERPAYING') {
								$session_key && $_SESSION[$session_key] = '1';
								$this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'), '', array('errcode' => 'USERPAYING'));
							}
							$this->error(L_('支付失败！支付返回：').$return['err_code_des']);
						} elseif (isset($return['trade_state']) && $return['trade_state'] == 'USERPAYING') {
						    $this->error(L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'), '', array('errcode' => 'USERPAYING'));
						} elseif (isset($return['trade_state']) && $return['trade_state'] != 'SUCCESS') {
							fdump_sql(array('return'=>$return,'now_order'=>$now_order),'storestaff_arrival_pay_error');
						    $this->error(L_('支付时发生错误，请联系技术处理。'));
						}

						if($session_key){
							unset($_SESSION[$session_key]);
						}
						if(isset($return['trade_state']) && $return['trade_state'] != 'SUCCESS') {
						    $this->error('支付失败！微信返回：'. $return['trade_state_desc']);
						}

						$data['third_id'] = $return['transaction_id'];
						$data['payment_money'] = $return['total_fee']/100;
						$data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
					}else{
						$data['pay_type'] = '';
					}
					unset($_SESSION[$session_key]);
				}
			}
		}

		$data['uid'] = $uid;
		$data['paid'] = 1;
		$data['status'] = 2;
		$data['order_status'] = 5;
		$data['from_plat'] = 3;
		$data['is_own'] = isset($return['is_own']) ? $return['is_own'] : $is_own;
		$data['pay_time'] = time();
		$data['use_time'] = time();

		if(M($table_name)->where(array('order_id' => $order_id,'paid'=>0))->data($data)->save()){
			$now_order = M($table_name)->where(array('order_id'=>$order_id))->find();
			$this->shop_notice($now_order, true);
			D('Shop_order')->shop_score_get($now_order);
			$this->success(L_('支付成功！'));
		}else{
			$this->error(L_('支付失败！请联系管理员处理。'));
		}
	}

	public function arrival_offline_pay($order_id, $now_order, $table_name, $data = array())
	{
		$data['paid'] = 1;
		$data['status'] = 2;
		$data['order_status'] = 5;
		$data['pay_time'] = $_SERVER['REQUEST_TIME'];
		$data['use_time'] = time();
		$data['pay_type'] = 'offline';
		$data['offline_pay'] = $_POST['offline_pay'];
		$data['third_id'] = $order_id;
		if(M($table_name)->where(array('order_id' => $order_id))->data($data)->save()){
			$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
			$this->shop_notice($now_order, true);
			$res = D('Shop_order')->shop_score_get($now_order);
			$this->success(L_('支付成功！'));
		}else{
			$this->error(L_('支付失败！请联系管理员处理。'));
		}
	}

	public function alipay_auth_code(){
		import('@.ORG.pay.Aop.AopClient');
		import('@.ORG.pay.Aop.SignData');
		import('@.ORG.pay.Aop.AlipayTradePayRequest');
		import('@.ORG.pay.Aop.AlipayOpenAuthTokenAppRequest');
		$aop = new AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $this->config['pay_alipay_sp_appid'];
		$aop->rsaPrivateKey = $this->config['pay_alipay_sp_prikey'];
		$aop->alipayrsaPublicKey=$this->config['pay_alipay_sp_pubkey'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='utf-8';
		$aop->format='json';
		$request = new AlipayOpenAuthTokenAppRequest();
		$request->setBizContent;
		$now_merchant = D('Merchant')->get_info($this->store['mer_id']);

		$biz_content = array(
			'grant_type' => 'authorization_code',
			'code' => $now_merchant['alipay_auth_code'],
		);
		$request->setBizContent(json_encode($biz_content,JSON_UNESCAPED_UNICODE));
		$result = $aop->execute ( $request);
		return $result;
	}

	public function arrival_alipay_pay($order_id, $now_order, $table_name, $data = array())
	{
		$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
		if($now_merchant['open_sub_mchid']==1 && $this->config['pay_alipay_sp_appid']!=''&& $this->config['pay_alipay_sp_prikey']!='' && $now_merchant['alipay_auth_code']!=''){
			$this->arrival_alipay_face_to_face($order_id,$now_order,$now_merchant['alipay_auth_code']);
			die;
		}
		if (empty($this->config['arrival_alipay_open'])) {
			$this->error(L_('平台未开启支付宝收银'));
		}
		$param['app_id'] = $this->config['arrival_alipay_app_id'];
		$param['method'] = 'alipay.trade.pay';
		$param['charset'] = 'utf-8';
		$param['sign_type'] = $this->config['alipay_arrival_encrypt_type'] ? $this->config['alipay_arrival_encrypt_type'] : 'RSA';
		$param['timestamp'] = date('Y-m-d H:i:s');
		$param['version'] = '1.0';
		$biz_content = array(
				'out_trade_no' => 'shop_'.$now_order['real_orderid'],
				'scene' => 'bar_code',
				'auth_code' => $_POST['auth_code'],
				'total_amount' => $now_order['price'],
				'subject' => $now_order['real_orderid'],
		);
		$param['biz_content'] = json_encode($biz_content,JSON_UNESCAPED_UNICODE);
		ksort($param);
		$stringToBeSigned = "";
		$i = 0;
		foreach ($param as $k => $v) {
			if (!empty($v) && "@" != substr($v, 0, 1)) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		$priKey = $this->config['arrival_alipay_app_prikey'];;
		$res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

		if($param['sign_type'] == 'RSA2'){
			openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($stringToBeSigned, $sign, $res);
		}

		if (empty($sign)) {
			$this->error(L_('支付宝收银商户密钥错误，请联系管理员解决。'));
		}

		$sign = base64_encode($sign);

		$param['sign'] = $sign;
		$requestUrl = "https://openapi.alipay.com/gateway.do?";
		foreach ($param as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);
		// echo $requestUrl;die;
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlGet($requestUrl);
		$returnArr = json_decode($return,true);

        if (isset($returnArr['alipay_trade_pay_response']['code']) && $returnArr['alipay_trade_pay_response']['code'] != 10000) {
            fdump_sql($returnArr, 'arrival_alipay_pay_error');
        }

		if (!empty($returnArr['alipay_trade_pay_response']) && "10000" == $returnArr['alipay_trade_pay_response']['code']) {
			$data['paid'] = 1;
			$data['status'] = 2;
			$data['order_status'] = 5;
			$data['pay_time'] = strtotime($returnArr['alipay_trade_pay_response']['gmt_payment']);
			$data['use_time'] = time();
			$data['pay_type'] = 'alipay';
			$data['third_id'] = $returnArr['alipay_trade_pay_response']['trade_no'];
			$data['payment_money'] = $returnArr['alipay_trade_pay_response']['receipt_amount'];
			if (M($table_name)->where(array('order_id'=>$order_id))->data($data)->save()) {
				$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
				$this->shop_notice($now_order, true);
				D('Shop_order')->shop_score_get($now_order);
				$this->success(L_('支付成功！'));
			} else {
				$this->error(L_('支付失败！请联系管理员处理。'));
			}
		} elseif (!empty($returnArr['alipay_trade_pay_response']) && "10003" == $returnArr['alipay_trade_pay_response']['code']) {	//需要用户处理，下次查询订单

		} else {
			if ($returnArr['alipay_trade_pay_response']['sub_code'] == 'ACQ.TRADE_HAS_SUCCESS' && $now_order['paid'] != 1) {
				$data['paid'] = 1;
				$data['status'] = 2;
				$data['order_status'] = 5;
				$data['pay_time'] = strtotime($returnArr['alipay_trade_pay_response']['gmt_payment']);
				$data['use_time'] = time();
				$data['pay_type'] = 'alipay';
				$data['third_id'] = $returnArr['alipay_trade_pay_response']['trade_no'];
				$data['payment_money'] = $returnArr['alipay_trade_pay_response']['receipt_amount'];
				if (M($table_name)->where(array('order_id' => $order_id))->data($data)->save()) {
					$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
					$this->shop_notice($now_order, true);
					D('Shop_order')->shop_score_get($now_order);
					$this->success(L_('支付成功！'));
				} else {
					$this->error(L_('支付失败！请联系管理员处理。'));
				}
			} else {
				$this->error(L_('支付失败！支付宝返回：').$returnArr['alipay_trade_pay_response']['sub_msg']);
			}
		}
	}

    public function arrival_qiye_pay($orderid, $nowOrder, $tableName, $data = array())
    {
        if ($nowOrder['price'] > 0) {
            if (!(D('Qiye')->checkTheSameQiye($data['uid'], $nowOrder['mer_id']))) {

                $now_user = D('User')->get_user($data['uid'], 'uid');
                $data['uid'] = $now_user['uid'];
                if ($now_user['now_money'] < $nowOrder['price']) {
                    $this->error(L_('同一企业商家和用户才能使用企业预存款！'));
                }
                $this->arrival_balance_pay($orderid, $nowOrder, $tableName, $data);
                die;

            }

            $orderType = (new OrderModel())->getOrderTypeByTableName($tableName);
            $res = D('User')->use_qiye_money($data['uid'], $nowOrder['price'], $orderid, L_('线下零售使用企业预存款余额'),$orderType,true);
            if (!$res['error_code']) {
                $data['paid'] = 1;
                $data['pay_type'] = 'qiye';
                $data['status'] = 2;
                $data['order_status'] = 5;
                $data['pay_time'] = time();
                $data['use_time'] = time();
                $data['qiye_pay'] = $res['retval']['qiye_money'];
                $data['balance_pay'] = $res['retval']['balance_money'];
                $data['qiye_settlement_ratio'] = $res['retval']['qiye_settlement_ratio'];
                $data['qiye_settlement_money'] = $res['retval']['qiye_settlement_money'];
                if (M($tableName)->where(['order_id' => $orderid])->data($data)->save()) {
                    $nowOrder = M($tableName)->where(['order_id' => $orderid])->find();
                    $this->shop_notice($nowOrder, true);
                    D('Shop_order')->shop_score_get($nowOrder);
                    $this->success(L_('支付成功！'));
                }
            } else {
                $this->error($res['msg']);
            }
        }
    }

    public function store_arrival_qiye_pay($orderid, $nowOrder, $tableName, $data = array())
    {
        if ($nowOrder['price'] > 0) {
            if (!(D('Qiye')->checkTheSameQiye($data['uid'], $nowOrder['mer_id']))) {
                $now_user = D('User')->get_user($data['uid'], 'uid');
                $data['uid'] = $now_user['uid'];
                $data['name'] = $now_user['nickname'];
                $data['phone'] = $_POST['phone'];
                if ($now_user['now_money'] < $nowOrder['price']) {
                    $this->error(L_('同一企业商家和用户才能使用企业预存款！'));
                }
                // 开启到店付，线下零售绑定用户，使用优惠
                $this->store_notice($nowOrder, true);
                $this->store_arrival_balance_pay($orderid, $nowOrder, $tableName, $data);
                die;
            }

            $orderType = (new OrderModel())->getOrderTypeByTableName($tableName);
            $res = D('User')->use_qiye_money($data['uid'], $nowOrder['price'], $orderid, L_('买单支付使用企业预存款余额'),$orderType,true);
            if (!$res['error_code']) {
                $data['paid'] = 1;
                $data['pay_type'] = 'qiye';
                $data['pay_time'] = time();
                $data['use_time'] = time();
                $data['qiye_pay'] = $res['retval']['qiye_money'];
                $data['balance_pay'] = $res['retval']['balance_money'];
                $data['qiye_settlement_ratio'] = $res['retval']['qiye_settlement_ratio'];
                $data['qiye_settlement_money'] = $res['retval']['qiye_settlement_money'];
                if (M($tableName)->where(['order_id' => $orderid])->data($data)->save()) {
                    $now_order = M($tableName)->where(['order_id' => $orderid])->find();
                    $now_order['order_type'] = 'shop';
                    D('SystemBill')->bill_method($now_order['is_own'], $now_order);

                    if ($now_order['business_type']) {
                        switch ($now_order['business_type']) {
                            case 'foodshop':
                                $now_food_order = D('Foodshop_order')->where(array('order_id' => $now_order['business_id']))->find();
                                D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                                M('Foodshop_order')->where(array('order_id' => $now_order['business_id']))->data(array('name' => $data['name'], 'phone' => $data['phone'], 'status' => 3, 'uid' => $data['uid'], 'price' => $now_order['price'] + $now_food_order['book_price'], 'pay_type' => '1', 'payment_method' => ''))->save();
                                break;
                        }
                    }
                    D('Store_order')->store_score_get($now_order); // 增加积分
                    // 订单改变之后调用
                    D("Store_order")->after_store_change($orderid, 'cash', 2);

                    $this->success(L_('支付成功！'));
                }
            } else {
                $this->error($res['msg']);
            }
        }
    }

	public function arrival_balance_pay($order_id, $now_order, $table_name, $data = array())
	{
 		if (!$this->config['open_balance_pay_unverify']) { // 是否开启线下零售、到店付余额支付不使用短信验证码
			$laste_sms = M('Merchant_sms_record')->where(array('mer_id' => $this->store['mer_id'],'extra'=>$data['smscode'],'type'=>5,'status'=>0))->setField('status',1);
			if (!$laste_sms) {
				$this->error(L_('短信验证失败！'));
			}
		}
		if($now_order['price']>0){
			$res = D('User')->user_money($data['uid'],$now_order['price'],L_('线下零售使用平台余额'));
			if(!$res['error_code']){
				$data['paid'] = 1;
				$data['status'] = 2;
				$data['order_status'] = 5;
				$data['pay_time'] = time();
				$data['use_time'] = time();
				$data['balance_pay'] = $now_order['price'];
				if (M($table_name)->where(array('order_id' => $order_id))->data($data)->save()) {
					$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
					$this->shop_notice($now_order, true);
					D('Shop_order')->shop_score_get($now_order);
					$this->success(L_('支付成功！'));
				}
			}else {
				$this->error($res['msg']);
			}

		}
	}

	public function store_arrival_balance_pay($order_id, $now_order, $table_name, $data = array())
	{
		if (!$this->config['open_balance_pay_unverify']) { // 是否开启线下零售、到店付余额支付不使用短信验证码
			$laste_sms = M('Merchant_sms_record')->where(array('mer_id' => $this->store['mer_id'],'extra'=>$data['smscode'],'type'=>5,'status'=>0))->setField('status',1);
			if (!$laste_sms) {
				$this->error(L_('短信验证失败！'));
			}
		}

		if($now_order['price']>0){
			$res = D('User')->user_money($data['uid'],$now_order['price'],L_('买单支付使用平台余额'));
			if(!$res['error_code']){
				$data['paid'] = 1;
				$data['pay_time'] = time();
				$data['use_time'] = time();
				$data['balance_pay'] = $now_order['price'];
				if (M($table_name)->where(array('order_id' => $order_id))->data($data)->save()) {
                    M('System_order')->where(['type' => 'cash', 'order_id' => $order_id])->data(['uid' => $data['uid']])->save();
					$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
					if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
					    foreach ($data as $kData=>$vData){
                            $now_order[$kData]=$vData;
                        }
                    }
					$now_order['order_type'] = 'cash'; 
					D('SystemBill')->bill_method($now_order['is_own'],$now_order);

					if($now_order['business_type']){
						switch($now_order['business_type']){
							case 'foodshop':
								$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
								D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
								M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('name'=>$data['name'],'phone'=>$data['phone'],'status'=>3,'uid'=>$data['uid'],'price'=>$now_order['price']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>''))->save();
								break;
						}
					}

					if($this->config['add_score_by_system_commission']==0 && $this->config['merchant_buy_points_switch']!=1 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $now_order['score_discount_type']!=2) && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
						$now_user  = D('User')->get_user($now_order['uid']);
						if($this->config['store_score_get_percent']==1){
							$score_get =$this->config['store_score_percent']/100;
						}else{
							$score_get = $this->config['user_store_score_get'];
						}

						if($now_order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
							$now_order['payment_money'] = 0;
						}
						$score_get = D('Percent_rate')->get_user_add_score_times($score_get,$now_order['uid']);

						D('User')->add_score($now_order['uid'], (($now_order['payment_money'] + $now_order['balance_pay'])* $score_get), L_('在X1使用X2消费X3获得X4',array('X1'=>$this->store['name'],'X2'=>$this->config['cash_alias_name'],'X3'=>floatval($now_order['price']) . C('config.Currency_txt'),'X4'=>$this->config['score_name'])));
						D('Scroll_msg')->add_msg('store',$now_user['uid'],L_('用户X1于X2购买 X3成功并消费获得X4',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$now_order['order_name'],'X4'=>$this->config['score_name'])));
					}

					D('Store_order')->store_score_get($now_order); // 增加积分

					// 订单改变之后调用
					D("Store_order")->after_store_change($order_id,'cash',2);

                    $this->store_notice($now_order, true);

                    $this->success(L_('支付成功！'));
				}
			}else {
				$this->error($res['msg']);
			}

		}
	}

	public function arrival_balance_pay_before(){
		$order_id  = $_GET['order_id'];
		$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
		$this->assign('now_order',$now_order);
		$this->display();
	}

	public function alipay_face_to_face($order_id, $now_order, $table_name, $data = array()){
		import('@.ORG.pay.Aop.AopClient');
		import('@.ORG.pay.Aop.SignData');
		import('@.ORG.pay.Aop.AlipayTradePayRequest');
		$aop = new AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $this->config['pay_alipay_sp_appid'];
		$aop->rsaPrivateKey = $this->config['pay_alipay_sp_prikey'];
		$aop->alipayrsaPublicKey=$this->config['pay_alipay_sp_pubkey'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='UTF-8';
		$aop->format='json';
		$request = new AlipayTradePayRequest();
		$biz_content = array(
				'out_trade_no' => 'store_'.$now_order['order_id'],
				'scene' => 'bar_code',
				'auth_code' => $_POST['auth_code'],
				'product_code' => 'FACE_TO_FACE_PAYMENT',
				'total_amount' => $now_order['price'],
				'subject' => $now_order['orderid'],
		);
		$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
		$request->setBizContent(json_encode($biz_content,JSON_UNESCAPED_UNICODE));
		$result = $aop->execute ( $request,$_POST['auth_code'],$now_merchant['alipay_auth_code']);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
			$this->success_tips(L_('支付成功!'));
		} elseif($resultCode == 10003) {
			$this->error_tips(L_("等待用户验证支付"));
		}else{
			if($result->$responseNode->sub_code=='ACQ.TRADE_HAS_SUCCESS'){
				$this->error_tips($result->$responseNode->sub_msg);
			}else{
				$this->error_tips($result->$responseNode->sub_msg);
			}
		}
	}

	public function appoint_export()
	{
		$param = $_POST;
		$param['type'] = 'appoint';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		 $param['store_session']['store_id'] = $this->store['store_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
		die;
		set_time_limit(0);
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = L_('预约订单信息');
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);

		// 设置当前的sheet
		//$where['a.appoint_id'] = intval($_GET['appoint_id']);
		$store_id = $this->store['store_id'];
		$where['a.store_id'] = $store_id;
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'order_id') {
				$where['a.order_id'] = $_GET['keyword'];
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
				unset($where['orderid']);
				$where['a.order_id'] = $tmp_result['order_id'];
			}elseif ($_GET['searchtype'] == 'name') {
				$where['u.username'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['u.phone'] = htmlspecialchars($_GET['keyword']);
			}elseif ($_GET['searchtype'] == 'third_id') {
				$where['a.third_id'] =$_GET['keyword'];
			}
		}
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if($pay_type){
			if($pay_type=='balance'){
				$where['_string'] = "(`a`.`balance_pay`<>0 OR `a`.`merchant_balance` <> 0 OR a.product_merchant_balance <> 0 OR a.product_balance_pay <> 0 )";
			}else{
				$where['a.pay_type'] = $pay_type;
			}
		}
		$database_appoint = D('Appoint');
		$database_order = D('Appoint_order');
		$now_appoint = $database_appoint->field(true)->where(array('appoint_id'=>$_GET['appoint_id']))->find();
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$where['_string'] .= (empty($where['_string'])?'':' AND ')." (a.order_time BETWEEN ".$period[0].' AND '.$period[1].")";
		}
		$order_count = $database_order->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid left join '.C('DB_PREFIX').'merchant_store s ON s.store_id = a.store_id ')
				->where($where)->count();

		$length = ceil($order_count / 1000);
		for ($i = 0; $i < $length; $i++) {
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);

			$objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息',($i+1)));
			$objActSheet = $objExcel->getActiveSheet();

			$objActSheet->setCellValue('A1', L_('订单编号'));
			$objActSheet->setCellValue('B1', L_('定金'));
			$objActSheet->setCellValue('C1', L_('总价'));
			$objActSheet->setCellValue('D1', L_('类型'));
			$objActSheet->setCellValue('E1', L_('用户昵称'));
			$objActSheet->setCellValue('F1', L_('手机号码'));
			$objActSheet->setCellValue('G1', L_('订单状态'));
			$objActSheet->setCellValue('H1', L_('服务状态'));
			$objActSheet->setCellValue('I1', L_('平台余额支付'));
			$objActSheet->setCellValue('J1', L_('商家会员卡支付'));
			$objActSheet->setCellValue('K1', L_('在线支付金额'));
			$objActSheet->setCellValue('L1', L_('下单时间'));
			$objActSheet->setCellValue('M1', L_('支付时间'));
			$objActSheet->setCellValue('N1', L_('支付方式'));
			$order_list = $database_order->field('a.*,ap.appoint_name,a.appoint_type,a.mer_id,u.uid,u.nickname,a.order_time,a.pay_time,u.phone,s.name as store_name,s.adress as store_adress')
					->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid left join '.C('DB_PREFIX').'merchant_store s ON s.store_id = a.store_id ')
					->where($where)->limit(($i*1000).',1000')->order('`order_id` DESC')->select();
			$result_list = $order_list;

			if (!empty($result_list)) {
				$index = 2;
				foreach ($result_list as $value) {
					$objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
					if($value['product_id']>0){
						$objActSheet->setCellValueExplicit('B' . $index,floatval($value['product_payment_price']));
					}else{
						$objActSheet->setCellValueExplicit('B' . $index, floatval($value['payment_money']));
					}
					if($value['product_price']>0){
						$objActSheet->setCellValueExplicit('C' . $index,floatval($value['product_price']));
					}else{
						$objActSheet->setCellValueExplicit('C' . $index, floatval($value['appoint_price']));
					}
					if($value['type']==1){
						$objActSheet->setCellValueExplicit('D' . $index, L_('自营'));
					}else{
						$objActSheet->setCellValueExplicit('D' . $index, L_('商家'));
					}
					$objActSheet->setCellValueExplicit('E' . $index, $value['nickname'] . ' ');
					$objActSheet->setCellValueExplicit('F' . $index, $value['phone'] . ' ');
					if($value['paid']==0){
						$objActSheet->setCellValueExplicit('G' . $index, L_('未支付'));
					}elseif($value['paid']==1){
						$objActSheet->setCellValueExplicit('G' . $index, L_('已支付'));
					}elseif($value['paid']==2){
						$objActSheet->setCellValueExplicit('G' . $index, L_('已退款'));
					}
					if($value['service_status']==1){
						$objActSheet->setCellValueExplicit('H' . $index, L_('未服务'));
					}elseif($value['service_status']==2){
						$objActSheet->setCellValueExplicit('H' . $index, L_('已服务'));
					}elseif($value['service_status']==3){
						$objActSheet->setCellValueExplicit('H' . $index, L_('已评价'));
					}
					$objActSheet->setCellValueExplicit('I' . $index, floatval($value['balance_pay']));
					$objActSheet->setCellValueExplicit('J' . $index, floatval($value['merchant_balance']));
					$objActSheet->setCellValueExplicit('K' . $index, floatval($value['pay_money']));
					$objActSheet->setCellValueExplicit('L' . $index, $value['order_time'] ? date('Y-m-d H:i:s', $value['order_time']) : '');
					$objActSheet->setCellValueExplicit('M' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
					$objActSheet->setCellValueExplicit('N' . $index, D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']));


					$index++;
				}
			}
			sleep(2);
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
	}

	public function group_export()
	{
		$param = $_POST;
		$param['type'] = 'group';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		 $param['store_session']['store_id'] = $this->store['store_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
		die;
		set_time_limit(0);
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = L_('团购订单信息');
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);

		// 设置当前的sheet
		$store_id = $this->store['store_id'];

		$condition_where = " WHERE `o`.`store_id`='$store_id'";
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$condition_where .= "AND `o`.`real_orderid`='" . htmlspecialchars($_GET['keyword'])."'";
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$_GET['keyword']))->find();
				$condition_where .= "AND `o`.`order_id`='" . htmlspecialchars($tmp_result['order_id'])."'";
			} elseif ($_GET['searchtype'] == 'name') {
				$condition_where .= "AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
			} elseif ($_GET['searchtype'] == 'phone') {
				$condition_where .= "AND `u`.`phone`='" . htmlspecialchars($_GET['keyword']) . "'";
			} elseif ($_GET['searchtype'] == 's_name') {
				$condition_where .= "AND `g`.`s_name` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
			}elseif ($_GET['searchtype'] == 'third_id') {
				$condition_where .= " AND `o`.`third_id` =".$_GET['keyword'];
			}
		}


		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';
		if ($type && $sort) {
			$order_sort .= 'o.' . $type . ' ' . $sort . ',';
			$order_sort .= 'o.order_id DESC';
		} else {
			$order_sort .= 'o.order_id DESC';
		}

		if ($status != -1) {
			$condition_where .= " AND `o`.`status`={$status}";
		}
		if($pay_type){
			if($pay_type=='balance'){
				$condition_where .= " AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 )";
			}else{
				$condition_where .= " AND `o`.`pay_type`='{$pay_type}'";
			}
		}

		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$condition_where .= " AND (o.add_time BETWEEN ".$period[0].' AND '.$period[1].")";
			//$condition_where['_string']=$time_condition;
		}


		$sql = "SELECT count(order_id) as count FROM " . C('DB_PREFIX') . "group_order AS o  LEFT JOIN " . C('DB_PREFIX') . "group g ON g.group_id = o.group_id  LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON `o`.`mer_id`=`m`.`mer_id` LEFT JOIN " . C('DB_PREFIX') . "user u ON u.uid = o.uid ".$condition_where." ORDER BY o.order_id DESC ";
		$count = D()->query($sql);

		$length = ceil($count[0]['count'] / 1000);
		for ($i = 0; $i < $length; $i++) {
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);

			$objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息',($i+1)));
			$objActSheet = $objExcel->getActiveSheet();
			$objActSheet->getColumnDimension('A')->setAutoSize(true);
			$objActSheet->getColumnDimension('B')->setWidth(50);
			$objActSheet->getColumnDimension('C')->setWidth(20);
			$objActSheet->getColumnDimension('D')->setAutoSize(true);
			$objActSheet->getColumnDimension('E')->setAutoSize(true);
			$objActSheet->getColumnDimension('F')->setAutoSize(true);
			$objActSheet->getColumnDimension('G')->setAutoSize(true);
			$objActSheet->getColumnDimension('H')->setAutoSize(true);
			$objActSheet->getColumnDimension('I')->setAutoSize(true);
			$objActSheet->getColumnDimension('J')->setAutoSize(true);
			$objActSheet->getColumnDimension('K')->setAutoSize(true);
			$objActSheet->getColumnDimension('L')->setAutoSize(true);
			$objActSheet->getColumnDimension('M')->setAutoSize(true);
			$objActSheet->getColumnDimension('N')->setAutoSize(true);
			$objActSheet->getColumnDimension('O')->setWidth(20);
			$objActSheet->getColumnDimension('P')->setWidth(20);
			$objActSheet->getColumnDimension('Q')->setWidth(50);
			$objActSheet->setCellValue('A1', L_('订单编号'));
			$objActSheet->setCellValue('B1', L_('团购名称'));
			$objActSheet->setCellValue('C1', L_('商家名称'));
			$objActSheet->setCellValue('D1', L_('客户姓名'));
			$objActSheet->setCellValue('E1', L_('客户电话'));
			$objActSheet->setCellValue('F1', L_('订单总价'));
			$objActSheet->setCellValue('G1', L_('平台余额'));
			$objActSheet->setCellValue('H1', L_('商家余额'));
			$objActSheet->setCellValue('I1', L_('在线支付金额'));
			$objActSheet->setCellValue('J1', L_('平台').$this->config['score_name'].'');
			$objActSheet->setCellValue('K1', L_('平台优惠券'));
			$objActSheet->setCellValue('L1', L_('商家优惠券'));
			$objActSheet->setCellValue('M1', L_('商家折扣'));
			$objActSheet->setCellValue('N1', L_('支付时间'));
			$objActSheet->setCellValue('O1', L_('订单状态'));
			$objActSheet->setCellValue('P1', L_('支付情况'));
			$objActSheet->setCellValue('Q1', L_('收货地址'));


			$sql = "SELECT o.*, m.name AS merchant_name,u.nickname as username ,g.name as group_name FROM " . C('DB_PREFIX') . "group_order AS o  LEFT JOIN " . C('DB_PREFIX') . "group g ON g.group_id = o.group_id  LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON `o`.`mer_id`=`m`.`mer_id` LEFT JOIN " . C('DB_PREFIX') . "user u ON u.uid = o.uid ".$condition_where." ORDER BY o.order_id DESC LIMIT " . $i * 1000 . ",1000";
			$result_list = D()->query($sql);

			if (!empty($result_list)) {
				$index = 2;
				foreach ($result_list as $value) {
					$objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
					$objActSheet->setCellValueExplicit('B' . $index, $value['group_name']);
					$objActSheet->setCellValueExplicit('C' . $index, $value['merchant_name']);
					$objActSheet->setCellValueExplicit('D' . $index, $value['username'] . ' ');
					$objActSheet->setCellValueExplicit('E' . $index, $value['phone'] . ' ');
					$objActSheet->setCellValueExplicit('F' . $index, floatval($value['total_money']));
					$objActSheet->setCellValueExplicit('G' . $index, floatval($value['balance_pay']));
					$objActSheet->setCellValueExplicit('H' . $index, floatval($value['merchant_balance']));
					$objActSheet->setCellValueExplicit('I' . $index, floatval($value['payment_money']));
					$objActSheet->setCellValueExplicit('J' . $index, floatval($value['score_reducte']));
					$objActSheet->setCellValueExplicit('K' . $index, floatval($value['coupon_price']));
					$objActSheet->setCellValueExplicit('L' . $index, floatval($value['card_price']));
					$objActSheet->setCellValueExplicit('M' . $index, floatval($value['card_discount'])?floatval($value['card_discount']). L_('折'):'');
					$objActSheet->setCellValueExplicit('N' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
					$objActSheet->setCellValueExplicit('O' . $index, $this->get_order_status($value));
					$objActSheet->setCellValueExplicit('P' . $index, D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']));
					$objActSheet->setCellValueExplicit('Q' . $index, $value['adress']);


					$index++;
				}
			}
			sleep(2);
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
	}

	public function get_order_status($order){
		$status = '';
		if($order['paid']){
			if($order['pay_type']=='offline' && empty($order['third_id'])&& $order['status'] == 0){
				$status=L_('线下支付，未付款');
			}elseif($order['status']==0){
				$status=L_('已付款');
				if($order['tuan_type'] != 2){
					$status.=L_('已付款');
				}else{
					if($order['is_pick_in_store']){
						$status.=L_('未取货');
					}else{
						$status.=L_('未发货');
					}
				}
			}elseif($order['status']==1){
				if($order['tuan_type'] != 2){
					$status=L_('已消费');
				}else{
					if($order['is_pick_in_store']){
						$status=L_('已取货');
					}else{
						$status=L_('已发货');
					}
				}
				$status.=L_('待评价');
			}elseif($order['status']==2){
				$status=L_('已完成');
			}elseif($order['status']==3){
				$status=L_('已退款');
			}elseif($order['status']==4){
				$status=L_('已取消');
			}
		}else{
			if($status==4){
				$status=L_('已取消');
			}else{
				$status=L_('未付款');
			}
		}

		return $status;
	}

	public function foodshop_export(){
		$param = $_POST;
		$param['type'] = 'meal';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		 $param['store_session']['store_id'] = $this->store['store_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
		die;
		$store_id = $this->store['store_id'];
		$condition_where = 'Where o.mer_id = '.$this->store['mer_id'].' AND o.store_id = '.$store_id;

		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$where['real_orderid'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=' AND o.real_orderid ='.$where['real_orderid'];
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=' AND p.orderid ='.$where['orderid'];
			} elseif ($_GET['searchtype'] == 'name') {
				$where['name'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=' AND o.name ='.$where['name'];
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['phone'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=' AND o.phone ='.$where['phone'];
			} elseif ($_GET['searchtype'] == 'third_id') {
				$condition_where .=' AND p.third_id ='.$_GET['keyword'];
			}
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';
		if ($type && $sort) {
			$order_sort .= $type . ' ' . $sort . ',';
			$order_sort .= 'order_id DESC';
		} else {
			$order_sort .= 'order_id DESC';
		}
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if($pay_type&&$pay_type!='balance'){
			$condition_where .=' AND p.pay_type ="'.$pay_type.'"';
		}else if($pay_type=='balance'){
			$condition_where .= " AND  (`p`.`system_balance`<>0 OR `p`.`merchant_balance_pay` <> 0 )";
		}


		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$where['_string'] .=( $where['_string']?' AND ':''). " (create_time BETWEEN ".$period[0].' AND '.$period[1].")";
			$condition_where .=" AND  (o.create_time BETWEEN ".$period[0].' AND '.$period[1].")";
		}

		if ($status != -1) {
			$where['status'] = $status;
			$condition_where .= ' AND o.status ='.$status;
		}

		$sql_cont = 'SELECT count(o.order_id) as count from pigcms_foodshop_order o LEFT JOIN (select a.* from pigcms_plat_order a,(select max(order_id) as order_id,business_id from pigcms_plat_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id) p on o.order_id  = p.business_id '.$condition_where;
		$count  = M()->query($sql_cont);
		$count = $count[0]['count'];
		set_time_limit(0);
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = L_('订单信息');
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);
		$length = ceil($count / 1000);


		for ($i = 0; $i < $length; $i++) {
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);

			$objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息',($i+1)));
			$objActSheet = $objExcel->getActiveSheet();
			$objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('R')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('S')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('T')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('W')->setWidth(20);

			$objActSheet->setCellValue('A1', L_('订单流水号'));
			$objActSheet->setCellValue('B1', L_('商家名称'));
			$objActSheet->setCellValue('C1', L_('店铺名称'));
			$objActSheet->setCellValue('D1', L_('客户名称'));
			$objActSheet->setCellValue('E1', L_('客户电话'));
			$objActSheet->setCellValue('F1', L_('预定金'));
			$objActSheet->setCellValue('G1', L_('预定时间'));
			$objActSheet->setCellValue('H1', L_('桌台类型'));
			$objActSheet->setCellValue('I1', L_('桌台名称'));
			$objActSheet->setCellValue('J1', L_('商品名称'));
			$objActSheet->setCellValue('K1', '规格/属性');
			$objActSheet->setCellValue('L1', L_('单价'));
			$objActSheet->setCellValue('M1', L_('数量'));
			$objActSheet->setCellValue('N1', L_('单位'));
			$objActSheet->setCellValue('O1', L_('订单状态'));
			$objActSheet->setCellValue('P1', L_('订单总价'));
			$objActSheet->setCellValue('Q1', L_('余额支付'));
			$objActSheet->setCellValue('R1', L_('平台在线支付'));
			$objActSheet->setCellValue('S1', L_('商家余额支付'));
			$objActSheet->setCellValue('T1', $this->config['score_name']);
			$objActSheet->setCellValue('U1', L_('支付时间'));
			$objActSheet->setCellValue('V1', L_('支付方式'));
			$objActSheet->setCellValue('W1', L_('支付类型'));


			//$objActSheet->setCellValue('R1', '支付情况');

			$sql = 'SELECT o.*,fd.name as goods_name,fd.num as goods_num ,fd.spec as goods_spec,fd.unit as goods_unit,fd.price as goods_price,p.pay_type as pay_method,p.system_balance,p.merchant_balance_pay,p.pay_money,p.system_score_money,p.pay_time ,p.paid from '.C('DB_PREFIX').'foodshop_order o LEFT JOIN  '.C('DB_PREFIX').'plat_order  p on o.order_id  = p.business_id AND p.paid =1 LEFT JOIN '.C('DB_PREFIX').'foodshop_order_detail as fd ON fd.order_id = o.order_id '.$condition_where.' ORDER BY o.order_id DESC ' .'limit '.($i*1000).',1000';

			$list = M('')->query($sql);

			//appdump(M());
			$mer_ids = $store_ids = $table_types= $tids =array();
			foreach ($list as $l) {
				!in_array($l['mer_id'],$mer_ids) && $mer_ids[] = $l['mer_id'];
				!in_array($l['store_id'], $store_ids) && $store_ids[] = $l['store_id'];
				!in_array($l['table_type'], $table_types)&& $table_types[] = $l['table_type'];
				!in_array($l['table_id'], $tids) && $tids[] = $l['table_id'];

			}


			$type_list = array();
			if ($table_types) {
				$temp_type_list = M('Foodshop_table_type')->field(true)->where(array('id' => array('in', $table_types)))->select();
				foreach ($temp_type_list as $tmp) {
					$type_list[$tmp['id']] = $tmp;
				}
			}
			$table_list = array();
			if ($tids) {
				$temp_table_list = M('Foodshop_table')->field(true)->where(array('id' => array('in', $tids)))->select();
				foreach ($temp_table_list as $temp) {
					$table_list[$temp['id']] = $temp;
				}
			}


			$store_temp = $mer_temp = array();
			if ($mer_ids) {
				$merchants = D("Merchant")->where(array('mer_id' => array('in', $mer_ids)))->select();
				foreach ($merchants as $m) {
					$mer_temp[$m['mer_id']] = $m;
				}
			}
			if ($store_ids) {
				$merchant_stores = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
				foreach ($merchant_stores as $ms) {
					$store_temp[$ms['store_id']] = $ms;
				}
			}
			foreach ($list as &$li) {
				$li['merchant_name'] = isset($mer_temp[$li['mer_id']]['name']) ? $mer_temp[$li['mer_id']]['name'] : '';
				$li['store_name'] = isset($store_temp[$li['store_id']]['name']) ? $store_temp[$li['store_id']]['name'] : '';

				$li['table_type_name'] = isset($type_list[$li['table_type']]) ? $type_list[$li['table_type']]['name'] . '(' . $type_list[$li['table_type']]['min_people'] . '-' . $type_list[$li['table_type']]['max_people'] . '人)' : '';
				$li['table_name'] = isset($table_list[$li['table_id']]) ? $table_list[$li['table_id']]['name'] : '';
				$li['show_status'] = D('Foodshop_order')->status_list[$li['status']];
			}



			$tmp_id = 0;

			$index_good =0;
			if (!empty($list)) {
				$index = 1;
				foreach ($list as $value) {
					if($tmp_id == $value['order_id']){
						if($index_good==1){
							$objActSheet->setCellValueExplicit('A' . $index, '');
							$objActSheet->setCellValueExplicit('B' . $index, '');
							$objActSheet->setCellValueExplicit('C' . $index, '');
							$objActSheet->setCellValueExplicit('D' . $index, '');
							$objActSheet->setCellValueExplicit('E' . $index, '');
							$objActSheet->setCellValueExplicit('F' . $index, '');
							$objActSheet->setCellValueExplicit('G' . $index, '');
							$objActSheet->setCellValueExplicit('H' . $index, '');
							$objActSheet->setCellValueExplicit('I' . $index, '');
							$objActSheet->setCellValueExplicit('J' . $index, $value['goods_name']);
							$objActSheet->setCellValueExplicit('K' . $index, $value['goods_spec']);
							$objActSheet->setCellValueExplicit('L' . $index, $value['goods_price']);
							$objActSheet->setCellValueExplicit('M' . $index, $value['goods_num']);
							$objActSheet->setCellValueExplicit('N' . $index, $value['goods_unit']);
							$objActSheet->setCellValueExplicit('O' . $index, '');
							$objActSheet->setCellValueExplicit('P' . $index, '');
							$objActSheet->setCellValueExplicit('Q' . $index, '');
							$objActSheet->setCellValueExplicit('R' . $index, '');
							$objActSheet->setCellValueExplicit('S' . $index, '');
							$objActSheet->setCellValueExplicit('T' . $index, '');
							$objActSheet->setCellValueExplicit('U' . $index, '');
							$objActSheet->setCellValueExplicit('V' . $index, '');
							$objActSheet->setCellValueExplicit('W' . $index, '');
						}
						if($index_good==0) {
							$objActSheet->setCellValueExplicit('A' . $index, '');
							$objActSheet->setCellValueExplicit('B' . $index, '');
							$objActSheet->setCellValueExplicit('C' . $index, '');
							$objActSheet->setCellValueExplicit('D' . $index, '');
							$objActSheet->setCellValueExplicit('E' . $index, '');
							$objActSheet->setCellValueExplicit('F' . $index, '');
							$objActSheet->setCellValueExplicit('G' . $index, '');
							$objActSheet->setCellValueExplicit('H' . $index, '');
							$objActSheet->setCellValueExplicit('I' . $index, '');
							$objActSheet->setCellValueExplicit('J' . $index, $value['goods_name']);
							$objActSheet->setCellValueExplicit('K' . $index, $value['goods_spec']);
							$objActSheet->setCellValueExplicit('L' . $index, $value['goods_price']);
							$objActSheet->setCellValueExplicit('M' . $index, $value['goods_num']);
							$objActSheet->setCellValueExplicit('N' . $index, $value['goods_unit']);
							$objActSheet->setCellValueExplicit('O' . $index, $value['show_status']);
							$objActSheet->setCellValueExplicit('P' . $index, floatval($value['total_price']));
							$objActSheet->setCellValueExplicit('Q' . $index, floatval($value['system_balance']));
							$objActSheet->setCellValueExplicit('R' . $index, floatval($value['pay_money']));
							$objActSheet->setCellValueExplicit('S' . $index, floatval($value['merchant_balance_pay']));
							$objActSheet->setCellValueExplicit('T' . $index, floatval($value['system_score_money']));
							$objActSheet->setCellValueExplicit('U' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
							$objActSheet->setCellValueExplicit('V' . $index, D('Pay')->get_pay_name($value['pay_method'], $value['is_mobile_pay'],1));
							$objActSheet->setCellValueExplicit('W' . $index, L_('支付余额'));
							$index_good = 1;
						}

						$index++;
					}else{
						$index++;
						$objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
						$objActSheet->setCellValueExplicit('B' . $index, $value['merchant_name']);
						$objActSheet->setCellValueExplicit('C' . $index, $value['store_name']);
						$objActSheet->setCellValueExplicit('D' . $index, $value['name']);
						$objActSheet->setCellValueExplicit('E' . $index,$value['phone']);
						$objActSheet->setCellValueExplicit('F' . $index, $value['book_price']);
						$objActSheet->setCellValueExplicit('G' . $index, $value['book_time'] ? date('Y-m-d H:i:s', $value['book_time']) : '');
						$objActSheet->setCellValueExplicit('H' . $index,$value['table_type_name']);
						$objActSheet->setCellValueExplicit('I' . $index,$value['table_name']);
						$objActSheet->setCellValueExplicit('J' . $index,'');
						$objActSheet->setCellValueExplicit('K' . $index,'');
						$objActSheet->setCellValueExplicit('L' . $index,'');
						$objActSheet->setCellValueExplicit('M' . $index,'');
						$objActSheet->setCellValueExplicit('N' . $index,'');
						$objActSheet->setCellValueExplicit('O' . $index, $value['show_status']);
						$objActSheet->setCellValueExplicit('P' . $index, floatval($value['total_price']));
						$objActSheet->setCellValueExplicit('Q' . $index, floatval($value['system_balance']));
						$objActSheet->setCellValueExplicit('R' . $index, floatval($value['pay_money']));
						$objActSheet->setCellValueExplicit('S' . $index, floatval($value['merchant_balance_pay']));
						$objActSheet->setCellValueExplicit('T' . $index, floatval($value['system_score_money']));
						$objActSheet->setCellValueExplicit('U' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
						$objActSheet->setCellValueExplicit('V' . $index,D('Pay')->get_pay_name($value['pay_method'], $value['is_mobile_pay'], 1));
						$objActSheet->setCellValueExplicit('W' . $index,L_('支付定金'));
						$index++;
						$index_good = 0;
					}
					$tmp_id = $value['order_id'];

				}
			}
			sleep(2);
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
	}

	/**
	 * 实体卡管理
	 */
	public  function physical_card(){
		// 开启店员权限功能 店小二没权限
		if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
			$this->error(L_('您当前身份为【店小二】，没有报表统计权限，请联系管理员！'));
		}

		$card_list = D('Physical_card')->get_cardid_by_mer_id($this->store['mer_id']);
		$this->assign($card_list);
		$this->display();
	}

	public  function physical_card_add(){
		$mer_id = $this->store['mer_id'];
		if(IS_POST){
			$cardid = $_POST['cardid'];
			$card  = D('Physical_card');
			$result =$card->check_card($cardid,$_POST['phone'],$mer_id);
			if($result['error_code']){
				$this->error($result['msg']);
			}
			$card->bind_user($result['card_info'],$result['user'],L_('X1用实体卡（卡号：X2）为用户充值X3',array('X1'=>$this->staff_session['name'],'X2'=>$_POST['cardid'],'X3'=>$result['card']['balance_money'].C('config.Currency_txt'))));
			$log['staff_id'] = $this->staff_session['id'];
			$log['mer_id'] =$this->store['mer_id'];
			$log['card_id'] = $_POST['cardid'];
			$log['des'] = L_('店员 X1 为用户（X2）绑定实体卡（卡号：X3）',array('X1'=>$this->staff_session['name'],'X2'=>$result['user']['uid'],'X3'=>$_POST['cardid']));
			$card->add_log($log);
			$this->success(L_('绑定成功'));
		}else{
			$this->display();
		}
	}

	public function physical_card_log(){
		$log = D('Physical_card')->card_log(0,$this->staff_session['id'],0);
		$this->assign($log);
		$this->display();
	}

	public function scan_payid_check(){
		$payid = $_POST['payid'];
		$res = M('Tmp_payid')->where(array('payid'=>$payid))->order('add_time DESC')->find();
		if(($res['add_time']+60)<time()){
			$this->error(L_('付款码错误或超时，请重新输入!'));
		}else{
			$this->success(array('uid'=>$res['uid'],'payid'=>$payid));
		}
	}

    // 团购新单统计数量
    public function group_count()
    {
        $time = I('time', time());
        $where = array(
            'mer_id' => $this->store['mer_id'],
            'store_id' => $this->store['store_id'],
            'pay_time' => array('gt', $time)
        );
        $count = M('Group_order')->where($where)->count();
        exit(json_encode(array('errcode' => 0, 'data' => array('count' => intval($count), 'time' => time()))));
    }

    //景区门票当日未核销数量
    public function scenic_count(){
    	$time=I('time',time());
    	$db=D('Scenic_order');
    	$where=array();
    	$mer_id=$this->store['mer_id'];
        $scenic_id=M('Scenic_list')->field('scenic_id')->where(array('company_id'=>$mer_id))->getField('scenic_id');
		$where['scenic_id']=$scenic_id;
		$where['paid']=2;
		$where['order_status']=1;
		$date=date('Y-n-j');
		$where['ticket_time']=array('like','%'.$date.'%');
		$count=$db->where($where)->count(1);
    	exit(json_encode(array('errcode' => 0, 'data' => array('count' => intval($count), 'time' => time()))));
    }

    // 餐饮呼叫服务
    public function foodshop_call_service_count()
    {
        $time = I('time', time());
        // 通知桌台绑定店员的店员，桌台没绑定店员的不通知
        $condition_where = "`o`.`table_id`=`t`.`id` AND `o`.`mer_id`=".$this->store['mer_id'].' AND `o`.`store_id`='. $this->store['store_id'].' AND `o`.`service_status` = 0 AND `o`.`service_time` >'. $time.' AND `t`.`staff_id` ='.$this->staff_session['id'];

		$condition_table = array(C('DB_PREFIX').'foodshop_order'=>'o',C('DB_PREFIX').'foodshop_table'=>'t');
        $list = D('')->table($condition_table)->field('`t`.`name`,`o`.`order_id`')->where($condition_where)->select();
        $order_ids = array();
        foreach ($list as &$value) {
        	$order_ids[] = $value['order_id'];
        	$msg = L_('【X1】的客人呼叫服务，请您及时提供服务',$value['name']);
            //$voice_return = json_decode($this->voice_baidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?vol=15&tex=' . urlencode($msg) . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5&.mp3';
            $voice_mp3 = text2audio($msg);
            $value['msg'] = $msg;
            $value['voice_mp3'] = $voice_mp3;
        }
        if ($order_ids) {
        	D('Foodshop_order')->data(array('service_status'=>1))->where(array('order_id'=>array('in',$order_ids)))->save();

        }
        exit(json_encode(array('errcode' => 0, 'data' => array('count' => count($list), 'time' => $time, 'list'=>$list))));
    }

    // 餐饮新单统计数量
    public function foodshop_count()
    {
        $time = I('time', time());
        $where = array(
            'mer_id' => $this->store['mer_id'],
            'store_id' => $this->store['store_id'],
            'order_from' => array('neq',2),
        );
		$where['_string'] = "(status = 1 and account_type=1 and create_time>$time) OR (status = 3 and account_type=2 and pay_time>$time)";
        $count = M('Foodshop_order')->where($where)->count();
        exit(json_encode(array('errcode' => 0, 'data' => array('count' => intval($count), 'time' => time()))));
    }

    // 快店新单统计数量
    public function shop_count()
    {
        $time = I('time', time());
        $where = array(
            'mer_id' => $this->store['mer_id'],
            'store_id' => $this->store['store_id'],
            'pay_time' => array('gt', $time)
        );
        //去除商城的订单
        $where['order_from']=array('neq',1);
        $count = M('Shop_order')->where($where)->count();
        exit(json_encode(array('errcode' => 0, 'data' => array('count' => intval($count), 'time' => time()))));
    }

    //商城新单统计数量
    public function mall_count(){
    	$time=I('time',time());
    	$where=array();
    	$where['mer_id']=$this->store['mer_id'];
    	$where['store_id']=$this->store['store_id'];
    	$where['pay_time']=array('gt',$time);
    	$where['order_from']=1;
    	$where_str='group_id=0';
        $group_order_arr=D('Mall_group_act')->get_success_team($where['mer_id'],$where['store_id']);
        if($group_order_arr){
            $group_order_str='';
            foreach ($group_order_arr as $k => $v) {
                if($k==0){
                    $group_order_str=$v['order_id'];
                }else{
                    $group_order_str.=','.$v['order_id'];
                }
            }
            $where_str.=' or (group_id>0 and order_id in('.$group_order_str.'))';
        }
        $where['_string']=$where_str;
    	$count=M('Shop_order')->where($where)->count(1);
    	exit(json_encode(array('errcode' => 0, 'data' => array('count' => intval($count), 'time' => time()))));
    }

    // 预约新单统计数量
    public function appoint_count()
    {
        $time = I('time', time());
        $where = array(
            'store_id' => $this->store['store_id'],
            'paid' => 1,
            'order_time' => array('gt', $time)
        );
        $count = M('Appoint_order')->where($where)->count();
        exit(json_encode(array('errcode' => 0, 'data' => array('count' => intval($count), 'time' => time()))));
    }

    public function store_count()
    {
        $time = I('time', time());
        $where = array(
            'store_id' => $this->store['store_id'],
            'pay_time' => array('gt', $time),
            'from_plat' => 0
        );
        $count = M('Store_order')->where($where)->count();
        exit(json_encode(array('errcode' => 0, 'data' => array('count' => intval($count), 'time' => time()))));
    }

    public function cash_count()
    {
        $time = I('time', time());
        $where = array(
            'store_id' => $this->store['store_id'],
            'pay_time' => array('gt', $time),
            'from_plat' => 1
        );
        $count = M('Store_order')->where($where)->count();
        exit(json_encode(array('errcode' => 0, 'data' => array('count' => intval($count), 'time' => time()))));
    }

	// 通知上菜提醒
    public function foodshop_call_staff_count()
    {
        $time = I('time', time());

        $condition_where = "`o`.`table_id`=`t`.`id` AND `o`.`mer_id`=".$this->store['mer_id'].' AND `o`.`store_id`='. $this->store['store_id'].' AND `o`.`running_call_status` = 1 AND `o`.`running_time` >'. $time;
		$condition_table = array(C('DB_PREFIX').'foodshop_order'=>'o',C('DB_PREFIX').'foodshop_table'=>'t');
        $list = D('')->table($condition_table)->field('`t`.`name`,`o`.`order_id`')->where($condition_where)->select();

        $order_ids = array();
        foreach ($list as &$value) {
        	$order_ids[] = $value['order_id'];
        	$msg = L_('【X1】的客人上菜提醒，请及时确认菜品！',$value['name']);
            //$voice_return = json_decode($this->voice_baidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?vol=15&tex=' . urlencode($msg) . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5&.mp3';
            $voice_mp3 = text2audio($msg);
            $value['msg'] = $msg;
            $value['voice_mp3'] = $voice_mp3;
        }
        if ($order_ids) {
        	D('Foodshop_order')->data(array('running_call_status'=>0))->where(array('order_id'=>array('in',$order_ids)))->save();

        }
        exit(json_encode(array('errcode' => 0, 'data' => array('count' => count($list), 'time' => $time, 'list'=>$list))));
    }

	//店铺充值列表
	public function money_list(){
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'order_id') {
				$condition_where['order_id'] = htmlspecialchars($_GET['keyword']);
			}
		}
		$condition_where['store_id']=$this->store['store_id'];
		$_GET['type']=='all' && $_GET['type']='';
		!empty($_GET['type']) && $condition_where['type'] =$_GET['type'];
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}

			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$condition_where['_string']= "use_time BETWEEN ".$period[0].' AND '.$period[1];

		}

		$count = M('Store_money_list')->where($condition_where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 15);
		$order_list= M('Store_money_list')->where($condition_where)->order('id DESC')->limit($p->firstRow,$p->listRows)->select();
		$this->assign('pagebar',$p->show());
		$this->assign('order_list',$order_list);
		$this->assign('alias_name',$this->get_alias_c_name());
		$this->display();

	}

	protected  function get_alias_c_name(){
		$c_name = array(
				'all'=>L_('所有分类'),
				'group'=>$this->config['group_alias_name'],
				'shop'=>$this->config['shop_alias_name'],
				'shop_offline'=>$this->config['shop_alias_name'].L_('线下零售'),
				'meal'=>$this->config['meal_alias_name'],
				'appoint'=>$this->config['appoint_alias_name'],
				'store'=>L_('优惠买单'),
				'cash'=>L_('到店支付'),
			//	'coupon'=>'优惠券',
				'strecharge'=>L_('店铺充值'),

		);

		if(!$this->config['appoint_page_row']) unset($c_name['appoint']);
		if(!$this->config['is_cashier']) unset($c_name['store']);
		if(!$this->config['pay_in_store'] && !$this->config['is_cashier'] ) unset($c_name['cash'],$c_name['shop_offline']);
		return $c_name ;
	}
	public function recharge_list(){

		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'order_id') {
				$condition_where['order_id'] = htmlspecialchars($_GET['keyword']);
			}
		}
		!empty($_GET['pay_type']) && $condition_where['pay_type'] =$_GET['pay_type'];
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}

			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$condition_where['_string']= "pay_time BETWEEN ".$period[0].' AND '.$period[1];

		}
		$condition_where['store_id']=  $this->store['store_id'];

		$count = M('Store_recharge_order')->where($condition_where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 15);
		$order_list= M('Store_recharge_order')->where($condition_where)->order('order_id DESC')->limit($p->firstRow,$p->listRows)->select();
		$this->assign('pagebar',$p->show());
		$this->assign('order_list',$order_list);
		$this->assign('pay_type',$this->getPayName());
		$this->display();
	}

	protected function getPayName(){
		$payName = array(
				'weixin' => L_('微信支付'),
				'tenpay' => L_('财付通支付'),
				'yeepay' => L_('银行卡支付(易宝支付)'),
				'allinpay' => L_('银行卡支付(通联支付)'),
				'chinabank' => L_('银行卡支付(网银在线)'),
		);
		return $payName;
	}
	public function recharge(){
		if(IS_POST){
			$money = floatval($_POST['money']);
			if (empty($money)||$money <0 ||!is_numeric($money)) {
				$this->error(L_('请输入正确的充值金额'));
			}
			$data_store_recharge_order['store_id'] = $this->store['store_id'];
			$data_store_recharge_order['money'] = $money;
			$data_store_recharge_order['add_time'] = $_SERVER['REQUEST_TIME'];
			$data_store_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];

			if ($order_id = M('Store_recharge_order')->data($data_store_recharge_order)->add()) {
				$this->success($order_id,U('StorePay/check', array('order_id' => $order_id, 'type' => 'strecharge')));
			} else {
				$this->error_tips(L_('订单创建失败，请重试。'));
			}
		}else{
			$this->display();
		}
	}

	//店铺余额订单导出
	public function store_money_export()
	{
		$store_id = $this->store['store_id'];
		//$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'meal';

		$type = 'income';
		$title = '';

		switch ($type) {

			case 'income':
				$title = L_('收入明细');
				break;


		}
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);
		// 设置当前的sheet
		$objExcel->setActiveSheetIndex(0);


		$objExcel->getActiveSheet()->setTitle($type);
		$objActSheet = $objExcel->getActiveSheet();
		$cell_income   = array('type'=>L_('类型'),'order_id'=>L_('订单编号'), 'num'=>L_('数量'), 'money'=>L_('金额'),'score'=>L_('送出').$this->config['score_name'],'score_count'=>$this->config['score_name'].L_('使用数量'),'use_time'=>L_('记账时间'),'desc'=>L_('描述'));
		// 开始填充头部
		$cell_name = 'cell_'.$type;
		$cell_count = count($$cell_name);
		$cell_start = 1;
		for($f='A';$f<='Z';$f++,$cell_start++){
			if($cell_start>$cell_count){
				break;
			}
			$col_char[]=$f;
		}
		$col_k=0;
		foreach($$cell_name as $key=>$v){

			$objActSheet->setCellValue($col_char[$col_k].'1', $v);
			$col_k++;
		}
		$i = 2;
		//if($type=='income'){
			$where['store_id']=$store_id;
			if($_GET['type']&&$_GET['type']!='all'){
				$where['type']=$_GET['type'];
			}
			if($_GET['order_id']){
				$where['order_id']=$_GET['order_id'];
			}
			if($_GET['store_id']){
				$where['store_id']=$_GET['store_id'];
			}


			if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
				if ($_GET['begin_time']>$_GET['end_time']) {
					$this->error(L_("结束时间应大于开始时间"));
				}
				$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));

				$time_condition=" (use_time BETWEEN ".$period[0].' AND '.$period[1].")";
				$where['_string']=$time_condition;
			}
			$result = M('Store_money_list')->field('type,order_id,num,pow(-1,income+1)*money as money,use_time,desc,score,score_count')->where($where)->order('use_time DESC')->select();

		//}
		$alias_name = $this->get_alias_c_name();
		foreach ($result as $row) {
			$col_k=0;
			foreach($$cell_name as $k=>$vv){
				switch($k){
					case 'type':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $alias_name[$row[$k]].' ');
						break;
					case 'order_id':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
						break;
					case 'real_orderid':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
						break;
					case 'orderid':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
						break;
					case 'pay_time':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]?date('Y-m-d H:i:s', $row[$k]) : '');
						break;
					case 'use_time':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]?date('Y-m-d H:i:s', $row[$k]) : '');
						break;
					case 'desc':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
						break;
					default:
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]);
						break;
				}
				$col_k++;
			}
			if($type!='income'){
				$objActSheet->setCellValue($col_char[$cell_count-1] . $i, $row['balance_pay']+$row['coupon_price']+$row['score_deducte']+$row['payment_money']);
			}
			$i++;
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();

	}

	public function shop_order_print()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        $now_order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));
        if (empty($now_order)) {
            $this->error(L_('订单信息错误'));
        }
        $printHaddle = new PrintHaddle();
        $printHaddle->printit($now_order['order_id'], 'shop_order', -1, 0, '',1);

//         $msg = ArrayToStr::array_to_str($now_order['order_id'], 'shop_order');
//         $op = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
//         $op->printit($now_order['mer_id'], $now_order['store_id'], $msg, -1);

//         $str_format = ArrayToStr::print_format($now_order['order_id'], 'shop_order');
//         foreach ($str_format as $print_id => $print_msg) {
//             $print_id && $op->printit($now_order['mer_id'], $now_order['store_id'], $print_msg, -1, $print_id);
//         }

        $this->success(L_('打印成功'));
    }

	public function get_print_has(){
		$condition['mkey'] 	= $_POST['mkey'];
		$condition['store_id']	= $this->store['store_id'];
		if(M('Orderprinter')->where($condition)->find()){
			$this->success(L_('打印机存在'));
		}else{
			$this->error(L_('打印机不存在'));
		}
	}

	public function own_print_work(){
		$mkey = $_POST['mkey'];
		if(empty($mkey)){
			$this->error(L_('请携带密钥值'));
		}
		$op = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
		$content = $op->get_own_printer($mkey);
		$this->success($content);
	}

	public function shop_order_report_form()
	{
		$shop_type=I('shop_type',1,'intval');
		$this->assign('shop_type',$shop_type);
	    $this->display();
	}

	public function ajaxShopOrder()
	{
	    $day = isset($_POST['day']) ? intval($_POST['day']) : 0;
	    $period = isset($_POST['period']) ? htmlspecialchars($_POST['period']) : '';
	    $stime = $etime = 0;
	    if ($day) {
	        $stime = strtotime("-{$day} day");
	        $etime = time();
	    }
	    if ($period) {
	        $time_array = explode('-', $period);
	        $stime = strtotime($time_array[0]);
	        $etime = strtotime($time_array[1]);
	    }
	    $where = 'paid=1 AND status!=4 AND status!=5 AND store_id='.$this->store['store_id'];
	    if ($stime && $etime) {
	        $where .= ' AND pay_time>' . $stime . ' AND pay_time<' . $etime;
	    }
        if($this->staff_session['pick_addr_ids']){
            $where .= " and ((`is_pick_in_store` <> 2) OR (`is_pick_in_store` = 2 AND FIND_IN_SET(`pick_id`,'".$this->staff_session['pick_addr_ids']."')))";
        }
	    $list = M('Shop_order')->field('sum(price) AS totalPrice, count(1) AS total, platform')->where($where)->group('platform')->select();

	    $count = array();
	    $price = array();
	    $platformNames = array(C('config.shop_alias_name'), L_('饿了么'), L_('美团外卖'));
	    $countColor = array('#01b484', '#2f4554', '#91c7ae');
	    $priceColor = array('#d48265', '#4d542f', '#337ab7');
	    foreach ($list as $row) {
	        $ctemp = array();
	        $ctemp['name'] = $platformNames[$row['platform']];
	        $ctemp['value'] = intval($row['total']);
	        $ctemp['itemStyle'] = array('normal' => array('color' => $countColor[$row['platform']]));
	        $count[] = $ctemp;
	        $ptemp = array();
	        $ptemp['name'] = $platformNames[$row['platform']];
	        $ptemp['value'] = floatval($row['totalPrice']);
	        $ptemp['itemStyle'] = array('normal' => array('color' => $priceColor[$row['platform']]));
	        $price[] = $ptemp;
	    }
	    exit(json_encode(array('error_code' => false, 'price' => $price, 'count' => $count, 'plat' => $platformNames)));
	}

    public function sort_export()
    {
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = L_('商品销量统计');
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $sortId = isset($_GET['sort_id']) ? intval($_GET['sort_id']) : 0;
        $orderFrom= isset($_GET['orderFrom']) ? intval($_GET['orderFrom']) : 0;
        $keyword= isset($_GET['keyword']) ? $_GET['keyword'] : '';

        $stime = isset($_GET['stime']) && $_GET['stime'] ? htmlspecialchars($_GET['stime']) : '';
        $etime = isset($_GET['etime']) && $_GET['etime'] ? htmlspecialchars($_GET['etime']) : '';

        $store_id = $this->store['store_id'];

        $shopGoodsSortDB = D('Shop_goods_sort');
        $sortList = D(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id, 'fid' => 0))->select();

        $where = 'o.paid=1 AND (o.status=2 OR o.status=3) AND o.store_id=' . $store_id;
        if ($sort = D(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sortId, 'store_id' => $store_id))->find()) {
            if ($sort['fid'] == 0) {
                $ids = $shopGoodsSortDB->getAllSonIds($sortId, $store_id);
                if ($ids) {
                    $where .= ' AND d.sort_id IN (' . implode(',', $ids) . ')';
                }
            }
        }
        if ($stime && $etime) {
            $where .= ' AND o.pay_time>' . strtotime($stime) . ' AND o.pay_time<' . strtotime($etime);
        }
        if ($orderFrom) {
            if ($orderFrom == 1) {
                $where .= ' AND o.order_from=1';
            } elseif ($orderFrom == 3) {
                $where .= ' AND o.order_from=6';
            } else {
                $where .= ' AND o.order_from<>1 AND o.order_from<>6';
            }
        }
        if($keyword){
            $where .= " AND d.name like '%".$keyword."%' ";
        }
        //店铺类型，1-快店；2-商城
		$shop_type=I('get.shop_type',1,'intval');
		if($shop_type==1){
			$alias_name=$this->config['shop_alias_name'];
			$where.=' and order_from!=1';
		}elseif($shop_type==2){
			$alias_name=$this->config['mall_alias_name'];
			$alias_name=$alias_name?$alias_name:L_('商城');
			$where.=' and order_from=1';
		}
        $sql = "SELECT `d`.* FROM " . C('DB_PREFIX') . 'shop_order AS o INNER JOIN ' . C('DB_PREFIX') . 'shop_order_detail AS d ON d.order_id=o.order_id WHERE ' . $where;
        $orders = M()->query($sql);
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setTitle(L_('商品销量详情'));
        $objActSheet = $objExcel->getActiveSheet();
        $objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

        $objActSheet->setCellValue('A1', L_('商品名称'));
        $objActSheet->setCellValue('B1', L_('商品属性'));
        $objActSheet->setCellValue('C1', L_('数量'));
        $objActSheet->setCellValue('D1', L_('单价'));
        $objActSheet->setCellValue('E1', L_('销售时间'));
        $objActSheet->setCellValue('F1', L_('订单来源'));
        $objActSheet->setCellValue('G1', L_('商品条码'));
        $objActSheet->setCellValue('H1', L_('折扣'));
        $index = 1;
        foreach ($orders as $order) {
            $index ++;
            $objActSheet->setCellValueExplicit('A' . $index, $order['name']);
            $objActSheet->setCellValueExplicit('B' . $index, $order['spec']);
            $objActSheet->setCellValueExplicit('C' . $index, $order['num']);
            $objActSheet->setCellValueExplicit('D' . $index, $order['discount_price'] > 0 ? $order['discount_price'] : $order['price']);
            $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $order['create_time']));
            if ($order['order_from'] == 1) {
                $objActSheet->setCellValueExplicit('F' . $index, L_('商城'));
            } elseif ($order['order_from'] == 6) {
                $objActSheet->setCellValueExplicit('F' . $index, L_('线下零售'));
            } else {
                $objActSheet->setCellValueExplicit('F' . $index, $alias_name);
            }
            $objActSheet->setCellValueExplicit('G' . $index, $order['number']);
            $objActSheet->setCellValueExplicit('H' . $index, getFormatNumber($order['discount_rate']/10));
        }

        // 输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();

    }

	public function sub_card(){
		import('@.ORG.merchant_page');

		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'pass') {
				$where['pass'] = htmlspecialchars($_GET['keyword']);
			}else if ($_GET['searchtype'] == 'name') {
				$where['nickname'] = array('like','%'.htmlspecialchars($_GET['keyword']).'%');
			}else if ($_GET['searchtype'] == 'phone') {
				$where['phone'] = htmlspecialchars($_GET['keyword']);
			}
		}
		!empty($_GET['pay_type']) && $condition_where['pay_type'] =$_GET['pay_type'];
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}

			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$where['_string']= "use_time BETWEEN ".$period[0].' AND '.$period[1];

		}
		$where['status']=1;
		$where['store_id']=$this->store['store_id'];


		$count = M('Sub_card_user_pass')->where($where)->count();
		$p=new Page($count,15);
		$where['s.status']=1;
		unset($where['status']);
		$list = M('Sub_card_user_pass')->field('s.* ,sc.name,sc.desc,sc.price,sc.free_total_num,u.nickname,u.phone')->join('AS s LEFT JOIN '.C('DB_PREFIX').'sub_card sc ON sc.id = s.sub_card_id LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid = case  when s.share_uid>0 then s.share_uid else s.uid end ')->where($where)->order('s.use_time DESC')->limit($p->firstRow,$p->listRows)->select();

		$this->assign('order_list',$list);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	//搜索免单
	public function sub_card_find(){
		if(IS_POST) {
			$find_value = $_POST['find_value'];
			if (strlen($find_value) == 14) {
				$res = D('Sub_card_order')->get_orderid_by_pass($find_value,$this->store['store_id']);

				if (!empty($res)) {
					$return['list'] = array($res['pass_info']);
					$return['sub_card'] = $res['sub_card'];
					$return['row_count'] = 1;

					echo json_encode($return);
				} else {
					$return['row_count'] = 0;

					echo json_encode($return);
				}
			}
		}else{
			$this->display();
		}
	}

	//免单套餐验证消费
	public function sub_card_verify(){
		$find_value = $_GET['pass'];
		$res = D('Sub_card_order')->get_orderid_by_pass($find_value,$this->store['store_id']);
		if($res['pass_info']['status']==1){
			$this->error(L_('此消费码已消费，不能再验证消费！'));
		} else {
			$condition['id'] = $res['pass_info']['id'];
			$date['status'] = '1';
			$date['use_time'] = $_SERVER['REQUEST_TIME'];
			$date['last_staff'] = $this->staff_session['name'];
			if(M('Sub_card_user_pass')->where($condition)->data($date)->save()){
				//验证增加商家余额
				$now_order['order_type'] = 'sub_card';
				$now_order['order_id'] = $res['pass_info']['id'];
				$now_order['mer_id'] =$this->store['mer_id'];
				$now_order['store_id'] =$this->store['store_id'];
				$now_order['percent'] =$res['sub_card']['percent'];
				$now_order['desc']=L_('免单验证消费记入收入');
				$now_order['money'] = $res['sub_card']['price']/$res['sub_card']['free_total_num'];
				$now_order['total_money'] =$now_order['money'];
				D('SystemBill')->bill_method(0,$now_order);
				$this->success(L_('验证消费成功！'));
			}else{
				$this->error(L_('验证失败！请重试。'));
			}
		}
	}

	public function check_foodshop_goods_stock()
	{
	    if ($result = D('Foodshop_goods')->check_stock_list($this->store['store_id'])) {
	        exit(json_encode(array('status' => 1)));
	    } else {
	        exit(json_encode(array('status' => 0)));
	    }
	}

	public function foodshop_goods_stock()
	{
	    $this->assign('goods_list', D('Foodshop_goods')->check_stock_list($this->store['store_id']));
	    $this->display();
	}

    public function foodshop_change_order_note()
    {
        $condition_where['store_id'] = $this->store['store_id'];
        $condition_where['order_id'] = $_GET['order_id'];
        $condition_where['id'] = $_POST['detail_id'];
        $note = isset($_POST['note']) ? htmlspecialchars(trim($_POST['note'])) : '';

        if ($detail = M('Foodshop_order_detail')->where($condition_where)->find()) {
            if ($detail['note'] != $note) {
                M('Foodshop_order_detail')->where($condition_where)->save(array('note' => $note));
            }
            $this->success(L_('保存成功！'));
        } else {
            $this->error(L_('保存失败！'));
        }
    }


	public function appoint_reply_refund()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
		$type = isset($_POST['type']) ? htmlspecialchars(trim($_POST['type'])) : 'agree';
		$reply_content = isset($_POST['reply_content']) ? htmlspecialchars(trim($_POST['reply_content'])) : '';
		$order = D('Appoint_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find();
		if (empty($order)) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('订单信息错误,不是该店铺订单'))));
		}
		if (!in_array($type, array('agree', 'disagree'))) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('请选择正确的回复形式'))));
		}

		$refund = D('Appoint_order_refund')->field(true)->where(array('order_id' => $order_id, 'id' => $refund_id,'status' => array('neq',4)))->find();
		if (empty($refund)) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('订单的预约信息错误'))));
		}
		if ($refund['status'] != 0) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('当前的预约信息无需修改'))));
		}

		if($type == 'agree'){//同意退款
			$refundMoney = $refund;
			$score_used_count=0;
			$score_deducte=0;

			if ($refundMoney['score_deducte']>0 ) {
				$score_used_count = $refundMoney['score_used_count'];
				$score_deducte = $refundMoney['score_deducte'];
			}

			$merchant_balance = 0;
			$card_give_money = 0;

			if ($refundMoney['card_give_money']>0 ) {
				$card_give_money = $refundMoney['card_give_money'];
			}
			if ($refundMoney['merchant_balance']>0 ) {
				$merchant_balance = $refundMoney['merchant_balance'];
			}

			$balance_pay = 0;
			if ($refundMoney['balance_pay'] > 0) {
				$balance_pay = $refundMoney['balance_pay'];
			}

			$balance_payment_money = 0; //余额支付定金
			if ($refundMoney['balance_payment_money'] > 0) {
				$balance_payment_money = $refundMoney['balance_payment_money'];
			}

			/*退还的平台E卡的金额*/
			if($refundMoney['ecard_pay']>0){
				$ecard_pay=$refundMoney['ecard_pay'];
			}
			$payment_money = 0;
			if ($refundMoney['payment_money'] > 0) {
				$payment_money = $refundMoney['payment_money'];
                $pay_type = $order['product_pay_type'] ? $order['product_pay_type']: $order['pay_type'];
                $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['orderid'],'order_type'=>'balance-appoint','pay_type'=>$pay_type,'paid_money'=>array('gt',0)))->find();
                if ($order['is_own']) {
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
						}
					}
				} else {
                    if ($order['product_pay_type'] && $order['product_pay_type']=='alipayh5') {
                        $is_wap = 1;
                    } else {
                        $is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
                    }
					$pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0);
				}

				if (empty($pay_method)) {
					exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
				}
                if(empty($order['pay_type'])){
                    if ($order['product_pay_type'] && empty($pay_method[$order['product_pay_type']])) {
                        exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
                    }
                }else{
                    if ($order['pay_type'] && empty($pay_method[$order['pay_type']])) {
                        exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
                    }
                }

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
					$order['order_type'] = 'appoint';
					$order['orderNo'] = $payment_money_order['orderNo'];
					if($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
						$mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
						if(!empty($mer_juhe)){
							$lowfeepay->user_id = $mer_juhe['user_id'];
							$lowfeepay->key = $mer_juhe['key'];
							$order['is_own'] =1 ;
                        }
					}
					$go_refund_param= $lowfeepay->refund($order);
					fdump($go_refund_param,'lumin_refund',true);
                } else if ($order['product_pay_type'] || $order['pay_type']) {
					if($order['pay_type'] == ''){
						$pay_class_name = ucfirst($order['product_pay_type']);
						$order['is_mobile_pay'] = $order['product_is_mobile_pay'];
					}else{
						$pay_class_name = ucfirst($order['pay_type']);
					}
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if (empty($import_result)) {
						exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
					}

					$order['order_type'] = 'appoint';
					$order['order_id'] = $order['orderid'];
					if ($order['order_from'] == 4) {
						$pay_method['weixin']['config'] = array(
							'pay_weixin_appid' => C('config.pay_wxapp_appid'),
							'pay_weixin_key' => C('config.pay_wxapp_key'),
							'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
							'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
						);
						if ($order['is_own'] == 1) {
							$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
							$pay_method['weixin']['config'] = array(
								'pay_weixin_appid' => $wxapp_own_pay['appid'],
								'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
								'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
								'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
								'is_own' => 1
							);
						}
					}

					$now_user = D('User')->get_user($order['uid']);
					if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
					}
					$order['order_type'] = 'balance-appoint';
					$order['order_id'] = $order['orderid'];
					$order['third_id'] = '';
					$order['payment_money'] = $payment_money;

					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if($order['pay_type'] == ''){
						$pay_class = new $pay_class_name($order, $payment_money, $order['product_pay_type'], $pay_method[$order['product_pay_type']]['config'], $now_user, 1);
					}else{
						$pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
					}
					fdump($pay_class,'weixin',1);
					$go_refund_param = $pay_class->refund();
					fdump($go_refund_param,'weixin',1);
				}

                if ($go_refund_param && !(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
					exit(json_encode(array('errcode' => 1, 'msg' => $go_refund_param['msg'])));
				} else {
					$third_refund_id = $go_refund_param['refund_param']['refund_id'];
				}
			}
			$mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();

			$order_type='appoint';
			if ($score_used_count != 0) {
				$result = D('User')->add_score($order['uid'], $score_used_count,'预约退款, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
				$param = array('refund_time' => time());
				if ($result['error_code']) {
					$score_used_count = 0;
				}
			}
			//定金
			if ($refundMoney['online_payment_money'] > 0) {
				$payment_money = $refundMoney['online_payment_money'];
				$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['order_id'],'order_type'=>'appoint','pay_type'=>$order['pay_type'],'paid_money'=>array('gt',0)))->find();
				if ($order['is_own']) {
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
						}
					}
				} else {
					$is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
					$pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0);
				}

				if (empty($pay_method)) {
					exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
				}
				if (empty($pay_method[$order['pay_type']])) {
					exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
				}

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
					$order['order_type'] = 'appoint';
					$order['orderNo'] = $payment_money_order['orderNo'];
					if($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
						$mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
						if(!empty($mer_juhe)){
							$lowfeepay->user_id = $mer_juhe['user_id'];
							$lowfeepay->key = $mer_juhe['key'];
							$order['is_own'] =1 ;
                        }
					}
					$go_refund_param= $lowfeepay->refund($order);
					fdump($go_refund_param,'lumin_refund',true);
				} else {
					fdump($order['pay_type'],'weixin',1);
					$pay_class_name = ucfirst($order['pay_type']);
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if (empty($import_result)) {
						exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
					}

					$order['order_type'] = 'appoint';
					$order['order_id'] = $order['orderid'];
					if ($order['order_from'] == 4) {
						$pay_method['weixin']['config'] = array(
							'pay_weixin_appid' => C('config.pay_wxapp_appid'),
							'pay_weixin_key' => C('config.pay_wxapp_key'),
							'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
							'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
						);
						if ($order['is_own'] == 1) {
							$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
							$pay_method['weixin']['config'] = array(
								'pay_weixin_appid' => $wxapp_own_pay['appid'],
								'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
								'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
								'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
								'is_own' => 1
							);
						}
					}

					$now_user = D('User')->get_user($order['uid']);
					if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
					}
					$order['order_type'] = 'appoint';
					if($order['pay_type'] == 'alipayh5'){
						$order['order_id'] = $order['orderid'];
					}else{
						if(empty($order['orderids'])){
							$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order_id,'order_type'=>'appoint'))->find();
							$order['order_id'] = $payment_money_order['orderid'];
							$order['orderid'] = $payment_money_order['orderid'];
						}else{
							$order['order_id'] = $order['orderids'];
							$order['orderid'] = $order['orderids'];
						}
					}
					$order['third_id'] = '';
					$order['payment_money'] = $payment_money;

					$import_result = import('@.ORG.pay.' . $pay_class_name);
					$pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
					fdump($pay_class,'weixins',1);
					$go_refund_param = $pay_class->refund();
					fdump($go_refund_param,'weixins',1);
				}

				if (!(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
					exit(json_encode(array('errcode' => 1, 'msg' => $go_refund_param['msg'])));
				} else {
					$third_refund_id = $go_refund_param['refund_param']['refund_id'];
				}
			}
			$mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();

			$order_type='appoint';
			if ($score_used_count != 0) {
				$result = D('User')->add_score($order['uid'], $score_used_count,'预约退款, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
				$param = array('refund_time' => time());
				if ($result['error_code']) {
					$score_used_count = 0;
				}
			}

			//平台余额退款
			if ($balance_pay > 0) {
				$result = D('User')->add_money($order['uid'], $balance_pay, '预约退款,增加余额,订单编号' . $order['real_orderid']);
				fdump("22222",'test1',1);
				if($result['error_code']){
					$balance_pay = 0;
				}
			}

			if ($balance_payment_money > 0) {
				$result = D('User')->add_money($order['uid'], $balance_payment_money, '预约定金退款,增加余额,订单编号' . $order['real_orderid']);
				if($result['error_code']){
					$balance_pay = 0;
				}
			}
			//平台E卡退款返回余额
			if($ecard_pay>0){
				$result=D('User')->add_money($order['uid'],$ecard_pay,'预约退款，'.$this->config['ecard_alias_name'].'退还增加余额'.$order['real_orderid']);
				if($result['error_code']){
					$ecard_pay=0;
				}
			}

			//商家会员卡余额退款
			if ($merchant_balance > 0 || $card_give_money > 0) {
				$result = D('Card_new')->add_user_money($order['mer_id'], $order['uid'],  $merchant_balance, $card_give_money, 0,  '预约退款,增加会员卡赠送余额,订单编号' . $order['real_orderid'],'预约退款,增加会员卡赠送余额,订单编号' . $order['real_orderid']);
				if ($result['error_code']) {
					$merchant_balance = 0;
					$card_give_money = 0;
				}
			}
			$data = array();
			$data['reply_content'] = $reply_content;
			$data['reply_time'] = time();
			$data['status'] = 1;
			$data['score_deducte'] = $score_deducte;
			$data['score_used_count'] = $score_used_count;
			$data['balance_pay'] = $balance_pay;
			$data['ecard_pay']=$ecard_pay;
			$data['merchant_balance'] = $merchant_balance;
			$data['card_give_money'] = $card_give_money;
			$data['payment_money'] = $payment_money;
			//$data['third_refund_id'] = $third_refund_id;

			D('Appoint_refund_log')->add(array('refund_id' => $refund_id, 'status' => 1, 'dateline' => time(), 'note' => $reply_content));
			D('Appoint_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save($data);

			$recordContent['content'] = [
				[
					'title' => '退款理由',
					'value' => $refund['reason'],
					'type'  => 'text'
				],[
					'title' => '退款时间',
					'value' => date('Y-m-d H:i:s'),
					'type'  => 'text'
				],
			];
			D('Appoint_order_records')->addOrderRecord($order['mer_id'], $order['order_id'], '订单退款', $recordContent);
			$saveData = array('status' => 4);
			$mdata = D('Merchant_money_list')->field('money,total_money,percent,type,system_take')->where(array('_string' => "type='appoint'",'income'=>1, 'order_id' => $order_id))->find();
			if ($mdata) {
				$totalMoney = floatval(round($mdata['money'], 2));
				if ($totalMoney<0) {
					$totalMoney=0;
				}
                $returnSystemTake = $returnTakePercent = 0;
                if($mdata['system_take'] > 0){
                    $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                    $returnTakePercent = $mdata['percent'];
                }
				D('Merchant_money_list')->use_money(
					$order['mer_id'], 
					$totalMoney, 
					$mdata['type'],
					'预约退款,减少余额,订单编号' . $order['real_orderid'], 
					$order_id, 
					$returnTakePercent, 
					$returnSystemTake,
					[],
					$order['store_id'],
					$go_refund_param['refund_param']??[]
				);
			}
			D('Appoint_order')->where(array('order_id' => $order_id))->save(array('is_apply_refund'=>0,'paid'=>2));
			/*更新平台订单状态*/
            $saveData['system_status']=4;
			D('System_order')->save_order_info($order_type,$order_id,$saveData);
            $appointSupply = D('Appoint_supply')->where(['order_id' => $order_id])->find();
            if($appointSupply && $appointSupply['status'] == 2) {
                D('Appoint_supply')->where(['order_id' => $order_id])->save(['status' => 5]);// 未服务的退款标记一下 5 已退款
            }
			//增加消息通知
            $this->addUserNotice($order_id,$order['mer_id'],$order['store_id'],$order['uid'],'商家已经同意退款','您申请的退款商家已经同意，请查看订单详情','appoint');
			exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));
		}else{
			//不同意
			M('Appoint_refund_log')->add(array('refund_id'=>$refund_id,'status'=>2,'dateline' => time(),'note'=>$reply_content));
			M('Appoint_order_refund')->where(array('order_id'=>$order_id,'id'=>$refund_id))->save(array('reply_time'=>time(),'reply_content'=>$reply_content,'status'=>2));
            D('Appoint_order')->where(array('order_id' => $order_id))->save(array('is_apply_refund'=>0));
			//D('Appoint_order')->where(array('order_id' => $order_id, 'id' => $refund_id))->save(array('reply_time' => time(), 'reply_content' => $reply_content, 'status' => 2));
			exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));

		}

	}

	public function replyRefund()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
		$type = isset($_POST['type']) ? htmlspecialchars(trim($_POST['type'])) : 'agree';
		$reply_content = isset($_POST['reply_content']) ? htmlspecialchars(trim($_POST['reply_content'])) : '';
		
		$where = [
            'order_id' => $order_id, 
        ];
		if(!$_POST['is_mer']){
            $where['store_id'] = $this->store['store_id'];
        }

		$order = D('Shop_order')->field(true)->where($where)->find();
		if (empty($order)) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('订单信息错误'))));
		}
		if (!in_array($type, array('agree', 'disagree'))) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('请选择正确的回复形式'))));
		}

        $goods_total_all = D('Shop_order_detail')->where(['order_id'=>$order_id])->sum('num');
        if($order['status']==4){
            $refund_goods_total_all = D('Shop_order_refund')->join(' as r inner join '.C("DB_PREFIX").'shop_refund_detail as rd on rd.refund_id = r.id')
                ->where(['r.order_id'=>$order_id,'r.status'=>1])->sum('rd.num');
            if(!$refund_goods_total_all||$refund_goods_total_all >= $goods_total_all){
                exit(json_encode(array('errcode' => 1, 'msg' => '该订单已退款，请不要重复操作')));
            }
        }

		$refund = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'id' => $refund_id,'status' => array('neq',4)))->find();
		if (empty($refund)) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('订单的退货信息错误'))));
		}
		if ($refund['status'] != 0) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('当前的退货信息无需修改'))));
		}
		if($order['order_from']==1){
			$order_type='mall';
		}else{
			$order_type='shop';
		}
		//状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请)
		if ($type == 'agree') {
			//先减余额支付，在进行线上支付的退款
			//已退款的金额的查询
			// $refundMoney = D('Shop_order_refund')->field('sum(balance_pay) as balance_pay, sum(merchant_balance) as merchant_balance, sum(card_give_money) as card_give_money, sum(payment_money) as payment_money, sum(score_used_count) as score_used_count, sum(score_deducte) as score_deducte')->where(array('order_id' => $order_id,'status' => array('neq',4)))->find();
			$refundMoney = $refund;
			//积分->商家余额->平台余额->在线支付的金额
			$nowPayMoney = floatval($refund['price']);//当前要退款的金额

            // 查看要退换的积分 积分不够则从退款金额抵扣 20191219
            $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,$nowPayMoney);

			if($this->config['open_allinyun']==1){
                $nowPayMoney = $returnScoreBack['refundMoney'];

				import('@.ORG.AccountDeposit.AccountDeposit');
				$deposit = new AccountDeposit('Allinyun');
				$allyun = $deposit->getDeposit();

				$allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid']);

				if($allinyun_user['bizUserId']!=''){
					$allyun->setUser($allinyun_user);
					$params['bizOrderNo'] = 'shop_'.$order['orderid'];
					$params['oriBizOrderNo'] = $order['third_id'];
					$params['amount'] = intval($nowPayMoney*100);
					$res = $allyun->refundApply($params);

					if($res['status']=='OK'){
						$data_shop_order['order_id'] = $order['order_id'];
						$data_shop_order['status'] = 4;
						$data_shop_order['last_time'] = time();
						$data_shop_order['refund_detail'] = serialize($res);
						$return = $this->shop_refund_detail($order, $order['store_id']);
						if ($return['error_code']) {
							exit(json_encode(array('errcode' => 1, 'msg' =>$return['msg'])));
//							$this->error_tips($return['msg']);
						}
						D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
						D('Shop_order')->data($data_shop_order)->save();

			            // 退还获得积分 20191219
			            $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,floatval($refund['price']),1);

						exit(json_encode(array('errcode' => 0, 'msg' =>L_('退款成功'))));
					}else{
						exit(json_encode(array('errcode' => 1, 'msg' =>$res['message'])));
					}
				}
			}

			$returnRefundMoney = D('Shop_order')->getRrfundDetail($refundMoney,$returnScoreBack);
            // 积分
            $score_used_count=$returnRefundMoney['score_used_count'];
            // 积分抵扣金额
            $score_deducte=$returnRefundMoney['score_deducte'];
            // 商家余额
            $merchant_balance=$returnRefundMoney['merchant_balance'];
            // 商家赠送余额
            $card_give_money=$returnRefundMoney['card_give_money'];
            // 平台余额
            $balance_pay=$returnRefundMoney['balance_pay'];
            // ecard
            $ecard_pay=$returnRefundMoney['ecard_pay'];
            // 剩余退还金额
            $backMoney=$returnRefundMoney['backMoney'];
			//员工卡积分
            $employee_score_pay=$returnRefundMoney['employee_score_pay'];
			//员工卡余额
            $employee_balance_pay=$returnRefundMoney['employee_balance_pay'];

			$payment_money = 0;
			$third_refund_id = 0;
			if ($refundMoney['payment_money'] > 0) {
				$payment_money = $refundMoney['payment_money'];
				// $payment_money = $refundMoney['price'];
				$order_from_type = $order['order_from'] == '1' ? 'mall' : 'shop';
				$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['order_id'],'order_type'=>$order_from_type,'pay_type'=>$order['pay_type'],'paid_money'=>array('gt',0)))->find();
				if($payment_money == $payment_money_order['payment_money']){
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $payment_money = $payment_money_order['paid_money'];
                }

                // 退回积分不够，需要扣除相应金额
	            if ($backMoney) {
	            	$payment_money = getFormatNumber($payment_money - $backMoney);
	            	if ($payment_money<0) {
	                    // 不够抵扣
	            		$backMoney -= $payment_money;
	            		$payment_money = 0;
	            	}else{
	                    // 足够抵扣
	            		$backMoney=0;
	            	}
	            }
			}

			if ($payment_money > 0) {
				if ($order['is_own'] && $payment_money_order['pay_method'] != 'lichu') {
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
						}
					}
					$now_merchant = D('Merchant')->get_info($order['mer_id']);
                    $open_sub_mchid = C("config.open_sub_mchid");
	                if ($now_merchant['sub_mch_refund'] && $open_sub_mchid && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
	                    $pay_method['weixin']['config']['pay_weixin_appid'] = C("config.pay_weixin_appid");
	                    $pay_method['weixin']['config']['pay_weixin_appsecret'] = C("config.pay_weixin_appsecret");
	                    $pay_method['weixin']['config']['pay_weixin_mchid'] = C("config.pay_weixin_sp_mchid");
	                    $pay_method['weixin']['config']['pay_weixin_key'] = C("config.pay_weixin_sp_key");
	                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
	                    $pay_method['weixin']['config']['pay_weixin_client_cert'] = C("config.pay_weixin_sp_client_cert");
	                    $pay_method['weixin']['config']['pay_weixin_client_key'] = C("config.pay_weixin_sp_client_key");
	                    $pay_method['weixin']['config']['is_own'] = 2 ;
	                }
				} else {
					$is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
                    $pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0,false,true);
				}

				if (empty($pay_method)) {
					exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
					return false;
				}
                if (in_array($order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                    $pay_class_name = 'Hqpay';
                    empty($pay_method[$order['pay_type']]) && $pay_method[$order['pay_type']] = 1;
                }
                if ($order['is_mobile_pay'] == 2 && $order['pay_type'] == 'weixin' && empty($pay_method['weixinapp'])) {
                    exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
                } else if (empty($pay_method[$order['pay_type']])) {
                    exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
                }

				if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'xiaoma' || $payment_money_order['pay_method'] == 'icbcpay' || $payment_money_order['pay_method'] == 'xkypay')){
					if($payment_money_order['pay_method'] == 'wft'){
						$pay_method = 'wftpay';
					}
					if($payment_money_order['pay_method'] == 'paylinx'){
						$pay_method = 'paylinx';
					}
					if($payment_money_order['pay_method'] == 'weikebaba'){
						$pay_method = 'weikebaba';
					}
					if($payment_money_order['pay_method'] == 'xiaoma'){
						$pay_method = 'xiaoma';
					}
					if($payment_money_order['pay_method'] == 'icbcpay'){
                        $pay_method = 'icbcpay';
                    }
                    if($payment_money_order['pay_method'] == 'xkypay'){
                        $pay_method = 'xkypay';
                    }
                	import('@.ORG.LowFeePay');
                	$lowfeepay = new LowFeePay($pay_method);
					$order['payment_refund_money'] = $payment_money;
                	$order['order_type'] = 'shop';
                	$order['orderNo'] = $payment_money_order['orderNo'];
                	if($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $order['is_own'] =1 ;
                            $order['orderNo'] =$order['orderNo'];
                        }

                    }
                	$go_refund_param= $lowfeepay->refund($order);
                	fdump($go_refund_param,'lumin_refund',true);
                }else if($payment_money_order && $payment_money_order['pay_method'] == 'lichu'){//利楚支付
                    $config = json_decode($payment_money_order['third_plat_data'],true);
                    $config['out_trade_no'] = $payment_money_order['orderNo'];
                    require_once $_SERVER['DOCUMENT_ROOT'] . "/v20/extend/pay/LichuPay.php";
                    $pay = new LichuPay();
                    $go_refund_param= $pay->refund([
                        'real_orderid'=>$order['real_orderid'],
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
					if($order['mer_id']){
						$now_merchant = M("Merchant")->where(['mer_id'=>$order['mer_id']])->find();
						if(!empty($now_merchant['chinaums_merno'])){
							$chinaums_merno = $now_merchant['chinaums_merno'];
						}
					}
					$order['order_type'] = $order['order_from'] == '1' ? 'mall' : 'shop';
                	$go_refund_param= $pay->refund($order['order_type'].'_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money, $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
                }else if($payment_money_order && $payment_money_order['pay_method'] == 'scrcu'){
                    import("@.ORG.pay.Scrcu");
                    $pay = new scrcu();
                    
                    $scrcu_mer_no = '';
                    if($order['mer_id']){
                        $now_merchant = M("Merchant")->where(['mer_id'=>$order['mer_id']])->find();
                        if(!empty($now_merchant['scrcu_mer_no'])){
                            $scrcu_mer_no = $now_merchant['scrcu_mer_no'];
                        }
                    }

                    $go_refund_param= $pay->refund($order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
                }else if($payment_money_order && strpos($payment_money_order['pay_type'],'xiantao') !== false){// 智慧仙桃支付退款
                    if($payment_money_order['paid_money'] == $payment_money){// 不支持部分退款
                        import('@.ORG.pay.XianTaoCard');
                        $pay = new XianTaoCard();

                        $now_user = D('User')->get_user($order['uid']);
                        try{
                            // 退款
                            $go_refund_param = $pay->refund($payment_money_order['orderNo'], $now_user['phone']);
                        } catch(Exception $e){
                            exit(json_encode(array('errcode' => 1, 'msg' => $e->getMessage())));
                        }
                    }else{
                        exit(json_encode(array('errcode' => 1, 'msg' => '仙桃支付方式不支持部分退款')));
                    }
                } else {

					$pay_class_name != 'Hqpay' && $pay_class_name = ucfirst($order['pay_type']);
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if (empty($import_result)) {
						exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
					}

                    $order['payment_refund_money'] = $payment_money;
					$order['order_type'] = 'shop';
					$order['order_id'] = $order['orderid'];
					if ($order['order_from'] == 4 && $order['is_own'] != 2) {
						$pay_method['weixin']['config'] = array(
								'pay_weixin_appid' => C('config.pay_wxapp_appid'),
								'pay_weixin_key' => C('config.pay_wxapp_key'),
								'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
								'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
						);
						if ($order['is_own'] == 1) {
							$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
							$pay_method['weixin']['config'] = array(
									'pay_weixin_appid' => $wxapp_own_pay['appid'],
									'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
									'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
									'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
									'is_own' => 1
							);
						}
					}

					$now_user = D('User')->get_user($order['uid']);
					if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
					}
	                $import_result = import('@.ORG.pay.' . $pay_class_name);
	                $pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
	                $go_refund_param = $pay_class->refund();
	            }

				if (!(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
					exit(json_encode(array('errcode' => 1, 'msg' => $go_refund_param['msg'])));
				} else {
					$third_refund_id = $go_refund_param['refund_param']['refund_id'];
				}
			}

			$mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();
			/*重要！！！*/
			if($order['order_from']==1){
				$alias_name=$this->config['mall_alias_name'];
				$alias_name=$alias_name?$alias_name:L_('商城');
				$order_type='mall';
			}else{
				$alias_name=$this->config['shop_alias_name'];
				$order_type='shop';
			}

			if ($score_used_count != 0) {
				$result = D('User')->add_score($order['uid'], $score_used_count, $alias_name . '部分商品退货, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
				$param = array('refund_time' => time());
				if ($result['error_code']) {
					$score_used_count = 0;
				}
			}

			//平台余额退款
			if ($balance_pay > 0) {
				$result = D('User')->add_money($order['uid'], $balance_pay, $alias_name . '部分商品退货,增加余额,订单编号' . $order['real_orderid']);
				if($result['error_code']){
					$balance_pay = 0;
				}
			}
			//平台E卡退款返回余额
			if($ecard_pay>0){
				$result=D('User')->add_money($order['uid'],$ecard_pay,$alias_name.'部分商品退货，'.$this->config['ecard_alias_name'].'退还增加余额'.$order['real_orderid']);
				if($result['error_code']){
					$ecard_pay=0;
				}
			}

			//商家会员卡余额退款
			if ($merchant_balance > 0 || $card_give_money > 0) {				
				$card_mer_id = $order['mer_id'];

	            // 商家联盟会员卡
	            if ($order['union_mer_id']) {
	                $card_mer_id = $order['union_mer_id'];
	            }
				$result = D('Card_new')->add_user_money($card_mer_id, $order['uid'],  $merchant_balance, $card_give_money, 0, $alias_name . '部分商品退货,增加余额,订单编号' . $order['real_orderid'], $alias_name . '部分商品退货,增加赠送余额,订单编号' . $order['real_orderid']);
				if ($result['error_code']) {
					$merchant_balance = 0;
					$card_give_money = 0;
				}
			}
			//员工卡退款
			if($employee_score_pay > 0 || $employee_balance_pay > 0){
				$condition = [];
				$condition['uid'] = $order['uid'];
				$condition['user_id'] = $order['employee_card_user_id'];
				$condition['status'] = 1;
				$employeeCardUserModel = M('Employee_card_user');
				$employeeCardUser = $employeeCardUserModel->where($condition)->find();
				if($employeeCardUser){
					$shop_alias_name = c('config.shop_alias_name');
					$employeeCardLogModel = M('Employee_card_log');
					$employeeCondition = [
						'user_id'	=>	$order['employee_card_user_id']
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
							'description'	=>	$shop_alias_name . '商品退款，增加'. $employee_score_pay . '积分，订单号：' . $order['real_orderid'],
							'user_desc'		=>	$shop_alias_name . '商品退款，增加'. $employee_score_pay . '积分',
							'operate_type'	=>	'user',
							'operate_id'	=>	$order['uid'],
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
							'description'	=>	$shop_alias_name . '商品退款，增加余额'. $employee_balance_pay . '元，订单号：' . $order['real_orderid'],
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
					fdump_sql(['msg'=>'员工卡不存在',$order], 'employee_pay_refund_error');
				}
			}

			$data = array();
			$data['reply_content'] = $reply_content;
			$data['reply_time'] = time();
			$data['status'] = 1;
			$data['score_deducte'] = $score_deducte;
			$data['score_used_count'] = $score_used_count;
			$data['balance_pay'] = $balance_pay;
			$data['ecard_pay']=$ecard_pay;
			$data['merchant_balance'] = $merchant_balance;
			$data['card_give_money'] = $card_give_money;
			$data['payment_money'] = $payment_money;
			$data['employee_score_pay'] = $employee_score_pay;
			$data['employee_balance_pay'] = $employee_balance_pay;
			$data['third_refund_id'] = $third_refund_id;
            $data['refund_price'] = getFormatNumber($score_deducte+$balance_pay+$ecard_pay+$merchant_balance+$card_give_money+$payment_money+$employee_score_pay+$employee_balance_pay);

			D('Shop_refund_log')->add(array('refund_id' => $refund_id, 'status' => 1, 'dateline' => time(), 'note' => $reply_content));
			D('Shop_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save($data);
			D('Shop_order')->refundTwOrder($order_id,$refund_id);
			D('System_order')->save_order_status($order_type, $order_id, 4);

            $count = D('Shop_order_detail')->where(array('order_id' => $order_id, '_string' => 'refundNum < num'))->count();
			$rcount = D('Shop_order_refund')->where(array('order_id' => $order_id, 'status' => 0))->count();
			$saveData = array('is_apply_refund' => 0);
			$this->addUserNotice($order_id,$order['mer_id'],$order['store_id'],$order['uid'],'商家已经同意退款','您申请的退款商家已经同意，请查看订单详情');
			if (empty($count) || empty($rcount)) {
			    $not_change_deliver_status = 0;
                $refund_goods_total_all = D('Shop_order_refund')->join(' as r inner join '.C("DB_PREFIX").'shop_refund_detail as rd on rd.refund_id = r.id')
                    ->where(['r.order_id'=>$order_id,'r.status'=>1])->sum('rd.num');
                if($goods_total_all>$refund_goods_total_all){
                    $not_change_deliver_status = 1;
                }
                $saveData['status'] = 4;
                $saveData['last_time'] = time();
				D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9, 'not_change_deliver_status' => $not_change_deliver_status));
			}

            // 退还获得积分 20191219
            $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,$nowPayMoney,$refund_id);

			/******************** 商家余额减少 start *******************/
            if (!$refund['merchant_money'] && $refund['price']>0) {
                $refund['merchant_money'] = $refund['price'];
            }

            //自有支付和子商户支付商家余额不减少
            if($order['is_own'] > 0 ){
                $refund['merchant_money'] = 0;
            }

            $totalMoney = floatval(round($refund['merchant_money'], 2));
            if($order['is_pick_in_store'] == 3 && $order['freight_charge'] > 0){
            	//如果是商家快递配送，那么扣余额的时候要把运费去掉
            	//【www.flsdj.com   商城订单用户退款，但是退还的金额和支付的进入不一致】https://www.tapd.cn/47145533/bugtrace/bugs/view?bug_id=1147145533001018943
            	$totalMoney = $totalMoney - $order['freight_charge'];
            }
            if ($totalMoney<0) {
                $totalMoney=0;
            }
            fdump([__FUNCTION__ => [$order,$data]],'refund',true);
            // 商家会员卡联盟 商家余额退款减少联盟商家的余额
            if ($data['merchant_balance']&&$order['union_mer_id']) {
            	//使用了联盟商家会员卡
            	// 联盟商家
				if ($mdata = D('Merchant_money_list')->field('percent,type,money,system_take')->where(array('_string' => "type='shop' or type='mall'",'income' =>1, 'order_id' => $order['real_orderid'],'union_mer_id'=>$order['union_mer_id']))->find()) {
					// 根据商家会员卡余额比例计算应退金额
	            	$union_mer_money = $totalMoney * ($data['merchant_balance']/$refundMoney['price']);
	            	$totalMoney = $totalMoney - $union_mer_money;

                    $returnSystemTake = $returnTakePercent = 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($union_mer_money,$mdata['money'],2), $mdata['system_take'],2);
                        $returnTakePercent = $mdata['percent'];
                    }

                    D('Merchant_money_list')->use_money($order['union_mer_id'], $union_mer_money, $mdata['type'], $alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], $order['real_orderid'], $returnTakePercent, $returnSystemTake);
				}

				// 此商家
                $mdata = D('Merchant_money_list')->field('percent,money,type,system_take')->where(array('_string' => "type='shop' or type='mall'", 'income'=>1, 'order_id' => $order['real_orderid']))->find();
                //如果是扫码购，则查询一下order_id
                //订单来源（0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团，9.扫码购）
                if (in_array($order['order_from'],[6,9])  && empty($mdata)) {
                    $mdata = D('Merchant_money_list')->field('percent,money,type,system_take')->where(['type' => 'shop_offline', 'income'=>1,'order_id' => $order['order_id']])->find();
                }
				if ($mdata) {
                    $returnSystemTake = $returnTakePercent = 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                        $returnTakePercent = $mdata['percent'];
                    }
					D('Merchant_money_list')->use_money(
						$order['mer_id'], 
						$totalMoney, 
						$mdata['type'], 
						$alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], 
						$order['real_orderid'], 
						$returnTakePercent, 
						$returnSystemTake,
						[],
						0,
						$go_refund_param['refund_param']??[]
					);
				}
            }else{
                $mdata = D('Merchant_money_list')->field('percent,money, type,system_take')->where(array('_string' => "type='shop' or type='mall'",'income'=>1, 'order_id' => $order['real_orderid']))->find();
                //如果是扫码购，则查询一下order_id
                //订单来源（0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团，9.扫码购）
                if (in_array($order['order_from'],[6,9]) && empty($mdata)) {
                    $mdata = D('Merchant_money_list')->field('percent,money, type,system_take')->where(['type' => 'shop_offline', 'income'=>1,'order_id' => $order['order_id']])->find();
                }
            	if ($mdata) {
            	    if($order['packing_charge']>0){//存在打包费，则扣除打包费
                        $totalMoney=$totalMoney-$order['packing_charge'];
                        if ($totalMoney<0) {
                            $totalMoney=0;
                        }
                    }
                    $returnSystemTake = $returnTakePercent = 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                        $returnTakePercent = $mdata['percent'];
                    }
					D('Merchant_money_list')->use_money(
						$order['mer_id'], 
						$totalMoney, 
						$mdata['type'], 
						$alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], 
						$order['real_orderid'], 
						$returnTakePercent, 
						$returnSystemTake,
						[],
						0,
						$go_refund_param['refund_param']??[]
					);
				}
            }
			/******************** 商家余额减少 end *******************/

			D('Shop_order')->where(array('order_id' => $order_id))->save($saveData);
            //打印
            $printHaddle = new PrintHaddle();
            $printHaddle->printit($order_id, 'shop_order', 3);
			/*更新平台订单状态*/
			D('System_order')->save_order_info($order_type,$order_id,$saveData);

			/*对接用友erp系统 2019/02/20 */
            if (C('Config.open_yongyou_erp') == 1) {
                D('Shop_order')->erp_after_pay($order, 4);
            }

            //如果该订单有免单行为需要扣除用户所得面单金额
            $refund_ready_num = D('Shop_order_refund')->join(' as r inner join pigcms_shop_refund_detail as rd on r.id = rd.refund_id')
                ->where(['r.order_id' => $order_id, 'r.status' => 1])
                ->sum('rd.num');
            if ($order['num'] <= $refund_ready_num) {
                $flipping_event_awards_log = D('Flipping_event_awards_log')->where(['order_id'=>$order_id,'uid'=>$order['uid'],'status'=>['neq',2]])->select();
                if($flipping_event_awards_log){
                    foreach ($flipping_event_awards_log as $log_item){
                        if($log_item['awards_type']==1) {
                            D('User')->user_money($order['uid'],$log_item['money'],'抽奖免单申请退款扣除余额',24,$order_id);
                        }
                        $flip_res = D('Flipping_event_awards_log')->where(['id'=>$log_item['id']])->delete();
                        $stock_inc_res = D('Flipping_event_awards')->where(['id'=>$log_item['awards_id']])->setInc('stocks_num',1);
                        fdump_api([$order_id,$order['uid'],$flip_res,D('Flipping_event_awards_log')->getLastSql(),$stock_inc_res,D('Flipping_event_awards')->getLastSql(),$log_item],'flipping_event',1);
                    }
                }
            }
			D('Shop_order_refund')->recoverLackWeightMoney($order_id, $refund_id);
			exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));
		} else {
			D('Shop_refund_log')->add(array('refund_id' => $refund_id, 'status' => 2, 'dateline' => time(), 'note' => $reply_content));
			$refundDetailList = D('Shop_refund_detail')->field(true)->where(array('refund_id' => $refund_id))->select();
			$orderDetailDB = D('Shop_order_detail');
			foreach ($refundDetailList as $row) {
                $orderDetailDB->where(array('id' => $row['detail_id']))->setDec('refundNum',$row['num']);
			}
			D('Shop_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save(array('reply_time' => time(), 'reply_content' => $reply_content, 'status' => 2));
			D('Shop_order')->where(array('order_id' => $order_id))->save(array('is_apply_refund' => 0));
			exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));
		}
	}

	private function shop_refund_detail($now_order, $store_id)
	{
		$order_id  = $now_order['order_id'];

		$mer_store = D('Merchant_store')->where(array('mer_id' => $this->mer_id, 'store_id' => $store_id))->find();
		/*----修改别名----*/
		$alias_name=C('config.shop_alias_name');
		if($now_order['order_from']==1){
			$alias_name=C('config.mall_alias_name');
			$alias_name=$alias_name?$alias_name:L_('商城');
		}


		$zbw_return = false;
		if($this->config['zbw_key']){

			$zbw_return = true;
			$now_user = D('User')->get_user($this->user_session['uid']);
			$result = D('ZbwErp')->VipRetSheet($now_user['zbw_cardid'],$now_order['balance_pay'],$now_order['score_used_count'],$now_order['real_orderid'], L_('X1商品退款，增加余额，订单编号X2',array('X1'=>$alias_name,'X2'=>$now_order['real_orderid'])));
			if($result['result']){
				$go_refund_param['msg'] .= L_('退款成功！');
			}else{
				$this->error_tips($result['err']);
			}
		}

		//如果使用了积分 2016-1-15
		if (!$zbw_return && $now_order['score_used_count'] != 0) {
			$result = D('User')->add_score($now_order['uid'],$now_order['score_used_count'], L_('X1商品退款，X2回滚，订单编号X3',array('X1'=>$alias_name,'X2'=>C('config.score_name'),'X3'=>$now_order['real_orderid'])));
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

		//平台余额退款
		if (!$zbw_return && $now_order['balance_pay'] != '0.00') {

			$add_result = D('User')->add_money($now_order['uid'],$now_order['balance_pay'], L_('X1商品退款，增加余额，订单编号X2',array('X1'=>$alias_name,'X2'=>$now_order['real_orderid'])));

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
			$go_refund_param['msg'] .= L_(' 平台余额退款成功');
		}
		//商家会员卡余额退款
		if ($now_order['merchant_balance'] != '0.00'||$now_order['card_give_money']!='0.00') {
			//$result = D('Member_card')->add_card($now_order['uid'],$now_order['mer_id'],$now_order['merchant_balance'],'退款 ' . $mer_store['name'] . '(' . $order_id . ')  增加余额');
			//$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['merchant_balance'],$now_order['card_give_money'],0,$alias_name . '商品退款，增加余额，订单编号' . $now_order['real_orderid'], $alias_name . L_('商品退款，增加赠送余额，订单编号') . $now_order['real_orderid']);
			$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['merchant_balance'],$now_order['card_give_money'],0,L_('X1商品退款，增加余额，订单编号X2',array('X1'=>$alias_name,'X2'=>$now_order['real_orderid'])), L_('X1商品退款，增加赠送余额，订单编号X2',array('X1'=>$alias_name,'X2'=>$now_order['real_orderid'])));

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
			$sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),在X3时已被您取消并退款，欢迎再次光临！' ,array('X1'=>$mer_store['name'],'X2'=>$order_id,'X3'=>date('Y-m-d H:i:s')));
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_shop_cancel_order'] == 2 || $this->config['sms_shop_cancel_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $mer_store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = L_('顾客X1的预定订单(订单号：X2),在X3时已被客户取消并退款！',array('X1'=>$now_order['username'],'X2'=>$order_id,'X3'=>date('Y-m-d H:i:s')));
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

	//取消订单（不是预定的订单，是支付过后的订单）
	public function cancel_foodshop_order(){
		if($this->staff_session['type'] <>2){
			exit(json_encode(array('err_code' => true, 'msg' => L_('您暂无权限取消订单，请联系店长处理'))));
		}
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $now_order = D('Foodshop_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find();
        if ($now_order['status'] < 3||$now_order['status']==5) {
            exit(json_encode(array('err_code' => true, 'msg' => L_('该订单尚未支付或已经取消，无法取消'))));
        }
        if (D('Foodshop_order')->where(array('order_id' => $order_id))->save(array('cancel_time' => time(), 'status' => 5, 'cancel_reason' => L_('店员手动取消！')))) {
            // 更新库存
            $temp_goods_list = D('Foodshop_order_detail')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'],'ishide'=>0))->order('id ASC')->select();
            $goodsDB = D('Foodshop_goods');
            foreach ($temp_goods_list as $index => $new_row) {
                //更新库存
                $goodsDB->update_stock($new_row,1);

            	// 打印退菜
                D('Foodshop_order')->order_notice($order_id, array($new_row), 0, 1, 1,$this->staff_session['name']);  
            }

            /* 增加系统订单记录 2019/01/29 */
			D('Foodshop_order')->add_system_order($order_id,2);

            $this->refundMoney($order_id,$now_order['order_from']);
        } else {
            exit(json_encode(array('err_code' => true, 'msg' => L_('取消时出现错误稍后重试！'))));
        }
	}

    // 取消订单
    public function cancel()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        $now_order = D('Store_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find();
        if ($now_order['business_type'] == 'foodshop' ) {
			redirect(U('cancel_book',array('order_id'=>$now_order['business_id'])));
        }
    }

    // 餐饮取消订单
    public function cancel_book()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        $now_order = D('Foodshop_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find();
        if ($now_order['status'] > 1) {
            exit(json_encode(array('err_code' => true, 'msg' => L_('您不能取消订单了'))));
        }
        if (D('Foodshop_order')->where(array('order_id' => $order_id))->save(array('cancel_time' => time(), 'status' => 5, 'cancel_reason' => L_('店员手动取消！')))) {
            // 更新库存
            $temp_goods_list = D('Foodshop_order_temp')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'],'ishide'=>0))->order('id ASC')->select();
            $goodsDB = D('Foodshop_goods');
            foreach ($temp_goods_list as $index => $new_row) {
                //更新库存
                $goodsDB->update_stock($new_row,1);
            }

            // 修改桌台状态
            if ($now_order['table_id']) {
                $order = M('Foodshop_order')->where(array('table_id'=>$now_order['table_id'],'status'=>array('in',array(1,2))))->order('`order_id` ASC')->find();
                if (!$order) {
                    M('Foodshop_table')->where(array('id'=>$now_order['table_id']))->data(array('status'=>0))->save();
                }
            }

            /* 增加系统订单记录 2019/01/29 */
			D('Foodshop_order')->add_system_order($order_id,2);

            $this->refundMoney($order_id,$now_order['order_from']);
        } else {
            exit(json_encode(array('err_code' => true, 'msg' => L_('取消时出现错误稍后重试！'))));
        }
    }

    private function refundMoney($orderid,$order_from=0)
    {
        $now_order = M("Plat_order")->where(array('business_id' => $orderid,'business_type'=>'foodshop'))->find();

        if(empty($now_order)){
        	if ($order_from==2) { // 店员下单
            	exit(json_encode(array('err_code' => false, 'msg' => L_('取消成功'))));
        	}else{
            	exit(json_encode(array('err_code' => true, 'msg' => L_('订单不存在'))));
        	}
        }

        $now_order['order_type'] = 'plat';
        $now_order['payment_money'] = $now_order['pay_money'];
        $table = ucfirst($now_order['business_type']).'_order';
        $model =D($table);
        $business_order = $model->get_order_by_orderid($now_order['business_id']);

        if($now_order['total_money']==0){
           	exit(json_encode(array('err_code' => false, 'msg' => L_('此订单未支付定金！订单状态已修改为已取消。'))));
            // exit(json_encode(array('err_code' => true, 'msg' => '当前订单未支付定金')));
        }

        if(empty($now_order['paid'])){
            exit(json_encode(array('err_code' => false, 'msg' => L_('此订单未付款！订单状态已修改为已取消。'))));
            // exit(json_encode(array('err_code' => true, 'msg' => '当前订单还未付款！')));
        }

        // 在线支付详情
        $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>'plat','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
        if($payment_money_order['paid_money']){
            //可退金额包括用户实际支付的支付手续费
            $now_order['payment_money'] = $payment_money_order['paid_money'];
            $now_order['pay_method'] = $payment_money_order['pay_method'];
        }

        fdump_sql($now_order,'foodshop_refund');
        $refund_status = false;
        $refund_num = 0; // 退款种类数量
        if ($now_order['pay_money'] != '0.00') {
            if($now_order['is_own']){
                $pay_method = array();
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$business_order['mer_id']))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }
                $now_merchant = D('Merchant')->get_info($business_order['mer_id']);
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
            } else {
            	if ($business_order['is_foodshop_new']) {
                    // 堂扫
                    $pay_method = D('Merchant_store')->get_pay_method($business_order['store_id']);
                }else{

                    $pay_method = D('Config')->get_pay_method(0,0,1); 
                }
            }

            if(empty($pay_method)){
            	exit(json_encode(array('err_code' => true, 'msg' => L_('系统管理员没开启任一一种支付方式！'))));
            }
            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                $pay_class_name = 'Hqpay';
                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
            }
            if(empty($pay_method[$now_order['pay_type']])){
            	exit(json_encode(array('err_code' => true, 'msg' => L_('您选择的支付方式不存在，请更新支付方式！'))));
            }
 			if($payment_money_order && ($payment_money_order['pay_method'] == 'superpaymini' || $payment_money_order['pay_method'] == 'superpayh5' || $payment_money_order['pay_method'] == 'omipay' || $payment_money_order['pay_method'] == 'royalpay' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'nong')){
                if($payment_money_order['pay_method'] == 'superpaymini' || $payment_money_order['pay_method'] == 'superpayh5'){
                    $pay_method_type = 'superpay';
                }
                if($payment_money_order['pay_method'] == 'omipay'){
                    $now_order['omipay_orderno'] = $payment_money_order['third_plat_data'];
                    $pay_method_type = 'omipay';
                }
                if($payment_money_order['pay_method'] == 'royalpay'){
                    $pay_method_type = 'royalpay';
                }
                if($payment_money_order['pay_method'] == 'paylinx'){
                    $pay_method_type = 'paylinx';
                }
                if($payment_money_order['pay_method'] == 'nong'){
                    $pay_method_type = 'nong';
                }
                import('@.ORG.LowFeePay');
                $lowfeepay = new LowFeePay($pay_method_type);
                $now_order['order_type'] = 'plat';
                $now_order['orderNo'] = $payment_money_order['orderNo'];
                $now_order = array_merge($now_order,$pay_method[$now_order['pay_type']]);
                $go_refund_param= $lowfeepay->refund($now_order);
        		fdump_sql($go_refund_param,'foodshop_refund');
            } else {
	            $pay_class_name != 'Hqpay' && $pay_class_name = ucfirst($now_order['pay_type']);
	            if($pay_class_name=='Alipay' && $now_order['is_mobile_pay'] == 2 && $this->config['new_pay_alipay_app_public_key']!=''&& $this->config['new_pay_alipay_app_appid']!='' && $this->config['new_pay_alipay_app_private_key']!=''){
	                $pay_class_name = 'AlipayApp';
	                $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
	                $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
	                $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
	            }
	            $import_result = import('@.ORG.pay.'.$pay_class_name);
	            if(empty($import_result)){
	            	exit(json_encode(array('err_code' => true, 'msg' => L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'))));
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
	                $is_mobile = 3;
	            }else{
	                $is_mobile = 1;
	            }
	            $now_order['payment_money'] = $now_order['pay_money'];
	            $pay_class = new $pay_class_name($now_order,$now_order['pay_money'],$now_order['pay_type'],$pay_method[$now_order['pay_type']]['config'],$this->user_session,$is_mobile);
	            $go_refund_param = $pay_class->refund();
	        }

            $now_order['order_id'] = $orderid;
            $data_plat_order['order_id'] = $orderid;
            $data_plat_order['refund_detail'] = serialize($go_refund_param['refund_param']);
            
            if(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok'){
                $refund_status = true;
            }else{
            	exit(json_encode(array('err_code' => true, 'msg' => $go_refund_param['msg'])));
            }
            D('Plat_order')->data($data_plat_order)->save();
            $refund_num++;

        }elseif($now_order['pay_type'] == 'offline') {
            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize(array('refund_time'=>time()));
            D('Plat_order')->data($data_plat_order)->save();
        }

        //如果使用了积分 2016-1-15
        if ($now_order['system_score']>0) {

            $order_name=$now_order['order_name'];
            $result = D('User')->add_score($now_order['uid'],$now_order['system_score'],L_('退款 ').$order_name.$this->config['score_name'].L_('回滚'));
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
            	exit(json_encode(array('err_code' => true, 'msg' => $result['msg'])));
            }else{
                $refund_status = true;
            }
            $go_refund_param['msg'] = $result['msg'];
            $refund_num++;
        }

        //平台余额退款
        if($now_order['system_balance'] != '0.00'){
            $result = D('User')->add_money($now_order['uid'],$now_order['system_balance'],L_('退款 ').$now_order['order_name'].L_(' 增加余额'));
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
            	exit(json_encode(array('err_code' => true, 'msg' => $result['msg'])));
            }else{
                $refund_status = true;
            }
            $go_refund_param['msg'] = L_('退款成功');
            $refund_num++;
        }

        //商家会员卡余额退款
        if($now_order['merchant_balance_pay'] != '0.00'||$now_order['merchant_balance_give']!='0.00'){
            $card_mer_id = $business_order['mer_id'];

            // 商家联盟会员卡
            if ($now_order['union_mer_id']) {
                $card_mer_id = $now_order['union_mer_id'];
            }
            $result = D('Card_new')->add_user_money($card_mer_id,$now_order['uid'],$now_order['merchant_balance_pay'],$now_order['merchant_balance_give'],0,L_('退款 ').$now_order['order_name'].L_(' 增加余额'),L_('退款 ').$now_order['order_name'].L_(' 增加赠送余额'));
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
            	exit(json_encode(array('err_code' => true, 'msg' => $result['msg'])));
            }else{
                $refund_status = true;
            }
            $go_refund_param['msg'] = L_('商家会员卡余额退款成功');
            $refund_num++;
        }

        if ($refund_num>1) {
            $go_refund_param['msg'] = L_('退款成功');
        }

        //调用 打印 发短信 发模板消息 回滚库存 回调跳转地址
        $refund_result = $model->afert_refund($business_order);
        if(!$refund_status['error_code']){
            // 给用户推送模板消息
            $now_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
            if ($now_user['openid']) {
                $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=foodshop_order_list';
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('OPENTM405627933', array(
                    'href' => $href,
                    'wecha_id' => $now_user['openid'],
                    'first' => L_('您的X1订单已取消，系统已自动退款！',C('config.meal_alias_name')),
                    'keyword1' => $business_order['real_orderid'],
                    'keyword2' => $business_order['price'].C('config.Currency_txt'),
                    'keyword3'=>date('Y年m月d日 H:i'),
                    'remark' => L_('点击查看详情！')
                ));
            }
            if($now_order['pay_type']=='offline'){
            	exit(json_encode(array('err_code' => false, 'msg' => L_('此订单是线下支付！订单状态已修改为已退款。'))));
            }else{
            	exit(json_encode(array('err_code' => false, 'msg' => $go_refund_param['msg'])));
            }
        }else{
            exit(json_encode(array('err_code' => true, 'msg' => $result['msg'])));
        }

    }

	public function hotel_refund(){
		$this->check_group();
		$now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'],$_POST['order_id'],false);
		$temp_order = $now_order;
		$now_order['refund_detail'] = unserialize($now_order['refund_detail']);
		if(empty($now_order)){
            $this->error(L_('此订单不存在！'));
		}

		$trade_hotel_info = D('Trade_hotel_category')->format_order_trade_info($now_order['trade_info']);
		$refund = $_POST['refund_num'];

		$price_list = $trade_hotel_info['price_list'];
		$price_list_all = array_sum($price_list);

		$hotel_num_all = $trade_hotel_info['num'];    // 房间数量
		$all_hotel_num = count($price_list)*$hotel_num_all;  //整个酒店订单数量 房间数*天数
        $old_refund_num = 0;   //之前退款数量
        if($now_order['refund_detail']){
            $old_refund_num += array_sum(array_column($now_order['refund_detail']['detail'],'num'));
        }

		$hotel_num_all = $trade_hotel_info['num'];
		$refund_per_money = ($now_order['score_deducte']+$now_order['merchant_balance']+$now_order['card_give_money']+$now_order['balance_pay']+$now_order['payment_money'])/$hotel_num_all;
		$refund_money = 0;
		$refund_num = 0;

		foreach ($refund as $key=>$v) {
			$refund_money_per = $price_list[$key]*$refund_per_money/$price_list_all;
			$refund_money_tmp = $v*$refund_money_per;
			$refund_money += $refund_money_tmp;
			if($now_order['refund_detail'] ['detail'][$key]['num']>0 && $now_order['refund_detail'] ['detail'][$key]['num']+$v>$hotel_num_all){
				$this->error(L_('退款房间超出订购房间数'));
			}
			$tmp_detail[$key] = array('num'=>$v,'money'=>$refund_money_tmp);
			$refund_num+=$v;
		}



		$refund_status = 3;
		if($old_refund_num+$refund_num<$all_hotel_num){
			$refund_status = 6;
		}

		if($now_order['refund_detail']){
			$old_refund = $now_order['refund_detail'];



			if($old_refund['score']>0){
				$now_order['score_used_count']-=$old_refund['score'];
				$now_order['score_used_count'] =intval($now_order['score_used_count']);
			}

			if($old_refund['card_give_money']>0){
				$now_order['card_give_money']-=$old_refund['card_give_money'];
				$now_order['card_give_money'] = sprintf("%.2f",$now_order['card_give_money']);
			}

			if($old_refund['merchant_balance']>0){
				$now_order['merchant_balance']-=$old_refund['merchant_balance'];
				$now_order['merchant_balance'] = sprintf("%.2f",$now_order['merchant_balance']);
			}

			if($old_refund['balance_pay']>0){
				$now_order['balance_pay']-=$old_refund['balance_pay'];
				$now_order['balance_pay'] = sprintf("%.2f",$now_order['balance_pay']);
			}

			if($old_refund['payment_money']>0){
				$now_order['payment_money']-=$old_refund['payment_money'];
				$now_order['payment_money'] = sprintf("%.2f",$now_order['payment_money']);
			}
		}

		$refund_detail['detail'] = $tmp_detail;

		$go_refund_param['msg'] = '';
		//退积分
		if ($refund_money>0 && $now_order['score_used_count']>0 ) {
			if($refund_money>=$now_order['score_deducte']){
				$tmp_refund_money = $now_order['score_deducte'];
				$refund_score = $now_order['score_used_count'];
			}else{
				$tmp_refund_money = $refund_money;
				$per_score_deducte = $temp_order['score_deducte']/$temp_order['score_used_count'];
				$refund_score  = round($refund_money/$per_score_deducte,2);

				if($refund_score>$now_order['score_used_count']){
					$refund_score = $now_order['score_used_count'];
					$tmp_refund_money  = $refund_score*$per_score_deducte;
				}
			}

			$refund_detail['score'] = $refund_score;


			$result = D('User')->add_score($now_order['uid'],$refund_score,L_('退款 ').$now_order['order_name'].' '.C('config.score_name').L_('回滚'));
			$refund_detail['refund_time'] =time();
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_group_order['order_id'] = $now_order['order_id'];
			$data_group_order['refund_detail'] = serialize($refund_detail);
			$result['error_code'] || $data_group_order['status'] = $refund_status;
			D('Group_order')->data($data_group_order)->save();
			if ($result['error_code']) {
				$data_group_order['order_id'] = $now_order['order_id'];

				$refund_detail['msg']  =  L_('退款失败,退回X1失败，原因：X2',array('X1'=>C('config.score_name'),'X2'=>$result['msg']));
				$data_group_order['refund_detail'] = serialize($refund_detail);
				D('Group_order')->data($data_group_order)->save();
				$this->error($data_group_order['refund_detail']);
			}
			$go_refund_param['msg'] .= $result['msg'];
			$refund_money = $refund_money-$tmp_refund_money;

		}

		//商家会员卡余额退款

		if($refund_money>0 && $now_order['card_give_money'] >0 ){
			if($refund_money>=$now_order['card_give_money']){
				$tmp_refund_money = $now_order['card_give_money'];
			}else{
				$tmp_refund_money = $refund_money;
			}
			$tmp_refund_money = round($tmp_refund_money,2);
			$refund_detail['card_give_money'] = $tmp_refund_money;
			$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],0,$tmp_refund_money,0,L_('退款 ').$now_order['order_name'].' 增加余额','退款 '.$now_order['order_name'].' 增加赠送余额');
			$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],0,$tmp_refund_money,0,L_('退款 X1 增加余额',$now_order['order_name']),L_('退款 X1 增加赠送余额',$now_order['order_name']));
			$refund_detail['refund_time'] =time();
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_group_order['order_id'] = $now_order['order_id'];
			$refund_detail['refund_time'] =time();
			$data_group_order['refund_detail'] = serialize($refund_detail);
			$result['error_code'] || $data_group_order['status'] = $refund_status;
			D('Group_order')->data($data_group_order)->save();
			if ($result['error_code']) {
				$data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;

				$refund_detail['msg']  =  L_('退款失败，退回商家会员卡赠送余额失败，原因：').$result['msg'];
				$data_group_order['refund_detail'] = serialize($refund_detail);
				D('Group_order')->data($data_group_order)->save();
				$this->error($data_group_order['refund_detail']);
			}
			$go_refund_param['msg'] = $result['msg'];
			$refund_money = $refund_money-$tmp_refund_money;
		}

		if($refund_money>0 && $now_order['merchant_balance']>0){

			if($refund_money>=$now_order['merchant_balance']){
				$tmp_refund_money = $now_order['merchant_balance'];
			}else{
				$tmp_refund_money = $refund_money;
			}
			$tmp_refund_money = round($tmp_refund_money,2);
			$refund_detail['merchant_balance'] = $tmp_refund_money;
			$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$tmp_refund_money,0,0,L_('退款 X1 增加余额',$now_order['order_name']),L_('退款 X1 增加赠送余额',$now_order['order_name']));
			$refund_detail['refund_time'] =time();
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_group_order['order_id'] = $now_order['order_id'];
			$data_group_order['refund_detail'] = serialize($refund_detail);
			$result['error_code'] || $data_group_order['status'] = $refund_status;
			D('Group_order')->data($data_group_order)->save();
			if ($result['error_code']) {
				$data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;
				$refund_detail['msg']  =  L_('退款失败，退回商家会员卡余额失败，原因：').$result['msg'];
				$data_group_order['refund_detail'] = serialize($refund_detail);
				D('Group_order')->data($data_group_order)->save();
				$this->error($data_group_order['refund_detail']);
			}
			$go_refund_param['msg'] = $result['msg'];
			$refund_money = $refund_money-$tmp_refund_money;
		}

		//平台余额退款
		if($refund_money>0 && $now_order['balance_pay']>0 ){

			if($refund_money>=$now_order['balance_pay']){
				$tmp_refund_money = $now_order['balance_pay'];
			}else{
				$tmp_refund_money = $refund_money;
			}
			$tmp_refund_money = round($tmp_refund_money,2);
			$refund_detail['balance_pay'] = $tmp_refund_money;

			$add_result = D('User')->add_money($now_order['uid'],$tmp_refund_money,L_('退款 X1 增加余额',$now_order['order_name']));
			$refund_detail['refund_time'] =  time();
			if($add_result['error_code']){
				$param['err_msg'] = $add_result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_group_order['order_id'] = $now_order['order_id'];
			$refund_detail['refund_time'] =time();
			$data_group_order['refund_detail'] = serialize($refund_detail);

			$add_result['error_code'] || $data_group_order['status'] = $refund_status;
			D('Group_order')->data($data_group_order)->save();
			if ($add_result['error_code']) {
				$data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;
				$refund_detail['msg']  =  L_('退款失败，退回余额失败，原因：').$add_result['msg'];
				$data_group_order['refund_detail'] = serialize($refund_detail);

				D('Group_order')->data($data_group_order)->save();
				$this->error($data_group_order['refund_detail']);
			}
			$go_refund_param['msg'] = L_('平台余额退款成功');
			$refund_money = $refund_money-$tmp_refund_money;
		}

		if($refund_money>0 && $now_order['payment_money'] >0 ){
			$refund_money = round($refund_money,2);
			if($refund_money>=$now_order['payment_money']){
				$tmp_refund_money = $now_order['payment_money'];
			}else{
				$tmp_refund_money = $refund_money;
			}
			$refund_detail['payment_money'] = $tmp_refund_money;

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
				if ($now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
					$pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_weixin_appid');
					$pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_weixin_appsecret');
					$pay_method['weixin']['config']['pay_weixin_mchid'] =C('config.pay_weixin_sp_mchid');
					$pay_method['weixin']['config']['pay_weixin_key'] = C('config.pay_weixin_sp_key');
					$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
					$pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
					$pay_method['weixin']['config']['pay_weixin_client_key'] = C('config.pay_weixin_sp_client_key');
					$pay_method['weixin']['config']['is_own'] = 2 ;
				}
			}else{
				$pay_method = D('Config')->get_pay_method(0, 0, $now_order['is_mobile_pay'] == 1 ? true : false, $now_order['is_mobile_pay'] == 2 ? true : false);
			}

			if(empty($pay_method)){
				$data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;

				$refund_detail['msg']  = L_('退款失败，原因：未找到支付方式');
				$data_group_order['refund_detail'] = serialize($refund_detail);
				D('Group_order')->data($data_group_order)->save();
				$this->error($data_group_order['refund_detail']);

			}
			$pay_class_name = ucfirst($now_order['pay_type']);
            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                $pay_class_name = 'Hqpay';
                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
            }
			if(empty($pay_method[$now_order['pay_type']])){
				$data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;

				$refund_detail['msg']  = L_('退款失败，原因：支付方式不存在');
				$data_group_order['refund_detail'] = serialize($refund_detail);
				D('Group_order')->data($data_group_order)->save();
				$this->error($data_group_order['refund_detail']);

			}

			$import_result = import('@.ORG.pay.'.$pay_class_name);
			if(empty($import_result)){
				$data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;
				$refund_detail['msg']  =L_('退款失败，原因：支付方式不存在');
				$data_group_order['refund_detail'] = serialize($refund_detail);
				D('Group_order')->data($data_group_order)->save();
				$this->error($data_group_order['refund_detail']);

			}
			$now_order['order_type'] = 'group';

			$tmp_order_id = $now_order['order_id'];
			if(!empty($now_order['orderid'])){
				$now_order['order_id']=$now_order['orderid'];
			}
			$now_order['payment_money'] = $temp_order['payment_money'];
			$pay_class = new $pay_class_name($now_order,$tmp_refund_money,$now_order['pay_type'],$pay_method[$now_order['pay_type']]['config'],'',1);
			$go_refund_param = $pay_class->refund();

			$refund_detail['refund_time'] =time();
			$refund_detail['payment_refund'] = $go_refund_param['refund_param'];
			$now_order['order_id'] = $tmp_order_id;
			$data_group_order['order_id'] = $now_order['order_id'];
			$data_group_order['refund_detail'] = serialize($go_refund_param['refund_param']);
			if(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok'){
				$data_group_order['status'] = $refund_status;
			}
			D('Group_order')->data($data_group_order)->save();

		}

		$trade_info = unserialize($now_order['trade_info']);
		switch($trade_info['type']){
			case 'hotel':
				$where['mer_id'] = $now_order['mer_id'];
				$where['cat_id'] = $trade_info['cat_id'];

				foreach ($refund_detail['detail'] as $k=>$num) {
					$where['stock_day']=$k;
					M('Trade_hotel_stock')->where($where)->setInc('stock',$num['num']);
				}
				break;
		}


		foreach ($price_list as $key=>$v) {
			if($old_refund['detail'][$key]['num']>0){
				if($refund_detail['detail'][$key]){
					$refund_detail['detail'][$key]['num'] +=$old_refund['detail'][$key]['num'];
					$refund_detail['detail'][$key]['money'] +=$old_refund['detail'][$key]['money'];
				}else{
					$refund_detail['detail'][$key] = $old_refund['detail'][$key];
				}
			}
		}

		if($old_refund['score']>0){
			$refund_detail['score']+=$old_refund['score'];
		}

		if($old_refund['card_give_money']>0){
			$refund_detail['card_give_money']+=$old_refund['card_give_money'];
		}

		if($old_refund['merchant_balance']>0){
			$refund_detail['merchant_balance']+=$old_refund['merchant_balance'];
		}

		if($old_refund['balance_pay']>0){
			$refund_detail['balance_pay']+=$old_refund['balance_pay'];
		}

		if($old_refund['payment_money']>0){
			$refund_detail['payment_money']+=$old_refund['payment_money'];
		}

		D('Group_order')->where(array('order_id'=>$now_order['order_id']))->save(array('refund_detail'=>serialize($refund_detail)));
		//系统订单状态修改
        if($refund_status==3){
            D('System_order')->where(array('order_id'=>$now_order['order_id'],'type'=>'group'))->save(array('status'=>$refund_status,'system_status'=>4));
        }

		if(in_array($now_order['status'],array(1,2,3)) && $merchant_money =  M('Merchant_money_list')->where(array('type'=>'group','income' => 1,'order_id'=>$now_order['real_orderid']))->find()){

			$num = $trade_hotel_info['num']*count($price_list);
			$refund_money_mer = $merchant_money['money']/$num*$refund_num;
			$desc  = L_("店员手动退房扣除商家余额");
            $returnSystemTake = $returnTakePercent = 0;
            if($merchant_money['system_take'] > 0){
                $returnSystemTake = bcmul(bcdiv($refund_money_mer,$merchant_money['money'],2), $merchant_money['system_take'],2);
                $returnTakePercent = $merchant_money['percent'];
            }
			D('Merchant_money_list')->use_money(
				$now_order['mer_id'],
				$refund_money_mer,
				'group',
				$desc,
				$now_order['real_orderid'], 
				$returnTakePercent, 
				$returnSystemTake,
				[],
				0,
				$go_refund_param['refund_param']??[]
			);
		}
		$this->success(L_('退款成功'));

	}

	public function balance_verify_phone(){
		$phone = $_POST['phone'];
		if(empty($phone)){
			$this->error(L_('手机号码不能为空'));
		}
		if($res=D('User')->get_user($phone,'phone')){
			$this->success(array('money'=>$res['now_money']));
		}else{
			$this->error(L_('检测到改手机号暂未注册为平台用户'));
		}
	}

	public function sendCode(){

		$code = mt_rand(1000, 9999);
		$text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',$code);

		if($_POST['phone']){
			$phone = $_POST['phone'];
		}
		$phone = phone_format($_POST['phone_country_type'], $phone);

		$columns = array();
		$columns['phone'] = $phone;
		$columns['mer_id'] = $this->store['mer_id'];
		$columns['extra'] = $code;
		$columns['type'] = 5;
		$columns['status'] = 0;
		$columns['send_time'] = time();
		$columns['expire_time'] = $columns['send_time'] + 1200;
		$result = M("Merchant_sms_record")->add($columns);
		if (!$result){
			$this->error(L_('短信记录保存失败'));
		}
		$sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'pay_verify');
		$_POST['phone_country_type'] && $sms_data['nationCode']  = $_POST['phone_country_type'];
		$return = Sms::sendSms($sms_data);

		if ($return != 0) {
			$this->error(self::ConverSmsCode($return));
		}
		$this->success(0, $return);
	}

	// 选择会员卡
	public  function select_card(){
		$key = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
		if(strlen($key) == 11 && $user = D('User')->field(true)->where(array('phone' => $key))->find()) {
			$card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id']))->find();
		}else{
			$condition_user['mer_id'] = $this->store['mer_id'];
			$condition_user['_string'] = "id={$key} OR wx_card_code={$key} OR physical_id = {$key}";
			$card = D('Card_userlist')->field(true)->where($condition_user)->find();

			if(empty($card) &&($user = D('User')->field(true)->where(array('truename' =>  $key))->find() || $user = D('User')->field(true)->where(array('nickname' =>  array('like','%'.$key.'%')))->find()) ){
				$card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id']))->find();
			}
		}

		$this->assign('list',$card);
		$this->display();
	}

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

    // 语音获取
    private function voice_baidu()
    {
        static $return;

        if (empty($return)) {
            $voice_baidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            import('ORG.Net.Http');
            $return = Http::curlGet($voice_baidu);
        }
        return $return;
    }

    //快递配送和自提用户支付之后店员接单操作
    public function take_order(){
    	$id = intval($_POST['id']);
    	$db = M('Shop_order');
    	$info = $db->where(['order_id'=>$id])->find();
    	if(empty($info)){
    		exit(json_encode(['status'=>0,'info'=>'订单不存在']));
    	}
    	if($info['order_status']<>0 ||($info['status']<>0 && $info['status']<>7)|| $info['paid']<>1){
    		exit(json_encode(['status'=>0,'info'=>'订单状态不正确，不可操作接单']));
    	}
        if($info['store_id']!=$this->staff_session['store_id']&&$info['is_pick_in_store']==2&&!$this->checkPickRule($info['pick_id'])){
            exit(json_encode(array('status'=>0,'info'=>L_('无权限操作！'))));
        }
    	$r = $db->where(array('order_id' => $info['order_id']))->save(array('status'=>1,'order_status' => 1, 'last_time' => time()));
        
        $changeAddressStatus = M('System_order')
            ->where(['type' => 'shop', 'order_id' => $info['order_id']])
            ->field('change_address_status')
            ->find();
        
        $addressSetting = M('Merchant_address_edit_setting')->where(['merchant_id' => $info['mer_id']])->find();
            
        if(
            $changeAddressStatus['change_address_status'] == 1 && //1 等待商家审核
            $info['is_pick_in_store'] == 1 && //商家配送
            !empty($addressSetting) &&
            $addressSetting['order_status'] == 1 //1:店铺接单前
        ){
            M('System_order')
                ->where(['type' => 'shop', 'order_id' => $info['order_id']])
                ->save(['change_address_status' => 3]);//3 修改地址自动撤销
        }
        
    	D('System_order')->save_order_status($info['order_from'] == 1 ? 'mall' : 'shop', $id, 1);
    	if($r){
    		$log_date = [
    			'order_id' =>  $info['order_id'], 
    			'status'   =>  2, 
    			'name'     =>  $this->staff_session['username'], 
    			'phone'    =>  $this->staff_session['tel'], 
    			'note'     =>  L_('订单已确认，商家备货中')
    		];
    		D('Shop_order_log')->add_log($log_date);
    		if($info['is_pick_in_store']==2){//自提的订单
                $log_date = [
                    'order_id' =>  $info['order_id'],
                    'status'   =>  11,
                    'name'     =>  $this->staff_session['username'],
                    'phone'    =>  $this->staff_session['tel'],
                    'note'     =>  '',
                ];
                D('Shop_order_log')->add_log($log_date);
            }
            if($info['is_pick_in_store']==0) {//平台配送的订单,分配订单到配送表
                D('Deliver_supply')->auto_set_by_orderid($info['order_id']);
            }
            $this->addUserNotice($info['order_id'],$info['mer_id'],$info['store_id'],$info['uid'],'外卖商家已接单','商家已接单，商品将尽快为你送达');
    		exit(json_encode(['status'=>1,'info'=>'接单成功']));
    	}else{
    		exit(json_encode(['status'=>1,'info'=>'接单失败']));
    	}	
    	
    }

	//店员同意取消订单
	public function agree_cancle_order()
	{

		$order_id = intval($_POST['id']);
		$now_order = D("Shop_order")->get_order_detail(array('order_id' => $order_id));
		$refund_rate = D("Shop_order")->get_period_refund_rate($now_order['order_id']);//适配周期购期数退款

		if(empty($now_order)){
			exit(json_encode(['status'=>0,'info'=>'订单不存在']));
		}
		if($this->config['open_allinyun']==1){
			import('@.ORG.AccountDeposit.AccountDeposit');
			$deposit = new AccountDeposit('Allinyun');
			$allyun = $deposit->getDeposit();

			$allinyun_user = D('Deposit')->allinyun_user($now_order['uid']);

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
					//$this->success_tips(L_('退款成功'), 'javascript:history.go(-2);');
					exit(json_encode(['status'=>1,'info'=>'退款成功']));
				}else{
					//$this->error_tips(L_('退款失败') . '，' . $res['message']);
					exit(json_encode(['status'=>0,'info'=>$res['message']]));
				}
			}
		}

		$now_merchant = D('Merchant')->get_info($now_order['mer_id']);

		$store_id = $now_order['store_id'];
		$this->mer_id = $now_order['mer_id'];
		if (!($now_order['paid'] == 1 && ($now_order['status'] == 11))) {
			//$this->error_tips(L_('当前订单店员正在处理中，不能退款或取消'));
			exit(json_encode(['status'=>0,'info'=>'当前订单店员正在处理中，不能退款或取消']));
		}
		if (empty($now_order['paid'])) {
			exit(json_encode(['status'=>0,'info'=>'当前订单未付款']));
		}

		$mer_store = D('Merchant_store')->where(array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']))->find();
		$my_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();

		//线下支付退款
		$data_shop_order['cancel_type'] = 5;//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户（），6：配送员, 7:超时取消）
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
					//$this->success_tips(L_('您使用的是线下支付！订单状态已修改为已退款。'),U('Shop/status',array('order_id' => $now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
					exit(json_encode(['status'=>1,'info'=>'您使用的是线下支付！订单状态已修改为已退款。']));
				}
			} else {
				//$this->error_tips(L_('取消订单失败！请重试。'));
				exit(json_encode(['status'=>0,'info'=>'取消订单失败！请重试。']));
			}
		}
		else {
			if ($now_order['payment_money'] != '0.00') {

				$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>['in',['shop','mall']],'pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
				if($now_order['payment_money'] == $payment_money_order['payment_money']){
					//如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
					$now_order['payment_money'] = $payment_money_order['paid_money'];
				}

				if(C("config.is_refund_balance") == 1 && $payment_money_order){
					//2019年6月17日，新增需求， 退款到余额 add by lumin
					$alias_name=C('config.shop_alias_name');
					if($now_order['order_from']==1){
						$alias_name=C('config.mall_alias_name');
					}
					$result = D('User')->add_money($now_order['uid'],$payment_money_order['paid_money']*$refund_rate, L_("X1商品退款，增加余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
					$go_refund_param = array('refund_time' => time());
					if($result['error_code']){
						$go_refund_param['err_msg'] = $result['msg'];
					} else {
						$go_refund_param['refund_id'] = $now_order['order_id'];
						$go_refund_param['type'] = 'ok';
					}
				}
				else{
					if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'xiaoma')){
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
						import('@.ORG.LowFeePay');
						$lowfeepay = new LowFeePay($pay_method);
						$now_order['order_type'] = 'shop';
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
                            }

						}
						$go_refund_param= $lowfeepay->refund($now_order);
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
					}else {
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
						} else {
							$is_app = false;
							if ((stripos($_SERVER['HTTP_USER_AGENT'], 'Android') || stripos($_SERVER['HTTP_USER_AGENT'], 'IOS')) && $now_order['pay_type'] == 'alipay') $is_app = true;
							$pay_method = D('Config')->get_pay_method(0, 0, 1, $is_app);
							if ($now_order['is_mobile_pay'] == 2) {
								$pay_method['weixin'] = $pay_method['weixinapp'];
							}
						}

						if (empty($pay_method)) {
							//$this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
							exit(json_encode(['status'=>0,'info'=>'系统管理员没开启任一一种支付方式！']));
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
							//$this->error_tips(L_('您选择的支付方式不存在，请更新支付方式！'));
							exit(json_encode(['status'=>0,'info'=>'您选择的支付方式不存在，请更新支付方式！']));
						}

						$import_result = import('@.ORG.pay.' . $pay_class_name);
						if (empty($import_result)) {
							//$this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
							exit(json_encode(['status'=>0,'info'=>'系统管理员暂未开启该支付方式，请更换其他的支付方式']));
						}
						D('Shop_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_refund' => 1));
						$now_order['order_type'] = 'shop';
						if($now_order['order_from'] ==1){
							//商城
							$now_order['order_type'] = 'mall';
						}
						$now_order['order_id'] = $now_order['orderid'];
						if ($now_order['is_mobile_pay'] == 3) {
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

						$pay_class = new $pay_class_name($now_order, $now_order['payment_money']*$refund_rate, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, 1);
						$go_refund_param = $pay_class->refund();
					}
				}
				$now_order['order_id'] = $order_id;
				$data_shop_order['order_id'] = $order_id;
				$data_shop_order['refund_detail'] = serialize($go_refund_param['refund_param']);
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


			$return = $this->shop_refund_detail($now_order, $store_id,$refund_rate);
			if ($return['error_code']) {
				//$this->error_tips($return['msg']);
				exit(json_encode(['status'=>0,'info'=>$return['msg']]));
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
							'cancelReason'=>'店员同意顾客取消订单',
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

           // $desc="店员同意用户取消订单";
            //D('Merchant_money_list')->use_money($now_order['mer_id'],$now_order['price'],"mall",$desc,$now_order['real_orderid']);

			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10,'note'=>'店员同意用户取消订单'));
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9,'note'=>'店员同意用户取消订单'));
			exit(json_encode(['status'=>0,'info'=>$go_refund_param['msg']]));
		}
	}


	//

    public function goods_export()
    {
        $param = $_POST;
        $param['type'] = 'goods_to_be_delivered';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        $param['store_id'] = $this->store['store_id'];
        $param['shop_type'] = $_POST['shop_type'];
        $param['lang'] = C('config.system_lang');
        C('config.tmp_system_lang',$param['lang']);
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
        die;
    }

    /**
     * 生成二维码图片
     */
    public function get_qrcode()
    {
        $qrCon = C('config.site_url')."/wap.php?g=Wap&c=My_card&a=merchant_prepay&mer_id=".$_GET['mer_id']."&source=".$_GET['source'];
        header('Content-Type: image/png');
        ob_clean();
        import('@.ORG.phpqrcode');
        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)), false, 0, 8, 1, false);
    }

    /**
     * 接单时未生成配送单，重新生成
     */
    public function check_shop_again()
    {
        $database = D('Shop_order');
        $database->startTrans();
        $order_id = $condition['order_id'] = intval($_POST['order_id']);
        $condition['store_id'] = $this->store['store_id'];
        $order = $database->field(true)->where($condition)->find();
        if(empty($order)){
            $this->error(L_('订单不存在！'));
            exit;
        }
        if ($order['status'] > 1) {
            $this->error(L_('只有已接单状态可以重新生成配送单'));
            exit;
        }
        if($order['is_weight']==1){
            $order_detail = M('Shop_order_detail')->where(array('order_id'=>$order['order_id']))->select();
            $flag = false;
            foreach($order_detail as $o_d){
                if($o_d['sell_type']==2 && $o_d['real_weight']==0){
                    $flag = true;
                }
            }
            if($flag){
                $this->error(L_('该订单含称重商品，请录入重量后重新生成配送单！'));
            }
        }
        if ($order['status'] == 4 || $order['status'] == 5) {
            $this->error(L_('订单已取消，不能重新生成配送单！'));
            exit;
        }
        if ($order['is_refund']) {
            $this->error(L_('用户正在退款中~！'));
            exit;
        }
        if ($order['paid'] == 0) {
            $this->error(L_('订单未支付，不能重新生成配送单！'));
            exit;
        }
        if($order['platform'] != 0){
            $this->error(L_('订单非平台配送，不能重新生成配送单！'));
            exit;
        }

        //查询配送记录
        $deleverSupply = D('Deliver_supply')->where(['real_orderid'=>$order['real_orderid']])->field('supply_id')->find();
        if(!$deleverSupply){
            //添加配送记录
            $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
            if ($result['error_code']) {
                $database->rollback();
                $this->error($result['msg']);
                exit;
            }
        }
        //查询是否生成了接单记录
        $log = D('Shop_order_log')->where(['order_id'=>$_POST['order_id'],['status'=>2]])->field('id')->find();
        if(!$log){
            $phones = explode(' ', $this->store['phone']);
            //添加日志
            $addLog = D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
        }
        $database->commit();
        $this->success(L_('已接单'));
    }

    /**
     * 店员-外卖线下零售-商家实体卡支付
     * @param $order_id
     * @param $now_order
     * @param $table_name
     * @param array $data
     */
    public function arrival_mer_physical_card_pay($order_id, $mer_physical_card, $table_name, $data = array())
    {
        $data['paid'] = 1;
        $data['status'] = 2;
        $data['order_status'] = 5;
        $data['pay_time'] = $_SERVER['REQUEST_TIME'];
        $data['use_time'] = time();
        $data['pay_type'] = 'mer_physical_card';
        $data['third_id'] = $order_id;
        $data['mer_physical_card_no'] = $mer_physical_card['card_no'];
        D()->startTrans();
        try {
            if(M($table_name)->where(array('order_id' => $order_id))->data($data)->save()){
                $now_order = M($table_name)->where(array('order_id' => $order_id))->find();
                $this->shop_notice($now_order, true);
                $res = D('Shop_order')->shop_score_get($now_order);
                //更新
                $re = D('Merchant_physical_card')->addPhysicalCardOrder($now_order['price'],$mer_physical_card['id'],'shop_offline',$now_order['mer_id'],$now_order['order_id'],$now_order['store_id']);
                fdump_api($re,'merchant_physical_card');
                D()->commit();
                $this->success(L_('支付成功！'));
            }else{
                $this->error(L_('支付失败！请联系管理员处理。'));
            }
        }catch (\Exception $e){
            fdump_api($e->getMessage(),'merchant_physical_card');
            D()->rollback();
            $this->error($e->getMessage());
        }
        
    }

    /**
     * 店员-店铺收银-商家实体卡支付
     * @param $order_id
     * @param $now_order
     */
    public function store_arrival_mer_physical_card_pay($order_id,$mer_physical_card){
        $data['paid'] = '1';
        $data['pay_time'] = $_SERVER['REQUEST_TIME'];
        $data['pay_type'] = 'mer_physical_card';
        $data['mer_physical_card_no'] = $mer_physical_card['card_no'];
        D()->startTrans();
        try {
            if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
                $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                    foreach ($data as $kData=>$vData){
                        $now_order[$kData]=$vData;
                    }
                }
                if($now_order['business_type']){
                    switch($now_order['business_type']){
                        case 'foodshop':
                            $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                            D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                            M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['price']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
                            break;
                    }
                }

                if(C('config.open_extra_price')==1&&$now_order['uid']>0){
                    $now_order['order_type'] ='cash';
                    $score = D('Percent_rate')->get_extra_money($now_order);

                    if($score>0){
                        M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',$score);
                        //到店付给用户积分时需要商家预支积分
                        M('Merchant')->where(array('mer_id'=>$this->store['mer_id']))->setInc('extra_price_pay_for_system',$score);
                        $send_score_data['mer_id'] = $this->store['mer_id'];
                        $send_score_data['score_count'] = $score;
                        $send_score_data['add_time'] = time();
                        M('Merchant_score_send_log')->add($send_score_data);

                        M('Merchant')->where(array('mer_id'=>$this->store['mer_id']))->setInc('extra_price_pay_for_system',$score);
                        D('User')->add_score($now_order['uid'], floor($score),$this->store['name'] . ' 中使用到店消费支付了' . floatval($now_order['price']) . C('config.Currency_txt').' 获得'.C('config.extra_price_alias_name'));

                    }

                }

                D('Store_order')->store_score_get($now_order); // 增加积分

                // 订单改变之后调用
                D("Store_order")->after_store_change($order_id,'cash',2);

                //更新
                D('Merchant_physical_card')->addPhysicalCardOrder($now_order['price'],$mer_physical_card['id'],'store_staff',$now_order['mer_id'],$now_order['order_id'],$now_order['store_id']);
                D()->commit();
                $this->success(L_('支付成功！'));
            }else{
                D()->rollback();
                $this->error(L_('支付失败！请联系管理员处理。'));
            }
        }catch (\Exception $e){
            fdump_api($e->getMessage(),'merchant_physical_card');
            D()->rollback();
            $this->error($e->getMessage());
        }

    }

    public function consumer_order(){
        $pick_number = $_POST['pick_number'];
        $order = M('Shop_order')->where(array('pick_number' => $pick_number))->find();
        if(!$order){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('核销码无效')));exit;
        }
        $url = C('config.site_url') . '/v20/public/index.php/shop/storestaff.order/confirmConsume';
        $data = array('order_id' => $order['order_id'], 'staff_id' => $this->staff_session['id'], 'pick_addr_ids' => $this->staff_session['pick_addr_ids'], 'is_pc' => true);
        $res = $this->send_post($url, $data);
        if($res['status'] == 1000){
            echo json_encode(array('errorCode'=>0,'msg'=>L_('核销成功')));exit;
        }else{
            echo json_encode(array('errorCode'=>1,'msg'=>$res['msg']));exit;
        }
    }

    public function send_post($url, $post_data) {
        $postData = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return json_decode($result, true);
    }

    public function get_ecard(){
        $phone = $_POST['phone'];
        if(empty($phone)){
            $this->returnCode(1,'',L_('手机号码不能为空'));
        }
        $card_list = M('internal_membership_card')->where(array('phone' => $phone, 'status' => 1))->select();
        if(empty($card_list)){
            $this->returnCode(1,'',L_('没有一卡通信息'));
        }
        if(!$_POST['password']){
            $card_data['data'] = [];
            $card_data['data']['need_password'] = true;
            $this->returnCode(0,$card_data['data']);
        }else{
            $password = $_POST['password'];
            if($card_list[0]['password'] != md5($password)){
                $this->returnCode(1,array(),L_('一卡通密码错误'));
            }
        }
        $total_money = 0;
        foreach($card_list as $card){
            $total_money += $card['balance_money'];
        }
        $this->returnCode(0, getFormatNumber($total_money));
    }

	/**
	 * 店内收银和快速买单退款
	 */
	public function refund_store_order()
	{
		$orderid = isset($_POST['orderid']) ? trim($_POST['orderid']) : 0;
		if (empty($orderid)) {
			$this->error(L_('参数有误'));
		}
		$order = D('Store_order')->where(['orderid' => $orderid])->find();
        if (date('Ymd') != date('Ymd', $order['pay_time'])) {
			$this->error(L_('订单已经隔天，店员不允许退款。如需退款请联系商家'));
        }

		try {
			D('Store_order')->refund_store_order($orderid, true,$this->staff_session['id'], '店员:' . $this->staff_session['name']);
			$this->success('退款成功');
		} catch (Exception $e) {
			$this->error($e->getMessage());
		}
	}

}
?>