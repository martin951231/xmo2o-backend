<?php
/*
 * 社区中心-登录
 *
 */

class LoginAction extends BaseAction{


	public function house_village()
	{
        $user_bind_count = D('House_village_user_bind')->where(array('status'=>1,'type'=>array('in',array(0,1,2,3,4))))->count();
		if($user_bind_count){
			$this->assign('user_bind_count',$user_bind_count);
		}else{
			$this->assign('user_bind_count',0);
		}
		$app_config = D('Appapi_app_config')->get();
		$this->assign('app_config',$app_config);
        $android_ios_url = C('config.site_url').'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.urlencode(C('config.site_url').'/topic/app_wap.html');
		$this->assign('android_ios_url',$android_ios_url);
		$this->display();
	}
	public function index(){
		redirect(U('Login/new_login'));
		$this->display();
	}
	public function check(){

		if($this->isAjax()){
			if(empty($_POST['login_status'])){
				exit(json_encode(array('error'=>'1','msg'=>'请选择身份！','dom_id'=>'login_status')));
			}
			if(md5($_POST['verify']) != $_SESSION['house_login_verify']){
				exit(json_encode(array('error'=>'1','msg'=>'验证码不正确！','dom_id'=>'verify')));
			}
			$database_house = D('House_village');
			$database_house_admin = D('House_admin');
			$condition_house['account'] = trim($_POST['account']);

			//物业判断进入小区
			if($_POST['login_status'] == 1){//物业管理员
				$house_property = M('House_property')->field(true)->where(array('account'=>trim($_POST['account'])))->find();
				$house_property['login_identify'] = 1;
				if($house_property){
					if($house_property['status'] == 2){
						exit(json_encode(array('error'=>'5','msg'=>'您被禁止登录！请联系工作人员获得详细帮助。','dom_id'=>'account')));
					}
					$pwd = md5($_POST['pwd']);

					if($pwd != $house_property['password']){
						exit(json_encode(array('error'=>'3','msg'=>'密码错误！','dom_id'=>'pwd')));
					}
					if($house_property['status'] == 2){
						exit(json_encode(array('error'=>'5','msg'=>'您被禁止登录！请联系工作人员获得详细帮助。','dom_id'=>'account')));
					}

                    //判断套餐是否过期 start  2020/8/18
                    $returnInfo = D('PackageOrder')->judgeOrderEmploy($house_property['id']);
                    if($returnInfo['code'] == 2){
                        exit(json_encode(array('error'=>'6','msg'=>$returnInfo['msg'],'dom_id'=>'')));
                    }elseif ($returnInfo['code'] == 3){
                        exit(json_encode(array('error'=>'6','msg'=>$returnInfo['msg'],'dom_id'=>'')));
                    }
                    //判断套餐是否过期 end 2020/8/18
					//查询权限 超级管理员赋予所有权限
					$admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
					$menus = array();
					foreach ($admin_menus as $value) {
						$menus[] = $value['id'];
					}
					$house_property['menus'] = $menus;
					session('property',$house_property);
					$data_property['id'] = $house_property['id'];
					$data_property['last_time'] = $_SERVER['REQUEST_TIME'];
					M('House_property')->data($data_property)->save();
                    $ticket = Token::createToken($house_property['id'],3);
                    setcookie('property_access_token',$ticket,$_SERVER['REQUEST_TIME']+10000000,'/');
					exit(json_encode(array('error'=>'11','msg'=>'登录成功,现在跳转~','dom_id'=>'account')));
				}else{
					exit(json_encode(array('error'=>'6','msg'=>'账号不存在！请注册','dom_id'=>'')));
				}

			}elseif($_POST['login_status'] == 2){//物业工作人员
				$database_property = D('Property_admin');
				$condition_house['account'] = trim($_POST['account']);
				$now_role = $database_property->where($condition_house)->find();
                //2020/9/2 新增 start
                //$village_status = $database_house->where(['property_id'=>$now_role['property_id']])->getField('status');
                $property_status = M('House_property')->field(true)->where(array('id'=>$now_role['property_id']))->getField('status');
                if($property_status == 2){
                    exit(json_encode(array('error'=>'5','msg'=>'该物业已被禁止,请联系工作人员。','dom_id'=>'account')));
                }
                //2020/9/2 新增 end
				if (!$now_role) {
					exit(json_encode(array('error'=>'2','msg'=>'用户名不存在！','dom_id'=>'account')));
				}
				$pwd = md5($_POST['pwd']);
				if($pwd != $now_role['pwd']){
					exit(json_encode(array('error'=>'3','msg'=>'密码错误！','dom_id'=>'pwd')));
				}
                //判断套餐是否过期 start  2020/8/18
                $returnInfo = D('PackageOrder')->judgeOrderEmploy($now_role['property_id']);
                if($returnInfo['code'] == 2){
                    exit(json_encode(array('error'=>'6','msg'=>$returnInfo['msg'],'dom_id'=>'')));
                }elseif ($returnInfo['code'] == 3){
                    exit(json_encode(array('error'=>'6','msg'=>$returnInfo['msg'],'dom_id'=>'')));
                }
                //判断套餐是否过期 end 2020/8/18
				$data_house = array();
				$data_house['id'] = $now_role['id'];
				$data_house['login_count'] = $now_role['login_count'] + 1;
				$data_house['last_ip'] = get_client_ip(0);;
				$data_house['last_time'] = $_SERVER['REQUEST_TIME'];
				if($database_property->data($data_house)->save()){
					//查询权限
//					$now_house['menus'] = explode(',',$now_role['menus']);
//					session('house',$now_house);
//					session('house1',$now_house);
					$house_property = M('House_property')->field(true)->where(array('id'=>$now_role['property_id']))->find();
					$house_property['login_identify'] = 2;
					$house_property['menus'] = explode(',',$now_role['menus']);
					session('property',$house_property);
                    $ticket = Token::createToken($now_role['id'],4);
                    setcookie('property_access_token',$ticket,$_SERVER['REQUEST_TIME']+10000000,'/');

					exit(json_encode(array('error'=>'11','msg'=>'登录成功,现在跳转~','dom_id'=>'account')));
				}else{
					exit(json_encode(array('error'=>'6','msg'=>'登录信息保存失败,请重试！','dom_id'=>'account')));
				}

			}elseif($_POST['login_status'] == 3){//小区工作人员
				$database_house = D('House_village');
				$database_house_admin = D('House_admin');
				$condition_house['account'] = trim($_POST['account']);
				if($_POST['village_id']){
					$condition_house['village_id'] = trim($_POST['village_id']);
				}

//				$now_house = $database_house->field(true)->where($condition_house)->find();
                // 直接查询小区角色账号
                $now_role = $database_house_admin->get_info_one($condition_house);

				if(!empty($now_role)){
					//社区管理角色
					if (!$now_role) {
						exit(json_encode(array('error'=>'2','msg'=>'用户名不存在！','dom_id'=>'account')));
					}
					if ($now_role['status'] != 1) {
						exit(json_encode(array('error'=>'2','msg'=>'用户已禁止！','dom_id'=>'account')));
					}

					$pwd = md5($_POST['pwd']);
					if($pwd != $now_role['pwd']){
						exit(json_encode(array('error'=>'3','msg'=>'密码错误！','dom_id'=>'pwd')));
					}

					// 社区信息
					$now_house = $database_house->field(true)->where(array('village_id'=>$now_role['village_id']))->find();
                    //2020/9/2 新增 start
                    if($now_house['status'] == 2){
                        exit(json_encode(array('error'=>'5','msg'=>'该小区已被禁止,请联系工作人员。','dom_id'=>'account')));
                    }
                    //2020/9/2 新增 end
                    //判断套餐是否过期 start  2020/8/18
                    $returnInfo = D('PackageOrder')->judgeOrderEmploy($now_house['property_id']);
                    if($returnInfo['code'] == 2){
                        exit(json_encode(array('error'=>'6','msg'=>$returnInfo['msg'],'dom_id'=>'')));
                    }elseif ($returnInfo['code'] == 3){
                        exit(json_encode(array('error'=>'6','msg'=>$returnInfo['msg'],'dom_id'=>'')));
                    }
                    //判断套餐是否过期 end 2020/8/18
					$data_house = array();
					$data_house['id'] = $now_role['id'];
					$data_house['login_count'] = $now_role['login_count'] + 1;
					$data_house['last_ip'] = get_client_ip(0);;
					$data_house['last_time'] = $_SERVER['REQUEST_TIME'];
					$now_house['menus'] = explode(',', $now_role['menus']);
					$now_house['user_name'] = $now_role['realname'] ? $now_role['realname'] : $now_role['account'];
					$now_house['role_id'] = $now_role['id'];

					//判断社区是否到期
//					if (isset($now_house['expiration_time']) && $now_house['expiration_time'] && $now_house['expiration_time']<time()) {
//						exit(json_encode(array('error'=>'8','msg'=>'该小区已到期，请联系管理员！')));
//					}

					if($database_house_admin->data($data_house)->save()){
						$house_property = M('House_property')->field(true)->where(array('account'=>$now_house['property_id']))->find();
						$now_house['login_village'] = 3;
						session('house',$now_house);
						session('property',$house_property);
                        $ticket = Token::createToken($now_role['id'],5);
                        setcookie('village_access_token',$ticket,$_SERVER['REQUEST_TIME']+10000000,'/');
                        invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $now_role['village_id']]);
						exit(json_encode(array('error'=>'0','msg'=>'登录成功,现在跳转~','dom_id'=>'account')));
					}else{
						exit(json_encode(array('error'=>'6','msg'=>'登录信息保存失败,请重试！','dom_id'=>'account')));
					}
				} else {
                    exit(json_encode(array('error'=>'10','msg'=>'账号或者密码错误,请重试！','dom_id'=>'account')));
                }


			}
			die;
			$house_property = M('House_property')->field(true)->where(array('account'=>trim($_POST['account'])))->find();
			if($house_property){
				$now_house = $database_house->field(true)->where($condition_house)->find();
				if($house_property['status'] == 2){
					exit(json_encode(array('error'=>'5','msg'=>'您被禁止登录！请联系工作人员获得详细帮助。','dom_id'=>'account')));
				}

				$house_list = $database_house->field('`village_id`,`village_name`,`status`')->where(array('property_id'=>$house_property['id']))->order('`village_id` ASC')->select();
				if(is_array($house_list) && count($house_list) > 1){
					exit(json_encode(array('error'=>'7','house_list'=>$house_list)));
				} elseif(count($house_list)==1) {
					$single_house=reset($house_list);
					$now_house = $database_house->field(true)->where(array('village_id' => $single_house['village_id']))->find();
				}

				$pwd = md5($_POST['pwd']);

				if($pwd != $house_property['password']){
					exit(json_encode(array('error'=>'3','msg'=>'密码错误！','dom_id'=>'pwd')));
				}
				if($house_property['status'] == 2){
					exit(json_encode(array('error'=>'5','msg'=>'您被禁止登录！请联系工作人员获得详细帮助。','dom_id'=>'account')));
				}


				//判断社区是否到期
//				if (isset($now_house['expiration_time']) && $now_house['expiration_time'] && $now_house['expiration_time']<time()) {
//					exit(json_encode(array('error'=>'8','msg'=>'该小区已到期，请联系管理员！')));
//				}

				$data_house['village_id'] = $now_house['village_id'];
				$data_house['last_time'] = $_SERVER['REQUEST_TIME'];
				//查询权限 超级管理员赋予所有权限
				$admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
				$menus = array();
				foreach ($admin_menus as $value) {
					$menus[] = $value['id'];
				}
				$now_house['menus'] = $menus;
				if($database_house->data($data_house)->save()){
					session('house',$now_house);
					exit(json_encode(array('error'=>'0','msg'=>'登录成功,现在跳转~','dom_id'=>'account')));
				}else{
					exit(json_encode(array('error'=>'6','msg'=>'登录信息保存失败,请重试！','dom_id'=>'account')));
				}

			}else{
				exit(json_encode(array('error'=>'6','msg'=>'账号不存在！请注册','dom_id'=>'')));
			}

			die;
			if(md5($_POST['verify']) != $_SESSION['house_login_verify']){
				exit(json_encode(array('error'=>'1','msg'=>'验证码不正确！','dom_id'=>'verify')));
			}

			$database_house = D('House_village');
			$database_house_admin = D('House_admin');
			$condition_house['account'] = trim($_POST['account']);
			if($_POST['village_id']){
				$condition_house['village_id'] = trim($_POST['village_id']);
			}

			$now_house = $database_house->field(true)->where($condition_house)->find();
			if(empty($now_house)){
				//小区管理角色
				$now_role = $database_house_admin->get_info_one($condition_house);
				if (!$now_role) {
					exit(json_encode(array('error'=>'2','msg'=>'用户名不存在！','dom_id'=>'account')));
				}
				if ($now_role['status'] != 1) {
					exit(json_encode(array('error'=>'2','msg'=>'用户已禁止！','dom_id'=>'account')));
				}

				$pwd = md5($_POST['pwd']);
				if($pwd != $now_role['pwd']){
					exit(json_encode(array('error'=>'3','msg'=>'密码错误！','dom_id'=>'pwd')));
				}

				// 小区信息
				$now_house = $database_house->field(true)->where(array('village_id'=>$now_role['village_id']))->find();
				$data_house = array();
				$data_house['id'] = $now_role['id'];
				$data_house['login_count'] = $now_role['login_count'] + 1;
				$data_house['last_ip'] = get_client_ip(0);;
				$data_house['last_time'] = $_SERVER['REQUEST_TIME'];
				$now_house['menus'] = explode(',', $now_role['menus']);
				$now_house['user_name'] = $now_role['realname'] ? $now_role['realname'] : $now_role['account'];
				$now_house['role_id'] = $now_role['id'];

				//判断小区是否到期
//				if (isset($now_house['expiration_time']) && $now_house['expiration_time'] && $now_house['expiration_time']<time()) {
//					exit(json_encode(array('error'=>'8','msg'=>'该小区已到期，请联系管理员！')));
//				}

				if($database_house_admin->data($data_house)->save()){
					session('house',$now_house);
					exit(json_encode(array('error'=>'0','msg'=>'登录成功,现在跳转~','dom_id'=>'account')));
				}else{
					exit(json_encode(array('error'=>'6','msg'=>'登录信息保存失败,请重试！','dom_id'=>'account')));
				}

			}else{ //超级管理员

				if(empty($_POST['village_id'])){
					$house_list = $database_house->field('`village_id`,`village_name`,`status`')->where(array('account'=>$now_house['account'],'status'=>array('lt','2')))->order('`village_id` ASC')->select();
					if(is_array($house_list) && count($house_list) > 1){
						exit(json_encode(array('error'=>'7','house_list'=>$house_list)));
					} elseif(count($house_list)==1) {
						$single_house=reset($house_list);
						$now_house = $database_house->field(true)->where(array('village_id' => $single_house['village_id']))->find();
					}
				}
				$pwd = md5($_POST['pwd']);
				if($pwd != $now_house['pwd']){
					exit(json_encode(array('error'=>'3','msg'=>'密码错误！','dom_id'=>'pwd')));
				}

				if($now_house['status'] == 2){
					exit(json_encode(array('error'=>'5','msg'=>'您被禁止登录！请联系工作人员获得详细帮助。','dom_id'=>'account')));
				}

				//判断小区是否到期
//				if (isset($now_house['expiration_time']) && $now_house['expiration_time'] && $now_house['expiration_time']<time()) {
//					exit(json_encode(array('error'=>'8','msg'=>'该小区已到期，请联系管理员！')));
//				}

				$data_house['village_id'] = $now_house['village_id'];
				$data_house['last_time'] = $_SERVER['REQUEST_TIME'];
				//查询权限 超级管理员赋予所有权限				
				$admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
				$menus = array();
				foreach ($admin_menus as $value) {
					$menus[] = $value['id'];
				}
				$now_house['menus'] = $menus;
				if($database_house->data($data_house)->save()){
					session('house',$now_house);

					exit(json_encode(array('error'=>'0','msg'=>'登录成功,现在跳转~','dom_id'=>'account')));
				}else{
					exit(json_encode(array('error'=>'6','msg'=>'登录信息保存失败,请重试！','dom_id'=>'account')));
				}
			}
		}else{
			exit('deney Access !');
		}
	}

	public function logout(){
		session('house',null);
		session('house1',null);
		session('property',null);
        setcookie('village_access_token', '', -1, '/');
		header('Location: '.U('Login/index'));
	}
	public function verify(){
		$verify_type = $_GET['type'];
		if(empty($verify_type)){exit;}
		import('ORG.Util.Image');
		Image::buildImageVerify(4,1,'jpeg',53,26,'house_'.$verify_type.'_verify');
	}


	/*新登录页面处理*/
	public function reg(){
        $platform_property_qr_code = $this->config['platform_property_qr_code'];
        $site_phone = $this->config['site_phone'];
        // 1 全部联系方式  2 二维码图片  3 电话  0 没有联系方式
        if ($platform_property_qr_code && $site_phone) {
            $this->assign('is_tip', 1);
        } elseif ($platform_property_qr_code && !$site_phone) {
            $this->assign('is_tip', 2);
        } elseif (!$platform_property_qr_code && $site_phone) {
            $this->assign('is_tip', 3);
        } elseif (!$platform_property_qr_code && !$site_phone) {
            $this->assign('is_tip', 0);
        }
		$this->display('new_reg');
	}

	public function tip() {
        $platform_property_qr_code = $this->config['platform_property_qr_code'];
        $site_phone = $this->config['site_phone'];
        // 1 全部联系方式  2 二维码图片  3 电话  0 没有联系方式
        if ($platform_property_qr_code && $site_phone) {
            $this->assign('is_tip', 1);
        } elseif ($platform_property_qr_code && !$site_phone) {
            $this->assign('is_tip', 2);
        } elseif (!$platform_property_qr_code && $site_phone) {
            $this->assign('is_tip', 3);
        } elseif (!$platform_property_qr_code && !$site_phone) {
            $this->assign('is_tip', 0);
        }
        $this->assign('platform_property_qr_code', $platform_property_qr_code);
        $this->assign('site_phone', $site_phone);
        $this->display();
    }


	public function reg_check(){
		if($this->isAjax()){
			foreach($_POST as $key=>$value){
				$_POST[$key] = trim($value);
			}

			$database_house_village = M('House_village');
			//名称
			$house_name = $this->config['house_name'];
			if (empty($_POST['village_name'])) {
				exit(json_encode(array('error'=>'5','msg'=>L_('请输入').$house_name.L_('名称').'~','dom_id'=>'village_name')));
			}
			if (empty($_POST['village_address'])) {
				exit(json_encode(array('error'=>'5','msg'=>L_('请选择').$house_name.L_('地址').'~','dom_id'=>'address')));
			}
			if (empty($_POST['property_name'])) {
				exit(json_encode(array('error'=>'5','msg'=>L_('请填写物业公司名称~'),'dom_id'=>'property_name')));
			}
			if (empty($_POST['property_address'])) {
				exit(json_encode(array('error'=>'5','msg'=>L_('请填写物业联系地址~'),'dom_id'=>'property_address')));
			}
			if (empty($_POST['property_phone'])) {
				exit(json_encode(array('error'=>'5','msg'=>L_('请输入正确的物业联系电话~'),'dom_id'=>'reg_phone')));
			}
			if (empty($_POST['account'])) {
				exit(json_encode(array('error'=>'5','msg'=>L_('请输入').$house_name.L_('后台管理帐号').'~','dom_id'=>'account')));
			}
			if (empty($_POST['pwd'])) {
				exit(json_encode(array('error'=>'5','msg'=>L_('请输入').$house_name.L_('后台管理密码').'~','dom_id'=>'reg_pwd')));
			}
			// 添加默认允许游客访问
			if (!$_POST['tourist']) {
				$_POST['tourist'] = 1;
			}
			$database_house_village_config = D('House_village_config');
			$_POST['add_time'] = time();
			$_POST['status'] = 3;

			$data['account'] = $_POST['account'];//物业账号
			$data['password'] = md5($_POST['pwd']);//物业密码
			$data['property_name'] = $_POST['property_name'];//物业名称
			$data['status'] = 3;
			$data['create_time'] = time();
			$data['property_phone'] = $_POST['property_phone'];
			$data['property_address'] = $_POST['property_address'];
			$data['province_id'] = $_POST['province_id'];
			$data['city_id'] = $_POST['city_id'];
			$data['area_id'] = $_POST['area_id'];
			$model = M('house_property')->where(array('account'=>trim($_POST['account'])))->find();
			if($model){
				exit(json_encode(array('error'=>'6','msg'=>'小区账号已存在,请重新注册','dom_id'=>'')));
			}
			$property_id = M('house_property')->data($data)->add();
			if($property_id){
				$_POST['property_id'] = $property_id;
                D('House_village')->setSoftwareNewEffectCharge($property_id);
				$add_a = $database_house_village->data($_POST)->add();
				if($add_a){
					$data['village_id'] = $add_a;
					$database_house_village_config->add($data);
					exit(json_encode(array('error'=>'0','msg'=>'注册成功,请耐心等待审核或联系工作人员审核~')));
				}else{
					exit(json_encode(array('error'=>'6','msg'=>'注册失败,请重试！','dom_id'=>'village_name')));
				}
			}else{
				exit(json_encode(array('error'=>'6','msg'=>'注册失败,请重试！','dom_id'=>'village_name')));
			}
		}else{
			exit('deney Access !');
		}
	}

	public function new_login() {
        // 返回下注册要跳转页面链接
        $property_application_method = $this->config['property_application_method'];
        if (1==$property_application_method) {
            redirect($this->config['site_url'].'/v20/public/platform/#/user/community/login');
        } else {
            redirect($this->config['site_url'].'/v20/public/platform/#/user/community/login');
        }
	    $arrLevel = [
            ['id'=>1,
                'url'=>$this->config['site_url'] . '/shequ.php?g=House&c=Login&a=new_login',///
                'title'=>'请选择',
            ],
            ['id'=>2,
                'url'=>$this->config['site_url'].'/v20/public/platform/#/user/community/login',
                'title'=>'我是街道/社区管理员',
            ],
	        ['id'=>3,
                'url'=>$this->config['site_url'] . '/merchant.php?g=Merchant&c=Login&a=index',
                'title'=>'我是商家'],
            ['id'=>4,
                'url'=>$this->config['site_url'] . '/store.php?g=Merchant&c=Store&a=login',
                'title'=>'我是店员',
            ],

        ];
	    $this->assign('arrLevel',$arrLevel);
		$this->display();
	}

	public function agreement(){
		$this->display();
	}


    public function property_login()
    {
        fdump_api(['$_GET'=>$_GET,'$_POST' => $_POST],'property_login');
        $database_house_property = D('House_property');
        $database_village = D('House_village');
        $property_id = I('property_id');
        if ($_GET['villageTabLogin']&&!$property_id) {
            $property_id = $database_village->where(['village_id'=> $_GET['villageTabLogin']])->getField('property_id');
        }
        $condition_village['id'] = $property_id;
        $now_property = $database_house_property->field(true)->where($condition_village)->find();
        if(empty($now_property) || $now_property['status'] == 2){
            exit('<html><head><script>window.top.toggleMenu(0);window.top.msg(0,"该'.C('config.house_name').'的状态不存在！请查阅。",true,5);window.history.back();</script></head></html>');
        }

        import("@.ORG.Token");
        $v2o_ticket = $_COOKIE['village_access_token'] ? $_COOKIE['village_access_token'] : '';
        $ticket = Token::checkToken($v2o_ticket);
        if ($ticket && $ticket['memberId'] && $ticket['extends']) {
            $user_id = $ticket['memberId'];
            $login_role = $ticket['extends'];
            $dbCommunityAdmin = D('CommunityAdmin');
            if (in_array($login_role,[6,$dbCommunityAdmin::SYSTEM_ADMIN_TO_VILLAGE,$dbCommunityAdmin::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE])) {
                $role = D('CommunityAdmin')::SYSTEM_ADMIN_TO_PROPERTY;
                $admin_id = intval(session('system')['id']);
                if (!$admin_id) {
                    $info = explode('_',$user_id);
                    if (count($info)>1) {
                        $admin_id = array_pop($info);
                    }
                }
                $members = $property_id . '_' . $admin_id;
                $ticket = Token::createToken($members,$role);
            } elseif (in_array($login_role,[7,$dbCommunityAdmin::PROPERTY_ADMIN_TO_VILLAGE])) {
                $role = D('CommunityAdmin')::PROPERTY_ADMIN_LOGIN;
                $members = $property_id;
                $ticket = Token::createToken($members,$role);
            } else {
                exit('<html><head><script>alert("当前'.C('config.house_name').'您不能进入！");window.history.back();</script></head></html>');
            }
            setcookie('property_access_token',$ticket,$_SERVER['REQUEST_TIME']+10000000,'/');
        }

        redirect($this->config['site_url'].'/v20/public/platform/#/property/property.iframe/property_index');
    }
}