<?php
/**
 * 店员短信service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/08/29 17:35
 */

namespace app\storestaff\model\service\sms;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\UserService;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\sms\MerchantSmsRecordService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use app\storestaff\model\service\StoreStaffService;

class StaffSmsService {
    public $merchantSmsRecordModel = null;
    public function __construct()
    {
    }

    /**
     * 发送登录短信验证吗
     * @param $param array 登录信息
     * @return array
     */
    public function sendLoginSms($param){
        $phone = $param['phone'];
        $code = $param['code'] ?? '';
        $phoneCountryType = $param['phone_country_type'] ?? '';

        if(empty($phone)){
            throw new \think\Exception(L_("请输入手机号!"), 1003);
        }

        // 验证验证码
        if(cfg('qcloud_captcha_appid')){//腾讯云验证
            include(request()->server('DOCUMENT_ROOT').'/v20/extend/qcloud_captcha/QcloudCaptcha.php');
            $captchaTokenArr = explode('___', $code);
            if(count($captchaTokenArr) != 2){
                throw new \think\Exception(L_('图形校验码出现错误，请刷新页面后再试。'), 1003);
            }
            $captchaToken = $captchaTokenArr[0];
            $captchaRandstr = $captchaTokenArr[1];

            $QcloudCaptcha = new \QcloudCaptcha();
            $response = $QcloudCaptcha->send($captchaToken, $captchaRandstr);

            if($response['Response']['CaptchaCode'] != '1'){
                /**token验证失败**/
                throw new \think\Exception(L_('图形校验码出现错误，请刷新页面后再试。'). $response['Response']['CaptchaMsg'], 1003);
            }
        }elseif (cfg('dingxiang_captcha_appid')) {    //顶象图文验证码
            if(!$code){
                throw new \think\Exception(L_("图形校验码未监控通过，请刷新页面后再试。"), 1003);
            }
            include(request()->server('DOCUMENT_ROOT').'/v20/extend/dingxianginc/CaptchaClient.php');
            $appId = cfg('dingxiang_captcha_appid');
            $appSecret = cfg('dingxiang_captcha_appsecret');
            $client = new \CaptchaClient($appId, $appSecret);
            $client->setTimeOut(5);      //设置超时时间，默认2秒
            $response = $client->verifyToken($code);  //token指的是前端传递的值，即验证码验证成功颁发的token
            //确保验证状态是SERVER_SUCCESS，SDK中有容错机制，在网络出现异常的情况会返回通过
            if($response->serverStatus != 'SERVER_SUCCESS' || !$response->result){
                /**token验证失败**/
                throw new \think\Exception(L_("图形校验码出现错误，请刷新页面后再试。"), 1003);
            }
        }

        // 验证账号是否存在
        $where = [
            'tel' => $phone
        ];
        $staffUserList = (new MerchantStoreStaffService())->getStaffListByCondition($where);
        if(!$staffUserList) {
            throw new \think\Exception(L_("帐号不存在"), 1003);
        }

        // 获得状态正常的店员账号信息
        (new StoreStaffService())->getNormalStaffUser($staffUserList);

        // 防止1分钟内多次发送短信
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
     * 发送短信验证吗
     * @param $param array 登录信息
     * @return array
     */
    public function sendSmsToUser($param){
        $phone = $param['phone'] ?? '';
        $phoneCountryType = $param['phone_country_type'] ?? '';

        if(empty($phone)){
            throw new \think\Exception(L_("请输入手机号!"), 1003);
        }

        // 查看手机号是否已注册
        $where = [
            'phone' => $phone
        ];
        $nowUser = (new UserService())->getUser($phone,'phone');
        if(empty($nowUser)){
            throw new \think\Exception(L_("用户不存在"), 1003);
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

        $param['store_id'] && $store = (new MerchantStoreService())->getStoreByStoreId($param['store_id']);
        // 添加短信记录
        $columns = array();
        $columns['phone'] = $phone;
        $columns['extra'] = $code;
        $columns['type'] = 5;
        $columns['mer_id'] = $store['mer_id'] ?? 0;
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
            'uid' => $nowUser['uid'],
            'mer_id' => $columns['mer_id'],
            'type' => 'merchant_pwd'
        ];
        $phoneCountryType && $smsData['nationCode']  = $phoneCountryType;
        try {
            $return = (new SmsService())->sendSms($smsData);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), $e->getCode());
        }
        $returnArr['user'] = [
            'nickname' => $nowUser['nickname'],
            'now_money' => $nowUser['now_money'],
        ];
        return $returnArr;
    }


}