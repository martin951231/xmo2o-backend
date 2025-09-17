<?php
/*
 * 街道、社区中心-登录
 *
 */

class NewLoginAction extends BaseAction{
	public function index(){
//        redirect(U('NewLogin/login'));
        redirect($this->config['site_url'].'/v20/public/platform/#/user/community/login');
	}
	public function check(){

		if($this->isAjax()){
			if(md5($_POST['verify']) != $_SESSION['house_login_verify']){
				exit(json_encode(array('error'=>'1','msg'=>'验证码不正确！','dom_id'=>'verify')));
			}

			//物业判断进入小区

            $house_area_street = M('Area_street')->field(true)->where(array('account'=>trim($_POST['account'])))->find();
            if($house_area_street){
                if($house_area_street['is_open'] == 0){
                    exit(json_encode(array('error'=>'5','msg'=>'您被禁止登录！请联系工作人员获得详细帮助。','dom_id'=>'account')));
                }
                $pwd = md5($_POST['pwd']);

                if($pwd != $house_area_street['pwd']){
                    exit(json_encode(array('error'=>'3','msg'=>'密码错误！','dom_id'=>'pwd')));
                }
                session('house_street',$house_area_street);

                $log['area_street_id'] = $house_area_street['area_id'];
                $log['add_time'] = $_SERVER['REQUEST_TIME'];
                $log['ip'] = get_client_ip(0);
                M('area_street_login_log')->data($log)->add();
                exit(json_encode(array('error'=>'11','msg'=>'登录成功,现在跳转~','dom_id'=>'account')));
            }else{
                exit(json_encode(array('error'=>'6','msg'=>'账号不存在！请注册','dom_id'=>'')));
            }
		}else{
			exit('deney Access !');
		}
	}

	public function logout(){
		session('house_street',null);
		header('Location: '.U('NewLogin/index'));
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
			$_POST['pwd'] = md5($_POST['pwd']);
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
				exit(json_encode(array('error'=>'6','msg'=>'社区账号已存在,请重新注册','dom_id'=>'')));
			}
			$property_id = M('house_property')->data($data)->add();
			if($property_id){
				$_POST['property_id'] = $property_id;
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

	public function login() {
        redirect($this->config['site_url'].'/v20/public/platform/#/user/community/login');
		$this->display();
	}

	public function agreement(){
		$this->display();
	}


    public function property_login()
    {
        $database_property = D('House_property');
        $condition_village['id'] = $_GET['property_id'];
        $now_property = $database_property->field(true)->where($condition_village)->find();
        $admin_menus = D('House_menu')->field(true)->where(array('status'=>1))->select();
        $menus = array();
        foreach ($admin_menus as $value) {
            $menus[] = $value['id'];
        }
		$now_house['menus'] = $menus;
		session('house',$now_house);
		if(session('house1')){
			$now_property['login_identify'] = 2;
			session('property',$now_property);
		}else{
			$now_property['login_identify'] = 1;
			session('property',$now_property);
		}

		redirect($this->config['site_url'].'/shequ.php?g=House&c=NewProperty&a=index_new');
    }
}