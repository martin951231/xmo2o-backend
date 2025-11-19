<?php
/**
 * 商家登录service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/03 09:59
 */

namespace app\merchant\model\service;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\UserService;
use app\merchant\model\db\Merchant as MerchantModel;
use app\merchant\model\db\Bd;
use app\merchant\model\service\sms\MerchantSmsRecordService;
use app\common\model\service\admin_user\AdminUserService;
use app\common\model\service\UserSpreadService;
use app\merchant\model\service\distributor\UserSpreadMerchantService;
use app\common\model\service\weixin\LoginQrcodeService;
use app\merchant\model\service\distributor\DistributorAgentService;
use app\new_marketing\model\db\NewMarketingJoinLog;
use app\new_marketing\model\db\NewMarketingPersonManager;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use think\captcha\facade\Captcha;
use token\Token;
use net\IpLocation;
class LoginService {
    public $merchantModel = null;
    public function __construct()
    {
        $this->merchantModel = new MerchantModel();
    }

    /**
     * 登录
     * @param $param array 登录信息
     * @return array
     */
    public function login($param){
        $merchantService = new MerchantService();
        switch($param['ltype']){
            case '1':
                // 用户名密码登录
                // 用户信息
                $where = [
                    'account' => $param['account']
                ];

                if( !Captcha::check($param['verify'])) {
                    // 验证失败
                     throw new \think\Exception(L_("验证码错误"), 1003);
                }

                $merchantUser = $merchantService->getOne($where);
                if(!$merchantUser) {
                    $where = [
                        ['phone', '=', $param['account']],
                        ['status', '<>', 4]
                    ];
                    $merchantUser = $merchantService->getOne($where);

                    if(!$merchantUser) {
                        throw new \think\Exception(L_("用户名或密码有误，请重新输入"), 1003);
                    }
                }

                if($merchantUser['pwd'] != md5($param['pwd'])) {
                    throw new \think\Exception(L_("用户名或密码有误，请重新输入"), 1003);
                }
                break;
            case '2':
                // 扫码登录
                $where = [
                    'mer_id' => $param['mer_id']
                ];
                $merchantUser = $merchantService->getOne($where);
                if(!$merchantUser) {
                    throw new \think\Exception(L_("商家不存在"), 1003);
                }
                break;
            case '3':
                $where = [
                    ['phone','=',$param['account']],
                    ['extra','=',$param['smscode']],
                    ['type','=',70],
                    ['status','=',0],
                    ['expire_time','>',time()],
                ];
                $smsresult = \think\facade\Db::name('app_sms_record')->where($where)->find();
                if (!$smsresult) {
                    $loginLog['logContent'] = '错误验证码:'.$param['smscode'];
                    throw new \think\Exception(L_($loginLog['logContent']), 1003);
                }
                $save_set = [
                    'status' => 1,
                    'expire_time' => time(),
                ];
                \think\facade\Db::name("app_sms_record")->where(['pigcms_id'=>$smsresult['pigcms_id']])->save($save_set);
                // 手机号验证
                $where = [
                    'phone' => $param['account']
                ];
                $merchantUser = $merchantService->getOne($where);
                if(!$merchantUser) {
                    throw new \think\Exception(L_("商家不存在"), 1003);
                }
                break;
        }

        // 商家已到期
        if(!empty($merchantUser['merchant_end_time']) && $merchantUser['merchant_end_time'] < $_SERVER['REQUEST_TIME']){
            $data = [];
            $data['mer_id'] = $merchantUser['mer_id'];
            $data['status'] = '0';

            // $menus = M('Authority_group')->where(array('is_default'=>1))->find();
//            if($merchantUser['authority_group_id']>0){
//                $data['authority_group_id'] = $menus['id'];
//                $data['status'] = 1;
//            }
            // 更新商家状态为关闭
            $merchantService->updateByMerId($merchantUser['mer_id'], $data);

            throw new \think\Exception(L_("您的帐号已经过期！请联系工作人员获得详细帮助。"), 1003);
        }

        // 验证商家状态
        if($merchantUser['status'] == 0){
            throw new \think\Exception(L_("您被禁止登录！请联系工作人员获得详细帮助。"), 1003);
        }else if($merchantUser['status'] == 2){
            throw new \think\Exception(L_("您的帐号正在审核中，请耐心等待或联系工作人员审核。"), 1003);
        }else if($merchantUser['status'] == 4){
            throw new \think\Exception(L_("您的帐号已经被永久删除，无法使用。"), 1003);
        }

        $data = [];
        $data['last_ip'] = request()->ip();
        $data['last_time'] = time();
        $data['login_count'] = $merchantUser['login_count']+1;
        if(!$merchantService->updateByMerId($merchantUser['mer_id'], $data)){
            throw new \think\Exception(L_("登录信息保存失败,请重试！"), 1003);
        }

        if($merchantUser['status']==3){
            $remark = L_('您的账户处于欠费状态，您的商家业务已经被关闭，请及时充值，充值后将恢复业务');
        }else{
            $remark = L_('登录成功,现在跳转~');
        }

        // 返回数据
        $returnArr = [
            'msg' => $remark
        ];

        // 生成ticket
        $ticket = Token::createToken($merchantUser['mer_id']);
        if(!$ticket){
            throw new \think\Exception(L_("登陆失败,请重试！"), 1003);
        }
        $returnArr['ticket'] = $ticket;
        return $returnArr;
    }

    /**
     * 扫码登录
     * @param $param array 登录信息
     * @return array
     */
    public function weixinLogin(array $param) {
        $qrcodeId = $param['qrcode_id'];
        $longhttp = $param['longhttp'];
        $returnArr = ['error'=>1];
        $where['id'] = $qrcodeId;
        $loginQrcodeService = new LoginQrcodeService();
        $nowQrcode = $loginQrcodeService->getOne($where);
        if (!empty($nowQrcode['uid'])) {
            if ($nowQrcode['uid'] == -1) {
                $dataLoginQrcode['uid'] = 0;
                $loginQrcodeService->updateThis($where,$dataLoginQrcode);
                $returnArr['error'] = 1;
                $returnArr['msg'] = L_('已扫描！请在微信公众号里点击授权登录。');
                return $returnArr;
            }

            // 删除扫码记录
            $loginQrcodeService->del($where);

            //查看用户是否存在
            $user = (new UserService())->getUser($nowQrcode['uid']);
            if(empty($user)){
                throw new \think\Exception(L_("没有查找到此用户，请重新扫描二维码！"), 1003);
            }

            // 登录商家后台
            $conditionMerchant = [];
            $conditionMerchant['uid'] = $nowQrcode['uid'];
            $nowMerchant = (new MerchantService())->getOne($conditionMerchant);
            if(empty($nowMerchant)){
                throw new \think\Exception(L_("微信号未绑定商家，请使用手机号登录商家后台绑定"), 1003);
            }
            try {
                $param['mer_id'] = $nowMerchant['mer_id'];
                $param['ltype'] = 2;
                $returnArr = $this->login($param);
                $returnArr['error'] = 0;
                return $returnArr;
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage(), $e->getCode());
            }
        }
        $returnArr['error'] = 1;
        return $returnArr;
    }

    /**
     * 注册
     * @param $param array 登录信息
     * @return array
     */
    public function register($param){
        $verify = $param['verify'];//验证码
        $smscode = $param['smscode'];//短信验证码
        $phone = $param['phone'];//手机号
        $name = $param['name'];//用户名
        $companyame = $param['company_name'];//公司名
        $spreadCode = $param['spread_code'];//推广码
        $tradingCertificateImage = $param['trading_certificate_image'] ?? '';//营业执照证书照片
        $idCardFront = $param['id_card_front'] ?? '';//身份证正面照
        $idCardReverse = $param['id_card_reverse'] ?? '';//身份证反面照
        $account = $param['account']??'';
        // 保存数据
        $data = [];
        $data['trading_certificate_image'] = $tradingCertificateImage;
        $data['id_card_front'] = $idCardFront;
        $data['id_card_reverse'] = $idCardReverse;
        $data['account'] = $account ?: $phone;
        $lastSms = [];
        if(cfg('open_merchant_reg_sms')){//开启短信验证
            if(!$verify){
                throw new \think\Exception(L_("请输入验证码"), 1003);
            }

            // 获得最后一次短信验证码
            $where = [
                'phone' => $phone
            ];
            $lastSms = (new MerchantSmsRecordService())->getLastOne($where);

            if($lastSms['status']==1){
                throw new \think\Exception(L_("该短信验证码已失效，请重新获取！"), 1003);
            }
            if(time() - $lastSms['send_time'] > 1200){
                throw new \think\Exception(L_("短信验证码已超过20分钟！"), 1003);
            }
            if($smscode != $lastSms['extra']){
                throw new \think\Exception(L_("短信验证码不正确！"), 1003);
            }
        }
        
        if(!isset($param['address'])||!$param['address']){
            throw new \think\Exception(L_("请填写详细地址！"), 1003);
        }

        //校验密码
        $check_password = verifyPassword($param['pwd']);
        if($check_password['code']!=0){
            throw new \think\Exception($check_password['msg']);
        }

        // 商家service
        $merchantService = new MerchantService();

        //查询商家名称是否已经存在
        $where = [];
        $where['name'] = $name;
        $nowerchant = $merchantService->getOne($where);
        if(!empty($nowMerchant)){
            throw new \think\Exception(L_("商家名称已经存在！"), 1003);
        }

        //手机号
        $where = [];
        $where['phone'] = $phone;
        $nowerchant = $merchantService->getOne($where);
        if(!empty($nowerchant)){
            throw new \think\Exception(L_("手机号已经存在！"), 1003);
        }

        //账号作为手机号
        $where = [];
        $where['account'] = $phone;
        $nowerchant = $merchantService->getOne($where);
        if(!empty($nowerchant)){
            throw new \think\Exception(L_("该手机号作为账号已经存在，不允许重复！"), 1003);
        }

        // 处理 公司名称（营业执照上的名称） 在 【商家推荐商家批发佣金功能】开关开启的情况下
        if (cfg('merchant_type_switch') == 1) {
            if (empty($companyName)) {
//                throw new \think\Exception(L_("请填写公司名称（营业执照上的名称）"), 1003);
            }
        }

        $data['spread_code'] =  '';
        $data['mer_spread_code'] =  '';

        // 处理下推广码问题
        if ($spreadCode && stripos($spreadCode,'mer') === 0) {
            // 兼容原来旧数据
            if (cfg('merchant_recommend_wholesale_switch') != 1) {

//                throw new \think\Exception(L_("您填写的推广码无效，请核实后重新填写!"), 1003);
            }

            // 查看推广码是否存在
            $where = [];
            $where['mer_code'] = $spreadCode;
            $spreadMerchant = $merchantService->getOne($where);
            if (!$spreadMerchant) {
//                throw new \think\Exception(L_("您填写的推广码错误，请核实后重新填写。"), 1003);
            }

            // 查看平台是否有推广功能
            $where = [
                'name' => '我的商家推广',
                'status' => 1
            ];
            $menuRole = (new MerchantMenuService())->getOne($where);
            if(empty($menuRole)){
//                throw new \think\Exception(L_("您填写的推广码无效，请核实后重新填写!"), 1003);
            }

            // 查看推广商家是否有这个权限
            $menusArr = explode(',', $spreadMerchant['menus']);
            if(!is_array($menusArr) || !in_array($menuRole['id'],$menusArr)){
//                throw new \think\Exception(L_("您填写的推广码无效，请核实后重新填写!"), 1003);
            }

            $data['mer_spread_code'] =  $spreadCode;
            $data['spread_code'] =  '';

        } elseif ($spreadCode && intval($spreadCode) > 100000 && intval($spreadCode) < 999999) {
            if (cfg('merchant_recommend_wholesale_switch') != 1) {
//                throw new \think\Exception(L_("您填写的推广码无效，请核实后重新填写!"), 1003);
            }
            // 查看推广码是否存在
            $where = [];
            $where['mer_code'] = $spreadCode;
            $spreadMerchant = $merchantService->getOne($where);
            if (!$spreadMerchant) {
//                throw new \think\Exception(L_("您填写的推广码错误，请核实后重新填写。"), 1003);
            }

            // 查看平台是否有推广功能
            $where = [
                'name' => '我的商家推广',
                'status' => 1
            ];
            $menuRole = (new MerchantMenuService())->getOne($where);
            if(empty($menuRole)){
//                throw new \think\Exception(L_("您填写的推广码无效，请核实后重新填写!"), 1003);
            }

            // 查看推广商家是否有这个权限
            $menusArr = explode(',', $spreadMerchant['menus']);
            if(!is_array($menusArr) || !in_array($menuRole['id'],$menusArr)){
//                throw new \think\Exception(L_("您填写的推广码无效，请核实后重新填写!"), 1003);
            }
            $data['mer_spread_code'] =  $spreadCode;

        } elseif(!$spreadCode && cfg('merchant_recommend_wholesale_switch')) {
            // 如果开启了没填，提示信息
//            throw new \think\Exception(L_("您填写的推广码无效，请核实后重新填写!"), 1003);
        } else {
            $data['spread_code'] = $spreadCode;
        }


        //邀请码
//        if(cfg('open_admin_code')==1){
//            $where['invit_code'] = $param['invit_code'];
//            $admin = (new AdminUserService())->getOne($where);
//            if(!$admin){
//                throw new \think\Exception(L_("邀请码不存在!"), 1003);
//            }
//        }

        $cityInvite = [];
        if ($param['invit_code']) {
            $cityInvite = \think\facade\Db::name('merchant_spread_user')->where(['city_invite_code' => $param['invit_code']])->findOrEmpty();
        }

        if(empty($cityInvite)){
            if(cfg('open_bd_spread') == 1 && trim($param['invit_code']) != ''){
                $bd_where = [
                    ['invitation_code', '=', strtoupper(trim($param['invit_code']))],
                    ['status', '=', 1],
                    ['is_del', '=', 0]
                ];
                $bd = (new Bd())->getOne($bd_where);
                $bd && $bd = $bd->toArray();
                if(empty($bd)){
                    throw new \think\Exception("业务员BD邀请码不存在！", 1003);
                }
                $data['bd_type'] = $bd['type'];
                $data['bd_id'] = $bd['bd_id'];
            }
	}

        $personData = [];
        if (cfg('open_bd_spread_new') == 1 && $param['invit_code'] != '') {//新版营销
            $data['newmarket_code'] = $param['invit_code'];
            $personData = (new NewMarketingPersonManager())->where([['invitation_code', '=', $data['newmarket_code']], ['is_del', '=', 0]])->field('person_id,team_id')->find() ?? [];
            if (!$personData) {
                $personData = (new NewMarketingPersonSalesman())->where([['invitation_code', '=', $data['newmarket_code']], ['is_del', '=', 0]])->field('person_id,team_id')->find() ?? [];
                if (!$personData) {
                    throw new \think\Exception("营销邀请码有误！", 1003);
                }
            }
        } else if(cfg('open_bd_spread') == 1 && trim($param['invit_code']) != ''){
            $bd_where = [
                ['invitation_code', '=', strtoupper(trim($param['invit_code']))],
                ['status', '=', 1],
                ['is_del', '=', 0]
            ];
            $bd = (new Bd())->getOne($bd_where);
            if(empty($bd)){
                throw new \think\Exception("业务员BD邀请码不存在！", 1003);
            }
            $bd = $bd->toArray();
            $data['bd_type'] = $bd['type'];
            $data['bd_id'] = $bd['bd_id'];
        }


        // 状态
        if(cfg('merchant_verify')){
            $data['status'] = 2;
        }else{
            $data['status'] = 1;
        }
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['pwd'] = md5($param['pwd']);
        $data['reg_ip'] = request()->ip();
        $data['reg_time'] = time();
        $data['login_count'] = 0;
        $data['reg_from'] = 0;
        $data['phone_country_type'] = $param['phone_country_type'];
        $data['province_id'] = $param['province_id'];
        $data['city_id'] = $param['city_id'];
        $data['area_id'] = $param['area_id'];
        $data['street_id'] = $param['street_id'] ?? 0;
        $data['address'] = $param['address'] ?? '';



        //商家注册自定义表单
        $diyData = $param['diy_form'] ?? [];
        $diyData = array_values($diyData);
        $diyData && $data['diy_form_data'] = serialize($diyData);

		
        if(cfg('open_bind_merchant_boss') && $phone){// 定制 用户成为商家老板
            $user = (new UserService)->getUser($phone,'phone');
            if($user){
                $data['boss_uid'] = $user['uid'];
                
                if(isset($bd) && $bd && $bd['uid']){ // 业务员推广商家，商家绑定个人账号，个人账号如果没有上级，则业务员成为个人账号上级。同时修改商家绑定的个人账号，新绑定的账号同样遵从以上逻辑。
                    // 查询老板账号是否存在上级
                    $spreadWhere = [
                        'uid' => $user['uid']
                    ];
                    $oldSpread = (new UserSpreadService())->getOneRow($spreadWhere);
                    if(empty($oldSpread)){
                        $spreadData = [
                            'uid' =>  $user['uid'],
                            'spread_uid' => $bd['uid'],
                            'add_time' => time()
                        ]; 
                        (new UserSpreadService())->add($spreadData);
                    }

                }
            }
        }


        // 添加商家
        $insertId = $merchantService->add($data);

        if(!$insertId) {
            throw new \think\Exception(L_("注注册失败,请重试！"), 1003);
        }


        if ($cityInvite) {
            $inviteData = [
                'uid' => $cityInvite['uid'],
                'mer_id' => $insertId,
                'create_time' => time()
            ];
            \think\facade\Db::name('merchant_spread_invite_merchant')->insert($inviteData);
 	}

        if ($personData) {//营销商家邀请
            (new NewMarketingPersonMer())->insert([
                'mer_id' => $insertId,
                'person_id' => $personData['person_id'],
                'team_id' => $personData['team_id'],
                'type' => 0,
                'add_time' => time()
            ]);
            (new NewMarketingJoinLog())->insert([
                'invite_id' => $personData['person_id'],
                'mer_id' => $insertId,
                'type' => 1,
                'add_time' => time()
            ]);

        }

        // 添加积分
//        M('Merchant_score')->add(array('parent_id'=>$insertId,'type'=>1));

        /*无需审核的商家注册，注册后即添加滚动信息*/
        if($data['status']==1){
//                D('Scroll_msg')->add_msg('mer_reg',$insertId,L_('商家X1于X2注册成功！',array('X1'=>str_replace_name($_POST['name']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));
        }

        /*校验成功，将验证码状态至为已使用*/
        if(cfg('open_merchant_reg_sms') && $lastSms) {//开启短信验证
            $where = [
                'pigcms_id' => $lastSms['pigcms_id']
            ];
            $smsDate = [
                'status' => 1
            ];
            (new MerchantSmsRecordService())->updateThis($where, $smsDate);
        }


        //多语言表增加数据 TODO
//            $lang_model = D('Lang');
//            $_POST['mer_id'] = $insertId;
//            $lang_model->add_lang_data('Merchant',$_POST);


        if (0 == cfg('merchant_verify') && cfg('open_distributor')==1 && $spreadCode) {
            (new DistributorAgentService())->agentSpreadLog($insertId);
        }

        // 通知管理员
        $merchantService->registerNotice($insertId);
        $returnArr = [];
        if(cfg('merchant_verify')){
            $returnArr['msg'] = L_('注册成功,请耐心等待审核或联系工作人员审核~');
        }else{
            $returnArr['msg'] = L_('注册成功,请登录~');
        }
        return $returnArr;
    }

    /**
     * 发送短信验证吗
     * @param $param array 登录信息
     * @return array
     */
    public function sendSms($param){
        $phone = $param['phone'];
        $phoneCountryType = $param['phone_country_type'];
        $verify = $param['verify'];

        if(!captcha_check($verify)){
            // 验证失败
            throw new \think\Exception("验证码错误", 1003);
        }

        if(empty($phone)){
            throw new \think\Exception(L_("请输入手机号!"), 1003);
        }

        // 查看手机号是否已注册
        $where = [
            'phone' => $phone
        ];
        $nowMerchant = (new MerchantService())->getOne($where);
        if(!empty($nowMerchant)){
            throw new \think\Exception(L_("该手机号已注册商家"), 1003);
        }

        //防止1分钟内多次发送短信
        $where = [
            'phone' => $phone,
            'type' => 1
        ];
        $lastSms = (new MerchantSmsRecordService())->getLastOne($where);
        if(time() - $lastSms['send_time'] < 60){
            throw new \think\Exception(L_("一分钟内不能多次发送短信"), 1003);
        }

        // 生成验证码
        $code = mt_rand(1000, 9999);

        // 短信内容
        $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',$code);

        // 通过区号获得正确手机号
        $phone = phone_format($phoneCountryType, $phone);

        // 添加短信记录
        $columns = array();
        $columns['phone'] = $phone;
        $columns['extra'] = $code;
        $columns['type'] = 1;
        $columns['mer_id'] = 0;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = (new MerchantSmsRecordService())->add($columns);
        if (!$result){
            throw new \think\Exception(L_("短信数据写入失败"), 1003);
        }

        // 发送
        $smsData = [
            'mer_id' => 0,
            'store_id' => 0,
            'content' => $text,
            'mobile' => $phone,
            'uid' => 0,
            'type' => 'merchant_pwd'
        ];
        $phoneCountryType && $smsData['nationCode']  = $phoneCountryType;

        $return = (new SmsService())->sendSms($smsData);

        return true;
    }

    /**
     * 自动登录商家后台
     * @param $param array
     * @return array
     */
    public function autoLogin($param, $systemUser)
    {
        $merId = $param['mer_id'];
        if($systemUser['level']<2){
            if(!in_array(310,$systemUser['menus']))
                throw new \think\Exception(L_("您没有访问的权限！"), 1003);
        }

        $where = [
            'mer_id' => $merId
        ];
        $nowMerchant = (new MerchantService())->getOne($where);

        if(empty($nowMerchant) || $nowMerchant['status'] == 0 || $nowMerchant['status'] == 2){
            throw new \think\Exception(L_("该商家的状态不存在！请查阅。"), 1003);
        }


        // 返回数据
        $returnArr = [];

        //系统后台伪登录标识
        $extends = 'system_login';

        // 生成ticket
        $ticket = Token::createToken($nowMerchant['mer_id'],$extends);
        if(!$ticket){
            throw new \think\Exception("登陆失败,请重试！", 1003);
        }
        $returnArr['ticket'] = $ticket;
        return $returnArr;
    }

    /**
     * 商家子账号登录
     * @author: zt
     * @date: 2023/04/12
     */
    public function subAccountLogin($param)
    {
        if (!Captcha::check($param['verify'])) {
            // 验证失败
            throw new \think\Exception(L_("验证码错误"), 1003);
        }

        $arr = explode('@', $param['account']);
        if (count($arr) != 2) {
            throw new \think\Exception(L_("账号格式错误"), 1003);
        }
        $merSubAccount = $arr[0];
        $merAccount = $arr[1];
        $where = [
            ['m.account', '=', $merAccount],
            ['m.status', '<>', 4],
            ['ua.account', '=', $merSubAccount],
            ['ua.is_del', '=', 0],
            ['ua.status', '=', 1],
        ];
        $subAccount = (new \app\merchant\model\db\MerchantUserAccount())->alias('ua')
            ->join('merchant m', 'm.mer_id=ua.mer_id')
            ->where($where)->field('ua.*,m.name AS mer_name')->findOrEmpty()->toArray();
        if (empty($subAccount)) {
            throw new \think\Exception(L_("账号或密码错误"), 1003);
        }

        // 返回数据
        $returnArr = ['msg' => L_('登录成功')];
        $ticket = Token::createToken($subAccount['mer_id'], ['mer_subaccount_id' => $subAccount['id']]);
        if (!$ticket) {
            throw new \think\Exception(L_("登陆失败,请重试！"), 1003);
        }
        $returnArr['ticket'] = $ticket;
        return $returnArr;
    }
}