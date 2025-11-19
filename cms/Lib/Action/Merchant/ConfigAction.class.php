<?php
/*
 * 系统配置
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/05 15:28
 *
 */

class ConfigAction extends BaseAction{
	public $staff_type = [];

	public function _initialize(){
		parent::_initialize();
		$this->staff_type[0] = L_('店小二');
		$this->staff_type[1] = L_('核销员');
		$this->staff_type[2] = L_('店长');
	}
	/* 商家设置 */
    public function merchant(){
		$database_merchant = D('Merchant');
		if(IS_POST){
            if (!$this->config['is_demo_domain']) {
				$data_merchant['phone'] = $_POST['phone'];
				if(empty($data_merchant['phone'])){
					$this->error(L_('请输入联系人电话'));
				}
			}

			$data_merchant['email'] = $_POST['email'];
			$data_merchant['open_money_tempnews'] = $_POST['open_money_tempnews'];
			$data_merchant['is_offline'] = isset($_POST['is_offline']) ? intval($_POST['is_offline']) : 1;
			// if(empty($data_merchant['email'])){
				// $this->error('请输入联系人邮箱');
			// }

			if(!empty($_POST['new_pass'])){
				$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
				$now_merchant = $database_merchant->field('`pwd`')->where($condition_merchant)->find();
				if(md5($_POST['old_pass']) != $now_merchant['pwd']){
					$this->error(L_('原密码输入错误'));
				}else if(strlen($_POST['new_pass']) < 6){
					$this->error(L_('新密码最少6个字符'));
				}else if($_POST['new_pass'] != $_POST['re_pass']){
					$this->error(L_('两次新密码输入不一致，请重新输入'));
				}else{
					$data_merchant['pwd'] = md5($_POST['new_pass']);
					fdump_sql([
					    'mer_id' => $this->merchant_session['mer_id'],
                        'old_pwd' => $_POST['old_pass'],
                        'new_pass' => $_POST['new_pass'],
                        'msg' => '商家修改密码'
                    ],'merchant_change_pwd');
				}
			}

			if(empty($_POST['pic'])){
				$this->error(L_('请至少上传一张图片'));
			}
			$data_merchant['pic_info'] = implode(';',$_POST['pic']);

            $data_merchant['txt_info'] = $_POST['txt_info'];
            $data_merchant['s_txt'] = $_POST['s_txt'];
			$data_merchant['group_express_outtime'] = $_POST['group_express_outtime'];
			if(isset($_POST['group_express_outtime']) && (!is_numeric($_POST['group_express_outtime'])||$_POST['group_express_outtime']<0)){
				$this->error(L_('超时时间设置有误'));
			}

			// if(empty($data_merchant['txt_info'])){
			// 	$this->error(L_('请输入商家描述信息'));
			// }
			$data_merchant['adverimg']=isset($_POST['adverimg']) ? trim($_POST['adverimg']) : '';
			$data_merchant['mer_id'] = $this->merchant_session['mer_id'];
			$data_merchant['scan_group_show_other'] = $_POST['scan_group_show_other'];
            isset($_POST['transfer_multi_kefu']) && $data_merchant['transfer_multi_kefu'] = $_POST['transfer_multi_kefu'];
			$_POST['invit_code'] && $data_merchant['invit_code'] = $_POST['invit_code'];

			if($_POST['logo']){
				$data_merchant['logo'] = str_replace(file_domain(),'',$_POST['logo']);
			}
			if($this->config['open_merchant_reg_sms']==1){

				$_POST['new_phone'] = phone_format($_POST['phone_country_type'], $_POST['new_phone']);

				if( $_POST['old_phone'] && $_POST['new_phone'] && $_POST['SmsCode'] && $_POST['SmsCode2']) {
					$now_merchant = M('Merchant')->where(array('phone' => $_POST['new_phone']))->find();
					if ($now_merchant) {
						$this->error(L_('手机已绑定其他商家'));
					}

					$where = array();
					$where['mer_id'] = $this->merchant_session['mer_id'];
					$where['extra'] = $_POST['SmsCode'];
					$where['status'] = 0;
					$where['expire_time'] = array('gt', time());
					$sms_result = M("Merchant_sms_record")->where($where)->find();
					if (!$sms_result) {
						$this->error(L_('旧手机验证码错误'));
					}

					$where = array();
					$where['mer_id'] = $this->merchant_session['mer_id'];
					$where['extra'] = $_POST['SmsCode2'];
					$where['status'] = 0;
					$where['expire_time'] = array('gt', time());
					$sms_result = M("Merchant_sms_record")->where($where)->find();
					if (!$sms_result) {
						$this->error(L_('新手机验证码错误'));
					}
//				$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
					$data_merchant['phone'] = $_POST['new_phone'];
				}
			}else{
				$_POST['new_phone'] && $data_merchant['phone'] = $_POST['new_phone'];
			}

			$data_merchant['merchant_default_lang'] = $_POST['merchant_default_lang'];

			$_POST['phone_country_type'] && $data_merchant['phone_country_type'] = $_POST['phone_country_type'];
			
			if(C("config.open_admin_code") == '1' && !M('Admin')->where(array('invit_code'=>$_POST['invit_code']))->find()){
				$this->error(L_('该邀请码不存在'));
			}

            //手机号唯一判断
            if ($data_merchant['phone'] && !$database_merchant->phoneIsUnique($data_merchant['phone'], [$data_merchant['mer_id']])) {
                $this->error(L_('联系电话已绑定其他商户'));
            }
			if($database_merchant->data($data_merchant)->save() !== false){
				//多语言
				$lang_model = D('Lang');
				$data_merchant['mer_id'] = $this->merchant_session['mer_id'];
				$lang_model->edit_lang_data('Merchant',$data_merchant);
				$_SESSION['merchant']['logo'] = $data_merchant['logo'];
				//添加电子发票信息
                $data['is_open'] = $_POST['is_open'];
                $data['seller_department'] = $_POST['seller_department'];
                $data['duty'] = $_POST['duty'];
                $data['seller_address'] = $_POST['seller_address'];
                $data['seller_phone'] = $_POST['seller_phone'];
                $data['deposit_bank'] = $_POST['deposit_bank'];
                $data['account_number'] = $_POST['account_number'];
                $data['create_time'] = time();
                $data['mer_id'] = $this->merchant_session['mer_id'];
                $e_id = M('Mall_e_config')->where(['mer_id'=>$this->merchant_session['mer_id']])->find();
                if(!empty($e_id)){
                    $res = M('Mall_e_config')->where(['mer_id'=>$this->merchant_session['mer_id']])->save($data);
                }else{
                    $res = M('Mall_e_config')->add($data);
                }
                if($res !== false){
                    $this->success(L_('保存成功！'));
                }else{
                    $this->error(L_('保存失败！请检查是否有修改过内容后重试'));
                }
			}else{
				$this->error(L_('保存失败！请检查是否有修改过内容后重试'));
			}
		}else{
			$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
			$now_merchant = M(_view('Merchant'))->field(true,'pwd')->where($condition_merchant)->find();
			if(!empty($now_merchant['pic_info'])){
				$merchant_image_class = new merchant_image();
				$now_merchant['adverimgurl']=!empty($now_merchant['adverimg']) ? $merchant_image_class->get_image_by_path($now_merchant['adverimg']) :'';
				$tmp_pic_arr = explode(';',$now_merchant['pic_info']);
				foreach($tmp_pic_arr as $key=>$value){
					$now_merchant['pic'][$key]['title'] = $value;
					$now_merchant['pic'][$key]['url'] = $merchant_image_class->get_image_by_path($value);
				}
			}
			$this->assign('now_merchant',$now_merchant);
			$mer_percent_rate = M('Merchant_percent_rate')->where(array('mer_id'=>$this->merchant_session['mer_id']))->find();
			if(!empty($mer_percent_rate)){
				$mer_offline = $mer_percent_rate['merchant_offline'];
			}else{
				$mer_offline = true;
			}

			$deposit =D('Deposit')->get_merchant_info($this->merchant_session['mer_id']);
			$this->assign('deposit', $deposit);
			$user = null;
			if ($now_merchant['uid']) {
				$user = M('User')->field(true)->where(array('uid' => $now_merchant['uid']))->find();
			}
			$this->assign('user', $user);
			$this->assign('pay_offline_open', $mer_offline);
			if(isset($config['group_page_row'])){
				$merchant_group_list = D('Group')->get_grouplist_by_MerchantId($now_merchant['mer_id']);
				$this->assign('merchant_group_list',$merchant_group_list);
			}
			$admin = M('Admin')->field('realname,invit_code')->where(array('invit_code'=>array('neq','')))->select();

			$this->assign('admin',$admin);
            // 根据开关获取获取商家类型信息
            if ($this->config['merchant_type_switch'] == 1 && $now_merchant['type_id']>0) {
                $type_info = M('Merchant_type')->where(array('type_id'=>$now_merchant['type_id']))->find();
            } else {
                $type_info = array();
            }
            //获取商家电子发票信息
            $data_invoice = M('Mall_e_config')->where(['mer_id'=>$this->merchant_session['mer_id']])->find();
            $this->assign('data_invoice',$data_invoice);
            $this->assign('service_lang', D("Lang")->service_lang);
            $this->assign('type_info', $type_info);
		}

        if($now_merchant['boss_uid']){
        	$bossUser = D('User')->get_user($now_merchant['boss_uid']);
        	$this->assign('boss_user',$bossUser);
        }
		$this->display();
    }

	public function sendsms(){
		$type = I('type',2);
		if($type != 4 && empty($this->merchant_session['mer_id'])){
			$this->error(L_('商家不存在'));
		}

		if(empty($_POST['phone'])){
			$this->error(L_('请输入手机号'));
		}
		$now_merchant = M('Merchant')->where(array('phone'=>$_POST['phone']))->find();
		if(empty($now_merchant)){
			$this->error(L_('该手机号暂未注册商家'));
		}
//		$this->merchant = $now_merchant;
//		$this->mer_id = $now_merchant['mer_id'];

		//防止1分钟内多次发送短信
//		$laste_sms = M('Merchant_sms_record')->where(array('mer_id'=>$this->merchant_session['mer_id']))->order('pigcms_id DESC')->find();
//		if(time()-$laste_sms['send_time']<60){
//			$this->error('一分钟内不能多次发送短信');
//		}
		$phone = $now_merchant['phone'];
		$code = mt_rand(1000, 9999);

		if($type == 4){
			$text = L_('验证码：X1。您正在进行找回密码操作，请不要把验证码泄露给其他人。',array('X1'=>$code));
		}else{
			$text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',array('X1'=>$code));
		}

		if($_POST['newphone']){
			$phone = $_POST['newphone'];
		}


		$columns = array();
		$columns['phone'] = $phone;
		$columns['mer_id'] = $this->mer_id;
		$columns['extra'] = $code;
		$columns['type'] = $type;
		$columns['status'] = 0;
		$columns['send_time'] = time();
		$columns['expire_time'] = $columns['send_time'] + 1200;
		$result = M("Merchant_sms_record")->add($columns);
		if (!$result){
			$this->error(L_('短信数据写入失败'));
		}
		$sms_data = array('mer_id' => $this->mer_id, 'store_id' => 0, 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'merchant_pwd');
		$_POST['phone_country_type'] && $sms_data['nationCode']  = $_POST['phone_country_type'];
		$sms_data['mobile'] = phone_format($sms_data['nationCode'], $sms_data['mobile']);
		
		$return = Sms::sendSms($sms_data);

		if ($result != 0) {
			$this->error(self::ConverSmsCode($return));
		}
		$this->error(0, $return);
	}

	//修改密码
    public function changePwd(){
    	$database_merchant = M('Merchant');
		$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
		// $now_merchant = $database_merchant->field('`pwd`')->where($condition_merchant)->find();
		// if (md5($_POST['old_pass'])!=$now_merchant['pwd']) {
		// 	exit(json_encode(array('error'=>'1','msg'=>'原密码输入错误')));
		// }

        if(md5($_POST['new_pass']) == $this->merchant_session['pwd']){
			exit(json_encode(array('error'=>'1','msg'=>'不能与原密码一致')));
		}

        if(strlen($_POST['new_pass']) < 6){
			exit(json_encode(array('error'=>'1','msg'=>'请输入6位或以上的密码')));
		}

		if($_POST['new_pass'] != $_POST['re_pass']){
			exit(json_encode(array('error'=>'1','msg'=>'两次新密码输入不一致，请重新输入')));
		}
		$data_merchant['pwd'] = md5($_POST['new_pass']);
        if($database_merchant->where($condition_merchant)->data($data_merchant)->save()){
        	$_SESSION['merchant']['pwd'] = md5($_POST['new_pass']);
			exit(json_encode(array('error'=>'0','msg'=>'修改成功')));
        }else{
			exit(json_encode(array('error'=>'1','msg'=>'修改失败')));
        }
    }
	

	public function sendsms2(){
		$type = I('type',2);
		if($type != 4 && empty($this->merchant_session['mer_id'])){
			$this->error(L_('商家不存在'));
		}
		$now_merchant = M('Merchant')->where(array('phone'=>$_POST['newphone']))->find();
		if(!empty($now_merchant)){
			$this->error(L_('该手机号已注册商家'));
		}

		if(empty($_POST['newphone'])){
			$this->error(L_('请输入手机号'));
		}
		$code = mt_rand(1000, 9999);

		if($type == 4){
			$text = L_('验证码：X1。您正在进行找回密码操作，请不要把验证码泄露给其他人。',array('X1'=>$code));
		}else{
			$text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',array('X1'=>$code));
		}

		$phone = $_POST['newphone'];
		$columns = array();
		$columns['phone'] = $phone;
		$columns['mer_id'] = $this->mer_id;
		$columns['extra'] = $code;
		$columns['type'] = $type;
		$columns['status'] = 0;
		$columns['send_time'] = time();
		$columns['expire_time'] = $columns['send_time'] + 1200;
		$result = M("Merchant_sms_record")->add($columns);
		if (!$result){
			$this->error(L_('短信数据写入失败'));
		}
		$sms_data = array('mer_id' => $this->mer_id, 'store_id' => 0, 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'merchant_pwd');
		$_POST['phone_country_type'] && $sms_data['nationCode']  = $_POST['phone_country_type'];
		$return = Sms::sendSms($sms_data);

		if ($result != 0) {
			$this->error(self::ConverSmsCode($return));
		}
		$this->error(0, $return);
	}

    public function merchant_promote()
    {
    	$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
		$now_merchant = $database_merchant->field(true,'pwd')->where($condition_merchant)->find();

		if(!empty($now_merchant['pic_info'])){
			$merchant_image_class = new merchant_image();
			$tmp_pic_arr = explode(';',$now_merchant['pic_info']);
			foreach($tmp_pic_arr as $key=>$value){
				$now_merchant['pic'][$key]['title'] = $value;
				$now_merchant['pic'][$key]['url'] = $merchant_image_class->get_image_by_path($value);
			}
		}
		$this->assign('now_merchant',$now_merchant);

		$merchant_group_list = D('Group')->get_grouplist_by_MerchantId($now_merchant['mer_id']);

		$this->assign('merchant_group_list',$merchant_group_list);


		$hits = D('Group')->get_hits_log($now_merchant['mer_id']);
		$this->assign('hits', $hits['group_list']);

		$this->assign('pagebar', $hits['pagebar']);

    	$this->display();
    }
	public function merchant_indexsort(){
		if(IS_POST){
			$database_merchant = D('Merchant');
			//转存首页储存值
			$group_indexsort = intval($_POST['group_indexsort']);
			if($group_indexsort){
				$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
				$now_merchant = $database_merchant->field('`storage_indexsort`')->where($condition_merchant)->find();
				if($now_merchant['storage_indexsort']){
					$condition_group['group_id'] = $group_indexsort;
					if(D('Group')->where($condition_group)->setInc('index_sort',$now_merchant['storage_indexsort'])){
						$database_merchant->where($condition_merchant)->setField('storage_indexsort','0');
					}
				}
			}

			//设置团购自动增长
			$indexsort_groupid = intval($_POST['indexsort_groupid']);
			$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
			$database_merchant->where($condition_merchant)->setField('auto_indexsort_groupid',$indexsort_groupid);
		}
	}

	public  function ajax_upload_wx_media(){
		if($_FILES['imgFile']['error'] != 4){
			$path = $_GET['path'] ?$_GET['path']:'system';
			$image = D('Image')->handle(rand(1,100), $path, 1);
			if ($image['error']) {
				exit(json_encode($image));
			} else {
				$url = $_SERVER['DOCUMENT_ROOT'].$image['url']['imgFile'];
				$mode = D('Access_token_expires');
				$res = $mode->get_access_token();
				import('ORG.Net.Http');
				$http = new Http();
				$file  = $url;
				$return = $http->curlUploadFile('https://api.weixin.qq.com/cgi-bin/media/upload?type=image&access_token='.$res['access_token'],$file,1);
				$return = json_decode($return,true);

				exit(json_encode(array('error' => 0, 'url' => $return['media_id'],'title' => $image['url']['imgFile'])));
			}

		} else {
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}

	public function ajax_upload_pic(){
		if($_FILES['imgFile']['error'] != 4){
			$path = $_GET['path'] ?$_GET['path']:'merchant';
			$image = D('Image')->handle($this->merchant_session['mer_id'], $path, 1);
			if ($image['error']) {
				exit(json_encode($image));
			} else {
				$title = $image['title']['imgFile'];
				$merchant_image_class = new merchant_image();
				$url = $merchant_image_class->get_image_by_path($title);
                $url = trim($url,'/');
				$url = str_replace('/merchant/','/'.$path.'/',$url);
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			}
		} else {
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}

	public  function ajax_upload_pic_wx(){
		if($_FILES['imgFile']['error'] != 4){
			$path = $_GET['path'] ?$_GET['path']:'merchant';
			$image = D('Image')->handle($this->merchant_session['mer_id'], $path, 1);
			if ($image['error']) {
				exit(json_encode($image));
			} else {
				$url = $_SERVER['DOCUMENT_ROOT'].$image['url']['imgFile'];
				$mode = D('Access_token_expires');
				$res = $mode->get_access_token();
				import('ORG.Net.Http');
				$http = new Http();
				$file  = $url;
				$return = $http->curlUploadFile('https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$res['access_token'],$file,1);
				$return = json_decode($return,true);
				exit(json_encode(array('error' => 0, 'url' => $return['url'])));
			}

		} else {
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}
	public function ajax_del_pic(){
		// $merchant_image_class = new merchant_image();
		// $merchant_image_class->del_image_by_path($_POST['path']);
	}
	/* 店铺管理 */
	public function store()
    {
        $mer_id = $this->merchant_session['mer_id'];
        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['mer_id'] = $mer_id;
        if ($this->merchant_session['store_id']) {
            $count_store = 1;
        } else {
            $count_store = $database_merchant_store->where("mer_id='{$mer_id}' AND status<>4")->count();
        }
        
		$where = [
			's.mer_id'=>$mer_id,
			's.status'=>['neq',4]
		];
        import('@.ORG.merchant_page');
        $p = new Page($count_store, 15);
        if ($this->merchant_session['store_id']) {
			$where['s.store_id'] = $this->merchant_session['store_id'];
			$store_list = D('Merchant_store')->alias('s')->join('LEFT JOIN '.C('DB_PREFIX') . 'area AS a ON `s`.`area_id`=`a`.`area_id`')
						->where($where)
						->select();
        } else {
            $store_list = D('Merchant_store')->alias('s')->join('LEFT JOIN '.C('DB_PREFIX') . 'area AS a ON `s`.`area_id`=`a`.`area_id`')
						->where($where)
						->order('`sort` DESC,`store_id` ASC')
						->limit($p->firstRow . ',' . $p->listRows)
						->select();
        }
		
        
        $this->assign('store_list', $store_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        
        $this->display();
    }
	public function store_ajax_upload_pic() {
		if ($_FILES['imgFile']['error'] != 4) {
            $path = isset($_GET['path']) ? trim($_GET['path']) : 'store';
			$image = D('Image')->handle($this->merchant_session['mer_id'], $path, 1);
			if ($image['error']) {
				exit(json_encode($image));
			} else {
                exit(json_encode(array('error' => 0, 'long_url' => replace_file_domain($image['url']['imgFile']), 'url' => $image['url']['imgFile'], 'title' => $image['title']['imgFile'])));
			}
		} else {
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}
	
    public function pickAddressUploadPic() {
        if ($_FILES['imgFile']['error'] != 4) {
            $image = D('Image')->handle($this->merchant_session['mer_id'], 'pick_address', 1);
            if ($image['error']) {
                exit(json_encode($image));
            } else {
                exit(json_encode(array('error' => 0, 'long_url' => replace_file_domain($image['url']['file']), 'url' => $image['url']['file'], 'title' => $image['title']['file'])));
            }
        } else {
            exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
        }
    }
	public function store_ajax_del_pic(){
		//$store_image_class = new store_image();
		//$store_image_class->del_image_by_path($_POST['path']);
	}
	/* 添加店铺 */
	public function store_add(){
	    if ($this->merchant_session['store_id']) {
	        $this->error(L_('您没有创建店铺的权限！'));
	    }

		$database_merchant_store = D('Merchant_store');
		if(IS_POST){
//			var_dump($_POST);die;

            if(empty($_POST['name'])){
				$this->error(L_('店铺名称必填！'));
			}
			if(empty($_POST['phone'])){
				$this->error(L_('联系电话必填！'));
			}
			if(empty($_POST['long_lat'])){
				$this->error(L_('店铺经纬度必填！'));
			}
			if(empty($_POST['adress'])){
				$this->error(L_('店铺地址必填！'));
			}
			// if(empty($_POST['permoney'])){
			// 	$this->error(L_('人均消费必填！'));
			// }
			// if(empty($_POST['feature'])){
			// 	$this->error(L_('店铺特色必填！'));
			// }
// 			if(empty($_POST['trafficroute'])){
// 				$this->error('交通路线必填！');
// 			}
// 			
 			$_POST['age_limit_buy'] = intval($_POST['age_limit_buy']);
			if($_POST['age_limit_buy_switch'] == 1 && intval($_POST['age_limit_buy']) > 99){
				$this->error(L_('年龄限制不能超过100岁！'));
			}
			if(empty($_POST['pic'])){
				$this->error(L_('请至少上传一张图片'));
			}
			$_POST['pic_info'] = implode(';',$_POST['pic']);

			// if(empty($_POST['txt_info'])){
			// 	$this->error(L_('请输入店铺描述信息'));
			// }
			
			if($_POST['logo']){
				$_POST['logo'] = str_replace(file_domain(),'',$_POST['logo']);
			}

			//判断关键词
			$keywords = trim($_POST['keywords']);
			if(!empty($keywords)){
				$tmp_key_arr = explode(' ',$keywords);
				$key_arr = array();
				foreach($tmp_key_arr as $value){
					if(!empty($value)){
						array_push($key_arr,$value);
					}
				}
				if(count($key_arr)>5){
					$this->error(L_('关键词最多5个。'));
				}
			}
			$_POST['supplier_nav_first_name'] = isset($_POST['supplier_nav_first_name'])? $_POST['supplier_nav_first_name'] : '';
            $_POST['supplier_nav_first_link'] = isset($_POST['supplier_nav_first_link'])? $_POST['supplier_nav_first_link'] : '';
            $_POST['supplier_nav_second_name'] = isset($_POST['supplier_nav_second_name'])? $_POST['supplier_nav_second_name'] : '';
            $_POST['supplier_nav_second_link'] = isset($_POST['supplier_nav_second_link'])? $_POST['supplier_nav_second_link'] : '';
			//营业时间
// 			$office_time = array();
// 			if($_POST['office_start_time'] != '00:00' || $_POST['office_stop_time'] != '00:00'){
// 				array_push($office_time,array('open'=>$_POST['office_start_time'],'close'=>$_POST['office_stop_time']));
// 			}
// 			if($_POST['office_start_time2'] != '00:00' || $_POST['office_stop_time2'] != '00:00'){
// 				array_push($office_time,array('open'=>$_POST['office_start_time2'],'close'=>$_POST['office_stop_time2']));
// 			}
// 			if($_POST['office_start_time3'] != '00:00' || $_POST['office_stop_time3'] != '00:00'){
// 				array_push($office_time,array('open'=>$_POST['office_start_time3'],'close'=>$_POST['office_stop_time3']));
// 			}
			$_POST['office_time'] = '';

			$_POST['sort'] = intval($_POST['sort']);
			$long_lat = explode(',',$_POST['long_lat']);
			$_POST['long'] = $long_lat[0];
			$_POST['lat'] = $long_lat[1];
			$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
			$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
			$_POST['add_from'] = '0';
			$_POST['mer_id'] = $this->merchant_session['mer_id'];
			$ismain=intval($_POST['ismain']);
			if($this->config['store_verify']){
				$_POST['status'] = $this->merchant_session['issign'] ? '1' :'2';
			}else{
				$_POST['status'] = '1';
			}

			$_POST['discount_txt'] = '';
			$discount_type = isset($_POST['discount_type']) ? intval($_POST['discount_type']) : 0;
			if ($discount_type == 1) {
				$discount_percent = isset($_POST['discount_percent']) ? (intval($_POST['discount_percent'] * 10) / 10) : 0;
				if ($discount_percent > 0 && $discount_percent < 10) {
					//$_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent));
					if($this->config['open_extra_price']==1){
						$_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent,'discount_limit'=>$_POST['discount_limit'],'discount_limit_percent'=>$_POST['discount_limit_percent']));
					}else{
						$_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent));
					}
				} elseif ($discount_percent <= 0 || $discount_percent >= 10) {
					$this->error(L_('折扣率必须在0~10之间的数。'));
				}
			} elseif ($discount_type == 2) {
				$condition_price = isset($_POST['condition_price']) ? (intval($_POST['condition_price'] * 100) / 100) : 0;
				$minus_price = isset($_POST['minus_price']) ? (intval($_POST['minus_price'] * 100) / 100) : 0;
				if ($condition_price < 0 || $minus_price < 0 || $minus_price > $condition_price) {
					$this->error(L_('满减的填写不正确，必须都是大于0且满足的金额要大于减免金额。'));
				}
				if ($condition_price > 0 && $minus_price > 0 && $minus_price < $condition_price) {
					$_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'condition_price' => $condition_price, 'minus_price' => $minus_price));
				}
			}

			if($ismain==1){
			   $database_merchant_store->where(array('mer_id'=>$_POST['mer_id']))->save(array('ismain'=>0));
			}
			$_POST['store_type'] = isset($_POST['store_type']) ? intval($_POST['store_type']) : 1;
			$leveloff = isset($_POST['leveloff']) ? $_POST['leveloff'] : false;
			$newleveloff = array();
			if (!empty($leveloff)) {
				foreach ($leveloff as $kk => $vv) {
					$vv['type'] = intval($vv['type']);
					$vv['vv'] = intval($vv['vv']);
					if (($vv['type'] > 0) && ($vv['vv'] > 0)) {
						$vv['level'] = $kk;
						$newleveloff[$kk] = $vv;
					}
				}
			}

			$_POST['leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';

			if($merchant_ststore_addore_id = $database_merchant_store->data($_POST)->add()){
				fdump($merchant_ststore_addore_id,"==dingding=",true);
				//绑定叮叮配送
				if($this->config['dindin_is_open'] == 1){
					if($_POST['is_open_dingding'] == 1){
						import('@.ORG.Dingding');
						$dingDing = new Dingding('','','','','','','','');
						$province = M('Area')->where(array('province_id'=>$_POST['city_id']))->find();
						$city = M('Area')->where(array('area_id'=>$_POST['city_id']))->find();
						$area = M('Area')->where(array('area_id'=>$_POST['area_id']))->find();
						$long_lat = explode(',',$_POST['long_lat']);
						$postData = array(
							'isadd'=>1,
							'cargotype_code'=>1,
							'appid'=>$this->config['dingding_appid'],
							'username'=>$_POST['dingding_user_name'],
							'password'=>$_POST['dingding_pass'],
							'station_name'=>$_POST['name'],
							'city_name'=>$city['area_name'],
							'area_name'=>$area['area_name'],
							'station_address'=>$province['area_name'].$city['area_name'].$area['area_name'].$_POST['adress'],
							'lng'=>$long_lat[0],
							'lat'=>$long_lat[1],
							'contact_name'=>$_POST['name'],
							'mobile'=>$_POST['kefu_phone'],
							'per_userid'=>$merchant_ststore_addore_id,
						);
						fdump($postData,"==dingding=",true);
						$res = $dingDing->createUpdateN($postData);
						$res = json_decode($res,true);
						M('Merchant_store')->where(array('store_id'=>$merchant_ststore_addore_id))->save(array('dingding_per_userid'=>$merchant_ststore_addore_id));
						fdump($res,"==dingding=",true);
					}
				}
				//营业时间
				D('Merchant_store_open_time')->add_edit_data($_POST['mer_id'],$merchant_ststore_addore_id,$_POST['week_status'],$_POST['open_time'],$_POST['close_time'],1);
				M('Merchant_score')->add(array('parent_id'=>$merchant_ststore_addore_id,'type'=>2));
				//判断关键词
				if(!empty($key_arr)){
					$database_keywords = D('Keywords');
					$data_keywords['third_id'] = $merchant_ststore_addore_id;
					$data_keywords['third_type'] = 'Merchant_store';
					foreach($key_arr as $value){
						$data_keywords['keyword'] = $value;
						$database_keywords->data($data_keywords)->add();
					}
				}

				//多语言表增加数据
				$lang_model = D('Lang');
				$_POST['store_id'] = $merchant_ststore_addore_id;
				$lang_model->add_lang_data('Merchant_store',$_POST);				

				// 自动添加线下支付方式
				$database_pay = D('Store_pay');
				$store_pay_data['store_id'] = $merchant_ststore_addore_id;
				$store_pay_data['name'] = L_('线下支付');
				$database_pay->add($store_pay_data);

				$this->success(L_('添加成功！'));
			}else{
				$this->error(L_('添加失败！请重试~'));
			}
		}else{
		    $merchant_mstore = $database_merchant_store->where(array('mer_id' => $this->merchant_session['mer_id'], 'ismain' => 1))->find();
			$levelDb = M('User_level');
			$tmparr = M(_view('User_level'))->order('id ASC')->select();
			$levelarr = array();
			if ($tmparr && $this->config['level_onoff']) {
				foreach ($tmparr as $vv) {
					$vv['vv'] = $vv['level']['vv'];
					$vv['type'] = $vv['level']['type'];
					$levelarr[$vv['level']] = $vv;
				}
			}
			unset($tmparr);
			$this->assign('levelarr', $levelarr);
			
			//找到商家所在城市的经纬度
			$now_city = M('Area')->where(['area_id'=>$this->merchant_session['city_id']])->find();
			$this->assign('now_city',$now_city);

			$ismainno=true;
			if(!empty($merchant_mstore)) $ismainno=false;
			$this->assign('ismainno',$ismainno);
			$this->assign('open_time',$this->get_empty_open_time());
			$this->display();
		}
	}

	/**
	 * @desc 营业时间空数组
	 * @return array
	 */
	protected  function get_empty_open_time(){
		$week = array('周日','周一','周二','周三','周四','周五','周六');
		$sort = array(1,2,3);
		$open_time = array();
		foreach($week as $key => $val){
			$open_time[$key]['show_week'] =$val;
			$open_time[$key]['week']  =$key;
			foreach($sort as $k => $v){
				$open_time[$key]['sort'][] = $v;
			}
		}
		return $open_time;
	}
	/* 编辑店铺 */
	public function store_edit(){
		$database_merchant_store = D('Merchant_store');

		if(IS_POST){
//var_dump($_POST);die;
		    if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $_POST['store_id']) {
		        $this->error(L_('您没有修改的权限'));
		    }
			if(empty($_POST['name'])){
				$this->error(L_('店铺名称必填！'));
			}
			if(empty($_POST['phone'])){
				$this->error(L_('联系电话必填！'));
			}
			if(empty($_POST['long_lat'])){
				$this->error(L_('店铺经纬度必填！'));
			}
			if(empty($_POST['adress'])){
				$this->error(L_('店铺地址必填！'));
			}
			// if(empty($_POST['permoney'])){
			// 	$this->error(L_('人均消费必填！'));
			// }
			// if(empty($_POST['feature'])){
			// 	$this->error(L_('店铺特色必填！'));
			// }
// 			if(empty($_POST['trafficroute'])){
// 				$this->error('交通路线必填！');
// 			}
			if(empty($_POST['pic'])){
				$this->error(L_('请上传一张商家图片'));
			}
			$_POST['pic_info'] = implode(';',$_POST['pic']);

			// if(empty($_POST['txt_info'])){
			// 	$this->error(L_('请输入店铺描述信息'));
			// }
			
			if($_POST['logo']){
				$_POST['logo'] = str_replace(file_domain(),'',$_POST['logo']);
			}
			
			//小程序分享图若未设置，则保存空字符串
			if(!$_POST['wxapp_share_pic']){
				$_POST['wxapp_share_pic'] = '';
			}
			
			//判断关键词
			$keywords = trim($_POST['keywords']);
			if(!empty($keywords)){
				$tmp_key_arr = explode(' ',$keywords);
				$key_arr = array();
				foreach($tmp_key_arr as $value){
					if(!empty($value)){
						array_push($key_arr,$value);
					}
				}
				if(count($key_arr)>5){
					$this->error(L_('关键词最多5个。'));
				}
			}
			$_POST['age_limit_buy'] = intval($_POST['age_limit_buy']);
			if($_POST['age_limit_buy_switch'] == 1 && intval($_POST['age_limit_buy']) > 99){
				$this->error(L_('年龄限制不能超过100岁！'));
			}

			$_POST['supplier_nav_first_name'] = isset($_POST['supplier_nav_first_name'])? $_POST['supplier_nav_first_name'] : '';
            $_POST['supplier_nav_first_link'] = isset($_POST['supplier_nav_first_link'])? $_POST['supplier_nav_first_link'] : '';
            $_POST['supplier_nav_second_name'] = isset($_POST['supplier_nav_second_name'])? $_POST['supplier_nav_second_name'] : '';
            $_POST['supplier_nav_second_link'] = isset($_POST['supplier_nav_second_link'])? $_POST['supplier_nav_second_link'] : '';
// 			$office_time = array();
// 			if($_POST['office_start_time'] != '00:00' || $_POST['office_stop_time'] != '00:00'){
// 				array_push($office_time,array('open'=>$_POST['office_start_time'],'close'=>$_POST['office_stop_time']));
// 			}
// 			if($_POST['office_start_time2'] != '00:00' || $_POST['office_stop_time2'] != '00:00'){
// 				array_push($office_time,array('open'=>$_POST['office_start_time2'],'close'=>$_POST['office_stop_time2']));
// 			}
// 			if($_POST['office_start_time3'] != '00:00' || $_POST['office_stop_time3'] != '00:00'){
// 				array_push($office_time,array('open'=>$_POST['office_start_time3'],'close'=>$_POST['office_stop_time3']));
// 			}
			$_POST['office_time'] = '';

			$_POST['sort'] = intval($_POST['sort']);
			$long_lat = explode(',',$_POST['long_lat']);
			$_POST['long'] = $long_lat[0];
			$_POST['lat'] = $long_lat[1];
			$_POST['last_time'] = $_SERVER['REQUEST_TIME'];

			$_POST['discount_txt'] = '';
			$discount_type = isset($_POST['discount_type']) ? intval($_POST['discount_type']) : 0;
			if ($discount_type == 1) {
				$discount_percent = isset($_POST['discount_percent']) ? (intval($_POST['discount_percent'] * 10) / 10) : 0;
				if ($discount_percent >= 0 && $discount_percent <= 10) {
					if($this->config['open_extra_price']==1){
						$_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent,'discount_limit'=>$_POST['discount_limit'],'discount_limit_percent'=>$_POST['discount_limit_percent']));
					}else{
						$_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent));
					}
				} elseif ($discount_percent < 0 || $discount_percent > 10) {
					$this->error(L_('折扣率必须在0~10之间的数。'));
				}
			} elseif ($discount_type == 2) {
				$condition_price = isset($_POST['condition_price']) ? (intval($_POST['condition_price'] * 100) / 100) : 0;
				$minus_price = isset($_POST['minus_price']) ? (intval($_POST['minus_price'] * 100) / 100) : 0;
				if ($condition_price < 0 || $minus_price < 0 || $minus_price > $condition_price) {
					$this->error(L_('满减的填写不正确，必须都是大于0且满足的金额要大于减免金额。'));
				}
				if ($condition_price > 0 && $minus_price > 0 && $minus_price < $condition_price) {
					$_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'condition_price' => $condition_price, 'minus_price' => $minus_price));
				}
			}

			$database_merchant_store_model = M(_view('Merchant_store'));
			$condition_merchant_store['store_id'] = $_POST['store_id'];
			$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
			$now_store = $database_merchant_store_model->where($condition_merchant_store)->find();
			$_POST['store_type'] = isset($_POST['store_type']) ? intval($_POST['store_type']) : 1;
			unset($_POST['store_id']);
			$ismain = intval($_POST['ismain']);
			if($ismain==1){
			   $database_merchant_store->where(array('mer_id'=>$now_store['mer_id']))->save(array('ismain'=>0));
			}
			$leveloff = isset($_POST['leveloff']) ? $_POST['leveloff'] : false;
			$newleveloff = array();
			if (!empty($leveloff)) {
				foreach ($leveloff as $kk => $vv) {
					$vv['type'] = intval($vv['type']);
					$vv['vv'] = intval($vv['vv']);
					if (($vv['type'] > 0) && ($vv['vv'] > 0)) {
						$vv['level'] = $kk;
						$newleveloff[$kk] = $vv;
					}
				}
			}

			$_POST['leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';
			//营业时间
			D('Merchant_store_open_time')->add_edit_data($now_store['mer_id'],$condition_merchant_store['store_id'],$_POST['week_status'],$_POST['open_time'],$_POST['close_time']);
			unset($_POST['week_status']);
			unset($_POST['open_time']);
			unset($_POST['close_time']);

			if($_POST['plat_recommend_type'] == 'group'){
			    $_POST['plat_recommend_info'] = 'group_'.$_POST['plat_recommend_type_group'];
            }
			if($database_merchant_store->where($condition_merchant_store)->data($_POST)->save()){
			    //更新IM店铺客服头像和昵称
                hook::hook_exec('merchant_store.after_update',array('type'=>'store','store_id'=>$condition_merchant_store['store_id']));

				//加入叮叮配送处理
				//绑定叮叮配送
				if($this->config['dindin_is_open'] == 1){
					if($_POST['is_open_dingding'] == 1){
						import('@.ORG.Dingding');
						$dingDing = new Dingding('','','','','','','','');
						$province = M('Area')->where(array('province_id'=>$_POST['city_id']))->find();
						$city = M('Area')->where(array('area_id'=>$_POST['city_id']))->find();
						$area = M('Area')->where(array('area_id'=>$_POST['area_id']))->find();
						$long_lat = explode(',',$_POST['long_lat']);
						$postData = array(
							'isadd'=>0,
							'cargotype_code'=>1,
							'appid'=>$this->config['dingding_appid'],
							'username'=>$_POST['dingding_user_name'],
							'password'=>$_POST['dingding_pass'],
							'station_name'=>$_POST['name'],
							'city_name'=>$city['area_name'],
							'area_name'=>$area['area_name'],
							'station_address'=>$city['area_name'].$area['area_name'].$_POST['adress'],
							'lng'=>$long_lat[0],
							'lat'=>$long_lat[1],
							'contact_name'=>$_POST['name'],
							'mobile'=>$_POST['kefu_phone'],
							'per_userid'=>empty($_POST['dingding_per_userid'])?$_POST['store_id']:$_POST['dingding_per_userid'],
						);
						fdump($_POST['store_id'],"==dingding_edit=",true);
						fdump($postData,"==dingding_edit=",true);
						$res = $dingDing->createUpdateN($postData);
						$res = json_decode($res,true);

						fdump($res,"==dingding_edit=",true);
					}
				}
				$data_keywords['third_id'] = $condition_merchant_store['store_id'];
				$data_keywords['third_type'] = 'Merchant_store';
				$database_keywords = D('Keywords');
				$database_keywords->where($data_keywords)->delete();
				//判断关键词
				if(!empty($key_arr)){
					foreach($key_arr as $value){
						$data_keywords['keyword'] = $value;
						$database_keywords->data($data_keywords)->add();
					}
				}

				//多语言表增加数据
				$lang_model = D('Lang');
				$_POST['store_id'] = $condition_merchant_store['store_id'];
				$lang_model->edit_lang_data('Merchant_store',$_POST);

				//判断票务插件
				if($this->config['store_ticket_have']){
					$database_store_trade_ticket = D('Merchant_store_trade_ticket');
					$condition_store_trade_ticket = array('store_id'=>$condition_merchant_store['store_id']);
					$data_store_trade_ticket = $_POST['store_trade_ticket'];
					if($database_store_trade_ticket->where($condition_store_trade_ticket)->find()){
						$database_store_trade_ticket->where($condition_store_trade_ticket)->data($data_store_trade_ticket)->save();
					}else{
						$data_store_trade_ticket = array_merge($condition_store_trade_ticket,$data_store_trade_ticket);
						$database_store_trade_ticket->data($data_store_trade_ticket)->add();
					}
				}

//				$this->success(L_('保存成功！'));
				$this->success(L_('保存成功！'),U('Config/store'));
			}else{
				$this->error(L_('保存失败！！您是不是没做过修改？请重试~'));
			}
		}
		else{
		    if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $_GET['id']) {
		        $this->error(L_('您没有修改的权限'));
		    }
			$condition_merchant_store['store_id'] = $_GET['id'];
			$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
			$database_merchant_store_model = M(_view('Merchant_store'));
			$now_store = $database_merchant_store_model->where($condition_merchant_store)->find();
            $now_store['supplier_info'] = replace_file_domain_content($now_store['supplier_info']);
			if(empty($now_store)){
				$this->error(L_('店铺不存在！'));
			}

			if(!empty($now_store['pic_info'])){
				$store_image_class = new store_image();
				$tmp_pic_arr = explode(';',$now_store['pic_info']);
				foreach($tmp_pic_arr as $key=>$value){
					$now_store['pic'][] = $value;
				}
			}
			$keywords = D('Keywords')->where(array('third_type' => 'Merchant_store', 'third_id' => $condition_merchant_store['store_id']))->select();
			$str = "";
			foreach ($keywords as $key) {
				$str .= $key['keyword'] . " ";
			}
			$now_store['keywords'] = $str;

			$now_store['discount_txt'] = unserialize($now_store['discount_txt']);


			//判断票务插件
			// if($this->config['store_ticket_have']){
				$database_store_trade_ticket = D('Merchant_store_trade_ticket');
				$condition_store_trade_ticket = array('store_id'=>$condition_merchant_store['store_id']);
				$now_store['store_trade_ticket'] = $database_store_trade_ticket->where($condition_store_trade_ticket)->find();
			// }

			//判断平台推荐
			if($now_store['plat_recommend_info']){
				$tmp_plat_recommend_info = explode('_',$now_store['plat_recommend_info']);
				$now_store['plat_recommend_info_arr']['type'] = $tmp_plat_recommend_info[0];
				$now_store['plat_recommend_info_arr']['value'] = $tmp_plat_recommend_info[1];
			}

			//得到店铺下的团购
			$store_id = $now_store['store_id'];
			$now_time = $_SERVER['REQUEST_TIME'];
			$condition_where_plat_recommend_group = "`g`.`group_id`=`gs`.`group_id` AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
			$plat_recommend_group_list = D('')->field('`g`.`group_id`,`g`.`name` AS `group_name`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where($condition_where_plat_recommend_group)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->select();
			$this->assign('plat_recommend_group_list',$plat_recommend_group_list);

			//得到店铺下的拼团
			$condition_where_plat_recommend_pin_group = "`g`.`group_id`=`gs`.`group_id` AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`pin_num`>1 AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
			$plat_recommend_pin_group_list = D('')->field('`g`.`group_id`,`g`.`name` AS `group_name`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where($condition_where_plat_recommend_pin_group)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->select();
			$this->assign('plat_recommend_pin_group_list',$plat_recommend_pin_group_list);


			$this->assign('now_store',$now_store);

			$leveloff = !empty($now_store['leveloff']) ? unserialize($now_store['leveloff']) : false;
			$levelDb = M(_view('User_level'));
			$tmparr = $levelDb->order('id ASC')->select();

			$levelarr = array();
			if ($tmparr && $this->config['level_onoff']) {
				foreach ($tmparr as $vv) {
					if (!empty($leveloff) && isset($leveloff[$vv['level']])) {
						$vv['vv'] = $leveloff[$vv['level']]['vv'];
						$vv['type'] = $leveloff[$vv['level']]['type'];
					} else {
						$vv['vv'] = '';
						$vv['type'] = '';
					}
					$levelarr[$vv['level']] = $vv;
				}
			}
			unset($tmparr);
			$this->assign('levelarr', $levelarr);
			$time_date = D('Merchant_store_open_time')->get_store_open_time($now_store['mer_id'],$now_store['store_id']);
			$this->assign('time_date', $time_date);

			$this->display();
		}
	}

	/* 店铺状态 */
	public function store_status(){
		$database_merchant_store = D('Merchant_store');
		$data_merchant_store['status'] = $_POST['type'] == 'open' ? '1' : '0';
		$condition_merchant_store['store_id'] = $_POST['id'];
		$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
		if($database_merchant_store->where($condition_merchant_store)->data($data_merchant_store)->save()){
			exit('1');
		}else{
			exit;
		}
	}
	/* 删除店铺 */
	public function store_del()
    {
        if ($this->merchant_session['store_id']) {
            $this->error(L_('您没有权限删除'));
        }
		$condition_merchant_store['s.store_id'] = intval(trim($_GET['id']));
		$condition_merchant_store['g.group_id'] = array('exp','is not null');

		$group_storeDb = D('Group_store');
		if ( $group_storeDb->join('as s LEFT JOIN '.C('DB_PREFIX').'group g ON s.group_id = g.group_id ')->where($condition_merchant_store)->order('s.group_id desc')->find() ) {
			//$this->error(L_('该店铺下有') . $this->config['group_alias_name'] . L_('，请先解除店铺与对应') . $this->config['group_alias_name'] . L_('的关系才能删除！'));
			$this->error(L_('该店铺下有X1，请先解除店铺与对应X2的关系才能删除！',array('X1'=>$this->config['group_alias_name'],'X2'=>$this->config['group_alias_name'])));
			exit();
		}
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store_['mer_id'] = $this->merchant_session['mer_id'];
		$condition_merchant_store_['store_id'] = intval(trim($_GET['id']));
		/**
		 * *$database_merchant_store->where($condition_merchant_store)->delete()**改软删除*4禁用**
		 */
		if ($database_merchant_store->where($condition_merchant_store_)->save(array('status' => 4))) {
			$this->success(L_('删除成功！'));
		} else {
			$this->error(L_('删除失败！'));
		}
    }

	public function staff()
    {
        if ($this->merchant_session['store_id'] && $_GET['store_id'] != $this->merchant_session['store_id']) {
            $this->error(L_('您没有权限'));
        }
        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['store_id'] = $_GET['store_id'];
        $condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
        $now_store = $database_merchant_store->where($condition_merchant_store)->find();
        if (empty($now_store)) {
            $this->error(L_('店铺不存在！'));
        }
        $this->assign('now_store', $now_store);

        $condition_store_staff['token'] = $this->token;
        $condition_store_staff['store_id'] = $_GET['store_id'];
        $staff_list = D('Merchant_store_staff')->where($condition_store_staff)->order('`id` desc')->select();
        $this->assign('staff_list', $staff_list);
        $this->assign('staff_type', $this->staff_type);
        $this->display();
    }

    //设置店铺店员信息
	public function staffSet(){
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['store_id'] = $_GET['store_id'];
		$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
		$now_store = $database_merchant_store->where($condition_merchant_store)->find();
		if(empty($now_store)){
			$this->error(L_('店铺不存在！'));
		}
		$this->assign('staff_type', $this->staff_type);
		$this->assign('now_store',$now_store);

		$_POST['store_id'] = $now_store['store_id'];
		$company_staff_db = M('Merchant_store_staff');

		if(IS_POST){
			if (!trim($_POST['name']) || !trim($_POST['username'])){
				$this->error(L_('姓名、帐号都不能为空'));
			}
			$_POST['username'] = trim($_POST['username']);
			$_POST['tel'] = trim($_POST['tel']);
			$_POST['token'] = $this->token;
			$_POST['time'] = time();

			if (!isset($_GET['itemid'])){
				$condition_store_staff_username['username'] = $_POST['username'];
				if($company_staff_db->field('`id`')->where($condition_store_staff_username)->find()){
					$this->error(L_('帐号已经存在！请换一个。'));
				}
				if(!trim($_POST['password'])){
					$this->error(L_('密码不能为空'));
				}
				$_POST['password'] = md5($_POST['password']);

				if(!$company_staff_db->add($_POST)){
					$this->error(L_('添加失败，请重试。'));
				}
			}else{
				/* 检测帐号 */
				$condition_store_staff_username['username'] = $_POST['username'];
				$username_staff = $company_staff_db->field('`id`')->where($condition_store_staff_username)->find();
				if($username_staff && $username_staff['id'] != $_GET['itemid']){
					$this->error(L_('帐号已经存在！请换一个。'));
				}

				if(!trim($_POST['password'])){
					unset($_POST['password']);
				}else{
					$_POST['password'] = md5($_POST['password']);
				}
				if(!$company_staff_db->where(array('id'=>intval($_GET['itemid'])))->save($_POST)){
					$this->error(L_('修改失败，请重试。'));
				}
				//优化设置店员管理风景区门票功能
				if($_GET['itemid']==$_SESSION['staff']['id'] && is_numeric($_POST['is_verify_ticket'])){
					$_SESSION['staff']['is_verify_ticket']=$_POST['is_verify_ticket'];
				}
			}
			$this->success(L_('操作成功'));
		}else{
			if (isset($_GET['itemid'])) {
				$thisItem = $company_staff_db->where(array('id'=>intval($_GET['itemid'])))->find();
			} else {
				$thisItem['companyid'] = 0;
			}
			//获取商户是否开区景区功能
			$is_open_scenic=M('merchant')->where(array('mer_id'=>$this->merchant_session['mer_id']))->getfield('is_open_scenic');

			$this->assign('item', $thisItem);
			$this->assign('is_open_scenic', $is_open_scenic);
			$this->display('staffSet');
		}
	}
	public function staffDelete(){
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['store_id'] = $_GET['store_id'];
		$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
		$now_store = $database_merchant_store->where($condition_merchant_store)->find();
		if(empty($now_store)){
			$this->error(L_('店铺不存在！'));
		}
		$this->assign('now_store',$now_store);

		$company_staff_db = M('Merchant_store_staff');

		$condition_store_staff['token'] = $this->token;
		$condition_store_staff['id'] = $_GET['itemid'];
		if($company_staff_db->where($condition_store_staff)->delete()){
			$this->success(L_('操作成功'),U('Config/staff',array('store_id'=>$now_store['store_id'])));
		}else{
			$this->error(L_('操作失败，请重试。'));
		}

	}

    public function pick()
    {
        $pick_addr = D('Pick_address')->get_pick_addr_by_merid($this->merchant_session['mer_id'],true);
        $this->assign('pick_addr', $pick_addr);
        $this->display();
    }

    public function pick_address_add()
    {
        if (IS_POST) {
			if (empty($_POST['long_lat'])) {
				$this->error(L_('店铺经纬度必填！'));
			}
            if (empty($_POST['pick_addr'])||empty($_POST['phone'])) {
                $this->error(L_("信息不全，请检查！"));
            }
			$long_lat = explode(',',$_POST['long_lat']);
			$_POST['long'] = $long_lat[0];
			$_POST['lat'] = $long_lat[1];

            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            if(M('Pick_address')->add($_POST)){
                //多语言表增加数据
                $_POST['id'] = M('Pick_address')->getLastInsID();
                D('Lang')->add_lang_data('Pick_address',$_POST);

                $this->success(L_("保存成功！"));
            }else{
                $this->error(L_("保存失败！"));
            }

        }else{
            $this->display();
        }
    }

    public function pick_address_edit(){
        if(IS_POST){
			if(empty($_POST['long_lat'])){
				$this->error(L_('店铺经纬度必填！'));
			}
            if(empty($_POST['pick_addr'])||empty($_POST['phone'])){
                $this->error(L_("信息不全，请检查！"));
            }
        	$long_lat = explode(',',$_POST['long_lat']);
        	$_POST['long'] = $long_lat[0];
        	$_POST['lat'] = $long_lat[1];
            if(M('Pick_address')->where(array('id'=>$_POST['id']))->save($_POST)){
                //多语言表修改数据
                D('Lang')->edit_lang_data('Pick_address',$_POST);

                $this->success(L_("保存成功！"));
            }else{
                $this->error(L_("保存失败！"));
            }

        }else{
            if(!empty($_GET['id'])){
                $n = preg_match('/\d+/',$_GET['id'],$id);
                $pick_addr = M('Pick_address')->where(array('id'=>$id[0]))->find();
                $pick_addr['image'] =  replace_file_domain($pick_addr['image']);
                $this->assign('pick_addr',$pick_addr);
                $this->display();
            }else{
                $this->error(L_("访问失败！"));
            }
        }
    }

    public function pick_address_del()
    {
        if(IS_GET){
            $n = preg_match('/\d+/',$_GET['id'],$id);
            if(M('Pick_address')->where(array('id'=>$id[0]))->delete()){
                $this->success(L_("删除成功！"));
            }else{
                $this->error(L_("删除失败！"));
            }
        }
    }

    public function see_pick_pwd()
    {
    	$pick_id = isset($_GET['pick_id']) ? htmlspecialchars($_GET['pick_id']) : '';
    	$pick_id = substr($pick_id, 1);
    	$pick_address = M('Pick_address')->where(array('id' => $pick_id))->find();
    	if ($pick_address) {
    		if (IS_POST) {
    			$now_time = time();
    			$pick_address['pwd'] = $pwd = md5($pick_id . $now_time . rand(10000, 99999));
    			D('Pick_address')->where(array('id' => $pick_id))->save(array('last_time' => $now_time, 'pwd' => $pwd));
    		}
    		if (empty($pick_address['pwd'])) {
    			$now_time = time();
    			$pick_address['pwd'] = $pwd = md5($pick_id . $now_time . rand(10000, 99999));
    			D('Pick_address')->where(array('id' => $pick_id))->save(array('last_time' => $now_time, 'pwd' => $pwd));
    		}
    	} else {
    		$this->error(L_('配送点不存在！'));
    		exit;
    	}
    	$pick_address['pick_addr_id'] = 'p' . $pick_address['id'];
    	$this->assign('pick_address', $pick_address);
    	$this->display();
    }

	/**
	 * 自定义支付列表
	 */
	public function pay()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$pay_list = M('Store_pay')->field(true)->where(array('store_id' => $store_id))->select();
		$this->assign(array('now_store' => $now_store, 'pay_list' => $pay_list));
		$this->display();
	}

	public function pay_add()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store',$now_store);

		if (IS_POST) {
			$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
			if (empty($name)) {
				$this->error(L_('支付名称不能为空'));
				exit;
			}
			$database_pay = D('Store_pay');
			$data['store_id'] = $now_store['store_id'];
			$data['name'] = $name;
			if ($database_pay->add($data)) {
				$this->success(L_('添加成功！！'), U('Config/pay',array('store_id' => $now_store['store_id'])));
				exit;
			} else {
				$this->error(L_('添加失败！！请重试。'), U('Config/pay',array('store_id' => $now_store['store_id'])));
				exit;
			}
		}
		$this->display();
	}

	public function pay_edit()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if (!($pay = D('Store_pay')->field(true)->where(array('id' => $id, 'store_id' => $now_store['store_id']))->find())) {
			$this->error(L_('不存在的支付方式。'), U('Config/pay', array('store_id' => $now_store['store_id'])));
			exit;
		}
		$this->assign('now_store',$now_store);

		if (IS_POST) {
			$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
			if (empty($name)) {
				$this->error(L_('支付名称不能为空'));
				exit;
			}
			$database_pay = D('Store_pay');
			$data['id'] = $pay['id'];
			$data['store_id'] = $now_store['store_id'];
			$data['name'] = $name;
			if ($database_pay->data($data)->save()) {
				$this->success(L_('修改成功！！'), U('Config/pay', array('store_id' => $now_store['store_id'])));
				exit;
			} else {
				$this->error(L_('修改失败！！请重试。'), U('Config/pay', array('store_id' => $now_store['store_id'])));
				exit;
			}
		} else {
			$this->assign('now_pay', $pay);
		}
		$this->display();

	}

	public function pay_del()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$where = array('store_id' => $store_id, 'id' => $id);
		if ($pay = D('Store_pay')->field(true)->where($where)->find()) {
			if (D('Store_pay')->where($where)->delete()) {
				$this->success(L_('删除成功！'));
			} else {
				$this->error(L_('删除失败！'));
			}
		} else {
			$this->error(L_('不存在的支付方式。'), U('Config/pay', array('store_id' => $now_store['store_id'])));
		}
	}

	/* 检测店铺存在，并检测是不是归属于商家 */
	protected function check_store($store_id)
	{
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['store_id'] = $store_id;
		$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
		$now_store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
		if (empty($now_store)) {
			$this->error(L_('店铺不存在！'));
		} else {
			return $now_store;
		}
	}

	/**
	 * 编辑店铺资质审核资料
	 */
	public function auth_edit()
	{
		$database_merchant_store = D('Merchant_store');
		$database_store_authfile = D('Merchant_store_authfile');
		$store_id = intval($_GET['id']);

		if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $store_id) {
		    $this->error(L_('您没有修改的权限'));
		}
		$now_store = $this->check_store($store_id);
		if (IS_POST) {
			if(empty($_POST['pic'])){
				$this->error(L_('请至少上传一张图片'));
			}
			$where = array('store_id' => $store_id);
			$data['auth_files'] = implode(';', $_POST['pic']);

			if ($now_store['auth'] < 3) {
				if ($authfile_row = $database_store_authfile->field(true)->where($where)->find()) {
					$data['dateline'] = time();
					$result = $database_store_authfile->where($where)->save($data);
				} else {
					$data['dateline'] = time();
					$data['store_id'] = $store_id;
					$result = $database_store_authfile->add($data);
				}
				if ($result) {
					$store_data = array('auth_files' => $data['auth_files'], 'auth' => 1, 'auth_time' => time());
					$database_merchant_store->where($where)->save($store_data);
					$this->success(L_('保存成功！'),U('Config/store'));
				} else {
					$this->error(L_('保存失败！！您是不是没做过修改？请重试~'));
				}
			} else {
				$data['dateline'] = time();
				if ($database_store_authfile->where($where)->save($data)) {
					$store_data = array('auth' => 4, 'auth_time' => time());
					$database_merchant_store->where($where)->save($store_data);
					$this->success(L_('保存成功！'),U('Config/store'));
				} else {
					$this->error(L_('保存失败！！您是不是没做过修改？请重试~'));
				}
			}
		} else {
			if (empty($now_store)) {
				$this->error(L_('店铺不存在！'));
			}
			$now_store['reason'] = '';
			$auth_files = array();
			if ($store_authfile = $database_store_authfile->field(true)->where(array('store_id' => $store_id))->find()) {
				if (!empty($store_authfile['auth_files'])) {
					$auth_file_class = new auth_file();
					$tmp_pic_arr = explode(';', $store_authfile['auth_files']);
					foreach($tmp_pic_arr as $key => $value){
					    $images = $auth_file_class->get_image_by_path($value, '-1');
						$auth_files[] = array('title' => $value, 'url' => $images['image']);
					}
				}
				$now_store['reason'] = $store_authfile['reason'];
			}
			$now_store['auth_files'] = $auth_files;
			$this->assign('now_store', $now_store);
			$this->display();
		}
	}

	public function ajax_upload_authfile()
	{
		if ($_FILES['file']['error'] != 4) {
			$param = array('size' => 5);
			$param['thumb'] = true;
			$param['imageClassPath'] = 'ORG.Util.Image';
			$param['thumbPrefix'] = 'm_,s_';
			$param['thumbMaxWidth'] = '1000,160';
			$param['thumbMaxHeight'] = '1000,160';
			$param['thumbRemoveOrigin'] = false;

			$image = D('Image')->handle($this->merchant_session['mer_id'], 'authfile', 1, $param);
			if ($image['error']) {
				exit(json_encode(array('error' => 1,'message' =>$image['msg'])));
			} else {
				$title = $image['title']['file'];
				$auth_file_class = new auth_file();
				$url = $auth_file_class->get_image_by_path($title, 's');
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			}
		} else {
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}
	public function ajax_del_authfile()
	{
		// $auth_file_class = new auth_file();
		// $auth_file_class->del_image_by_path($_POST['path']);
	}

	public function ajax_del_binduser()
	{
		if (D('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->save(array('uid' => 0))) {
			exit(json_encode(array('error_code' => false, 'msg' => L_('成功解除绑定'))));
		} else {
			exit(json_encode(array('error_code' => true, 'msg' => '解绑失败,稍后重试！')));
		}
	}

	// 绑定老板账号
	public function ajax_bind_boss()
	{
		$phone = $_POST['phone'];
		$user = D('User')->get_user($phone, 'phone');
		if(!$user){
			exit(json_encode(array('error_code' => true, 'msg' => '账号不存在！')));
		}

		if(M('Merchant')->where(['boss_uid'=>$user['uid']])->find()){
			exit(json_encode(array('error_code' => true, 'msg' => '账号已绑定别的商家！')));
		}

		$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
		$now_merchant = M(_view('Merchant'))->field(true,'pwd')->where($condition_merchant)->find();
		if (D('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->save(array('boss_uid' => $user['uid']))) {
			if($now_merchant['boss_uid']){// 转移原来粉丝至当前账户
				M('User_spread')->where(['spread_uid'=>$now_merchant['boss_uid'],'uid'=>['neq',$user['uid']]])->data(['spread_uid'=>$user['uid']])->save();
			}

			if(isset($now_merchant['bd_id'])){ // 业务员推广商家，商家绑定个人账号，个人账号如果没有上级，则业务员成为个人账号上级。同时修改商家绑定的个人账号，新绑定的账号同样遵从以上逻辑。

				// 查询推广业务员
				$bd = M("Bd")->where(['bd_id'=>strtoupper($now_merchant['bd_id']), 'status'=>1, 'is_del'=>0])->find();
				
				// 查询老板账号是否存在上级
				$spreadWhere = [
					'uid' => $user['uid']
				];
				$oldSpread = M('User_spread')->where($spreadWhere)->find();
				if(empty($oldSpread)){
					$spreadData = [
						'uid' =>  $user['uid'],
						'spread_uid' => $bd['uid'],
						'add_time' => time()
					]; 
					M('User_spread')->add($spreadData);
				}

			}

			exit(json_encode(array('error_code' => false, 'msg' => L_('成功绑定老板账号'), 'user'=>$user)));
		} else {
			exit(json_encode(array('error_code' => true, 'msg' => '绑定失败,稍后重试！')));
		}
	}

	public function see_tmp_qrcode(){
		$qrcode_id = $this->merchant_session['mer_id'] + 2550000000;
		$qrcode_return = D('Recognition')->get_tmp_qrcode($qrcode_id);
		if($qrcode_return['error_code']){
			echo '<html><head></head><body>'.$qrcode_return['msg'].'<br/><br/><font color="red">请关闭此窗口再打开重试。</font></body></html>';
		}else{
			$this->assign($qrcode_return);
			$this->display();
		}
	}


	public function has_bind()
	{
		$condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
		$now_merchant = D('Merchant')->field('uid')->where($condition_merchant)->find();
		if ($now_merchant['uid']) {
			$user = M('User')->field(true)->where(array('uid' => $now_merchant['uid']))->find();
			exit(json_encode(array('error_code' => false, 'nickname' => $user['nickname'])));
		} else {
			exit(json_encode(array('error_code' => true)));
		}
	}

	public function ajax_upload_file(){

		$allowExts =array (
			0 => 'pem',
		);
		if($_FILES['imgFile']['error'] != 4){
			$img_admin_id = sprintf("%09d", $this->system_session['id']);
			$rand_num = substr($img_admin_id,0,3) . '/' . substr($img_admin_id,3,3) . '/' . substr($img_admin_id,6,3);
			$upload_dir = "./upload/files/{$rand_num}/";
			if(!is_dir($upload_dir)){
				mkdir($upload_dir,0777,true);
			}
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxSize = 10*1024*1024;
			$upload->allowExts = $allowExts;
			$upload->savePath = $upload_dir;
			$upload->saveRule = 'uniqid';
			if($upload->upload()){
				$uploadList = $upload->getUploadFileInfo();
				$title = $rand_num.','.$uploadList[0]['savename'];
				exit(json_encode(array('error' => 0,'url' =>'./upload/files/'.$rand_num.'/'.$uploadList[0]['savename'],'title'=>$title)));
			}else{
				exit(json_encode(array('error' => 1,'message' =>$upload->getErrorMsg())));
			}
		}else{
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择文件'))));
		}
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

	//数据多语言导入
	public function data_import(){
		$mer_id = $this->merchant_session['mer_id'];
		$database_merchant_store = M(_view('Merchant_store'));
		$condition_merchant_store['mer_id'] = $mer_id;
		$condition_merchant_store['have_shop'] = '1';
		$condition_merchant_store['status'] = '1';
		$store_list = $database_merchant_store->where($condition_merchant_store)->select();
		$this->assign('store_list',$store_list);
		$this->display();
	}

	//上传表格
	public function data_import_upload(){
		set_time_limit(0);
		$type 				= strtolower(I('type'));
		$store_id 			= I('store_id');

		if(in_array($type, array('shop_goods')) && empty($store_id)){
			$this->ajaxReturn(array(),L_('参数错误'),2);
		}

		import('ORG.Util.Dir');
        Dir::delDirnotself('./upload/excel/language');
        $upload_dir = './upload/excel/language/' . date('Ymd') . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $lang_model = D('Lang');
        if(empty($lang_model->type_fields[$type])){
			$this->ajaxReturn(array(),L_('数据错误'),2);
		}//1.02
		import('ORG.Net.UploadFile');
		$upload = new UploadFile();
		$upload->maxSize = 10 * 1024 * 1024;
        $upload->allowExts = array('xls');
        $upload->savePath = $upload_dir;
        $upload->saveRule = 'uniqid';
        if ($upload->upload()){
        	$upload_info = $upload->getUploadFileInfo();
        	import('@.ORG.phpexcel.PHPExcel','','.php');
			$objPHPExcel = new PHPExcel();
			$path = $upload_info[0]['savepath'].$upload_info[0]['savename'];
			$fileType = PHPExcel_IOFactory::identify($path);
			$objReader = PHPExcel_IOFactory::createReader($fileType);
			$objPHPExcel = $objReader->load($path);
			$currentSheet = $objPHPExcel->getSheet(0);

			$highestRow = $currentSheet->getHighestRow(); // 取得总行数
			$highestColumm = $currentSheet->getHighestColumn(); // 取得总列数
			$data = array();
			$stop = false;
			$chars_arr = [
				'A' => 1,'B' => 2,'C' => 3,'D' => 4,'E' => 5,'F' => 6,'G' => 7,'H' => 8,'I' => 9,'J' => 10,'K' => 11,'L' => 12,'M' => 13,'N' => 14,'O' => 15,'P' => 16,'Q' => 17,'R' => 18,'S' => 19,'T' => 20,'U' => 21,'V' => 22,'W' => 23,'X' => 24,'Y' => 25,'Z' => 26,'AA' => 27,'AB' => 28,'AC' => 29,'AD' => 30,'AE' => 31,'AF' => 32,'AG' => 33,'AH' => 34,'AI' => 35,'AJ' => 36,'AK' => 37,'AL' => 38,'AM' => 39,'AN' => 40,'AO' => 41,'AP' => 42,'AQ' => 43,'AR' => 44,'AS' => 45,'AT' => 46,'AU' => 47,'AV' => 48,'AW' => 49,'AX' => 50,'AY' => 51,'AZ' => 52,'BA' => 53,'BB' => 54,'BC' => 55,'BD' => 56,'BE' => 57,'BF' => 58,'BG' => 59,'BH' => 60
			];
			for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {        //循环读取每个单元格的内容。注意行从1开始，列从A开始
				if(!$stop){
				    for ($colIndex = 'A', $colIndex_num = $chars_arr[$colIndex]; $colIndex_num <= $chars_arr[$highestColumm]; $colIndex++, $colIndex_num = $chars_arr[$colIndex]) {
				        $addr = $colIndex . $rowIndex;
				        $cell = trim($currentSheet->getCell($addr)->getValue());
				        if($colIndex == 'A' && empty($cell)){
				        	$stop = true;
				        	break;
				        }
				        if ($cell instanceof PHPExcel_RichText) { //富文本转换字符串
				            $cell = $cell->__toString();
				        }
				        // if(empty($cell)){
				        // 	$stop = true;
				        // 	break;
				        // }
				        $data[$rowIndex][$colIndex] = $cell;
				    }
				}
				else{
					break;
				}
			}
			$title = $data[1];//表格标题
			//验证标题的正确性
			if(!$this->checkTitle($title,$type)){
				$this->ajaxReturn(array(),L_('表格标题不正确！无法执行导入'),2);
			}

			$table_model = M($type);
			for($i = 2; $i <= count($data) ; $i++){
				$lang_data = array();
				if($data[$i] && $data[$i]['A']){
					if($store_id){//数据过滤校验
						$w = array(
							'store_id' => $store_id,
							$table_model->getPk() => $data[$i]['A']
						);
						$find = $table_model->where($w)->find();
						if(empty($find)) continue;
					}
					$tf = $lang_model->type_fields[$type];
					$has = true;
					$loop = 0;
					foreach ($data[$i] as $key => $value) {
						$value = empty(trim($value)) ? '' : trim($value);
						if($has){
							if($key == 'A' && is_numeric($value)){
								foreach ($lang_model->service_lang as $service) {
									$lang_data[$service['val']][key($tf)] = $value;
								}
								$has = false;
								array_shift($tf);
								continue;
							}
							if($loop != count($lang_model->service_lang)-1){
								$lang_data[$lang_model->service_lang[$loop]['val']][key($tf)] = $value;
								$loop++;
							}
							else{
								$lang_data[$lang_model->service_lang[$loop]['val']][key($tf)] = $value;
								$loop = 0;
								$has = false;
								array_shift($tf);
							}
						}
						else{
							$has = true;
						}
					}
				}
				if($lang_data){
					foreach ($lang_data as $key => $value) {
						foreach ($value as $k => $v) {
							$value[$k."_".$key] = $v;
							unset($value[$k]);
						}
						$model = M($type."_".$key);
						$f = key($value);
						$pk = array_shift($value);
						$where = array(
							$f => $pk
						);
						if($model->where($where)->find()){
							$model->data($value)->where($where)->save();
						}
						else{
							$value[$f] = $pk;
							$model->data($value)->add();
						}
					}
				}
			}
			$this->ajaxReturn(array(),L_("导入成功"),1);
        }
        else{
        	$this->ajaxReturn(array(),$upload->getErrorMsg(),2);
        }
	}

	//判断表格的每一列标题，必须一致
	public function checkTitle($title,$type){
		$lang_model = D('Lang');
		$s = 'A';
		$data = array();
		foreach ($lang_model->type_fields[$type] as $key => $value) {
			if($s != 'A'){
				$data[$s] = $value['name'];
				$s++;
				foreach($lang_model->service_lang as $l){
					$data[$s] = $value['name']."(".$l['display'].")";
					$s++;
				}
			}
			else{
				$data[$s] = $value['name'];
				$s++;
			}
		}
		return empty(array_diff_assoc($title,$data)) ? true : false;
	}

	//下载表格
	public function data_import_download(){
		set_time_limit(0);
		ini_set('memory_limit', '2048M');
		$mer_id 			= $this->merchant_session['mer_id'];
		$type 				= strtolower(I('type'));
		$store_id 			= I("store_id");
		$db 				= M($type);
		$lang_model 		= D('Lang');

		if(in_array($type, array('shop_goods')) && empty($store_id)){
			$this->error(L_("参数错误"),U('data_import'));
		}

		$type_conditions = array(
			'merchant_store' => array(
				'mer_id' => $mer_id
			),
			'merchant' => array(
				'mer_id' => $mer_id
			),
			'merchant_store_shop' => array(
				's.mer_id' => $mer_id
			),
			'shop_goods' => array(
				'store_id' => $store_id
			),
			'shop_goods_properties' => array(
				'ss.store_id' => $store_id
			),
			'shop_goods_spec' => array(
				'ss.store_id' => $store_id
			),
			'shop_goods_spec_value' => array(
				'ss.store_id' => $store_id
			),
            'shop_goods_subsidiary_group' => array(
                'store_id' => $store_id
            ),
            'shop_subsidiary_piece' => array(
                'store_id' => $store_id,
                'is_delete' => 0,
            ),
			'shop_goods_sort' => array(
					'store_id' => $store_id
			),
			'card_group' => array(
					'mer_id' => $mer_id
			),
			'card_new' => array(
					'mer_id' => $mer_id
			),
			'shop_combination' => array(
				'mer_id' => $mer_id,
                'store_id' => $store_id,
			),
			'merchant_store_foodshop_data' => array(
					'mer_id' => $mer_id
			),
			'foodshop_customer_form' => array(
					'mer_id' => $mer_id
			),
			'foodshop_customer_label' => array(
					'mer_id' => $mer_id
			),
			'group' => array(
					'mer_id' => $mer_id
			),
            'foodshop_goods_package' => array(
                'store_id' => $store_id
            ),
            'foodshop_goods_sort' => array(
                'store_id' => $store_id
            ),
		);
		if(empty($lang_model->type_fields[$type])){
			$this->error(L_("参数错误"),U('data_import'));
		}

		$fields = array_keys($lang_model->type_fields[$type]);
		$type_conditions[$type] = $type_conditions[$type] ? $type_conditions[$type] : array();

		if(in_array($type, ['merchant_store_foodshop_data','foodshop_table_type','foodshop_table','merchant_store_foodshop'])){
			$store_ids = array_column( M('Merchant_store')->field('store_id')->where(['mer_id'=>$this->merchant_session['mer_id']])->select(), 'store_id');
			$type_conditions[$type]['store_id'] = array('in',$store_ids);
			
		}
		//查询所有主表数据
		if($type == 'merchant_store_shop'){
			$table_data = $db->table(C('DB_PREFIX')."merchant_store_shop as ss")->join("left join ".C('DB_PREFIX')."merchant_store as s on s.store_id=ss.store_id")->where($type_conditions[$type])->field('ss.'.implode(',ss.', $fields))->select();
		}
		elseif(in_array($type, ['shop_goods_properties', 'shop_goods_spec'])){
			$table_data = $db->table(C('DB_PREFIX').$type." as s")->join("left join ".C('DB_PREFIX')."shop_goods as ss on ss.goods_id=s.goods_id")->where($type_conditions[$type])->field('s.'.implode(',s.', $fields))->select();
		}
		elseif(in_array($type, ['shop_goods_spec_value'])){
			$table_data = $db->table(C('DB_PREFIX').$type." as s1")->join("left join ".C('DB_PREFIX')."shop_goods_spec as s2 on s1.sid=s2.id")->join("left join ".C('DB_PREFIX')."shop_goods as ss on ss.goods_id=s2.goods_id")->where($type_conditions[$type])->field('s1.'.implode(',s1.', $fields))->select();
		}
		else{
			$table_data = $db->where($type_conditions[$type])->field(implode(',', $fields))->select();
		}

		if($type == 'shop_goods'){
			$where_shop_goods_goods_id_arr = array_column($table_data, 'goods_id');
		}

		//查询所有语言表数据
		$lang_data = array();
		foreach ($lang_model->service_lang as $value) {
			$model = M($type."_".$value['val']."_view");

			$where_lang_data = [];
			if($where_shop_goods_goods_id_arr){
				$where_lang_data = ['goods_id_' . $value['val'] => ['in', $where_shop_goods_goods_id_arr]];
			}
			$lang_data[$value['val']] = $model->field(implode('_'.$value['val'].',', $fields).'_'.$value['val'])->where($where_lang_data)->select();

			$_lang = array();
			foreach ($lang_data[$value['val']] as $k => $v) {
				$temp = array_keys($v);
				if($v[$temp[0]]){
					$_lang[$v[$temp[0]]] = $v;
				}
			}
			$lang_data[$value['val']] = $_lang;
		}
		//生成csv表格
		import('@.ORG.phpexcel.PHPExcel','','.php');
		$objPHPExcel = new PHPExcel();
		$e = $objPHPExcel->setActiveSheetIndex(0);
		// 所有单元格默认高度
		$array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
		//设置第一行
		$i = 0;
		$line = 1;
		foreach ($lang_model->type_fields[$type] as $key => $value) {
			if($i != 0){
				$e->setCellValue($array[$i].$line, $value['name']);
				$e->getColumnDimension($array[$i])->setWidth($value['width']);
				$e->getstyle($array[$i].$line)->getFont()->setBold(true);
				$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
				$i++;
				foreach($lang_model->service_lang as $l){
					$e->setCellValue($array[$i].$line, $value['name']."(".$l['display'].")");
					$e->getColumnDimension($array[$i])->setWidth($value['width']+10);
					$e->getstyle($array[$i].$line)->getFont()->setBold(true);
					$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
					$i++;
				}
			}
			else{
				$e->setCellValue($array[$i].$line, $value['name']);
				$e->getColumnDimension($array[$i])->setWidth($value['width']);
				$e->getstyle($array[$i].$line)->getFont()->setBold(true);
				$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
				$i++;
			}
		}
		$line++;//行数+1
		foreach($table_data as $tdata){
			$pk = '';
			$i = 0;
			foreach ($tdata as $key => $value) {
				if($i != 0){
					$e->setCellValueExplicit($array[$i].$line, $value, PHPExcel_Cell_DataType::TYPE_STRING);
					$e->getStyle($array[$i].$line)->getAlignment()->setWrapText(TRUE);
					$i++;
					foreach($lang_model->service_lang as $l){
						$text = $lang_data[$l['val']][$tdata[$pk]][$key.'_'.$l['val']];
						if($text){
							$e->setCellValueExplicit($array[$i].$line, $text, PHPExcel_Cell_DataType::TYPE_STRING);
							$e->getStyle($array[$i].$line)->getAlignment()->setWrapText(TRUE);
						}
						else{
							$e->setCellValue($array[$i].$line, '');
						}
						$i++;
					}
				}
				else{
					$e->setCellValue($array[$i].$line, $value);
					$i++;
					$pk = $key;
				}
			}
			$line++;
		}

		//$objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(25);
		$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(25);
	    // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true); //保护工作表
	    $objPHPExcel->getActiveSheet()->setTitle($type);
	    $objPHPExcel->setActiveSheetIndex(0);
	    ob_end_clean();//清除缓冲区,避免乱码
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$type.'.xls"');
		header('Cache-Control: max-age=0');
	    $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter->save('php://output');
        exit;
	}

	/**
	 * @desc 店铺营业时间老数据跑一下
	 */
	public function update_merchant_old_time(){
		$model = D('Merchant_store_open_time');
		$model->deal_old_data();
	}

	/**
	 * @desc 计划任务（每分钟）更新店铺营业时间
	 */
	public function run_time_task(){
		D('Merchant_store_open_time')->checkBusinessTime();
//		$model = D('Merchant_store_open_time');
//		$model->run_time_task();
	}
	public function discount()
	{
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('store', $now_store);
		$where=array();
		$where['store_id']=$now_store['store_id'];
		$where['source']=1;
		$where['shop_type']=1;
		$discount = D('Shop_discount')->field(true)->where($where)->select();
		$this->assign('discount_list', $discount);

		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);
		
		$this->display();
	}

	public function discount_add()
	{
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		if (IS_POST) {
			$database_discount = D('Shop_discount');
			$data_discount['store_id'] = $now_store['store_id'];
			$data_discount['mer_id'] = $now_store['mer_id'];
			$data_discount['full_money'] = $_POST['full_money'];
			$data_discount['reduce_money'] = $_POST['reduce_money'];
			$data_discount['type'] = intval($_POST['type']);
			$data_discount['status'] = intval($_POST['status']);
			$data_discount['is_share'] = intval($_POST['is_share']);
			$data_discount['source'] = 1;
			$data_discount['use_limit'] = intval($_POST['use_limit']);
            //新单order_count置为1
            if ($data_discount['type'] == 0) {
                $data_discount['order_count'] = 1;
            }
			if ($database_discount->data($data_discount)->add()) {
				$this->success(L_('添加成功！！'), U('Config/discount',array('store_id' => $now_store['store_id'])));
				die;
			}else{
				$this->error(L_('添加失败！！请重试。'), U('Config/discount',array('store_id' => $now_store['store_id'])));
				die;
			}
			if(!empty($error_tips)){
				$this->assign('now_discount', $_POST);
			}
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		$this->display();
	}

	public function discount_edit()
	{
		$now_store = $this->check_store($_GET['store_id']);
		if (!($discount = D('Shop_discount')->field(true)->where(array('id' => intval($_GET['id']), 'store_id' => $now_store['store_id']))->find())) {
			$this->error(L_('不存在的优惠，请查证后修改。'), U('Config/discount',array('store_id' => $now_store['store_id'])));
		}
		$this->assign('now_store',$now_store);

		if (IS_POST) {
			$database_discount = D('Shop_discount');
			$data_discount['id'] = $discount['id'];
			$data_discount['store_id'] = $now_store['store_id'];
			$data_discount['mer_id'] = $now_store['mer_id'];
			$data_discount['full_money'] = $_POST['full_money'];
			$data_discount['reduce_money'] = $_POST['reduce_money'];
			$data_discount['type'] = intval($_POST['type']);
			$data_discount['status'] = intval($_POST['status']);
			$data_discount['is_share'] = intval($_POST['is_share']);
			$data_discount['source'] = 1;
			$data_discount['use_limit'] = intval($_POST['use_limit']);
            //新单order_count置为1
            if ($data_discount['type'] == 0) {
                $data_discount['order_count'] = 1;
            }else if($data_discount['type'] == 1){
                $data_discount['order_count'] = 0;
            }
			if ($database_discount->data($data_discount)->save()) {
				$this->success(L_('修改成功！！'), U('Config/discount',array('store_id' => $now_store['store_id'])));
				die;
			}else{
				$this->error(L_('修改失败！！请重试。'), U('Config/discount',array('store_id' => $now_store['store_id'])));
				die;
			}
			if(!empty($error_tips)){
				$this->assign('now_discount', $_POST);
			}
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		} else {
			$this->assign('now_discount', $discount);
		}
		$this->display();
	}
	
	public  function store_slider(){

		$db = M('Merchant_store_slider');
		$count  = $db->where(array('store_id'=>$_GET['store_id']))->count();

		import('@.ORG.merchant_page');
		$p = new Page($count,15);
		$slider_list = $db->where(array('store_id'=>$_GET['store_id']))->order('sort DESC')->limit($p->firstRow,$p->listRows)->select();

		$this->assign('slider_list',$slider_list);
		$pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function slider_del(){
		$id = $_GET['id'];
		$r = M('Merchant_store_slider')->where(array('id'=>$id))->delete();
		if($r){
			$this->success(L_('删除成功！'));
		}else{
			$this->error(L_('删除失败！'));
		}

	}

	public  function store_slider_add(){
		$db = M('Merchant_store_slider');
		if(IS_POST){

			if($_FILES['pic']['error'] != 4) {
				$image = D('Image')->handle($this->system_session['id'], 'slider');
				if (!$image['error']) {

					$_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
				} else {
					$this->error( $image['msg']);
				}
			}

			if(empty($_POST['name']) || empty($_POST['url']) ){
				$this->error(L_('数据未填写完整'));
			}
			$_POST['url'] = htmlspecialchars_decode($_POST['url']);
			$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
			if($res = $db->where(array('id'=>$_POST['id']))->find()){
				$id = $res['id'];
				$result = $db->where(array('id'=>$_POST['id']))->save($_POST);
			}else{
				if( empty($_POST['pic'])){
					$this->error(L_('数据未填写完整'));
				}
				$result = $db->add($_POST);
				$id = $result;
			}
			if($result){
				D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');
				S('slider_list_'.$_POST['cat_id'],NULL);
				$this->success(L_('保存成功'));
			}else{
				$this->error(L_('保存失败'));
			}
		}else{
			$silder = $db->where(array('id'=>$_GET['id']))->find();
			$this->assign('slider',$silder);
			$this->display();
		}
	}

    public function goods_price_modify_log()
    {
        $_GET['mer_id'] = $this->merchant_session['mer_id'];
        $rs = D('Shop_goods_price_modify_log')->get_lists($_GET);
        $this->assign($rs);
        $this->display();
    }

    public function show_spec()
    {
        $id = intval($_GET['id']);
        $where = [
            'id' => $id,
            'mer_id' => $this->merchant_session['mer_id']
        ];
        $log = D('Shop_goods_price_modify_log')->where($where)->find();
        $specs = [];
        if ($log) {
            $specs = $log['spec_value'] ? json_decode($log['spec_value'], true) : [];
        }
        $this->assign('specs', $specs);
        $this->display();
    }

    /**
     * 淼迈云ERP配置
     * @author: 张涛
     * @date: 2021/04/01
     */
    public function miaomaiyun_erp()
    {
        $mer_id = $this->merchant_session['mer_id'];
        if (IS_POST) {
            $store_id = $_POST['store_id'] ?? 0;
            if ($store_id < 1) {
                $this->error(L_('店铺参数缺失'));
            }
            $data = [
                'miaomaiyun_erp_appid' => $_POST['miaomaiyun_erp_appid'],
                'miaomaiyun_erp_appsecret' => $_POST['miaomaiyun_erp_appsecret'],
                'miaomaiyun_erp_storeid' => $_POST['miaomaiyun_erp_storeid'],
                'miaomaiyun_erp_pt_user_id' => $_POST['miaomaiyun_erp_pt_user_id'],
            ];
            D('Merchant_store')->where(['mer_id' => $mer_id, 'store_id' => $store_id])->save($data);
            $this->success(L_('保存成功'));
        } else {
            $store_id = $_GET['store_id'] ?? 0;
            $now_store = D('Merchant_store')->where(['mer_id' => $mer_id, 'store_id' => $store_id])->find();
            $this->assign('now_store', $now_store);
            $this->display();
        }
    }
}


?>