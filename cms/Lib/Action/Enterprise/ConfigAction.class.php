<?php

class ConfigAction extends BaseAction
{
    public function index()
    {
		//dump($this->enterprise_session);exit;
        if (IS_POST) {
            $post = $_POST;
            $qiyeId = $this->qiyeId;
            $mod = M('Qiye');
            $update = [];
            $info = $mod->where(['qiye_id' => $qiyeId])->find();
            if (!$info) {
                $this->error(L_('企业账号不存在'));
            }
            if (!empty($post['intro'])) {
                $update['intro'] = trim($post['intro']);
            }
            if (!empty($post['email'])) {
                $update['email'] = trim($post['email']);
            }
            if (!empty($post['logo'])) {
                $update['logo'] = trim($post['logo']);
            }
            if ($post['new_pass'] && $post['re_pass']) {
                if ($post['new_pass'] != $post['re_pass']) {
                    $this->error(L_('登录新密码和确认密码不一致'));
                }
                $update['pwd'] = md5($post['new_pass'] . $info['salt']);
            }
            if ($post['new_operate_pass'] && $post['re_operate_pass']) {
                if ($post['re_operate_pass'] != $post['new_operate_pass']) {
                    $this->error(L_('操作新密码和确认密码不一致'));
                }
                $update['operate_pwd'] = md5($post['new_operate_pass'] . $info['salt']);
            }
            $smsId = [];
			//（修改登陆密码或操作密码）手机号短信验证码验证下
			if(!empty($post['new_pass'])||!empty($post['new_operate_pass'])){
					$where = array();
					$where['qiye_id'] = $this->enterprise_session['qiye_id'];
					$where['extra'] = $_POST['qiye_sms_code'];
                    $where['type'] = 2;
					$where['status'] = 0;
					$where['expire_time'] = array('gt', time());
					$sms_result = M("Qiye_sms_record")->where($where)->find();
					if (!$sms_result) {
						$this->error(L_('企业手机验证码错误'));
					}
					$smsId[] = $sms_result['pigcms_id'];
			}
			//修改手机 验证码验证
			if(!empty($post['new_phone'])){
				$where = array();
				$where['qiye_id'] = $this->enterprise_session['qiye_id'];
				$where['extra'] = $_POST['origin_phone_code'];
				$where['phone'] = $_POST['origin_phone'];
                $where['type'] = 2;
				$where['status'] = 0;
				$where['expire_time'] = array('gt', time());
				$sms_result = M("Qiye_sms_record")->where($where)->find();
				if (!$sms_result) {
					$this->error(L_('旧手机验证码错误'));
				}
                $smsId[] = $sms_result['pigcms_id'];

				$where = array();
				$where['qiye_id'] = $this->enterprise_session['qiye_id'];
				$where['extra'] = $_POST['phone_code'];
                $where['phone'] = $_POST['new_phone'];
                $where['type'] = 3;
				$where['status'] = 0;
				$where['expire_time'] = array('gt', time());
				$sms_result = M("Qiye_sms_record")->where($where)->find();
				if (!$sms_result) {
					$this->error(L_('新手机验证码错误'));
				}
                $smsId[] = $sms_result['pigcms_id'];
			}
            if (!empty($post['new_phone'])) {
                $update['phone'] = trim($post['new_phone']);
            }
            $rs = true;
            if ($update) {
                $rs = $mod->where(['qiye_id' => $qiyeId])->save($update);
            }
            if ($rs !== false) {
                if ($smsId) {
                    M("Qiye_sms_record")->where(['pigcms_id' => ['IN', $smsId]])->save(['status' => 1]);
                }
                $this->success(L_('保存成功'));
            } else {
                $this->error(L_('保存失败'));
            }

        } else {
            $info = M('Qiye')->where(['qiye_id' => $this->qiyeId])->find();
            $this->assign('qiyeinfo',$info);
            $this->display();
        }
    }
	
	
	public function sendsms(){
		$type = I('type',2);
		if(empty($this->enterprise_session['qiye_id'])){
			$this->error(L_('商家不存在'));
		}

		if(empty($_POST['phone'])){
			$this->error(L_('请输入手机号'));
		}

		$phone = $this->enterprise_session['phone'];
		$code = mt_rand(1000, 9999);
		$text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',array('X1'=>$code));

		$columns = array();
		$columns['phone'] = $phone;
		$columns['qiye_id'] = $this->enterprise_session['qiye_id'];
		$columns['extra'] = $code;
		$columns['type'] = $type;
		$columns['status'] = 0;
		$columns['send_time'] = time();
		$columns['expire_time'] = $columns['send_time'] + 1200;
		$result = M("Qiye_sms_record")->add($columns);
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
	
	public function sendsms2(){
		$type = I('type',2);
		$code = mt_rand(1000, 9999);
		$text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',array('X1'=>$code));
		$phone = $_POST['newphone'];
		$columns = array();
		$columns['phone'] = $phone;
		$columns['qiye_id'] = $this->enterprise_session['qiye_id'];
		$columns['extra'] = $code;
		$columns['type'] = $type;
		$columns['status'] = 0;
		$columns['send_time'] = time();
		$columns['expire_time'] = $columns['send_time'] + 1200;
		$result = M("Qiye_sms_record")->add($columns);
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

    public function ajax_upload_pic()
    {
        if ($_FILES['imgFile']['error'] != 4) {
            $path = $_GET['path'] ? $_GET['path'] : 'qiye';
            $image = D('Image')->handle($this->enterprise_session['qiye_id'], $path, 1);
            if ($image['error']) {
                exit(json_encode($image));
            } else {
                $title = $image['title']['imgFile'];
                $merchant_image_class = new merchant_image();
                $url = $merchant_image_class->get_image_by_path($title);
                $url = str_replace('merchant', $path, $url);
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => L_('没有选择图片'))));
        }
    }
}