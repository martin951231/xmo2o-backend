<?php
class UserModel extends Model
{

	/*得到所有用户*/
	public function get_user($field_value,$field='uid')
	{
		$condition_user[$field] = $field_value;
		$now_user = $this->field(true)->where($condition_user)->find();
		if(!empty($now_user)){
			
			// 执行获取用户信息的钩子，若有执行钩子，再重新查询一次用户
			$hook_result = hook::hook_exec('user.get_user_before',['uid'=>$now_user['uid'],'user'=>$now_user]);
			if($hook_result){
				$now_user = $this->field(true)->where(['uid'=>$now_user['uid']])->find();
			}
			
			if(C('config.nmgzhcs_appid') && $now_user['nmgzhcs_openid']){
				$appid = C('config.nmgzhcs_appid') ;
				$appsecret = C('config.nmgzhcs_appsecret');

				$url= C('config.nmgzhcs_base_url') . '/api/member/memberinfo';

				$params=array(
					'appid'=>$appid,
					'openid'=>$now_user['nmgzhcs_openid'],
				);
				ksort($params);
				$paramsJoined = array();
				foreach($params as $param => $value) {
					$paramsJoined[] = "$param=$value";
				}
				$paramData = implode('&', $paramsJoined);

				$sign = strtoupper(md5($paramData.$appsecret));

				$params['sign'] = $sign;

				$res = httpRequest($url,'POST',$params);
				$res = json_decode($res[1],true);
				if($res['data']){
					$data_user['nickname'] = $res['name'];
					$data_user['avatar'] = $res['data']['head_img'] ? $res['data']['head_img'] : '';
					$data_user['phone'] = $res['mobile'];
					$data_user['now_money'] = $res['data']['money_sum'];
					$data_user['score_count'] = $res['data']['points_sum'];
					M('User')->where(array('uid'=>$now_user['uid']))->save($data_user);
					$now_user = $this->field(true)->where($condition_user)->find();
				}
			}
			
			if(C('config.open_frozen_money')==1) {
				if ($now_user['frozen_time'] < $_SERVER['REQUEST_TIME'] && $_SERVER['REQUEST_TIME'] < $now_user['free_time']) {
					$now_user['now_money'] -= $now_user['frozen_money'];
					$now_user['now_money'] = $now_user['now_money']<0?0:$now_user['now_money'];
				}
			}
			
			$now_user['can_withdraw_money'] = floatval($now_user['now_money'])- floatval($now_user['score_recharge_money']);
			if($now_user['can_withdraw_money'] < 0){
				$now_user['can_withdraw_money'] = 0;
			}
			
			if($now_user['phone']){
				if(strlen($now_user['phone']) == 11){
					$now_user['showPhone'] = substr($now_user['phone'],0,3).'****'.substr($now_user['phone'],7,4);
				}else if(strlen($now_user['phone']) == 10){
					$now_user['showPhone'] = substr($now_user['phone'],0,2).'****'.substr($now_user['phone'],7,4);
				}else if(strlen($now_user['phone']) == 9){
					$now_user['showPhone'] = substr($now_user['phone'],0,2).'****'.substr($now_user['phone'],7,3);
				}else if(strlen($now_user['phone']) == 8){
					$now_user['showPhone'] = substr($now_user['phone'],0,2).'***'.substr($now_user['phone'],7,3);
				}else if(strlen($now_user['phone']) == 7){
					$now_user['showPhone'] = substr($now_user['phone'],0,2).'***'.substr($now_user['phone'],7,2);
				}else{
					$now_user['showPhone'] = $now_user['phone'];
				}
				if($now_user['phone_country_type']){
					$now_user['showPhone'] = '+'.$now_user['phone_country_type'].' '.$now_user['showPhone'];
				}
			}else{
				$now_user['showPhone'] = '';
			}
			
			//处理头像
			if(!$now_user['avatar']){
				$now_user['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
			}else{
				$now_user['avatar'] = replace_file_domain($now_user['avatar']);
			}
			
			if($now_user['email']){
				$email_arr = explode('@',$now_user['email']);
				$now_user['showEmail'] = strlen($email_arr[0]) > 3 ? substr($email_arr[0],0,3) : (strlen($email_arr[0]) > 2 ? substr($email_arr[0],0,2) : substr($email_arr[0],0,1));
				$now_user['showEmail'].= '***@' . $email_arr[1];
			}else{
				$now_user['showEmail'] = '';
			}
			//判断生日是否正确
			if($now_user['birthday'] != '0000-00-00'){
				$birthYear = date('Y',strtotime($now_user['birthday']));
				if($birthYear < date('Y')-120){
					$now_user['birthday'] = '0000-00-00';
				}
			}

			$now_user['now_money'] = getFormatNumber($now_user['now_money']);
		}
		return $now_user;
	}
	/*账号密码检入*/
	public function checkin($phone,$pwd,$type=false,$smscode=false,$phone_country_type='',$check_pwd=true){

		if (empty($phone)){
			if($type){
				return array('error_code' => true, 'msg' => '20042001');
			}else{
				return array('error_code' => true, 'msg' => L_('账号或密码错误'));
			}
		}
		if (empty($pwd) && !$smscode && $check_pwd){
			if($type){
				return array('error_code' => true, 'msg' => '20042001');
			}else{
				return array('error_code' => true, 'msg' => L_('账号或密码错误'));
			}
		}
		if(C('config.open_school') == 1){
			$_complex = [
				'phone' => ['eq', $phone],
				'school_sfz' => ['eq', $phone],
				'school_no' => ['eq', $phone],
				'_logic' => 'or'
			];
			$sms_login_where['_complex'] = $_complex;
		}
		else{
			$sms_login_where = ['phone' => $phone];
		}
		if($phone_country_type && C('config.international_phone')){
			$sms_login_where['phone_country_type'] = $phone_country_type;
		}
		$now_user = $this->field(true)->where($sms_login_where)->find();
		
		if($smscode && $now_user){
			return array('error_code' => false, 'msg' => 'OK' ,'user'=>$now_user);
		}
		if ($now_user){
			$now_user = $this->get_user($now_user['uid']);
			//获取用户年龄
			$now_user['age'] = 0;
			$today_time = time();
			$birthday_time = strtotime($now_user['birthday']);
			if($now_user['birthday'] == '0000-00-00' || $birthday_time <= 0 || $today_time <= $birthday_time){
				$now_user['age'] = -1;
			}
			else{
				$now_user['age'] = ($today_time - $birthday_time)/(3600*24*365);
			}
			
			if($pwd && !$now_user['pwd']){
				if($type){
					return array('error_code' => true, 'msg' => L_('账号未设置密码，无法使用密码登录'));
				}else{
					return array('error_code' => true, 'msg' => L_('账号未设置密码，无法使用密码登录'));
				}
			}
			
			if($now_user['pwd'] != md5($pwd) && $now_user['pwd']){
                $loginLog = [
                    'logAccount' => $phone,
                    'logContent' => '密码输入错误',
                ];
			    D('User_login_log')->addLoginErrLog($loginLog);
				if($type){
					return array('error_code' => true, 'msg' => '20120007');
				}else{
					return array('error_code' => true, 'msg' => L_('账号或密码错误!'));
				}
			}
			if(empty($now_user['status'])){
				if($type){
					return array('error_code' => true, 'msg' => '20120008');
				}else{
					return array('error_code' => true, 'msg' => L_('该账号被禁止登录!'));
				}
			}
			if($now_user['status'] == 2){
				if($type){
					return array('error_code' => true, 'msg' => '20120008');
				}else{
					return array('error_code' => true, 'msg' => L_('该账号未审核，无法登录'));
				}
			}
			if($now_user['status'] == 4){
				if($type){
					return array('error_code' => true, 'msg' => '20120008');
				}else{
					return array('error_code' => true, 'msg' => L_('该账号（'.$now_user['uid'].'）已注销，无法登录'));
				}
			}

			$condition_save_user['uid'] = $now_user['uid'];
			$data_save_user['last_time'] = $_SERVER['REQUEST_TIME'];
			$data_save_user['last_ip'] = get_client_ip(0);
			/****判断此用户是否在user_import表中***/
			$user_importDb=D('User_import');
			$user_import=$user_importDb->where(array('telphone'=>$phone,'isuse'=>'0'))->find();
			if(!empty($user_import)){
			   !empty($user_import['ppname']) && $data_save_user['truename']=$user_import['ppname'];
			   $data_save_user['qq']=$user_import['qq'];
			   $data_save_user['email']=$user_import['email'];
			   $data_save_user['level']=max($now_user['level'],$user_import['level']);
			   $data_save_user['score_count']=max($now_user['score_count'],$user_import['integral']);
			   $data_save_user['now_money']=max($now_user['now_money'],$user_import['money']);
			   $data_save_user['importid']=$user_import['id'];
			   	if(C('config.reg_verify_sms')){
					$data_user['status'] = 1; //开启注册验证短信就不需要审核
				}else{
					$data_user['status'] = 2; /*             * *未审核*** */

				}
			   $mer_id=$user_import['mer_id'];
			   $data_save_user['openid']=isset($_SESSION['weixin']) && isset($_SESSION['weixin']['user']) ? $_SESSION['weixin']['user']['openid'] :'';
			   if(($mer_id>0) && !empty($data_save_user['openid'])){
			      $merchant_user_relationDb=M('Merchant_user_relation');
				  $mwhere=array('openid'=>$data_save_user['openid'],'mer_id'=>$mer_id);
				  $mtmp=$merchant_user_relationDb->where($mwhere)->find();
				  if(empty($mtmp)){
					 $mwhere['dateline']=time();
					 $mwhere['from_merchant']=3;
				     $merchant_user_relationDb->add($mwhere);
				  }
			   }
			}

			if($this->where($condition_save_user)->data($data_save_user)->save()){
			    if(!empty($user_import)){
			    	if ($now_user['now_money']<$user_import['money']) {
						// 增加余额记录
		                D('User_money_list')->add_row($now_user['uid'], 1, $user_import['now_money']-$now_user['now_money'], L_('增加余额'),1);
                    	D('User_score_list')->add_row($now_user['uid'],1,$user_import['integral']-$now_user['score_count'],L_('增加积分'));
			    	}
				   $user_importDb->where(array('id'=>$user_import['id']))->save(array('isuse'=>1));
				}


				$database_house_village_user_bind = D('House_village_user_bind');
				$bind_where['uid'] = $now_user['uid'];
				$database_house_village_user_bind->where($bind_where)->data(array('phone'=>$_POST['phone']))->save();
			}
			return array('error_code' => false, 'msg' => 'OK' ,'user'=>$now_user);
		} else {
			if($type){
				return array('error_code' => true, 'msg' => '20120009');
			}else{
				return array('error_code' => true, 'msg' => L_('账号或密码错误!'));
			}
		}
	}
	/*手机号、union_id、open_id 直接登录入口*/
	public function autologin($field,$value){
		$now_user = $this->get_user($value, $field);
		if($now_user){
			if(empty($now_user['status'])){
				return array('error_code' => true, 'msg' => L_('该账号被禁止登录！请联系平台管理员'));
			}
			if($now_user['status'] == 2){
				return array('error_code' => true, 'msg' => L_('该账号未审核，无法登录!'));
			}
			if($now_user['status'] == 4){
				return array('error_code' => true, 'msg' => L_('该账号（'.$now_user['uid'].'）被禁止，无法登录！请联系平台管理员'));
			}
			$condition_save_user['uid'] = $now_user['uid'];
			$data_save_user['last_time'] = $_SERVER['REQUEST_TIME'];
			$data_save_user['last_ip'] = get_client_ip(0);
			$this->where($condition_save_user)->data($data_save_user)->save();
			
			$this->check_score($now_user['uid']);

			//获取用户年龄
			$now_user['age'] = 0;
			$today_time = time();
			$birthday_time = strtotime($now_user['birthday']);
			if($now_user['birthday'] == '0000-00-00' || $birthday_time <= 0 || $today_time <= $birthday_time){
				$now_user['age'] = -1;
			}
			else{
				$now_user['age'] = ($today_time - $birthday_time)/(3600*24*365);
			}
			
			return array('error_code' => false, 'msg' => 'OK' ,'user'=>$now_user);
		}else{
			return array('error_code'=>1001,'msg'=>L_('账号或密码错误！'));
		}
	}
	/*
	 *	提供用户信息注册用户，密码需要自行md5处理
	 *
	 *	**** 请自行处理逻辑，此处直接插入用户表 ****
	 */
	public function autoreg($data_user,$is_app=false){
		if($data_user['openid'] && $res = $this->where(array('openid'=>$data_user['openid']))->find()){
			if($res['status']==0 || $res['status'] == 4){
				return array('error_code' => true, 'msg' => L_('您已被禁止登录') . '：' . $res['uid']);
			}
			return array('error_code' => true, 'msg' => L_('您已经注册过了') . '：' . $res['uid']);
		}
		$data_user['add_time'] = $data_user['last_time'] = $_SERVER['REQUEST_TIME'];
		$data_user['add_ip'] = $data_user['last_ip'] = get_client_ip(0);
		$data_user['status'] = 1;
		$data_user['score_clean_time'] = $_SERVER['REQUEST_TIME'];

		if($data_user['openid']){
			$data_user['last_weixin_time'] = $_SERVER['REQUEST_TIME'];
		}
		if($data_user['union_id']){
			$res = $this->where(array('union_id'=>$data_user['union_id']))->find();
			if($res){
				if($res['status'] == 0 || $res['status'] == 4){
					return array('error_code' => true, 'msg' => L_('您已被禁止登录！请联系平台管理员'));
				}
				
				$this->save_user($res['uid'],'openid',$data_user['openid']);
				
				return array('error_code' =>false,'msg' =>'OK','uid'=>$res['uid']);
			}
		}
		//判断手机号存在时即直接返回用户
		$data_user['phone'] = trim($data_user['phone']);
		if($data_user['phone']){
			$where_phone = [
				'phone' => $data_user['phone']
			];
			
			//如果有区号则组合查询，中国区号除外
			if($data_user['phone_country_type'] && $data_user['phone_country_type'] != 86){
				$where_phone['phone_country_type'] = $data_user['phone_country_type'];
			}
			$res = $this->where($where_phone)->find();
			if($res){
				if($res['status']==0 || $res['status']==4){
					return array('error_code' => true, 'msg' => L_('您已被禁止登录！请联系平台管理员'));
				}
				
				if($data_user['openid']){
					$this->save_user($res['uid'], 'openid', $data_user['openid']);
				}
				
				return array('error_code' =>false,'msg' =>'OK','uid'=>$res['uid']);
			}
		}
		
		
		/****判断此用户是否在user_import表中***/
		if($data_user['phone']){
			$user_importDb=D('User_import');
			$user_import=$user_importDb->where(array('telphone'=>$data_user['phone'],'isuse'=>'0'))->find();
			if(!empty($user_import)){
				$data_user['truename'] = $user_import['ppname'];
				$data_user['qq'] = $user_import['qq'];
				$data_user['email'] = $user_import['email'];
				$data_user['level'] = $user_import['level'];
				$data_user['score_count'] = $user_import['integral'];
				$data_user['now_money'] = $user_import['money'] ? $user_import['money'] : 0;
				$data_user['importid'] = $user_import['id'];
				$data_user['youaddress'] = !empty($user_import['address']) ? str_replace('|', ' ', $user_import['address']) : '';
			}
		}
		if(C('config.live_show_new_user_open') == '1'){
			$data_user['open_live_show'] = 1;
		}
		if(C('config.video_show_new_user_open') == '1'){
			$data_user['open_live_video'] = 1;
		}
		
		if($uid = $this->data($data_user)->add()){
			//如果有openid，则将推广表的uid自动填充一次
			if($data_user['openid']){
				M('User_spread')->where(['openid' => $data_user['openid']])->data(['uid'=>$uid])->save();
			}
			//如果没有openid，且有wxapp_openid，则将推广表的uid自动填充一次
			if(!$data_user['openid'] && $data_user['wxapp_openid']){
				M('User_spread')->where(['openid' => $data_user['wxapp_openid']])->data(['uid'=>$uid])->save();
			}
			
			if(!empty($user_import)){
				$user_importDb->where(array('id'=>$user_import['id']))->save(array('isuse'=>2));
			}
			$this->register_give_money($uid,$is_app);
			$spread_user_give_type = C('config.spread_user_give_type');
			if($spread_user_give_type!=3){
 				$now_user_spread = M('User_spread')->where(array('uid'=>$uid))->find();
  				if($now_user_spread) {
  					$spread_user = $this->get_user($now_user_spread['spread_uid']);
  					$now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();
  					if ($spread_user_give_type == 0 ||$spread_user_give_type == 2) {
						$spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']: C('config.spread_give_money');
						if(C('config.open_score_fenrun')){
							$this->add_money($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'),'','',$uid);
						}else{
							$this->add_money($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'));
						}
  						D('Scroll_msg')->add_msg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠平台余额X3",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $spread_give_money.C('config.Currency_txt')))); 
  					}
  
 					if ($spread_user_give_type == 1 || $spread_user_give_type == 2) {
						$spread_give_score = $now_level['spread_user_give_score']>0?$now_level['spread_user_give_score']: C('config.spread_give_score');
						$this->add_score($spread_user['uid'], $spread_give_score, L_('推荐新用户注册平台赠送') . C('config.score_name'));
						if($spread_give_score>0){
							D('Scroll_msg')->add_msg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠X3个",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => C('config.score_name').$spread_give_score))); 
						}
  					}
  
				}
			}

            if(C('customization.internal_membership_card') && !empty($data_user['phone'])){
                D('Internal_membership_card')->where(['phone' => $data_user['phone']])->save(['uid' => $uid]);
            }
            
			return array('error_code' =>false,'msg' =>'OK','uid'=>$uid);
		}else{
			return array('error_code' => true, 'msg' => L_('注册失败！请重试。'));
		}
	}

	public function register_give_money($uid,$is_app=false){
		$register_give_money_condition = C('config.register_give_money_condition');
		if ($register_give_money_condition == 2 || $register_give_money_condition == 4 || ($is_app && $register_give_money_condition==3)) {
			$register_give_money_type = C('config.register_give_money_type');
			if($register_give_money_type=='0' ||$register_give_money_type==2 ){
				$this->add_money($uid, C('config.register_give_money'), L_('新用户注册平台赠送余额'));
				$this->add_score_recharge_money($uid, C('config.register_give_money'), L_('新用户注册平台赠送余额'));
			}
			if($register_give_money_type==1 ||$register_give_money_type==2 ){
				$this->add_score($uid,C('config.register_give_score'), L_('新用户注册平台赠送').C('config.score_name'));
			}
		}
	}

	public function autoreg_by_scan_merchant_qrcode($data_user){
		$data_user['add_time'] = $data_user['last_time'] = $_SERVER['REQUEST_TIME'];
		//$data_user['add_ip'] = $data_user['last_ip'] = get_client_ip(0);
		$data_user['status'] = 1;
		$data_user['score_clean_time'] = $_SERVER['REQUEST_TIME'];

		if($data_user['openid']){
			$data_user['last_weixin_time'] = $_SERVER['REQUEST_TIME'];
		}
		if($uid = $this->data($data_user)->add()){
			$this->register_give_money($uid);

			$spread_user_give_type = C('config.spread_user_give_type');
			if($spread_user_give_type!=3){
 				$now_user_spread = M('User_spread')->where(array('uid'=>$uid))->find();
  				if($now_user_spread) {
  					$spread_user = $this->get_user($now_user_spread['spread_uid']);
  					$now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();
  					if ($spread_user_give_type == 0 ||$spread_user_give_type == 2) {
						$spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']: C('config.spread_give_money');
						if(C('config.open_score_fenrun')){
							$this->add_money($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'),'','',$uid);
						}else{
							$this->add_money($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'));
						}
  						D('Scroll_msg')->add_msg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠平台余额X3",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $spread_give_money.C('config.Currency_txt')))); 
  					}
  
 					if ($spread_user_give_type == 1 || $spread_user_give_type == 2) {
						$spread_give_score = $now_level['spread_user_give_score']>0?$now_level['spread_user_give_score']: C('config.spread_give_score');
						$this->add_score($spread_user['uid'], $spread_give_score, L_('推荐新用户注册平台赠送') . C('config.score_name'));
						if($spread_give_score>0){
							D('Scroll_msg')->add_msg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠X3个",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => C('config.score_name').$spread_give_score))); 
						}
  					}
  
				}
			}
			return array('error_code' =>false,'msg' =>array('uid'=>$uid));
		}else{
			return array('error_code' => true, 'msg' => L_('注册失败！请重试。'));
		}
	}

	/*账号密码注册*/
	public function checkreg($phone,$pwd,$phone_country_type=''){
		if (empty($phone)) {
			return array('error_code' => true, 'msg' => L_('手机号不能为空'));
		}
		if (empty($pwd)) {
			return array('error_code' => true, 'msg' => L_('密码不能为空'));
		}

		if(!C('config.international_phone') && !preg_match('/^[0-9]{11}$/',$phone)){
			return array('error_code' => true, 'msg' => L_('请输入有效的手机号'));
		}

		$condition_user['phone'] = $phone;
		if(C('config.international_phone')){
			$condition_user['phone_country_type'] = $phone_country_type;
			$condition_user['phone'] = phone_format($phone_country_type, $condition_user['phone']);
		}
		if($this->field('`uid`')->where($condition_user)->find()){
			return array('error_code' => true, 'msg' => L_('手机号已存在'));
		}

		$data_user['phone'] = $phone;
		$data_user['pwd'] = md5($pwd);
		$data_user['status'] = 1;
		$data_user['nickname'] = substr($phone,0,3).'****'.substr($phone,7);

		$data_user['add_time'] = $data_user['last_time'] = $_SERVER['REQUEST_TIME'];
		$data_user['add_ip'] = $data_user['last_ip'] = get_client_ip(0);
		$data_user['score_clean_time'] = $_SERVER['REQUEST_TIME'];
		$phone_country_type && $data_user['phone_country_type'] =$phone_country_type;
		if($uid = $this->data($data_user)->add()){
			$this->register_give_money($uid);

			$spread_user_give_type = C('config.spread_user_give_type');

			if($spread_user_give_type!=3){
 				$now_user_spread = M('User_spread')->where(array('uid'=>$uid))->find();
  				if($now_user_spread) {
  					$spread_user = $this->get_user($now_user_spread['spread_uid']);
  					$now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();
  					if ($spread_user_give_type == 0 ||$spread_user_give_type == 2) {
						$spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']: C('config.spread_give_money');
						if(C('config.open_score_fenrun')){
							$this->add_money($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'),'','',$uid);
						}else{
							$this->add_money($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'));
						}
  						D('Scroll_msg')->add_msg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠平台余额X3",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $spread_give_money.C('config.Currency_txt')))); 
  					}
  
 					if ($spread_user_give_type == 1 || $spread_user_give_type == 2) {
						$spread_give_score = $now_level['spread_user_give_score']>0?$now_level['spread_user_give_score']: C('config.spread_give_score');
						$this->add_score($spread_user['uid'], $spread_give_score, L_('推荐新用户注册平台赠送') . C('config.score_name'));
						if($spread_give_score>0){
							D('Scroll_msg')->add_msg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠X3个",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => C('config.score_name').$spread_give_score))); 
						}
  					}
				}
			}
			$return = $this->checkin($phone,$pwd,false,false,$phone_country_type);

			if(empty($result['error_code'])){
    			return $return;
    		}else{
				return array('error_code' =>false,'msg' =>'OK');
			}
		}else{
			return array('error_code' => true, 'msg' => L_('注册失败！请重试。'));
		}
	}

	public function check_phone($phone){
		$condition_user['phone'] = $phone;
		if($this->field('`uid`')->where($condition_user)->find()){
			return array('error_code' => true, 'msg' => L_('手机号已存在'));
		}
	}
	/*修改用户信息*/
	public function save_user($uid,$field,$value){
		if(!$uid){
			return array('error'=>1,'msg'=>L_('请求参数必须携带 uid'));
		}
		$condition_user['uid'] = $uid;
		$data_user[$field] = $value;
		if($this->where($condition_user)->data($data_user)->save()){
			return array('error'=>0,$field=>$value);
		}else{
			return array('error'=>1,'msg'=>L_('修改失败！请重试。'));
		}
	}
	/*修改用户信息*/
	public function scenic_save_user($where,$data){
		if(empty($where)){
			return 0;
		}
		if(!is_array($where)){
			return 0;
		}
        if (isset($data['add_time'])) {
            unset($data['add_time']);
        }
		$save	=	$this->where($where)->data($data)->save();
		if($save){
			return 1;
		}else{
			return 0;
		}
	}

	/*增加用户的钱*/
	public function add_money($uid,$money,$desc,$ask=0,$ask_id=0,$type_id=0,$is_live = 0){
		if($money>0){
			
			if(C('config.mdd_api_url')){
				// mdd 用户查询
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->select($uid);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 用户查询完
		
				// mdd 扣款
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->add_money($uid,$money,$desc);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 扣款完
			}
			
			$condition_user['uid'] = $uid;
			$now_user = $this->get_user($uid);
			if($type_id>0){
				D('Fenrun')->add_recommend_award($uid,$type_id,1,$money,$desc);
			}else {
				$zbw_sync =false;
				if(C('config.zbw_key')){

					if($now_user['zbw_cardid']){
						$result = D('ZbwErp')->VipFullAmt($now_user,$money,$desc);

						if(!$result){
							return array('error_code' => true, 'msg' => L_('智百威卡余额充值失败，请联系管理员'));
						}
						if(!$result['result'] ){
							return array('error_code' => true, 'msg' => $result['err']);
						}else{
							D('ZbwErp')->sync_data($uid);
							$zbw_sync = true;
						}

					}
				}
				if ($this->where($condition_user)->setInc('now_money', $money)) {
					if(!$zbw_sync) {
						D('User_money_list')->add_row($uid, 1, $money, $desc, true, $ask, $ask_id,false,0,$is_live);
					}
					
					//执行增加用户余额后的钩子
					$hook_result = hook::hook_exec('user.add_user_money_after',['uid'=>$now_user['uid'],'user'=>$now_user,'money'=>$money,'desc'=>$desc]);

					//商盈通对接
        			$shangyingtong = D("UserShangyingtong");
        			$shangyingtong->recharge($uid,$money);
					
					if(C('config.nmgzhcs_appid') && $now_user['nmgzhcs_openid']){
						$this->nmgzhcs_money($now_user['nmgzhcs_openid'],$money,$desc);
					}
					
					if($now_user['openid'] && (strpos($desc,L_('广用户')) || $desc==L_('在线充值'))) {
					    $is_spread = 0;
						if(strpos($desc,L_('广用户'))){
						    $is_spread = 1;
							$money_type  =L_('用户推广佣金结算');
						}else{
							$money_type  =L_('平台余额在线充值');
						}

                        //微信小程序订阅消息提醒
                        $wxapp_message_send = false;
                        if($is_spread && $now_user['wxapp_openid']){
                            $param['page'] = '/pages/plat_menu/index?redirect=webview&webview_url='.urlencode('/wap.php?g=Wap&c=My&a=my_money');
                            $param['wxapp_openid'] = $now_user['wxapp_openid'];
                            $param['type'] = 'add_spread_money';

                            $data_info['price'] = $money;
                            $data_info['time'] = time();
                            $data_info['note'] = L_('你获得的佣金已存入账户，请及时查收~');
                            $param['data_info'] = $data_info;
                            $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
                        }

						if($is_spread==0 || !$wxapp_message_send){
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $href = C('config.site_url') . '/wap.php?c=My&a=transaction';

							$newInfo = [
								'tempKey' => '45572',
								'thing20' => '平台余额账户',
								'amount3' => $money,
								'amount15' => getFormatNumber($now_user['now_money'] + $money),
								'time2' => date('Y-m-d H:i:s')
							];
                            $model->sendTempMsg('OPENTM401833445', array('href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => L_("尊敬的X1，您的平台余额账户发生变动",array("X1" => $now_user['nickname'])),
                                'keyword1' => date('Y-m-d H:i'),
                                'keyword2' => $money_type,
                                'keyword3' => '+'.$money,
                                'keyword4' => $now_user['now_money'] + $money,
                                'remark' => L_('详情请点击此消息进入会员中心-余额记录进行查询!'),
								'new_info' => $newInfo),
                                0);
                        }
					}
					return array('error_code' => 0, 'msg' => 'OK');
				} else {
					return array('error_code' => 1, 'msg' => L_('用户余额充值失败！请联系管理员协助解决。'));
				}
			}
		}else{
			return array('error_code' => 1, 'msg' => L_('充值金额有误'));
		}
	}

	public function add_score_recharge_money($uid,$money,$desc){
		if($money>0){
			$condition_user['uid'] = $uid;
			if($this->where($condition_user)->setInc('score_recharge_money',$money)){
				D('User_money_list')->add_row($uid,1,$money,$desc);
				return array('error_code' =>false,'msg' =>'OK');
			}else{
				return array('error_code' => true, 'msg' => L_("用户X1兑换余额保存记录失败！请联系管理员协助解决。",array("X1" => C('config.score_name')))); 
			}
		}else{
			return array('error_code' => 1, 'msg' => L_('充值金额有误'));
		}
	}

    /**
     * 使用用户企业预存款
     * @param $uid
     * @param $money
     * @param $desc
     */
    public function use_qiye_money($uid, $money, $orderId, $desc, $orderType = '', $leftUseBalance = true)
    {
        $staff = M('Qiye_staff')->where(['job_number' => $uid])->find();
        $user = M('User')->where(['uid' => $uid])->find();
        $qiye = M('Qiye')->where(['qiye_id' => $staff['qiye_id']])->find();

        //企业预存款余额不足，不足部分使用平台余额扣除
        if ($leftUseBalance) {
            if (!$staff || !$user || $staff['money'] + $user['now_money'] < $money) {
                return ['error_code' => true, 'msg' => L_('用户企业预存款余额和平台余额总金额不足')];
            }
            if ($staff['money'] >= $money) {
                $takeoffQiyeMoney = $money;
                $takeoffBalanceMoney = 0;
            } else {
                $takeoffQiyeMoney = $staff['money'];
                $takeoffBalanceMoney = $money - $takeoffQiyeMoney;
            }
        } else {
            if (!$staff || $staff['money'] < $money) {
                return ['error_code' => true, 'msg' => L_('用户企业预存款余额不足')];
            }
            $takeoffQiyeMoney = $money;
            $takeoffBalanceMoney = 0;
        }


        $mod = M();
        $mod->startTrans();
        try {
            //扣除企业预存款
            if ($takeoffQiyeMoney > 0) {
                if (M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setDec('money', $takeoffQiyeMoney)) {
                    if ($orderType == 'micro') {
                        //新大陆扫码支付
                        $order = M('Micropay_order')->where(['id' => $orderId])->field('orderid as real_orderid,0 as store_id')->find();
                    } else if ($orderType == 'shop') {
                        //快店
                        $order = M('Shop_order')->where(['order_id' => $orderId])->field('real_orderid,store_id')->find();
                    } else if ($orderType == 'store') {
                        //到店付
                        $order = M('Store_order')->where(['order_id' => $orderId])->field('orderid as real_orderid,store_id')->find();
                    }

                    $log = [
                        'staff_id' => $staff['staff_id'],
                        'uid' => $uid,
                        'type' => 2,
                        'money' => $takeoffQiyeMoney,
                        'time' => time(),
                        'ip' => get_client_ip(),
                        'desc' => $desc,
                        'order_id' => $orderId,
                        'real_orderid' => isset($order['real_orderid']) ? $order['real_orderid'] : '',
                        'store_id' => isset($order['store_id']) ? $order['store_id'] : '',
                        'order_type' => $orderType,
                        'now_money' => $staff['money'] - $takeoffQiyeMoney
                    ];
                    M('Qiye_staff_money_list')->add($log);

                } else {
                    throw new \Exception(M('Qiye_staff')->getError());
                }
            }
            //扣除平台余额
            if ($takeoffBalanceMoney > 0) {
                $balanceRs = $this->user_money($uid, $takeoffBalanceMoney, L_('企业预存款不足，使用平台余额'));
                if ($balanceRs['error_code']) {
                    throw new \Exception($balanceRs['msg']);
                }
            }
            $mod->commit();
            $retval = [
                'balance_money' => $takeoffBalanceMoney,
                'qiye_money' => $takeoffQiyeMoney,
                'qiye_settlement_ratio' => $qiye['settlement_ratio'],
                'qiye_settlement_money' => round($takeoffQiyeMoney * $qiye['settlement_ratio']/100, 2)
            ];
            return ['error_code' => false, 'retval' => $retval];
        } catch (\Exception $e) {
            $mod->rollback();
            fdump_sql($e->getMessage(), 'use_qiye_money_fail');
            return ['error_code' => true, 'msg' => L_('企业预存款余额扣除失败！请联系管理员协助解决。')];
        }
    }

	/*使用用户的钱*/
	public function user_money($uid,$money,$desc,$ask=0,$ask_id=0,$withdraw=0){
		if($money>0){

			if(C('config.mdd_api_url')){
				// mdd 用户查询
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->select($uid);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 用户查询完
				// mdd 扣款
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->use_money($uid,$money,$desc);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 扣款完
			}
			
			$zbw_sync =false;
			if(C('config.zbw_key')){
				$now_user = $this->where(array('uid'=>$uid))->find();
				if($now_user['zbw_cardid']){
					$zbw_card = D('ZbwErp')->GetVipInfo($now_user['zbw_cardid']);
					if($zbw_card['money']!=$now_user['now_money']){
						return array('error_code' => true, 'msg' => L_('智百威卡余额不足扣款失败，请联系管理员'));
					}
					$result = D('ZbwErp')->VipPaySheet($now_user,$money,$desc);
					if(!$result){
						return array('error_code' => true, 'msg' => L_('智百威卡余额扣款失败，请联系管理员'));
					}
					if(!$result['result'] ){
						return array('error_code' => true, 'msg' => $result['err']);
					}else{
						D('ZbwErp')->sync_data($uid);
						$zbw_sync = true;
					}

				}
			}
			$now_user = $this->get_user($uid);
			if($now_user['now_money']<$money){
				return array('error_code' => true, 'msg' => L_('用户余额扣除失败！请联系管理员协助解决。'));
			}
			$condition_user['uid'] = $uid;
			if($this->where($condition_user)->setDec('now_money',$money)){

				//商盈通对接
    			$shangyingtong = D("UserShangyingtong");
    			$shangyingtong->consume($uid,$money);
    			
				$score_recharge_money = $this->where($condition_user)->getField('score_recharge_money');
				if($score_recharge_money>0 && $withdraw==0){
					$now_score_recharge_money = $score_recharge_money>$money?$money:$score_recharge_money;
					$this->where($condition_user)->setDec('score_recharge_money',$now_score_recharge_money);
					D('User_money_list')->add_row($uid,2,$now_score_recharge_money,L_("X1兑换余额记录减扣 X2",array("X1" => C('config.score_name'),"X2" => $now_score_recharge_money.C('config.Currency_txt'))),true,$ask,$ask_id);
				}
				if(!$zbw_sync){
					D('User_money_list')->add_row($uid,2,$money,$desc,true,$ask,$ask_id);
				}
				
				//执行减少用户余额后的钩子
				$hook_result = hook::hook_exec('user.use_user_money_after',['uid'=>$now_user['uid'],'user'=>$now_user,'money'=>$money,'desc'=>$desc]);
				
				if(C('config.nmgzhcs_appid') && $now_user['nmgzhcs_openid']){
					$this->nmgzhcs_money($now_user['nmgzhcs_openid'],-1 * $money,$desc);
				}
				
				return array('error_code' =>false,'msg' =>'OK');
			}else{
                fdump_sql($this->getError(), 'ID1016266');
				return array('error_code' => true, 'msg' => L_('用户余额扣除失败！请联系管理员协助解决。'));
			}
		}else{
			return array('error_code' => true, 'msg' => L_('余额数据有误'));
		}
	}


	/*增加用户的积分*/
	public function add_score($uid,$score,$desc,$clean=0,$param=array(),$is_erp_callback = false){
		fdump_api(func_get_args(),'user_add_score',true);
		if(C('config.score_round_two')==1){
			$score = sprintf('%.2f',floatval($score));
		}else{
			$score = round($score);
		}

		if($score>0){

			if(C('config.mdd_api_url')){
				// mdd 用户查询
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->select($uid);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 用户查询完
				
				// mdd 增加积分
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->add_score($uid,$score,$desc);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 增加积分完
			}
			
			if(C('config.uni_url')){
				import('@.ORG.uni_user');
				$uni_user = new uni_user();
				$uni_result = $uni_user->select($uid);
				if($uni_result['error_code']){
					return array('error_code' => true, 'msg' => $uni_result['msg']);
				}
				
				import('@.ORG.uni_user');
				$uni_user = new uni_user();
				$uni_result = $uni_user->add_score($uid,$score,$desc);
				if($uni_result['error_code']){
					return array('error_code' => true, 'msg' => $uni_result['msg']);
				}
			}
			
			$zbw_sync =false;
			if(C('config.zbw_key')){
				$now_user = $this->get_user($uid);
				if($now_user['zbw_cardid']){
					$result = D('ZbwErp')->VipSaleSheet($now_user,$score,L_('获得系统积分'));
					D('ZbwErp')->sync_data($now_user['uid']);

				}
			}

			//2020/10/10 start 业主房屋名下所有住客所得积分 归业主所有
            //小区积分开关 （1开启 0关闭）
            $house_score_switch = C('config.house_score_switch');
            $score_count_res = 0;
            //小区积分开关开启则执行
            if($house_score_switch == 1 ){
                $score_count = $this->houseIntegral($uid,$score,1);//给业主增加积分
                $score_count_res = $score_count['owner_uid'];
                //给业主增加积分记录
                if($score_count['owner_uid']) {
                    $uid = $score_count['owner_uid'];
                }
                //租客/家人 消费获得积分归业主所有，记录随之改变
                if($score_count['owner_desc']){
                    $desc = $desc.$score_count['owner_desc'];
                }
            }
            if(!$score_count_res)
            {
                $condition_user['uid'] = $uid;
                $score_count_res = $this->where($condition_user)->setInc('score_count',$score);
            }
			//2020/10/10 end

			if($score_count_res){
				if(!$zbw_sync){
					D('User_score_list')->add_row($uid,1,$score,$desc,1,$clean,0,$param,$is_erp_callback);
				}
				//执行增加用户积分后的钩子
				empty($now_user) && $now_user = $this->where(['uid'=>$uid])->find();
				$hook_result = hook::hook_exec('user.add_user_score_after',['uid'=>$now_user['uid'],'user'=>$now_user,'score'=>$score,'desc'=>$desc]);
				
				if(C('config.nmgzhcs_appid')){
					$now_user = $this->get_user($uid);
					if($now_user['nmgzhcs_openid']){
						$this->nmgzhcs_score($now_user['nmgzhcs_openid'],$score,$desc);
					}
				}
				
				if(C('config.uni_score_url')){
					
				}
                //微信小程序订阅消息提醒
                $wxapp_message_send = false;
                if($now_user['wxapp_openid']){
                    $param['page'] = '/pages/plat_menu/index?redirect=webview&webview_url='.urlencode('/wap.php?g=Wap&c=My&a=my_money');
                    $param['wxapp_openid'] = $now_user['wxapp_openid'];
                    $param['type'] = 'add_score';

                    $data_info['score'] = $score;
                    $data_info['real_orderid'] = $param['order_id'] ?:'';
                    $data_info['score_count'] = getFormatNumber($now_user['score_count']+$score);
                    $data_info['note'] = L_('消费商品获得积分');
                    $data_info['time'] = time();
                    $param['data_info'] = $data_info;
                    $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
                }
				
				return array('error_code' =>false,'msg' =>C('config.score_name').L_('增加成功！'),'score'=>$score);
			}else{

				return array('error_code' => true, 'msg' => L_("添加X1失败！请联系管理员协助解决。",array("X1" => C('config.score_name')))); 
			}
		}else{
			return array('error_code' => true, 'msg' => C('config.score_name').L_('数据有误'));
		}
	}
	//小区积分 业主 租客 消费积分 增/减 2020/10/09
	public function houseIntegral($uid,$score,$type)
    {
        $where['uid'] = $uid;
        $where['status'] = ['eq',1];
        //$where['parent_id'] = ['eq',0];//查询业主
        $where['type'] = ['in',[0,3]];//查询业主
        $house_user_owner = D('House_village_user_bind')->where($where)
            ->field('pigcms_id,uid,name,phone,parent_id,type,add_time')
            ->order('add_time asc')->find();
        $owner_uid = 0;//初始化业主id
        $desc = '';
        //增加积分时 不是业主情况下查询用户所在房间绑定的业主
        if(!$house_user_owner && $type == 1){
            unset($where['parent_id']);
            unset($where['type']);
            $where['type'] = ['in','1,2'];
            $house_user_tenant = D('House_village_user_bind')->where($where)
                ->field('pigcms_id,uid,name,phone,parent_id,type,add_time')
                ->order('add_time asc')->find();
            if($house_user_tenant)
            {
                $map['pigcms_id'] = $house_user_tenant['parent_id'];
                $house_user_owner = D('House_village_user_bind')->where($map)
                    ->field('pigcms_id,uid,name,phone,parent_id,type,add_time')
                    ->order('add_time asc')->find();
                if($house_user_owner){
                    if(!$house_user_owner['uid'] && $house_user_owner['phone']){
                        $user_owner_uid = D('User')->where(['phone'=>$house_user_owner['phone']])->getField('uid');
                        if($user_owner_uid) {
                            $house_user_owner['uid'] = $user_owner_uid;
                            D('House_village_user_bind')->where(['pigcms_id'=>$house_user_owner['pigcms_id']])
                                ->data(['uid'=>$user_owner_uid])->save();
                        }
                    }elseif (!$house_user_owner['uid'] && !$house_user_owner['phone'] && $house_user_owner['name']){
                        $data=['name'=>$house_user_owner['name']];
                        D('User')->data($data)->add();
                        $insert_id = D('User')->getLastInsID();
                        if($insert_id) {
                            $house_user_owner['uid'] = $insert_id;
                            D('House_village_user_bind')->where(['pigcms_id' => $house_user_owner['pigcms_id']])
                                ->data(['uid' => $insert_id])->save();
                        }
                    }
                }
                //有姓名或者手机号则表示不是虚拟业主
                if($house_user_owner['name'] || $house_user_owner['phone']) {
                    $owner_uid = $house_user_owner['uid'] ? $house_user_owner['uid'] : 0;
                    //取 租客/家人name
                    $owner_name = $house_user_tenant['name'];
                    $desc = ' 【'.$owner_name.'绑定为家属/租客，房主获得积分】';
                }
            }
        }else{
            //有姓名或者手机号则表示不是虚拟业主
            if((isset($house_user_owner['name']) && $house_user_owner['name']) || (isset($house_user_owner['phone']) || $house_user_owner['phone']))
            {
                $owner_uid = $house_user_owner['uid']?$house_user_owner['uid']:0;
            }
        }
        if($owner_uid)
        {
            $owner_where['uid'] = $owner_uid;
            if($type == 1)//增加积分
            {
                $this->where($owner_where)->setInc('score_count',$score);
                $this->where($owner_where)->setInc('house_integral',$score);//小区积分购买增加
            }else{//减少积分
                $this->where($owner_where)->setDec('score_count',$score);
                $this->where($owner_where)->setDec('house_integral',$score);//小区积分使用扣除
            }
        }
        $data['owner_uid'] = $owner_uid;
        $data['owner_desc'] = $desc;
        return $data;
    }
	
	/*使用用户的积分*/
	public function use_score($uid,$score,$desc,$param=array()){
		fdump_api(func_get_args(),'user_use_score',true);
		if($score>0){
			
			if(C('config.mdd_api_url')){
				// mdd 用户查询
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->select($uid);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 用户查询完
				
				// mdd 消耗积分
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->use_score($uid,$score,$desc);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 消耗积分完
			}
			
			if(C('config.uni_url')){
				import('@.ORG.uni_user');
				$uni_user = new uni_user();
				$uni_result = $uni_user->select($uid);
				if($uni_result['error_code']){
					return array('error_code' => true, 'msg' => $uni_result['msg']);
				}

				import('@.ORG.uni_user');
				$uni_user = new uni_user();
				$uni_result = $uni_user->use_score($uid,$score,$desc);
				if($uni_result['error_code']){
					return array('error_code' => true, 'msg' => $uni_result['msg']);
				}
			}
			
			$zbw_sync =false;
			if(C('config.zbw_key')){
				$now_user = $this->get_user($uid);
				if($now_user['zbw_cardid']){
					$zbw_card = D('ZbwErp')->GetVipInfo($now_user['zbw_cardid']);

					if($zbw_card['score']<$now_user['score_count']){
						return array('error_code' => true, 'msg' => L_('智百威卡积分不足扣款失败，请联系管理员'));
					}

					$result = D('ZbwErp')->VipSaleSheet($now_user,(-1*$score),L_('消费扣除积分'));

					D('ZbwErp')->sync_data($now_user['uid']);
					$zbw_sync =true;
				}
			}

            //2020/10/9 start 业主房屋名下所有住客所得积分 归业主所有
            //小区积分开关 （1开启 0关闭）
            $house_score_switch = C('config.house_score_switch');
            $score_count_res = 0;
            //小区积分开关开启则执行
            if($house_score_switch == 1) {
                $score_count = $this->houseIntegral($uid, $score, 0);//给业主减少积分
                $score_count_res = $score_count['owner_uid'];
            }
            if(!$score_count_res)
            {
                $condition_user['uid'] = $uid;
                $score_count_res = $this->where($condition_user)->setDec('score_count',$score);
            }
            //2020/10/9 end

//			$condition_user['uid'] = $uid;
//			if($this->where($condition_user)->setDec('score_count',$score)){
            if($score_count_res){
				if(!$zbw_sync){
					D('User_score_list')->add_row($uid,2,$score,$desc,1,0,0,$param);
				}
				
				//执行减少用户积分后的钩子
				empty($now_user) && $now_user = $this->where(['uid'=>$uid])->find();
				$hook_result = hook::hook_exec('user.use_user_score_after',['uid'=>$now_user['uid'],'user'=>$now_user,'score'=>$score,'desc'=>$desc]);
				
				if(C('config.nmgzhcs_appid')){
					$now_user = $this->get_user($uid);
					if($now_user['nmgzhcs_openid']){
						$this->nmgzhcs_score($now_user['nmgzhcs_openid'],-1 * $score,$desc);
					}
				}
				
				return array('error_code' =>false,'msg' =>C('config.score_name').L_('回退成功！'));
			}else{

				return array('error_code' => true, 'msg' => L_("添加X1失败！请联系管理员协助解决。",array("X1" => C('config.score_name')))); 
			}
		}else{
			return array('error_code' => true, 'msg' => C('config.score_name').L_('数据有误'));
		}
	}
	

	//这个方法增加的积分到一定时间会清零
	public function add_extra_score($uid,$score,$desc,$param=array()){
		if($score>0){

			$condition_user['uid'] = $uid;
			if($this->where($condition_user)->setInc('score_extra_count',$score) && $this->where($condition_user)->setInc('score_count',$score)){ //积分 跟 奖励积分同时增加，清理的时候同时减少
				PD('User_score_list')->add_row($uid,1,$score,$desc,true,false,0,$param);
				return array('error_code' =>false,'msg' =>C('config.score_name').L_('回退成功！'));
			}else{
				return array('error_code' => true, 'msg' => L_("添加X1失败！请联系管理员协助解决。",array("X1" => C('config.score_name')))); 
			}
		}else{
			return array('error_code' => true, 'msg' => C('config.score_name').L_('数据有误'));
		}
	}

	/*使用用户的积分*/
	public function user_score($uid,$score,$desc,$type=2,$is_erp_callback=false){
		if($score>0){
			
			if(C('config.mdd_api_url')){
				// mdd 用户查询
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->select($uid);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 用户查询完
				
				// mdd 消耗积分
				import('@.ORG.mdd_user');
				$mdd_user = new mdd_user();
				$mdd_result = $mdd_user->use_score($uid,$score,$desc);
				if($mdd_result['error_code']){
					return array('error_code' => true, 'msg' => $mdd_result['msg']);
				}
				// mdd 消耗积分完
			}
			
			if(C('config.uni_url')){
				import('@.ORG.uni_user');
				$uni_user = new uni_user();
				$uni_result = $uni_user->select($uid);
				if($uni_result['error_code']){
					return array('error_code' => true, 'msg' => $uni_result['msg']);
				}

				import('@.ORG.uni_user');
				$uni_user = new uni_user();
				$uni_result = $uni_user->use_score($uid,$score,$desc);
				if($uni_result['error_code']){
					return array('error_code' => true, 'msg' => $uni_result['msg']);
				}
			}
			
			$now_user = $this->get_user($uid);
			if($now_user['score_count']<$score){
				return array('error_code' => true, 'msg' => L_("减少X1失败！请联系管理员协助解决。",array("X1" => C('config.score_name')))); 
			}

            //2020/10/9 start 业主房屋名下所有住客所得积分 归业主所有
            //小区积分开关 （1开启 0关闭）
            $house_score_switch = C('config.house_score_switch');
            $score_count_res = 0;
            //小区积分开关开启则执行
            if($house_score_switch == 1) {
                $score_count = $this->houseIntegral($uid, $score, 0);//给业主减少积分
                $score_count_res = $score_count['owner_uid'];
            }
            $condition_user['uid'] = $uid;
            if(!$score_count_res)
            {
                $score_count_res = $this->where($condition_user)->setDec('score_count',$score);
            }
            //2020/10/9 end

//			$condition_user['uid'] = $uid;
//			if($this->where($condition_user)->setDec('score_count',$score)){
//                $dec_extra_score = $now_user['score_extra_count']<$score?$now_user['score_extra_count']:$score;
//                $this->where($condition_user)->setDec('score_extra_count',$dec_extra_score); //同时减少
//                D('User_score_list')->add_row($uid,$type,$score,$desc);

            if($score_count_res){
                $dec_extra_score = $now_user['score_extra_count']<$score?$now_user['score_extra_count']:$score;
                $this->where($condition_user)->setDec('score_extra_count',$dec_extra_score); //同时减少
                D('User_score_list')->add_row($uid, $type, $score, $desc, true, false, 0, [], $is_erp_callback);

				//执行减少用户积分后的钩子
				$hook_result = hook::hook_exec('user.use_user_score_after',['uid'=>$now_user['uid'],'user'=>$now_user,'score'=>$score,'desc'=>$desc]);
				
				if(C('config.nmgzhcs_appid')){
					if($now_user['nmgzhcs_openid']){
						$this->nmgzhcs_score($now_user['nmgzhcs_openid'],-1 * $score,$desc);
					}
				}
				
				return array('error_code' =>false,'msg' =>C('config.score_name').L_('回退成功！'));
			}else{
				return array('error_code' => true, 'msg' => L_("减少X1失败！请联系管理员协助解决。",array("X1" => C('config.score_name')))); 
			}
		}else{
			return array('error_code' => true, 'msg' => C('config.score_name').L_('数据有误'));
		}
	}

	/*public  function  check_new($phone,$cate_name){

		$user = $this->field('uid')->where(array('phone'=>$phone))->find();
		if(empty($user)){
			$user = $this->field('uid')->where(array('uid'=>$phone))->find();
		}
		$m = new Model();
		$table = array(C('DB_PREFIX').'group_order',C('DB_PREFIX').'meal_order',C('DB_PREFIX').'appoint_order',C('DB_PREFIX').'shop_order',C('DB_PREFIX').'foodshop_order',C('DB_PREFIX').'store_order');
		$count = 0;
		$where['uid']=$user['uid'];
		switch($cate_name){
			case 'all':
				foreach($table as  $v){
					$count += $m->table($v)->where($where)->count('order_id');
				}
				break;
			case 'group':
				$count  = $m->table($table[0])->where($where)->count('order_id');
				break;
			case 'meal':
				$count  = $m->table($table[1])->where($where)->count('order_id');
				break;
			case 'appoint':
				$count  = $m->table($table[2])->where($where)->count('order_id');
				break;
			case 'shop':
				$count  = $m->table($table[3])->where($where)->count('order_id');
				break;
			case 'foodshop':
				$count  = $m->table($table[4])->where($where)->count('order_id');
				break;
			case 'store':
				$count  = $m->table($table[5])->where($where)->count('order_id');
				break;
		}

		if($count>0){
			return 0;
		}else{
			return 1;
		}
	}*/
	
	public  function  check_new($phone,$cate_name){

		$user = $this->field('uid')->where(array('phone'=>$phone))->find();
		if(empty($user)){
			$user = $this->field('uid')->where(array('uid'=>$phone))->find();
		}
		$m = new Model();

		$db = $this->get_db();
		$count = 0;
		switch($cate_name){
			case 'all':
				foreach($db as  $v){
					$new_db = $v['db'];
					$where = array();
					$where['uid']=$user['uid'];
					$where[$v['name']] = $v['condition'];
					$count += $m->table($new_db)->where($where)->count('order_id');
				}
				break;
			case 'group':
				$new_db = $db['group'];
				$where = array();
				$where['uid']=$user['uid'];
				$where[$new_db['name']] = $new_db['condition'];
				$count  = $m->table($new_db['db'])->where($where)->count('order_id');
				break;
			case 'meal':
				$new_db = $db['meal'];
				$where = array();
				$where['uid']=$user['uid'];
				$where[$new_db['name']] = $new_db['condition'];
				$count  = $m->table($new_db['db'])->where($where)->count('order_id');
				break;
			case 'appoint':
				$new_db = $db['appoint'];
				$where = array();
				$where['uid']=$user['uid'];
				$where[$new_db['name']] = $new_db['condition'];
				$count  = $m->table($new_db['db'])->where($where)->count('order_id');
				break;
			case 'shop':
				$new_db = $db['shop'];
				$where = array();
				$where['uid']=$user['uid'];
				$where[$new_db['name']] = $new_db['condition'];
				$where['order_from'] = ['neq',1];//不是商城的订单
				$count  = $m->table($new_db['db'])->where($where)->count('order_id');
				break;
            case 'mall':
                $new_db = $db['mall'];
                $where = array();
                $where['uid']=$user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $count  = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
			case 'foodshop':
				$new_db = $db['foodshop'];
				$where = array();
				$where['uid']=$user['uid'];
				$where[$new_db['name']] = $new_db['condition'];
				$count  = $m->table($new_db['db'])->where($where)->count('order_id');
				break;
			case 'village_group':
				$new_db = $db['village_group'];
				$where = array();
				$where['uid']=$user['uid'];
				$where[$new_db['name']] = $new_db['condition'];
				$count  = $m->table($new_db['db'])->where($where)->count('order_id');
				break;
			case 'all':
				$new_db = $db['store'];
				$where = array();
				$where['uid']=$user['uid'];
				$where[$new_db['name']] = $new_db['condition'];
				$count  = $m->table($new_db['db'])->where($where)->count('order_id');
				break;
		}

		if($count>0){
			return 0;
		}else{
			return 1;
		}
	}
	
	//获取不同类型订单状态（存在这种订单状态的才算不是新用户）
	protected function get_db(){
		return array(
			'group' => array(
								'db' =>C('DB_PREFIX').'group_order',
								'name'=>'status',
								'condition'=>array('between',array('1','6'))
							),
			'meal' => array(
								'db'=>C('DB_PREFIX').'meal_order_log',
								'name'=>'status',
								'condition'=>array('eq',2),
							),
			'appoint' => array(
								'db'=>C('DB_PREFIX').'appoint_order',
								'name'=>'service_status',
								'condition'=>array('between',array('1','2'))
							),
			'shop' => array(
								'db'=>C('DB_PREFIX').'shop_order',
								'name'=>'status',
								'condition'=>array('between',array('2','4')),
							),
			'foodshop' => array(
								'db'=>C('DB_PREFIX').'foodshop_order',
								'name'=>'status',
								'condition'=>array('between',array('3','4'))
							),
			'store' =>array(
								'db'=>C('DB_PREFIX').'store_order',
								'name'=>'paid',
								'condition'=>array('eq',1)
							),
			'village_group' =>array(
								'db'=>C('DB_PREFIX').'village_group_order',
								'name'=>'status',
								'condition'=>array('in',[1,2,3,5,6,7,8,13])
							),
            'mall' => array(
                'db'=>C('DB_PREFIX').'mall_order',
                'name'=>'status',
                'condition'=>array('in',[30,31,32,33,40]),
            ),
		);
	}

	public function check_score_can_use($uid,$money,$order_type,$group_id=0,$mer_id=0){
		$now_user = $this->get_user($uid);
		$score_count = $now_user['score_count'];
		$score_can_use_count=0;
		$score_deducte=0;
		if ($order_type == 'group'||$order_type == 'meal'||$order_type == 'takeout'||$order_type == 'food'||$order_type == 'foodPad') {
			$user_score_use_condition = C('config.user_score_use_condition');
			$user_score_max_use = D('Percent_rate')->get_max_core_use($mer_id, $order_type);//不同业务不同积分
			if($order_type=='group'){
				$group_info = D('Group')->where(array('group_id'=>$group_id))->find();

				if($group_info['score_use']){
					if($group_info['group_max_score_use']!=0){
						$user_score_max_use = $group_info['group_max_score_use'];
					}
				}else{
					$user_score_max_use = 0;
				}
			}
			$user_score_use_percent=(float)C('config.user_score_use_percent');
			$score_max_deducte=bcdiv($user_score_max_use,$user_score_use_percent,2);


			if($user_score_use_percent>0&&$score_max_deducte>0&&$user_score_use_condition>0){   //如果设置没有错误
				if ($money>=$user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
					if($money>$score_max_deducte){                    //判断积分最大抵扣金额是否比这个订单的总额大
						$score_can_use_count = (int)($score_count>$user_score_max_use?$user_score_max_use:$score_count);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
						$score_deducte = bcdiv($score_can_use_count,$user_score_use_percent,2);
						$score_deducte = $score_deducte>$money?$money:$score_deducte;
					}else{
						//最大可抵扣的金额比总单金额大 只扣掉总单范围内的积分 扣除积分=总单*积分抵扣比例
						$score_can_use_count = ceil($money*$user_score_use_percent);
						$score_deducte = bcdiv($score_can_use_count,$user_score_use_percent,2);
						$score_deducte = $score_deducte>$money?$money:$score_deducte;
					}
				}
			}
		}
		return array('score'=>$score_can_use_count,'score_money'=>floatval($score_deducte));
	}

	public function get_user_by_phone($phone){
		$now_user = $this->where(array('phone'=>$phone))->find();
		return $now_user['uid'];
	}
	# 按出生年月算年龄	韩露
	public function age($birthday){
		if(empty($birthday)){
			return '';
		}
		$age = date('Y', time()) - date('Y', strtotime($birthday)) - 1;
		if (date('m', time()) == date('m', strtotime($birthday))){
			if (date('d', time()) > date('d', strtotime($birthday))){
			$age++;
			}
		}elseif (date('m', time()) > date('m', strtotime($birthday))){
			$age++;
		}
		return $age;
	}

	/*
	 * 用户签到功能
	 * */

	public function check_sign_today($uid){
		$recently_sign = M('User_sign')->where(array('uid'=>$uid))->order('id DESC')->find();
		if($recently_sign && strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))==strtotime(date('Ymd',$recently_sign['sign_time']))){
			return array('error_code'=>0,'msg'=>L_('已经签到了'));
		}else{
			return array('error_code'=>1,'msg'=>L_('今天没签到'));
		}
	}

	public function sign_in($uid){
		if(C('config.sign_get_score') == '0' || empty(C('config.sign_get_score'))){
			return array('error_code'=>0,'msg'=>"签到成功！");
		}
		$recently_sign = M('User_sign')->where(array('uid'=>$uid))->order('id DESC')->find();
		$now_user = $this->get_user($uid);
		if(empty($now_user['phone'])){
			return array('error_code'=>2,'msg'=>L_('您未绑定手机，请绑定手机'));
		}
		if($recently_sign && strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))==strtotime(date('Ymd',$recently_sign['sign_time']))){
			return array('error_code'=>1,'msg'=>L_('同一天只能签到一次，请明天再来！'));
		}
        if (C('config.sign_get_score') <= 0) {
            return array('error_code'=>1,'msg'=>L_('抱歉，当前不能使用签到功能！'));
        }
		
		//自定义签到模式需要计算，固定的不用计算。
		if(C('config.open_custom_sign')==1){
			if($recently_sign && (strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))-strtotime(date('Ymd',$recently_sign['sign_time'])))>86400){
				$score_get = C('config.sign_get_score');
				$sign_day = 1;
			}elseif($recently_sign && $recently_sign['sign_id']>0){	
				$score_get = C('config.sign_get_score');
				$sign_day = 1;
			}else{
				$score_get = (($recently_sign['day']+1)%30===0?30:($recently_sign['day']+1)%30)+C('config.sign_get_score')-1;
				$sign_day = $recently_sign['day']+1;
			}
		}else{
			$score_get = C('config.sign_get_score');
			$sign_day = 1;
		}
		
		$data['uid'] = $uid;
		$data['day'] = $sign_day;
		$data['score_count'] = $score_get;
		$data['sign_time'] = $_SERVER['REQUEST_TIME'];
		M('User_sign')->add($data);
		$this->add_extra_score($uid,$score_get,L_("第X1天签到获得X2个X3",array("X1" => $sign_day,"X2" => $score_get,"X3" => C('config.score_name')))); 

		D('Scroll_msg')->add_msg('sign',$uid,L_("用户X1于X2签到获得X3个X4",array("X1" => str_replace_name($now_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $score_get,"X4" => C('config.score_name')))); 
		$return_msg = L_("签到成功!获得X1个X2",array("X1" => $score_get,"X2" => C('config.score_name'))); 
		if($sign_day>1){
			$return_msg = L_("连续X1天签到!获得X2个X3",array("X1" => $sign_day,"X2" => $score_get,"X3" => C('config.score_name'))); 
		}
		return array('error_code'=>0,'msg'=>$return_msg);
	}

	//自定义签到接口
	public function custom_sign_in($uid,$sign_list){
		$recently_sign = M('User_sign')->where(array('uid'=>$uid))->order('id DESC')->find();
		$now_user = $this->get_user($uid);
		if(empty($now_user['phone'])){
			return array('error_code'=>2,'msg'=>L_('您未绑定手机，请绑定手机'));
		}
		if($recently_sign && strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))==strtotime(date('Ymd',$recently_sign['sign_time']))){
			return array('error_code'=>1,'msg'=>L_('同一天只能签到一次，请明天再来！'));
		}
        if (C('config.sign_get_score') <= 0) {
            return array('error_code'=>1,'msg'=>L_('抱歉，当前不能使用签到功能！'));
        }

		if($recently_sign && (strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))-strtotime(date('Ymd',$recently_sign['sign_time'])))>86400){
			$sign_day = 1;
			$tsign_day=1;
		}else{
			$max_day=count($sign_list);
			if($recently_sign['tday']>$max_day){
				$tsign_day=1;
			}else{
				$tsign_day = $recently_sign['tday']+1;
				//最大天数区域获取实际循环签到天数
				$tsign_day=$tsign_day%$max_day;
				$tsign_day=$tsign_day==0?$max_day:$tsign_day;	
			}
			$sign_day = $recently_sign['day']+1;
			
			//如果连续签到的天数已经达到系统后台设置的最大天数，那么签到天数还是1,tapd bugID1008215
			if($recently_sign['day']>=$max_day){
				$sign_day=1;
				$tsign_day=1;
			}
		}
		$data['uid'] = $uid;
		$data['day'] = $sign_day;
		$data['tday'] = $tsign_day;
		$sign_info=$sign_list[$tsign_day-1];
		$data['sign_id']=$sign_info['id'];
		$reward_arr=json_decode($sign_info['reward_info'],true);
		$sign_info['reward_arr']=$reward_arr;
		if($sign_info['type']==1){
			$score_get=$reward_arr['jifen'];
			$data['score_count'] = $score_get;
		}if($sign_info['type']==2){
			$coupon_id=$reward_arr['coupon_id'];
			$model=D('System_coupon');
			$result = $model->had_pull($coupon_id,$uid);
			if($result['errcode']!=0){
				return array('error_code'=>1,'msg'=>L_('签到失败，请重试！'));
			}
			$reduce_num=$result['coupon']['reduce_num'];
	        $reduce_num=$reduce_num>0?$reduce_num:1;
			$model->decrease_sku(0,$reduce_num,$coupon_id);//网页领取完，微信卡券库存需要同步减少
			$data['coupon_id']=$coupon_id;
		}
		$data['sign_time'] = $_SERVER['REQUEST_TIME'];
		M('User_sign')->add($data);
		if($sign_info['type']==1){
			$this->add_extra_score($uid,$score_get,L_("第X1天签到获得X2个X3",array("X1" => $sign_day,"X2" => $score_get,"X3" => C('config.score_name')))); 

			D('Scroll_msg')->add_msg('sign',$uid,L_("用户X1于X2签到获得X3个X4",array("X1" => str_replace_name($now_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $score_get,"X4" => C('config.score_name')))); 
			$return_msg = L_("签到成功!获得X1个X2",array("X1" => $score_get,"X2" => C('config.score_name'))); 
			if($sign_day>1){
				$return_msg = L_("连续X1天签到!获得X2个X3",array("X1" => $sign_day,"X2" => $score_get,"X3" => C('config.score_name'))); 
			}
		}else{
			$coupon_info=M('System_coupon')->where(array('coupon_id'=>$coupon_id))->find();
			$coupon_info['day']=floor(($coupon_info['end_time']-$coupon_info['start_time'])/86400);
			$sign_info['coupon_info']=$coupon_info;
			D('Scroll_msg')->add_msg('sign',$uid,L_("用户X1于X2签到获得平台优惠券（X3）",array("X1" => str_replace_name($now_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $coupon_info['name']))); 
			$return_msg = L_('签到成功!获得平台优惠券');
			if($sign_day>1){
				$return_msg = L_("连续X1天签到!获得平台优惠券",array("X1" => $sign_day)); 
			}
		}
		return array('error_code'=>0,'sign_info'=>$sign_info);
	}

	/*
	 * 今天签到人数
	 * */
	public function sign_num_today(){
		$today = date('Y-m-d',$_SERVER['REQUEST_TIME']);
		$today_start = strtotime($today.' 00:00:00');
		$today_end = strtotime($today.' 23:59:59');
		$where['_string'] = 'sign_time > '.$today_start.' OR sign_time < '.$today_end;
		$today_sign_num = M('User_sign')->where($where)->count();
		return $today_sign_num;
	}

	public function clean_canel_userlist(){
		$this->join('AS u LEFT JOIN '.C('DB_PREFIX').'user_level AS l ON l.level = u.level_id')->where(array('score_clean_notice_time'=>0))->limit(10)->select();
	}

	public function merge_user_from_new($uid,$old_uid){
		if(C('config.open_score_get_percent')==1){
			$score_get = C('config.score_get_percent')/100;
		}else{
			$score_get = C('config.user_score_get');
		}
		//$now_user = $this->get_user($uid);
		$where['uid'] =$uid;
		$where['paid'] =1;
		$arr[] = M('Store_order')->field('(SUM(balance_pay)+SUM(payment_money*(SIGN(0-is_own)+1)) +SUM(merchant_balance)) AS paymoney')->where($where)->group('uid')->select();

		$where['_string'] = 'status = 2 OR status = 3';
		$arr[] = M('Shop_order')->field('(SUM(balance_pay) +SUM(payment_money*(SIGN(0-is_own)+1)) +SUM(merchant_balance) )AS paymoney')->where($where)->group('uid')->select();

		//unset($where['_string']);
		//$where['business_type']='foodshop';
		//$arr[] = M('Plat_order')->field('(SUM(system_balance)+SUM(pay_money*(SIGN(0-is_own)+1)) +SUM(merchant_balance_pay)) AS paymoney')->where($where)->group('uid')->select();
		$all_money =0;

		foreach ($arr as $item) {
			$all_money+=$item[0]['paymoney'];
		}
		$merge_score = round($all_money*$score_get);
		$this->add_score($old_uid,$merge_score,L_("来自合并账号ID：X1返还积分",array("X1" => $uid))); 
		//return $all_money;

	}

	public function get_userinfo_weixin($openid){
		import('ORG.Net.Http');
		$http = new Http();
		$access_token_array = D('Access_token_expires')->get_access_token();
		if (!$access_token_array['errcode']) {
			$return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$openid.'&lang=zh_CN');
			$userifo = json_decode($return,true);
			if(empty($userifo)){
				return false;
			}else{
				return $userifo;
			}
		}
		return false;
	}

	public function get_userinfo_wxapp($code=''){
		$appid = C('config.pay_wxapp_appid');
		$appsecret = C('config.pay_wxapp_appsecret');

		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code', array());

		import('@.ORG.aeswxapp.wxBizDataCrypt');

		$pc = new WXBizDataCrypt($appid, $return['session_key']);
		$errCode = $pc->decryptData($_POST['encryptedData'],$_POST['iv'],$data);
		$jsonrt = json_decode($data,true);
		if(empty($jsonrt) || $jsonrt['errcode']){
			return false;
		}else{
			return $jsonrt;
		}
	}
	//获取单个用户信息
	public function user_find($field,$where){
		$result= $this->field($field)->where($where)->find();
		if($result){
			return $result;
		}else{
			return false;
		}
	}
	
	//清空用户积分
	public function check_score($uid){
		if(C('config.open_score_clean')){
			if (!empty($uid)){
				$now_user = $this->get_user($uid);
				$clean_score_type = C('config.clean_score_type');
				$clean_type = $clean_score_type==1 ? 'score_count' : 'score_extra_count';

				if($now_user[$clean_type]<=0){
					$this->where(array('uid'=>$uid))->setField($clean_type,0);
					return ;
				}
				$now_level = M(_view('User_level'))->where(array('id' => $now_user['level']))->find();
				$config_score_clean_time = C('config.score_clean_time');
				$clean_time = strtotime(date(Y).'-'.($now_level['score_clean_time']!='' ? $now_level['score_clean_time'] : $config_score_clean_time));
				$clean_percent = $now_level['score_clean_percent'] > 0 ? $now_level['score_clean_percent'] : C('config.score_clean_percent');
				$time = time();
				$time_Ymd = strtotime(date('Y-m-d',$time));


				if($now_user['score_clean_time']!=0 && date('Y',$time)>date('Y',$now_user['score_clean_time'])){ //下一年 一年一次 过期不减
					$now_user['score_clean_time']=0;
				}
				if($time_Ymd>=$clean_time && $clean_time && $now_user['score_clean_time']==0){
					$dec_score = round($now_user[$clean_type]*$clean_percent/100,2);  //四舍五入
					$this->user_score($now_user['uid'],$dec_score,L_('积分到期清理，日期：').date('Y-m-d',$time));
					$this->where(array('uid'=>$now_user['uid']))->setField('score_clean_time',$time);
				}
			}
		}
	}


	public function get_youshanzhu_token(){
		$url = C('config.youshanzhu_login_url').'/plugins/login_wx_client/index/index?pig_refer='.urlencode($this->config['site_url'].'/wap.php?c=Login&a=login_from_youshanzhu');
		redirect($url);
	}
	
	public function nmgzhcs_money($nmgzhcs_openid,$money,$desc){
		$appid = C('config.nmgzhcs_appid') ;
		$appsecret = C('config.nmgzhcs_appsecret');

		$url=C('config.nmgzhcs_base_url').'/api/membermoney/index';

		$params=array(
			'appid'=>$appid,
			'openid'=>$nmgzhcs_openid,
			'money'=>$money,
			'order_code'=>$nmgzhcs_openid,
			'xnote'=>$desc,
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		$params['sign'] = $sign;

		$res = httpRequest($url,'POST',$params);
		$res = json_decode($res[1],true);
		fdump($params,'membermoney',true);
		fdump($res,'membermoney',true);
	}
	
	public function nmgzhcs_score($nmgzhcs_openid,$score,$desc){
		$appid = C('config.nmgzhcs_appid') ;
		$appsecret = C('config.nmgzhcs_appsecret');

		$url=C('config.nmgzhcs_base_url').'/api/memberpoints/index';

		$params=array(
			'appid'=>$appid,
			'openid'=>$nmgzhcs_openid,
			'points'=>$score,
			'order_code'=>$nmgzhcs_openid,
			'xnote'=>$desc,
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		$params['sign'] = $sign;

		$res = httpRequest($url,'POST',$params);
		$res = json_decode($res[1],true);
		fdump($params,'memberscore',true);
		fdump($res,'memberscore',true);
	}

	public function nmgzhcs_autologin($openid){
		$appid = C('config.nmgzhcs_appid');
		$appsecret = C('config.nmgzhcs_appsecret');

		$url= C('config.nmgzhcs_base_url') . '/api/member/memberinfo';

		$params=array(
			'appid'=>$appid,
			'openid'=>$openid,
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		$params['sign'] = $sign;
		$res = httpRequest($url,'POST',$params);
		$res = json_decode($res[1],true);


		if($res['status']=='y' && $res['openid']!=''){
			$data_user['nickname'] = $res['name'];
			$data_user['avatar'] = $res['data']['head_img'] ? $res['data']['head_img'] : '';
			$data_user['nmgzhcs_openid'] = $res['openid'];
			if($_SESSION['openid']){
				$data_user['openid'] = strval($_SESSION['openid']);
			}
			if($_SESSION['alipay_uid']){
				$data_user['alipay_uid'] = strval($_SESSION['alipay_uid']);
			}
			$data_user['phone'] = $res['mobile'];

			$data_user['now_money'] = $res['data']['money_sum'];
			$data_user['score_count'] = $res['data']['points_sum'];
			$tmp_user = $this->get_user($res['openid'],'nmgzhcs_openid');
			if(!$tmp_user){
				$reg_result = $this->autoreg($data_user);
			}elseif($tmp_user['uid']){
				$this->where(array('uid'=>$tmp_user['uid']))->data($data_user)->save();
			}

			$login_result = $this->autologin('nmgzhcs_openid',$res['openid']);

			if($login_result['error_code']){
				return array('error_code'=>1001,'msg'=>L_('自动登录失败！'));
			}else{
				$now_user = $login_result['user'];
				session('user',$now_user);
				return array('error_code' => false, 'msg' => 'OK' ,'user'=>$now_user);
				$referer = !empty($_SESSION['weixin']['referer']) ? $_SESSION['weixin']['referer'] :U('My/index');

				redirect($referer);
				exit;
			}
		}
	}
	
	public function down_avatar($avatar, $avatar_token){
		if(empty($avatar)){
			return '';
		}
		if(strpos($avatar,'http') !== 0){
			return $avatar;
		}
		if(strpos($avatar,file_domain()) !== false){
			return $avatar;
		}
		import('ORG.Net.Http');
        $http = new Http();
        $avatar_content = Http::curlGet($avatar);
		if(empty($avatar_content)){
			return $avatar;
		}
		fdump($avatar,'avatar_content',true);
		fdump($avatar_content,'avatar_content',true);
		fdump($http,'avatar_content',true);
		$avatar_tmp = md5($avatar_token);
		$avatar_file_dir = WEB_PATH.'upload/avatar/'.substr($avatar_tmp,0,3).'/'.substr($avatar_tmp,3,3).'/';
		if(!file_exists($avatar_file_dir)){
			mkdir($avatar_file_dir,0777,true);
		}
		$avatar_file = $avatar_file_dir.$avatar_tmp.'.jpg';
		file_put_contents($avatar_file,$avatar_content);
		//判断是否需要上传至云存储
		$file_handle = new file_handle();
		$file_handle->upload($avatar_file);
		
		$avatar_file = str_replace(WEB_PATH, '', $avatar_file);
		
		$avatar = file_domain() . '/' . $avatar_file;
		
		return $avatar;
	}

	/*资产转移，更换账号*/
	public function change_property($from_uid,$to_uid){
		$from_user = $this->get_user($from_uid);
		$to_user = $this->get_user($to_uid);
		if($from_user['status']==4 && $to_user){
			// 转移会员卡
			$cardList = D('Card_userlist')->where(['uid'=>$from_uid])->select();
			foreach($cardList as $_card){
				if(!M('Card_userlist')->where(array('mer_id' => $_card['mer_id'], 'uid' => $to_uid))->find()){
					D('Card_userlist')->where(array('id'=>$_card['id']))->setField('uid',$to_uid);
				}
			}

			
			// 转移推广关系
            if ($to_user['openid']) {
                D('User_spread')->where(array('spread_uid' => $from_uid))->setField('spread_openid',$to_user['openid']);
                D('User_spread')->where(array('uid' => $from_uid))->setField('openid',$to_user['openid']);
            }
			return array('error_code' => false, 'msg' => 'OK');
		}else{
			return array('error_code'=>1001,'msg'=>L_('不能转移'));
		}
	}

	/*
	 *隐私号码保护 获得虚拟分机号码
	*/
	public function get_virtual_phone($phone,$start_time){
		// 有效时间
		$start_time = $start_time ? $start_time : time();
        $time = C('config.protect_time');
        $time = $time ? $time : 2;

		// 获取隐私号码
        $param['telA'] = $phone;
        //请求第三方接口
        import('@.ORG.phone.Phone');
        $phoneObj = new Phone();
        $result = $phoneObj->axxBind($param);
		fdump_api([$param,$result],'axxBind',true);
	
        // 返回错误
        if(empty($result) || $result['code'] > 0){
            $returnArr['error_code'] = 1;
            $returnArr['msg'] = $result['message'];

            // 写入日志
            $logData['create_time'] = time();
            $logData['phone'] = $phone;
            $logData['data'] = json_encode($result);
            D('Virtual_phone_log')->add($logData);

            return $returnArr;

        }

        // 请求结果 获得隐私号码
        $virtual_phone = $result['data']['telX'].','.$result['data']['telXext'];//主机号+分机号

        // 返回结果
        $returnArr['error_code'] = 0;
		$returnArr['data']['phone'] = $virtual_phone;
		$returnArr['data']['expiration_time'] = $start_time +86400*$time;
		return $returnArr;
	}

    public function getNextVipLevel($uid, $storeId)
    {
        $user = $this->where(['uid' => $uid])->find();
        if (empty($user)) {
            return [];
        } else {
            $tm = time();
            $userLevelMod = M('User_level');
            $level = $user['level'];
            $levelInfo = $userLevelMod->where(['level' => $level])->find();
            if ($user['level'] == 0 || ($levelInfo['validity'] > 0 && $user['level_time'] + $levelInfo['validity'] * 86400 < $tm)) {
                //未开通会员等级或者已过期
                return [];
            }
            $levelIncrByOne = $userLevelMod->where(['level' => ['gt', $level]])->find();
            if (empty($levelIncrByOne) || $levelIncrByOne['coupon_ids'] == '') {
                return [];
            }
            $levelIncrByOne['current_user_level'] = $level;
            $store = M('Merchant_store_shop')->where(['store_id' => $storeId])->field('`store_id`,`leveloff`')->find();
            $leveloff = unserialize($store['leveloff']);
            if (empty($leveloff) || empty($leveloff[$levelIncrByOne['level']]) || $leveloff[$levelIncrByOne['level']]['vv'] <= 0 || $leveloff[$levelIncrByOne['level']]['vv'] >= 100) {
                return [];
            }

            $levelIncrByOne['description'] =sprintf("开通".C('config.vip_card_alias')."，本单立享".getFormatNumber($leveloff[$levelIncrByOne['level']]['vv']/10)."折");
            $levelIncrByOne['alias'] = C('config.vip_card_alias')?C('config.vip_card_alias'):'';
            $couponIds = explode(',', $levelIncrByOne['coupon_ids']);
            $coupons = D('System_coupon')->get_coupon_list_by_ids($couponIds);
            $levelIncrByOne['coupons'] = array_values($coupons);
            return $levelIncrByOne;
        }
    }

	/*通过openid或alipay_uid绑定以往订单,并且获得积分*/
	public function bind_order($uid,$type='openid',$openid){
		if (!$uid || !$openid) {
			return array('error_code' => false, 'msg' => '参数不完整');
		}

		// 用户信息
		$now_user = $this->get_user($uid);
		if (!$now_user) {
			return array('error_code' => false, 'msg' => '用户不存在');
		}

		$where_dining_order = [];

		if ($type=='openid') {
			$openid = $now_user['openid'] ? $now_user['openid'] :  $now_user['wxapp_openid'];
			$where_dining_order['user_type'] = 'openid';
			$where_dining_order['user_id'] = $openid;
		}elseif ($type=='alipay_uid') {
			$openid = $now_user['alipay_uid'];
			$where_dining_order['user_type'] = 'alipay_uid';
			$where_dining_order['user_id'] = $openid;
		}

		if (!$openid) {
			return array('error_code' => false, 'msg' => '用户openid或alipay_uid不存在');
		}


		// 支付总金额
		$total_momey = 0;
		$score_count = 0;
		$money_list = array();
		// 查询快速买单订单
		$store_order = M('Store_order')->where(array($type=>$openid))->select();
		if ($store_order) {
			if (M('Store_order')->where(array($type=>$openid))->setField('uid',$now_user['uid'])) {
				$store_money_list = M('Store_order')->field('sum(payment_money) as payment_money,mer_id')->where(array($type=>$openid,'paid'=>1,'refund'=>0,'score_discount_type'=>array('neq',2),'is_own'=>0))->group('mer_id')->select();
				$store_money_list && $money_list = array_merge($money_list,$store_money_list);

				// 自由支付可以获得积分
				if (C('config.user_own_pay_get_score')==1) {
					$store_own_money_list = M('Store_order')->field('sum(payment_money) as payment_money,mer_id')->where(array($type=>$openid,'paid'=>1,'refund'=>0,'score_discount_type'=>array('neq',2),'is_own'=>array('gt',0)))->group('mer_id')->select();
					$store_own_money_list && $money_list = array_merge($money_list,$store_own_money_list);
				}
			}
		}

		// 查询扫码点餐订单
		$foodshop_order = M('Foodshop_order')->where(array($type=>$openid))->select();
		if ($foodshop_order) {
			if (M('Foodshop_order')->where(array($type=>$openid))->setField('uid',$now_user['uid'])) {
				$business_id_arr = array_column($foodshop_order, 'order_id');

				// 更新用户买单表plat_order
				M('Plat_order')->where(array('business_id'=>array('in',$business_id_arr),'business_type'=>'foodshop'))->setField('uid',$now_user['uid']);

				// 用户买单总金额
				$plat_money_list = M('Foodshop_order')->field('sum(p.pay_money) as payment_money,f.mer_id')->join(' as f left join '.C('DB_PREFIX').'plat_order as p on p.business_type="foodshop" AND p.business_id=f.order_id')->where(array('f.uid'=>$now_user['uid'],'p.paid'=>1,'f.status'=>3,'p.is_own'=>0,'p.score_discount_type'=>array('neq',2)))->group('f.mer_id')->select();
				$plat_money_list && $money_list = array_merge($money_list,$plat_money_list);

				// 自由支付可以获得积分
				if (C('config.user_own_pay_get_score')==1) {
					$own_money_list = M('Foodshop_order')->field('sum(p.pay_money) as payment_money')->join(' as f left join '.C('DB_PREFIX').'plat_order as p on p.business_type="foodshop" AND p.business_id=f.order_id')->where(array('f.uid'=>$now_user['uid'],'p.paid'=>1,'f.status'=>3,'p.is_own'=>array('gt',0),'p.score_discount_type'=>array('neq',2)))->group('f.mer_id')->select();
					$own_money_list && $money_list = array_merge($money_list,$own_money_list);
				}

				// 更新店员买单表store_order
				M('Store_order')->where(array('business_id'=>array('in',$business_id_arr),'business_type'=>'foodshop'))->setField('uid',$now_user['uid']);

				// 店员买单总金额
				$store_money_list = M('Foodshop_order')->field('sum(p.payment_money) as payment_money,f.mer_id')->join(' as f left join '.C('DB_PREFIX').'store_order as p on p.business_type="foodshop" AND p.business_id=f.order_id')->where(array('f.uid'=>$now_user['uid'],'p.paid'=>1,'f.status'=>3,'p.is_own'=>0,'p.score_discount_type'=>array('neq',2)))->group('f.mer_id')->select();
				$store_money_list && $money_list = array_merge($money_list,$store_money_list);

				// 自由支付可以获得积分
				if (C('config.user_own_pay_get_score')==1) {
					$store_own_money_list = M('Foodshop_order')->field('sum(p.payment_money) as payment_money,f.mer_id')->join(' as f left join '.C('DB_PREFIX').'store_order as p on p.business_type="foodshop" AND p.business_id=f.order_id')->where(array('f.uid'=>$now_user['uid'],'p.paid'=>1,'p.status'=>3,'p.is_own'=>array('gt',0),'p.score_discount_type'=>array('neq',2)))->group('f.mer_id')->select();
					$store_own_money_list && $money_list = array_merge($money_list,$store_own_money_list);
				}

			}

			foreach ($money_list as $key => $value) {
				$total_momey += $value['payment_money'];
				$score_count += $this->can_get_score($value['payment_money'],$value['mer_id'],$now_user['uid']);
			}
		}

		// 查询新版餐饮扫码点餐订单
		$dining_order = M('Dining_order')->where($where_dining_order)->select();
		if ($dining_order) {
			if (M('Dining_order')->where($where_dining_order)->setField('uid',$now_user['uid'])) {
				M('Dining_order_detail')->where($where_dining_order)->setField('uid',$now_user['uid']);
				M('Dining_order_log')->where($where_dining_order)->setField('uid',$now_user['uid']);
				M('Dining_order_goods_log')->where($where_dining_order)->setField('uid',$now_user['uid']);
				M('Dining_order_pay')->where($where_dining_order)->setField('uid',$now_user['uid']);

				$order_id_arr = array_column($foodshop_order, 'order_id');

				// 用户买单总金额
				$where = [
					'p.order_id' => ['in', $order_id_arr],
					'p.is_own' => 0,
					'o.uid' => $now_user['uid'],
					'p.paid' => 1,
					'p.score_discount_type' => array('neq',2),
					'o.status' => ['between',[40,49]]

				];

				// 用户买单总金额
				$pay_money_list = M('Dining_order')->field('sum(p.pay_money) as payment_money,o.mer_id')->join(' as o left join '.C('DB_PREFIX').'dining_order_pay as p on p.order_id=o.order_id')->where($where)->group('o.mer_id')->select();
				$pay_money_list && $money_list = array_merge($money_list,$pay_money_list);

				// 自由支付可以获得积分
				if (C('config.user_own_pay_get_score')==1) {
					$where = [
						'p.order_id' => ['in', $order_id_arr],
						'p.is_own' => array('gt',0),
						'o.uid' => $now_user['uid'],
						'p.paid' => 1,
						'p.score_discount_type' => array('neq',2),
						'o.status' => ['between',[40,49]]
					];
					$own_money_list = M('Dining_order')->field('sum(p.pay_money) as payment_money,o.mer_id')->join(' as o left join '.C('DB_PREFIX').'dining_order_pay as p on p.order_id=o.order_id')->where($where)->group('o.mer_id')->select();
					$own_money_list && $money_list = array_merge($money_list,$own_money_list);
				}
			}

			foreach ($money_list as $key => $value) {
				$total_momey += $value['payment_money'];
				$score_count += $this->can_get_score($value['payment_money'],$value['mer_id'],$now_user['uid']);
			}
		}


		$total_momey = getFormatNumber($total_momey);
		$score_count = getFormatNumber($score_count);
		if ($score_count) {
			// 用户获得积分
			D('User')->add_score($now_user['uid'], $score_count, L_("用户注册合并积分资产，获得X1",array("X1" => C('config.score_name'))));
		}
		return array('error_code' => false, 'msg' => 'OK');
	}

	// 通过消费金额 计算用户可以获得多少积分
	public function can_get_score($momey,$mer_id,$uid){
		if(C('config.add_score_by_system_commission')==0 && C('config.add_score_by_percent')==0 ){
			if(C('config.open_score_get_percent')==1){
				$score_get = C('config.score_get_percent')/100;
			}else{				
				$score_get = C('config.user_score_get');
			}

			if($order_info['is_own'] && C('config.user_own_pay_get_score')!=1){
				$order_info['payment_money'] = 0;
			}
			$now_merchant = D('Merchant')->get_info($mer_id);
			if($now_merchant['score_get_percent']>=0){
				$score_get = $now_merchant['score_get_percent']/100;
			}
			
			$score_get = D('Percent_rate')->get_user_add_score_times($score_get,$uid);
			return $score_get*$momey;
		}
	}

    /**
     * author htm
     * description 商城与快店业务的申请售后时要退还获得的平台积分与平台佣金
     * data 20191219 09:44
     * param uid 用户ID
     * param order_id 订单ID
     * param order_type 订单类型 shop-外卖 mall-商城
     * param refundMoney 本次退款金额
     * return 
    */
    public function refundScoreBack($uid,$order_id,$order_type,$refundMoney=0,$refund_id=0,$refund_type='shop_order_refund'){
		$returnArr = array('refundMoney' => $refundMoney,'backMoney'=>0);
         // 积分 
        // 获得的总积分
        $where = array(
            'order_id'=>$order_id,
            'order_type'=>$order_type,
            'type'=>1,
        );
        $scoreGet = D('User_score_list')->where($where)->sum('score');

        // 返回用户获得的积分
        $returnArr['scoreGet'] = $scoreGet;

        // 已退总积分
        $where = array(
            'order_id'=>$order_id,
            'order_type'=>$order_type,
            'type'=>2,
        );
        $scoreBack = D('User_score_list')->field('sum(score) as score')->where($where)->sum('score');

        // 还可以退还的积分
        $scoreCanBack = getFormatNumber($scoreGet-$scoreBack);

        // 当前退款用户信息
        $now_user = D('User')->get_user($uid);
        if ($scoreCanBack) {
            if ($order_type=='shop' || $order_type=='mall'){
                // 订单信息
                $now_order = D('Shop_order')->field(true)->where(array('order_id'=>$order_id))->find();
//                var_dump([$refundMoney,$now_order['discount_price'],$now_order['freight_charge'],$scoreGet['score']]);die;

                // 应该扣除的积分
                $scoreNeedBack = getFormatNumber($refundMoney/($now_order['price']-$now_order['freight_charge'])*$scoreGet);
//                var_dump([$refundMoney,$now_order['price'],$now_order['freight_charge'],$scoreGet]);die;
//                $scoreNeedBack = getFormatNumber($refundMoney/($now_order['discount_price']-$now_order['freight_charge'])*$scoreGet);
                $scoreNeedBack = min($scoreNeedBack,$scoreCanBack);
                if ($now_user['score_count']<$scoreNeedBack && !C('customization.back_order_user_score')) {
                	// 返回 用户积分不够抵扣的部分
                    $returnArr['backScore'] = $scoreNeedBack-$now_user['score_count'];

                	// 用户积分实际抵扣的部分
                    $scoreNeedBack = $now_user['score_count'];

                	// 用户积分需要抵扣的退款金额
                    if (C('config.user_score_recharge_percent')) {
                    	$scoreNeedBackMore = getFormatNumber( $returnArr['backScore']);
                        $scoreNeedBackMoney = getFormatNumber($scoreNeedBackMore/C('config.user_score_recharge_percent'));
                    }else{
                        $scoreNeedBackMoney = 0;
                    }
                }

                // 退款金额相应减少
                if ($scoreNeedBackMoney) {
                    $returnArr['refundMoney'] = getFormatNumber($returnArr['refundMoney'] - $scoreNeedBackMoney);
                    $returnArr['backMoney'] = $scoreNeedBackMoney;
                }

                // 扣除用户积分
                if ($scoreNeedBack&&$refund_id) {
                    $param['order_id'] = $order_id;
                    $param['order_type'] = $order_type; 
                    $desc = L_("商品申请售后扣除已获得的平台X1",array("X1" => C('config.score_name')));
                    D('User')->use_score($uid,$scoreNeedBack,$desc,$param);
                    $data['score_back_money'] = $scoreNeedBackMoney;
                    $data['score_back_count'] = $scoreNeedBackMore;
                    D(ucfirst($refund_type))->where(array('order_id' => $order_id, 'id' => $refund_id))->save($data);
                }
            }
        }
        return $returnArr;
    }

    /**
     * 升级会员
     */
    public function level_update($uid, $level, $payment_money, $desc)
    {
        $tm = time();
        $user_level = D('User_level');

        $levelarr = array('level' => $level, 'level_time' => time());
        M('User')->where(array('uid' => $uid))->save($levelarr);

        $user_level_info = $user_level->where(array('level' => $level))->find();
        D('User_level_open_log')->add_reward($uid, $user_level_info, $uid, 1, 2, $tm, 2, $desc, 0, 0, false, $payment_money);

        //绑定会员等级,获取优惠券
        $user = M('User')->where(array('uid' => $uid))->find();
        if (!empty($user)) {
            if ($user['level'] > 0) {
                $user_level = M('User_level')->where(array('level' => $user['level']))->find();
                if (!empty($user_level['coupon_ids'])) {
                    $hadpull = M('System_coupon_hadpull');
                    $coupon_id = explode(',', $user_level['coupon_ids']);
                    foreach ($coupon_id as $v) {
                        $coupon = M('System_coupon')->field(true)->where(array('coupon_id' => $v))->find();
                        $hadpull_count = $hadpull->where(array('uid' => $user['uid'], 'coupon_id' => $v))->count();
                        //实际可领取的数量
                        $receive_num = $coupon['limit'] - $hadpull_count;
                        //剩余的数量
                        $after_num = $coupon['num'] - $coupon['had_pull'];
                        $reduce_num = min($receive_num, $after_num);
                        $where['coupon_id'] = $v;
                        M('System_coupon')->where($where)->setInc('had_pull', $reduce_num);
                        M('System_coupon')->where($where)->setField('last_time', $_SERVER['REQUEST_TIME']);
                        $data_all = array();
                        for ($i = 0; $i < $reduce_num; $i++) {
                            $data = array();
                            $data['coupon_id'] = $v;
                            $data['num'] = 1;
                            $data['receive_time'] = $_SERVER['REQUEST_TIME'];
                            $data['status'] = 0;

                            $data['uid'] = $user['uid'];
                            $data_all[] = $data;
                        }
                        $hadpull->addAll($data_all);
                    }
                }

            }
        }
    }

    /**
     * 用户头像优化，没有则取默认头像
     * @author 张涛
     * @date 2020/4/25
     */
    public function userAvatarDisplay($avatar)
    {
        $defalut = C('config.site_url') . '/static/images/user_avatar.jpg';
        return $avatar ? replace_file_domain($avatar) : $defalut;
    }

    public function getV20Ticket($uid){
    	try{
    		import('ORG.Net.Http');
	        $http = new Http();
	        $content = Http::curlGet(C("config.site_url").'/v20/public/index.php/common/common.user/getUserToken?uid='.$uid);
	        $return = json_decode($content,true);
	        $ticket = '';
	        if($return['status'] == 1000){
	        	$ticket = $return['data']['ticket'];
	        } else {
                import("@.ORG.Token");
                $ticket = Token::createToken($uid);
            }
    	}catch(\Exception $e){
    		return '';
    	}
    	return $ticket;
    }
    /**
     * 插入用户
     * @author lijie
     * @date_time 2020/07/14
     * @param $data
     * @return mixed
     */
    public function addUser($data)
    {
        $insertId = $this->data($data)->add();
        return $insertId;
    }

    /**
     * 根据条件查询用户信息
     * @param $where
     * @author lijie
     * @date_time 2020/07/14
     * @param $field
     * @return mixed
     */
    public function getUser($where,$field)
    {
        $res = $this->where($where)->field($field)->find();
        return $res;
    }


    /**
     * 获取用户会员等级信息
     * @param $uid
     * @return array
     * @author: 张涛
     * @date: 2020/11/2
     */
    public function getLevelByUid($uid)
    {
        $rs = ['is_level' => false, 'level' => 0, 'can_spread' => false, 'expire_time' => 0];
        $user = $this->where(['uid' => $uid])->find();

        if (empty($user)) {
            return $rs;
        }
        $tm = time();
        $userLevelMod = M('User_level');
        $levelInfo = $userLevelMod->where(['level' => $user['level']])->find();

        //如果所有会员等级都关闭了推广，则所有用户都可以推广
        $isAllCloseSpread = $userLevelMod->where(['can_spread' => 1])->find() ? false : true;

        if ($levelInfo && $user['level'] > 0) {
            if ($levelInfo['validity'] == 0) {
                $rs['expire_time'] = -1;  //-1表示永久有效
                $rs['level'] = $user['level'];
                $rs['is_level'] = true;
            } else {
                $rs['expire_time'] = $user['level_time'] + $levelInfo['validity'] * 86400;
                $rs['level'] = $user['level'];
                $rs['is_level'] = $rs['expire_time'] > $tm ? true : false;
            }
        }
        $rs['can_spread'] = ($isAllCloseSpread || ($levelInfo['can_spread'] == 1 && $rs['is_level'])) ? true : false;
        return $rs;
    }

    /**
     * 判断用户是否可以推广获取佣金
     * @param $uid
     * @return mixed
     * @author: 张涛
     * @date: 2020/11/2
     */
    public function canSpread($uid)
    {
        $rs = $this->getLevelByUid($uid);
        return $rs['can_spread'];
    }


    public function _after_insert($data, $options)
    {
        if ($this->getLastInsID()) {
            $data = [
                'params' => json_encode(['table' => 'user', 'action' => 'add', 'id' => $this->getLastInsID()]),
                'status' => 0,
                'create_time' => time(),
				'url'=>$_SERVER['REQUEST_URI'],
				'data'=>$data
            ];
            M('Miaomaiyun_erp_sync_task')->add($data);
        }
    }

    public function _after_update($data, $options)
    {
        $uid = $data['uid'] ?: ($options['where']['uid'] ?? 0);
        if ($uid) {
            $data = [
                'params' => json_encode(['table' => 'user', 'action' => 'edit', 'id' => $uid]),
                'status' => 0,
                'create_time' => time(),
				'url'=>$_SERVER['REQUEST_URI'],
				'data'=>$data
            ];
            M('Miaomaiyun_erp_sync_task')->add($data);
        }
    }

    public function AppPush($bind_user,$arr){
        import('@.ORG.Apppush');
        $now_order['uid'] = $bind_user['uid'];
        $now_order['bind_id'] = $bind_user['village_id'] . '_' . $bind_user['pigcms_id'];
        $now_order['url'] = $arr['url'];
        $now_order['title'] = $arr['title'];
        $now_order['msg'] =$arr['msg'];
        if(isset($arr['pigcms_id'])){
            $now_order['pigcms_id']=$arr['pigcms_id'];
        }
        if(isset($arr['village_id'])){
            $now_order['village_id']=$arr['village_id'];
        }
        $apppush = new Apppush();
        $rel=$apppush->send($now_order, 'house_notice');
        fdump_api(['line:'.__LINE__,$rel],'app_push_log',1);
        return $rel;
    }

	//推送登录用户信息给楼宇系统
	public function pushUserLoginInfoToBuildingSystem($buildingLoginKey, $uid)
	{
		if (empty($buildingLoginKey) || empty($uid)) {
			return false;
		}
		$buildingDomain = C('config.building_domain');
		$buildingAppkey = C('config.building_appkey');
		if (empty($buildingDomain) || empty($buildingAppkey)) {
			return false;
		}
		$user = $this->where(['uid' => $uid])->find();
		if (empty($user) || $user['status'] != 1) {
			return false;
		}
		$url = $buildingDomain . '/user/oauth/o2o/addUser/byKey';
		$param = [
			'org_id' => C('config.building_org_id'),
			'appkey' => $buildingAppkey,
			'key' => $buildingLoginKey,
			'nickname' => $user['nickname'],
			'wxapp_openid' => $user['wxapp_openid'],
			'phone' => $user['phone'],
			'wxapp_appid' => C('config.pay_wxapp_appid'),
			'union_id' => $user['union_id'],
			'country_code' => $user['country_code'] ?? ''
		];
		$header = ['Content-Type: application/json'];
		httpRequest($url,  'POST', json_encode($param), $header);
		return true;
	}
}
?>