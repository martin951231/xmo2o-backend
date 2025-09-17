<?php
/**
 * 商家登录service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/03 09:59
 */

namespace app\merchant\model\service\storestaff;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\UserService;
use app\merchant\model\db\Merchant as MerchantModel;
use app\common\model\service\admin_user\AdminUserService;
use app\common\model\service\weixin\LoginQrcodeService;
use app\merchant\model\service\sms\MerchantSmsRecordService;
use app\storestaff\model\service\StoreStaffService;
use think\captcha\facade\Captcha;
use token\Token;
use net\IpLocation;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\storeImageService;
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
        $merchantStoreStaffService = new MerchantStoreStaffService();
        switch($param['ltype']){
            case '1':
                // 用户名密码登录
                // 用户信息
                $where = [
                    'username' => $param['username']
                ];
                if(empty($param['username'])){
                    // 验证失败
                    throw new \think\Exception(L_("请输入用户名"), 1003);
                }

                if(!Captcha::check($param['verify'])){
                    // 验证失败
                    throw new \think\Exception(L_("验证码错误"), 1003);
                }

                $staffUser = $merchantStoreStaffService->getOne($where);
                if(!$staffUser) {
                    throw new \think\Exception(L_("帐号不存在！"), 1003);
                }

                if($staffUser['password'] != md5($param['password'])) {
                    throw new \think\Exception(L_("输入的密码错误"), 1003);
                }
                break;
            case '2':
                // 扫码登录
                $where = [
                    'id' => $param['id']
                ];
                $staffUser = $merchantStoreStaffService->getOne($where);
                if(!$staffUser) {
                    throw new \think\Exception(L_("帐号不存在"), 1003);
                }
                break;
            case '3':
                $where = [
                    ['phone','=',$param['username']],
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
                    'tel' => $param['username']
                ];
                $staffUser = $merchantStoreStaffService->getOne($where);
                if(!$staffUser) {
                    throw new \think\Exception(L_("店员不存在"), 1003);
                }
                break;
        }

        // 判断店铺状态
        $where = [
            'store_id' => $staffUser['store_id']
        ];
        $store = (new MerchantStoreService())->getOne($where);
        if(empty($store) || $store['status'] != 1){
            throw new \think\Exception(L_("店铺状态不正常"), 1003);
        }

        // 判断商家状态
        $where = [
            'mer_id' => $store['mer_id']
        ];
        $merchant = (new MerchantService())->getOne($where);

        if($merchant['status'] == 4){
            throw new \think\Exception(L_("商户已被删除"), 1003);
        }

        if($merchant['status'] != 1){
            throw new \think\Exception(L_("商户状态不正常"), 1003);
        }


        // 保存登录信息
        $data = [];
        $data['last_time'] = time();
        $where = [
            'id' => $staffUser['id']
        ];
        if(!$merchantStoreStaffService->updateThis($where, $data)){
            throw new \think\Exception(L_("登录信息保存失败,请重试！"), 1003);
        }

        if ($staffUser['type'] == 2) {
            // 判断是否已登录商家

        }

        $remark = L_('登录成功,现在跳转~');

        // 返回数据
        $returnArr = [
            'msg' => $remark
        ];

        // 生成ticket
        $ticket = Token::createToken($staffUser['id']);
        if(!$ticket){
            throw new \think\Exception(L_("登陆失败,请重试！"), 1003);
        }
        $returnArr['ticket'] = $ticket;
        return $returnArr;
    }

    /**
     * app登录
     * @param $param array 登录信息
     * @return array
     */
    public function appLogin($param){
        $tm = time();
        $merchantStoreStaffService = new MerchantStoreStaffService();
        switch($param['ltype']){
            case '1':
                // 用户名密码登录
                // 用户信息
                $where = [
                    'username' => $param['username']
                ];
                if(empty($param['username'])){
                    // 验证失败
                    throw new \think\Exception(L_("请输入用户名"), 1003);
                }

                $staffUser = $merchantStoreStaffService->getOne($where);
                if(!$staffUser) {
                    throw new \think\Exception(L_("帐号不存在！"), 1003);
                }

                if($staffUser['password'] != md5($param['password'])) {
                    throw new \think\Exception(L_("输入的密码错误"), 1003);
                }
                $staffUserList = [$staffUser];
                break;
            case '2':
                // 手机号登录

                // 用户信息
                $phone =  $param['phone'] ?? '';
                $smsCode =  $param['sms_code'] ?? '';
                $where = [
                    'phone' => $param['phone']
                ];
                if(empty($param['phone'])){
                    // 验证失败
                    throw new \think\Exception(L_("请输入手机号"), 1003);
                }

               if(empty($param['sms_code'])){
                   throw new \think\Exception(L_("请输入短信验证码"), 1003);
               }

                // 获得最后一次短信验证码
                $where = [
                    'phone' => $phone,
                    'type' => 1
                ];
                $lastSms = (new MerchantSmsRecordService())->getLastOne($where);
                if (empty($lastSms)) {
                   throw new \think\Exception(L_("短信验证码不正确！"), 1003);
                }

               if($lastSms['status']==1){
                   throw new \think\Exception(L_("该短信验证码已失效，请重新获取！"), 1003);
               }
               if(time() - $lastSms['send_time'] > 1200){
                   throw new \think\Exception(L_("短信验证码已超过20分钟！"), 1003);
               }
               if($smsCode != $lastSms['extra']){
                   throw new \think\Exception(L_("短信验证码不正确！"), 1003);
               }

               $whereSms['pigcms_id'] = $lastSms['pigcms_id'];
               $saveSmsData = [
                   'status' => 1
               ];
               (new MerchantSmsRecordService())->updateThis($whereSms,$saveSmsData);

                $where = [
                    'tel' => $phone
                ];
                $staffUserList = $merchantStoreStaffService->getStaffListByCondition($where);
                if(!$staffUserList) {
                    throw new \think\Exception(L_("帐号不存在"), 1003);
                }
                break;
        }

        // 获得状态正常的店员账号信息
        $userList = (new StoreStaffService())->getNormalStaffUser($staffUserList);

        if($staffUserList){
            foreach ($staffUserList as $u) {
                // 保存登录信息
                $data = [];
                $data['last_time'] = $tm;
                $data['jpush_registrationId'] = $param['registrationId'] ?? '';
                $data['device_id'] = $param['Device-Id'] ?? '';
                $data['client'] = $param['client'] ?? '';
                $data['app_version'] = $param['app_version'] ?? '';
                $data['app_version_name'] = $param['app_version_name'] ?? '';
                $where = [
                    'id' => $u['id']
                ];
                if (!$merchantStoreStaffService->updateThis($where, $data)) {
                    throw new \think\Exception(L_("登录信息保存失败,请重试！"), 1003);
                }

                //记录登录日志
                $loginLog = [
                    'uid'         => $u['id'],
                    'client'     =>  $data['client'],
                    'device_id' => $data['device_id'],
                    'app_version' => intval($data['app_version']),
                    'app_version_name' => strval($data['app_version_name']),
                    'add_time'     =>  $data['last_time'],
                    'add_ip'     => request()->ip(),
                    'type'         => '0',
                ];
                \think\facade\Db::name('store_staff_login_log')->insert($loginLog);
            }
        }

        return ['list'=>$userList];
    }


    /**
     * 扫码登录
     * @param $param array 登录信息
     * @return array
     */
    public function weixinLogin(array $param) {
        $qrcodeId = $param['qrcode_id'];
        $returnArr = ['error'=>1];
        $where['id'] = $qrcodeId;
        $loginQrcodeService = new LoginQrcodeService();
        $nowQrcode = $loginQrcodeService->getOne($where);
//        var_dump($nowQrcode);
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

            // 登录店员后台
            $conditionStaff = [];
            $conditionStaff['openid'] = $user['openid'];
            $nowStaff = (new MerchantStoreStaffService())->getOne($conditionStaff);
            if(empty($nowStaff)){
                throw new \think\Exception(L_("微信号未绑定商家，请使用账号登录商家后台绑定"), 1003);
            }
            try {
                $param['id'] = $nowStaff['id'];
                $param['ltype'] = 2;
                $returnArr = $this->login($param);
                $returnArr['error'] = 0;
                return $returnArr;
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage(), $e->getCode());
            }
        }
        return $returnArr;
    }

    /**
     * 自动登录商家后台
     * @param $param array
     * @return array
     */
    public function autoLogin($param, $systemUser)
    {
        $storeId = $param['store_id'];
        if($systemUser['level']<2){
            if(!in_array(310,$systemUser['menus']))
                throw new \think\Exception(L_("您没有访问的权限！"), 1003);
        }

        // 获得一个店员
        $where = [
            'store_id' => $storeId
        ];
        $order = [
            'type' => 'DESC',
            'id' => 'DESC',
        ];
        $nowStaff = (new MerchantStoreStaffService())->getOne($where, '', $order);
        if(empty($nowStaff)){
            throw new \think\Exception(L_("该店铺没有找到店员"), 1003);
        }


        // 返回数据
        $returnArr = [];

        // 保存登录信息
        $data = [];
        $data['last_time'] = time();
        $where = [
            'id' => $nowStaff['id']
        ];
        if(!(new MerchantStoreStaffService())->updateThis($where, $data)){
            throw new \think\Exception("登录信息保存失败,请重试！", 1003);
        }

        //系统后台伪登录标识
        $extends = 'system_login';

        // 生成ticket
        $ticket = Token::createToken($nowStaff['id'],$extends);
        if(!$ticket){
            throw new \think\Exception("登陆失败,请重试！", 1003);
        }
        $returnArr['ticket'] = $ticket;
        return $returnArr;
    }


}