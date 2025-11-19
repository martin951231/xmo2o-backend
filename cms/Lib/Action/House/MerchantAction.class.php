<?php
/**
 * Created by PhpStorm.
 * User: win 10
 * Date: 2019/12/16
 * Time: 11:35
 */
class MerchantAction extends BaseAction{

    public function index(){

        $property_id = $this->property_id;
        $property = M('House_property')->where(array('id'=>$property_id))->find();
        if ($property['merchant_id'] > 0) {
            $database_merchant = D('Merchant');
            $condition_merchant['mer_id'] = $property['merchant_id'];
            $now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
        }
        if($now_merchant && $now_merchant['status']!=0){
            if(empty($now_merchant) || $now_merchant['status'] == 0 || $now_merchant['status'] == 2){
//                exit('<html><head><script>window.top.toggleMenu(0);window.top.msg(0,"该商家的状态不存在！请查阅。",true,5);window.history.back();</script></head></html>');
                $href = $this->config['site_url'].'/shequ.php?g=House&c=NewProperty&a=index_new&type=merchant';
                $tips = "该商家的状态不存在！请查阅【名称：{$now_merchant['name']} ID: {$now_merchant['mer_id']}】";
                $time = 2;
                exit('<html><head><script>window.top.msg(0,"'.$tips.'",true,'.$time.');</script></head></html>');
            }elseif(!empty($now_merchant) && $now_merchant['status'] == 4){
                $tips = "该商家的不存在！请查阅【名称：{$now_merchant['name']} ID: {$now_merchant['mer_id']}】";
                $time = 2;
                exit('<html><head><script>window.top.msg(0,"'.$tips.'",true,'.$time.');</script></head></html>');
            }
            if(!empty($now_merchant['last_ip'])){
                import('ORG.Net.IpLocation');
                $IpLocation = new IpLocation();
                $last_location = $IpLocation->getlocation($now_merchant['last_ip']);
                $now_merchant['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
                $now_merchant['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');
            }
            session('merchant',$now_merchant);
            session('staff',null);
            session('new_merchant','');

            import("@.ORG.Token");
            $ticket = Token::createToken($property['merchant_id']);
            setcookie('merchant_access_token', $ticket, -1, '/');
            $href = $this->config['site_url'].'/v20/public/platform/#/merchant/merchant.iframe/menu_999999';
            $this->assign('href', $href);
            $this->display();
        }else{
            if(IS_POST){
                $new_merchant = session('new_merchant');
                $phone		=	$_POST['new_phone'];
                $phone_country_type = $_POST['phone_country_type'];
                $phone = phone_format($phone_country_type, $phone);
                $where = array();
                $where['mer_id'] = $new_merchant['mer_id'];
                $where['extra'] = $_POST['SmsCode2'];
                $where['status'] = 0;
                $where['expire_time'] = array('gt', time());
                $sms_result = M("Merchant_sms_record")->where($where)->find();
                if (!$sms_result) {
                    $this->error(L_('验证码错误'));
                }
                $database_merchant = D('Merchant');
                $condition_merchant = [];
                $condition_merchant['phone'] = $phone;
                $condition_merchant['status'] = 1;
                $now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
                if($now_merchant){

                    $property = M('House_property')->where(array('id'=>$property_id))->save(array('merchant_id'=>$now_merchant['mer_id']));
                    if($property){
                        $this->success('绑定成功!',U('NewProperty/index_new'));
                    }else{
                        $this->success('绑定失败!');
                    }
                }else{
                    $this->error('商家手机号错误');
                }
            }else{
                $this->display();
            }

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


    public function sendsms(){
        $type = I('type',2);

        if(empty($_POST['newphone'])){
            $this->error(L_('请输入手机号'));
        }
        $now_merchant = M('Merchant')->where(array('phone'=>$_POST['newphone']))->find();
        if(empty($now_merchant)){
            $this->error(L_('该手机号暂未注册商家，请点击页面按钮“前往商家注册”'));
        }
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
        session('new_merchant',$now_merchant);
        $columns = array();
        $columns['phone'] = $phone;
        $columns['mer_id'] = $now_merchant['mer_id'];
        $columns['extra'] = $code;
        $columns['type'] = $type;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = M("Merchant_sms_record")->add($columns);
        if (!$result){
            $this->error(L_('短信数据写入失败'));
        }
        $sms_data = array('mer_id' => $now_merchant['mer_id'], 'store_id' => 0, 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'merchant_pwd');
        $sms_data['mobile'] = phone_format($sms_data['nationCode'], $sms_data['mobile']);

        $return = Sms::sendSms($sms_data);

        if ($result != 0) {
            $msg = self::ConverSmsCode($return);
            if ($msg) {
                $this->error($msg);
            }
        }
        $this->success('发送成功');
    }



    public function sendsms_test(){
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
}