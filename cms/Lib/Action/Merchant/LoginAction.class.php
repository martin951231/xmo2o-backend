<?php
/*
 * 商户登录
 *
 */

class LoginAction extends BaseAction
{

    public function index()
    {
        redirect(C('config.site_url')."/v20/public/platform/#/usernew/merchant/login");
        if(trim($_SERVER['SCRIPT_NAME'], '/') == 'store.php'){
			redirect(U('Store/login'));
        }
		if($this->merchant_session){
			redirect(U('Index/index'));
		}
        $this->display('new_login');
    }
	
	public function reg(){
		if(trim($_SERVER['SCRIPT_NAME'], '/') == 'store.php'){
			redirect(U('Store/login'));
        }
		if($this->merchant_session){
			redirect(U('Index/index'));
		}
        $this->display('new_reg');
	}
	
	public function agreement(){
		$this->display();
	}
	
	public function sendsms(){
		if(empty($_POST['phone'])){
			$this->error(L_('请输入手机号'));
		}
		$now_merchant = M('Merchant')->where(array('phone'=>$_POST['phone']))->find();
		if(!empty($now_merchant)){
			$this->error(L_('该手机号已注册商家'));
		}


		//防止1分钟内多次发送短信
		$laste_sms = M('Merchant_sms_record')->where(array('phone'=>$_POST['phone'],'type'=>1))->order('pigcms_id DESC')->find();
		if(time()-$laste_sms['send_time']<60){
			$this->error(L_('一分钟内不能多次发送短信'));
		}
		$phone =$_POST['phone'];
		$code = mt_rand(1000, 9999);


		$text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',$code);
		
		$phone = phone_format($_POST['phone_country_type'], $phone);
		
		$columns = array();
		$columns['phone'] = $phone;

		$columns['extra'] = $code;
		$columns['type'] = 1;

		$columns['mer_id'] = 0;
		$columns['status'] = 0;
		$columns['send_time'] = time();
		$columns['expire_time'] = $columns['send_time'] + 1200;
		$result = M("Merchant_sms_record")->add($columns);
		if (!$result){
			$this->error(L_('短信数据写入失败'));
		}
		$sms_data = array('mer_id' => 0, 'store_id' => 0, 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'merchant_pwd');
		$_POST['phone_country_type'] && $sms_data['nationCode']  = $_POST['phone_country_type'];
		$return = Sms::sendSms($sms_data);
		
		$this->success(L_('发送成功'));
	}


	public function check(){
		if($this->isAjax()){
			$database_merchant = D('Merchant');
			
			if($_GET['login_type'] == 'user_openid'){
				if(empty($_SESSION['user'])){
					exit(json_encode(array('error'=>'1','msg'=>L_('用户未登录，请重新扫码'),'dom_id'=>'verify')));
				}
				if(empty($_SESSION['user']['openid'])){
					exit(json_encode(array('error'=>'1','msg'=>L_('用户异常，请重新扫码'),'dom_id'=>'verify')));
				}
				$condition_merchant['uid'] = trim($_SESSION['user']['uid']);
				$now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
				if(empty($now_merchant)){
					exit(json_encode(array('error'=>'2','msg'=>L_('微信号未绑定商家，请使用手机号登录商家后台绑定'),'dom_id'=>'account')));
				}
			}else{
				if(md5($_POST['verify']) != $_SESSION['merchant_login_verify']){
					exit(json_encode(array('error'=>'1','msg'=>L_('验证码不正确！'),'dom_id'=>'verify')));
				}
				$condition_merchant['account'] = trim($_POST['account']);
                $condition_merchant['status'] = ['NEQ', 4];
				
				$now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
				if(empty($now_merchant)){

                    $condition_merchant_account['phone'] = $_POST['account'];
                    $condition_merchant_account['status'] = ['NEQ', 4];
					$now_merchant = $database_merchant->field(true)->where($condition_merchant_account)->find();
					if(empty($now_merchant)){
						exit(json_encode(array('error'=>'2','msg'=>L_('用户名不存在！'),'dom_id'=>'account')));
					}


				}
				$pwd = md5($_POST['pwd']);
				if($pwd != $now_merchant['pwd']){
					exit(json_encode(array('error'=>'3','msg'=>L_('密码错误！'),'dom_id'=>'pwd')));
				}
			}
			
			
			if(!empty($now_merchant['merchant_end_time']) && $now_merchant['merchant_end_time'] < $_SERVER['REQUEST_TIME']){
				$data_merchant['mer_id'] = $now_merchant['mer_id'];
				$data_merchant['status'] = '0';
				$menus = M('Authority_group')->where(array('is_default'=>1))->find();
				if($now_merchant['authority_group_id']>0){
					$data_merchant['authority_group_id'] = $menus['id'];
					$data_merchant['status'] = 1;
				}
				$database_merchant->data($data_merchant)->save();
				exit(json_encode(array('error'=>'7','msg'=>L_('您的帐号已经过期！请联系工作人员获得详细帮助。'),'dom_id'=>'account')));
			}
			if($now_merchant['status'] == 0){
				exit(json_encode(array('error'=>'4','msg'=>L_('您被禁止登录！请联系工作人员获得详细帮助。'),'dom_id'=>'account')));
			}else if($now_merchant['status'] == 2){
				exit(json_encode(array('error'=>'5','msg'=>L_('您的帐号正在审核中，请耐心等待或联系工作人员审核。'),'dom_id'=>'account')));
			}else if($now_merchant['status'] == 4){
				exit(json_encode(array('error'=>'6','msg'=>L_('您的帐号已经被永久删除，无法使用。'),'dom_id'=>'account')));
			}
			
			$data_merchant['mer_id'] = $now_merchant['mer_id'];
			$data_merchant['last_ip'] = get_client_ip(0);
			$data_merchant['last_time'] = $_SERVER['REQUEST_TIME'];
			$data_merchant['login_count'] = $now_merchant['login_count']+1;
			if($database_merchant->data($data_merchant)->save()){
				$now_merchant['login_count'] += 1;
				
				if(!empty($now_merchant['last_ip'])){
					import('ORG.Net.IpLocation');
					$IpLocation = new IpLocation();
					$last_location = $IpLocation->getlocation($now_merchant['last_ip']);
					$now_merchant['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
					$now_merchant['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');
				}
				session('merchant',$now_merchant);
				session('staff',null);

				if($now_merchant['status']==3){
					$remark = L_('您的账户处于欠费状态，您的商家业务已经被关闭，请及时充值，充值后将恢复业务');
				}else{
					session('is_login_from',1);
					$remark ='登录成功,现在跳转~';
				}
				exit(json_encode(array('error'=>'0','msg'=>$remark,'dom_id'=>'account')));
			}else{
				exit(json_encode(array('error'=>'6','msg'=>'登录信息保存失败,请重试！','dom_id'=>'account')));
			}
		}else{
			exit('deney Access !');
		}
	}
	public function reg_check(){
		if($this->isAjax()){
			if($this->config['open_merchant_reg_sms'] && md5($_POST['verify']) != $_SESSION['user_sms_verify']){
				exit(json_encode(array('error'=>'1','msg'=>L_('验证码不正确！'),'dom_id'=>'reg_sms')));
			}

			foreach($_POST as $key=>$value){
				$_POST[$key] = trim($value);
			}


			//判断短信验证码
			if($this->config['open_merchant_reg_sms']){

				$laste_sms = M('Merchant_sms_record')->where(array('phone' =>$_POST['phone']))->order('pigcms_id DESC')->find();
				if($laste_sms['status']==1){
					exit(json_encode(array('error'=>'1','msg'=>L_('该短信验证码已失效，请重新获取！'),'dom_id'=>'reg_sms')));
	            }
				if(time() - $laste_sms['send_time'] > 1200){
					exit(json_encode(array('error'=>'1','msg'=>L_('短信验证码已超过20分钟'),'dom_id'=>'reg_sms')));
				}
				if($_POST['smscode'] != $laste_sms['extra']){
					exit(json_encode(array('error'=>'1','msg'=>L_('短信验证码不正确'),'dom_id'=>'reg_sms')));
				}
			}
			$database_merchant = M('Merchant');
			
			//名称
			$condition_merchant_name['name'] = $_POST['name'];
			$now_merchant = $database_merchant->field('`mer_id`')->where($condition_merchant_name)->find();
			if(!empty($now_merchant)){
				exit(json_encode(array('error'=>'3','msg'=>L_('商家名称已经存在！'),'dom_id'=>'name')));
			}

            // 处理 公司名称（营业执照上的名称） 在 【商家推荐商家批发佣金功能】开关开启的情况下
            if ($this->config['merchant_type_switch'] == 1) {
                $company_name = $_POST['company_name'];
                $company_name = trim($company_name);
                if (empty($company_name)) {
				    exit(json_encode(array('error'=>'7','msg'=>L_('请填写公司名称（营业执照上的名称）'),'dom_id'=>'company_name')));
                }else {
                    $_POST['company_name'] = $company_name; // 去除前后空格
                }
            }
            // 处理下推广码问题
            $spread_code = $_POST['spread_code'] ? trim($_POST['spread_code']) : '';
            if ($spread_code && stripos($spread_code,'mer') === 0) {
                // 兼容原来旧数据
                if ($this->config['merchant_recommend_wholesale_switch'] == 1) {
                    // 如何符合条件且开关开启的时候可以关联
                    $spread_merchant = $database_merchant->field('`mer_id`')->field(true)->where(array('mer_code' => $spread_code))->find();
                    if (!$spread_merchant) {
				        exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码错误，请核实后重新填写。'),'dom_id'=>'spread_code')));
                    }else{
                    	/*校验该推广码的商户是否有商家推荐码的权限*/
                    	$root_menu_id=M('New_merchant_menu')->where(array('name'=>L_('我的商家推广'),'status'=>1))->getfield('id');
                    	if(empty($root_menu_id)){
                    		exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码无效，请核实后重新填写!'),'dom_id'=>'spread_code')));
                    	}
                    	$menus_arr=explode(',', $spread_merchant['menus']);
                    	if(!is_array($menus_arr) || !in_array($root_menu_id,$menus_arr)){
                    		exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码无效，请核实后重新填写！!'),'dom_id'=>'spread_code')));		
                    	}
                    }
                    $_POST['mer_spread_code'] =  $spread_code;
                    $_POST['spread_code'] =  '';
                } else {
                    // 如果关闭了，提示信息
                    exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码无效，请核实后重新填写。'),'dom_id'=>'spread_code')));
                }
            } elseif ($spread_code && intval($spread_code) > 100000 && intval($spread_code) < 999999) {
                if ($this->config['merchant_recommend_wholesale_switch'] == 1) {
                    // 如何符合条件且开关开启的时候可以关联
                    $spread_merchant = $database_merchant->field('`mer_id`')->field(true)->where(array('mer_code' => $spread_code))->find();
                    if (!$spread_merchant) {
                        exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码错误，请核实后重新填写。'),'dom_id'=>'spread_code')));
                    }else{
                    	/*校验该推广码的商户是否有商家推荐码的权限*/
                    	$root_menu_id=M('New_merchant_menu')->where(array('name'=>L_('我的商家推广'),'status'=>1))->getfield('id');
                    	if(empty($root_menu_id)){
                    		exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码无效，请核实后重新填写!'),'dom_id'=>'spread_code')));
                    	}
                    	$menus_arr=explode(',', $spread_merchant['menus']);
                    	if(!is_array($menus_arr) || !in_array($root_menu_id,$menus_arr)){
                    		exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码无效，请核实后重新填写!!'),'dom_id'=>'spread_code')));		
                    	}
                    }
                    $_POST['mer_spread_code'] =  $spread_code;
                    $_POST['spread_code'] =  '';
                } else {
                    // 如果关闭了，提示信息
                    exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码无效，请核实后重新填写。'),'dom_id'=>'spread_code')));
                }
            } elseif(!$spread_code && $this->config['merchant_recommend_wholesale_switch']) {
                // 如果开启了没填，提示信息
                exit(json_encode(array('error'=>'2','msg'=>L_('您填写的推广码无效，请核实后重新填写。'),'dom_id'=>'spread_code')));
            } else {
                $_POST['spread_code'] = $spread_code;
            }


			//邀请码
			if(($this->config['open_admin_code']==1 || $this->config['open_bd_spread'] == 1) && $_POST['invit_code'] != ''){
				if($this->config['open_bd_spread'] == 1){//这个BD优先
					$bd = M("Bd")->where(['invitation_code'=>strtoupper($_POST['invit_code']), 'status'=>1, 'is_del'=>0])->find();
					if(empty($bd)){
						exit(json_encode(array('error'=>'5','msg'=>L_('业务员BD邀请码不存在！'),'dom_id'=>'invit_code')));
					}
					$_POST['bd_type'] = $bd['type'];
					$_POST['bd_id'] = $bd['bd_id'];
				}
				else{
					if(!M('Admin')->where(array('invit_code'=>$_POST['invit_code']))->find()){
						exit(json_encode(array('error'=>'5','msg'=>L_('邀请码不存在！'),'dom_id'=>'invit_code')));
					}
				}
			}

			//手机号
			$condition_merchant_phone['phone'] = $_POST['phone'];
			$now_merchant = $database_merchant->field('`mer_id`')->where($condition_merchant_phone)->find();
			if(!empty($now_merchant)){
				exit(json_encode(array('error'=>'5','msg'=>L_('手机号已经存在！'),'dom_id'=>'phone')));
			}
			
			//账号作为手机号
			$condition_merchant_phone_account['phone'] = $_POST['phone'];
			$now_merchant = $database_merchant->field('`mer_id`')->where($condition_merchant_phone_account)->find();
			if(!empty($now_merchant)){
				exit(json_encode(array('error'=>'5','msg'=>L_('该手机号作为账号已经存在，不允许重复'),'dom_id'=>'phone')));
			}
			
			$_POST['mer_id'] = null;
			if($this->config['merchant_verify']){
				$_POST['status'] = 2;
			}else{

				$_POST['status'] = 1;
			}

			
			$_POST['pwd'] = md5($_POST['pwd']);
			$_POST['reg_ip'] = get_client_ip(0);
			$_POST['reg_time'] = $_SERVER['REQUEST_TIME'];
			$_POST['login_count'] = 0;
			$_POST['reg_from'] = 0;

			if($insert_id=$database_merchant->data($_POST)->add()){
				M('Merchant_score')->add(array('parent_id'=>$insert_id,'type'=>1));
				/*无需审核的商家注册，注册后即添加滚动信息*/
				if($_POST['status']==1){
					D('Scroll_msg')->add_msg('mer_reg',$insert_id,L_('商家X1于X2注册成功！',array('X1'=>str_replace_name($_POST['name']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));
				}
				/*校验成功，将验证码状态至为已使用*/
				M('Merchant_sms_record')->data(array('pigcms_id'=>$laste_sms['pigcms_id'],'status'=>1))->save();

				//多语言表增加数据
				$lang_model = D('Lang');
				$_POST['mer_id'] = $insert_id;
				$lang_model->add_lang_data('Merchant',$_POST);	

				if (0 == $this->config['merchant_verify'] && $this->config['open_distributor']==1 && $_POST['spread_code']) {
					D('Distributor_agent')->agent_spread_log($insert_id);
				}
				D('Merchant')->reg_notice($insert_id);
				
				if($this->config['merchant_verify']){
					exit(json_encode(array('error'=>'0','msg'=>'注册成功,请耐心等待审核或联系工作人员审核。~','dom_id'=>'phone')));
				}else{
					exit(json_encode(array('error'=>'0','msg'=>'注册成功,请登录~','dom_id'=>'phone')));
				}
			}else{
				exit(json_encode(array('error'=>'6','msg'=>'注册失败,请重试！','dom_id'=>'phone')));
			}
		}else{
			exit('deney Access !');
		}
	}
	public function logout(){
		session('merchant',null);
		header('Location: '.U('Login/index'));
	}
	public function verify(){
		$verify_type = $_GET['type'];
		if(empty($verify_type)){exit;}
		import('ORG.Util.Image');
		Image::buildImageVerify(4,1,'jpeg',53,26,'merchant_'.$verify_type.'_verify');
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