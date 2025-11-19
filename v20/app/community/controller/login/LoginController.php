<?php

/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/20 14:33
 */

namespace app\community\controller\login;

use app\common\model\service\send_message\SmsService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\AdminLoginService;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageConfigService;
use app\community\model\service\ConfigService;
use app\community\model\service\PropertyAdminService;

//物业普通管理员
use app\community\model\service\HouseAdminService;
use app\community\model\service\PrivilegePackageService;

//功能套餐
use app\community\model\service\PackageOrderService;
use app\common\model\service\config\ConfigCustomizationService;
//功能套餐
class LoginController extends CommunityBaseController
{

    public $serviceAdminLogin;

    public function initialize()
    {
        parent::initialize();
        $this->serviceAdminLogin = new AdminLoginService();
    }

    /**
     * 获取登录配置
     * @return \json
     * @author: wanziyang
     * @date_time: 2020/5/21 11:46
     */
    public function config()
    {
        $keys = array_keys($this->request->param());
		$params = ['system_type', 'xtype'];
		if (!request()->isPost() || array_diff($keys, $params)) {
			return api_output_error(1003, '非法请求', 403);
		}

        $xtype = $this->request->post('xtype', '', 'trim');
        $arr = $this->serviceAdminLogin->login_role($xtype);
        // 返回下注册要跳转页面链接
        $register = '';
        $register_v20_name = "";
        $arr['register'] = $register;
        $arr['register_v20_name'] = $register_v20_name;
        $enterprise_login_suiteId = cfg('enterprise_login_suiteId');
        $enterprise_wx_corpid = cfg('enterprise_wx_corpid');
        $wx_token = cfg('enterprise_login_token');
        $wx_encodingaeskey = cfg('enterprise_login_encodingaeskey');
        if ($enterprise_login_suiteId && $enterprise_wx_corpid && $wx_token && $wx_encodingaeskey) {
            $arr['authQyLogin'] = true;
        } else {
            $arr['authQyLogin'] = false;
        }
        $enterprise_wx_corpid = cfg('enterprise_wx_corpid');
        $id = $enterprise_login_suiteId ? $enterprise_login_suiteId : $enterprise_wx_corpid;
        $arr['largeLoginImg'] = "https://open.work.weixin.qq.com/service/img?id={$id}&t=login&c=white&s=large";
        $arr['srcsetLoginImg'] = "https://open.work.weixin.qq.com/service/img?id={$id}&t=login&c=white&s=large@2x 2x";
        $arr['is_support_https']=0;
        $use_https = cfg('use_https');
        if($use_https && $xtype!='sentryWatch'){
            $arr['is_support_https']=1;
        }
        $arr['have_phone_sms_login']=0;
        $configCustomizationService= new ConfigCustomizationService();
        $funcAqh2025cnSpecial=$configCustomizationService->getFuncAqh2025cnSpecial();
        if($funcAqh2025cnSpecial){
            $arr['have_phone_sms_login']=1;
        }
        return api_output(0, $arr);
    }

    public function check()
    {
        $account = $this->request->param('username', '', 'trim');
        $user_phone = $this->request->param('userphone', '', 'trim');
        $phone_code = $this->request->param('phone_code', '', 'intval');
        $login_type = $this->request->param('login_type', '', 'trim');  //登录方式 phone_vcode短信验证码登录
        $xtype = $this->request->post('xtype', '', 'trim');
        $village_id = $this->request->post('village_id', 0, 'intval');
        if (empty($account) && $login_type!='phone_vcode') {
            return api_output_error(1001, '请上传账号！');
        }
        $pwd = $this->request->param('password', '', 'trim');
        if (empty($pwd) && $login_type!='phone_vcode') {
            return api_output_error(1001, '请上传密码！');
        }
        $login_role = $this->request->param('login_role', '1', 'intval');
        if (empty($login_role)) {
            return api_output_error(1001, '请选择登录身份！');
        }
        $login_data = [
            'account' => $account,
            'pwd' => $pwd,
            'login_role' => $login_role,
            'xtype'=>$xtype,
            'village_id'=>$village_id,
            'user_phone'=>$user_phone,
            'phone_code'=>$phone_code,
            'login_type'=>$login_type
        ];
        fdump_api([ 'line' => __LINE__, 'login_data' => $login_data], '00LoginCheck', 1);
        try {
            $data = $this->serviceAdminLogin->login($login_data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($data) {
            return api_output(0, $data, "登录成功");
        }
        return api_output_error(-1, "账号或密码错误");
    }


    /**
     * 返回用户信息
     * @return \json
     * @author: wanziyang
     * @date_time: 2020/5/21 14:06
     */
    public function userInfo()
    {
        $returnArr = $this->serviceAdminLogin->formatUserData($this->adminUser, $this->login_role);
        if (!$returnArr) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        return api_output(0, $returnArr);
    }

    /**
     * 注册
     * @return \json
     * @author: weili
     * @date_time: 2020/7/6 14:01
     */
    public function regCheck()
    {
        //获取对应的配置
        $configService = new ConfigService;
        $houseName = $configService->get_config('house_name', 'value');
        $houseName = $houseName['value'];

        $villageName = $this->request->param('villageName', '', 'trim');
        if (empty($villageName)) {
            return api_output_error(1001, '请填写' . $houseName . '名称！');
        }
        $villageAddress = $this->request->param('villageAddress', '', 'trim');
        if (empty($villageAddress)) {
            return api_output_error(1001, '请选择' . $houseName . '地址！');
        }
        $propertyName = $this->request->param('propertyName', '', 'trim');
        if (empty($propertyName)) {
            return api_output_error(1001, '请填写物业公司名称！');
        }
        $propertyAdder = $this->request->param('propertyAdder', '', 'trim');
        if (empty($propertyAdder)) {
            return api_output_error(1001, '请填写物业公司名称！');
        }
        $propertyTel = $this->request->param('propertyTel', '', 'trim');
        if (empty($propertyTel)) {
            return api_output_error(1001, '请填写物业公司名称！');
        }
        $account = $this->request->param('account', '', 'trim');
        if (empty($account)) {
            return api_output_error(1001, '请填写' . $houseName . '后台管理帐号！');
        }
        $pwd = $this->request->param('password', '', 'trim');
        if (empty($pwd)) {
            return api_output_error(1001, '请填写' . $houseName . '后台管理密码！');
        }
        $randomNumber = $this->request->param('randomNumber', '', 'trim');
        $invitation_code = $this->request->param('invitation_code', '', 'trim');
       // print_r($invitation_code);exit;
        $provinceId = $this->request->param('provinceId', '', 'trim');
        $cityId = $this->request->param('cityId', '', 'trim');
        $areaId = $this->request->param('areaId', '', 'trim');
        $package_id = $this->request->param('package_id', '', 'intval');
        if (empty($provinceId) || empty($cityId) || empty($areaId)) {
            return api_output_error(1001, '请选择有效地址');
        }
        if (!$package_id) {
            return api_output_error(1001, '请选择有效套餐');
        }
        $dataArr = [
            'account' => $account,
            'password' => md5($pwd),
            'property_name' => $propertyName,
            'property_address' => $propertyAdder,
            'property_phone' => $propertyTel,
            'status' => 3,
            'create_time' => time(),
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'area_id' => $areaId,
            'invitation_code' => $invitation_code
        ];
//        return api_output(0, 1, "注册成功,请耐心等待审核或联系工作人员审核");
//        return json($dataArr);
        $serviceHouseProperty = new HousePropertyService();
        $serviceHouseVillage = new HouseVillageService();
        $serviceHouseVillageConfig = new HouseVillageConfigService;
        $servicePackageOrder = new PackageOrderService();
        $map[] = ['account', '=', $account];
        $field = 'id,account';
        $info = $serviceHouseProperty->getFind($map, $field);
        if ($info) {
            return api_output_error(1001, '物业账号已存在，请重新注册');
        }
        if (!empty($invitation_code)){
            $personInfo = $serviceHouseProperty->getPersonInfo($invitation_code);
            if (empty($personInfo)) {
                return api_output_error(1001, '邀请码错误');
            }
        }
        try {
            $propertyId = $serviceHouseProperty->addData($dataArr);
            if ($propertyId) {
                fdump_api(['注册成功后更新wx_bind'.__LINE__,$randomNumber,$propertyId],'getResult',true);
                $type='';
                if (strpos($randomNumber, 'Register') !== false) {
                   $type='register';
                }
                if (strpos($randomNumber, 'Install') !== false) {
                    $type='install';
                }
                $this->serviceAdminLogin->getResult($randomNumber,$type,$propertyId);
                //试用套餐 start
                $arr_post = [
                    'package_id' => $package_id,
                    'property_id' => $propertyId,
                    'property_name' => $propertyName,
                    'property_tel' => $propertyTel,
                ];
                $servicePackageOrder->addPackageOrder($arr_post);
                //试用套餐  end
                $dataArr['village_name'] = $villageName;
                $dataArr['village_address'] = $villageAddress;
                $dataArr['add_time'] = time();
                $dataArr['pwd'] = md5($pwd);
                $dataArr['property_id'] = $propertyId;
                unset($dataArr['create_time']);
                unset($dataArr['password']);
                // 添加默认允许游客访问
                if (!$this->request->param('tourist', '', 'trim')) {
                    $dataArr['tourist'] = 1;
                }
                unset($dataArr['invitation_code']);
                $villageId = $serviceHouseVillage->addHouseVillageData($dataArr);
                if ($villageId) {
                    $data['village_id'] = $villageId;
                    $serviceHouseVillageConfig->addData($data);
                    return api_output(0, $villageId, "注册成功,请耐心等待审核或联系工作人员审核");
                } else {
                    return api_output_error(1001, '注册失败,请重试！');
                }
            } else {
                return api_output_error(1001, '注册失败,请重试！');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes: 获取注册页使用功能套餐
     * @return \json
     * @author: weili
     * @datetime: 2020/8/15 11:29
     */
    public function getPackageList()
    {
        $servicePrivilegePackage = new PrivilegePackageService();
        $where[] = ['status', '=', 1];
        $order = 'package_price asc,sort desc,package_id asc';
        try {
            $list = $servicePrivilegePackage->getPackageContent($where, $order);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * 企业微信注册
     * @author:zhubaodi
     * @date_time: 2021/7/28 17:47
     */
    public function qyRegister()
    {
        try {
            $res = $this->serviceAdminLogin->qyRegister();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($res['errcode'] == 0) {
            return api_output(0, $res, "成功");
        } else {
            return api_output(1001, [], $res['errmsg']);
        }

    }

    /**
     * 安装企业微信
     * @author:zhubaodi
     * @date_time: 2021/7/29 10:30
     */
    public function qyIstall()
    {
        try {
            $res = $this->serviceAdminLogin->qyInstall();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($res['errcode'] == 0) {
            return api_output(0, $res, "成功");
        } else {
            return api_output(1001, [], $res['errmsg']);
        }
    }

    /**
     * 查询注册/安装结果
     * @author:zhubaodi
     * @date_time: 2021/8/3 9:54
     */
    public function getResult()
    {
        $randomNumber = $this->request->param('randomNumber', '', 'trim');
        $type = $this->request->param('type', '', 'trim');
        $property_id = $this->request->param('property_id', '', 'trim');
        if (empty($randomNumber)) {
            return api_output_error(1001, '请先获取参数');
        }
        try {
            $res = $this->serviceAdminLogin->getResult($randomNumber,$type,$property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($res) {
            return api_output(0, $res, "成功");
        } else {
            return api_output(1001, [], $res['errmsg']);
        }
    }

    /**
     * 企业微信登录
     * @author:zhubaodi
     * @date_time: 2021/8/4 11:17
     */
    public function qyLogin()
    {
        try {
            $res = $this->serviceAdminLogin->qyLogin();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($res['errcode'] == 0) {
            return api_output(0, $res, "成功");
        } else {
            return api_output(1001, [], $res['errmsg']);
        }
    }

    public function sendPhoneCode(){
        $send_type = $this->request->post('send_type', 1, 'intval');
        $login_role = $this->request->post('login_role', 0, 'intval');
        $xphone = $this->request->post('xphone', 0, 'trim');
        if(empty($xphone) || !preg_match("/^1[3456789]\d{9}$/", $xphone)){
            return api_output(1001,[], '手机号格式错误');
        }
        if($login_role<1){
            return api_output(1001,[], '请选择登录身份！');
        }
        $rolePhoneInfo=array();
        try {
            $rolePhoneInfo = $this->serviceAdminLogin->checkLoginRolePhone($login_role,$xphone);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        $houseSmsRecordService= new  \app\community\model\service\HouseSmsRecordService();
        $whereArr=array(['phone','=',$xphone],['send_type','=',1]);
        $smsRecordInfo=$houseSmsRecordService->getOneSmsRecord($whereArr,'*','id desc');
        $nowtime=time();
        $send_time_tmp=$nowtime-30;
        if($smsRecordInfo && $smsRecordInfo['send_time']>$send_time_tmp){
            return api_output(1001,[], '请不要频繁发送短信！');
        }
        // 生成验证码
        $code = mt_rand(10000, 99999);
        // 短信内容
        $text = L_('您的验证码是：X1。此验证码5分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',$code);
        // 发送
        $smsData = [
            'mer_id' => 0,
            'store_id' => 0,
            'content' => $text,
            'mobile' => $xphone,
            'uid' => 0,
            'type' => 'community_login',
            'noTaskForceSend' => 1,  //直接发
        ];
        $return = (new SmsService())->sendSms($smsData);
        $nowtime=time();
        $expire_time=$nowtime+300;
        $smsRecordAdd=array('phone'=>$xphone,'login_role'=>$login_role,'text'=>$text,'vcode'=>$code,'send_type'=>$send_type,'send_time'=>$nowtime,'expire_time'=>$expire_time);
        if($rolePhoneInfo){
            $smsRecordAdd['extra_data']=json_encode($rolePhoneInfo,JSON_UNESCAPED_UNICODE);
        }
        $record_id=$houseSmsRecordService->addOneSmsRecord($smsRecordAdd);
        
        return api_output(0,['record_id'=>$record_id,'ret'=>$return,'msg'=>'发送成功，请注意查收短信'], '发送成功，请注意查收短信');
    }
}
