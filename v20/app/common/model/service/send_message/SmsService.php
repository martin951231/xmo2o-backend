<?php
/**
 * 老版发送短信移植
 * time：2020/9/1
 * author zhumengqun
 */

namespace app\common\model\service\send_message;

use app\common\model\service\plan\PlanMsgService;
use app\common\model\service\plan\PlanService;
use app\common\model\service\UserService;
use app\common\model\service\MerchantService;
use app\community\model\db\HouseProperty;
use app\community\model\db\HouseVillage;
use app\community\model\service\workweixin\WorkWeiXinSuiteService;
use app\consts\WorkWeiXinConst;
use app\deliver\model\db\DeliverUser;
use app\deliver\model\service\DeliverUserService;
use think\Exception;

class SmsService
{
    /**
     *
     * 批量发送短信
     * @param array $mobile 手机号码
     * @param string $content 短信内容
     * @param datetime $send_time 发送时间
     * @param string $charset 短信字符类型 gbk / utf-8
     * @param string $id_code 唯一值 、可用于验证码
     * $data = array(mer_id, store_id, content, mobile, uid, type);
     */
    public function sendSms($data = array(), $send_time = '', $charset = 'utf-8', $id_code = '')
    {
        if (!cfg('sms_send_type')) {
            //直接发送不走计划任务
            $ret=invoke_cms_model('Sms/sendSms', func_get_args());
            return $ret;
        }
        if (isset($data['noTaskForceSend']) && $data['noTaskForceSend']==1) {
            //强制直接发送不走计划任务
            $ret= invoke_cms_model('Sms/sendSms', func_get_args());
            return $ret;
        }
        
        if (!empty($data)) {
            $type = isset($data['type']) ? $data['type'] : 'meal';
            if ($type == 'market' && cfg('market_sms_enable')) {    //批发市场短信控制
                throw new \think\Exception('false');
            }
            $sendto = isset($data['sendto']) && $data['sendto'] ? : 'user';
            $mer_id = $data['mer_id'] ? : 0;
            $store_id = $data['store_id'] ? : 0;
            $village_id = isset($data['village_id']) && $data['village_id'] ? $data['village_id'] : 0;
            $uid = $data['uid'] ? intval($data['uid']) : 0;
            $sms_sign=cfg('sms_sign');
            fdump_api(['data'=>$data,'sms_sign_0'=>$sms_sign], 'sendSms_0331', true);
            if (!empty($village_id)){
                $db_house_village=new HouseVillage();
                $village_info=$db_house_village->getOne($village_id,'property_id');
                if (!empty($village_info['property_id'])){
                    $db_house_property=new HouseProperty();
                    $property_info=$db_house_property->get_one(['id'=>$village_info['property_id']]);
                    if($property_info && !$property_info->isEmpty()){
                        $property_info=$property_info->toArray();
                    }
                    if (!empty($property_info) && !empty($property_info['sms_sign'])){
                        $sms_sign=$property_info['sms_sign'];
                    }
                }
            }
            fdump_api(['sms_sign_1'=>$sms_sign], 'sendSms_0331', true);
            $content = $data['content'] ? $this->_safe_replace($data['content']) : '';
            if (empty($content)) {
                throw new \think\Exception('send content is null');
            }
            $mobile = $data['mobile'] ? : '';
            $nationCode = $data['nationCode'] ?? '';
            if (empty($mobile)) {
                throw new \think\Exception('phone is null');
            }
            $village_send_id = isset($data['village_send_id']) && $data['village_send_id'] ? intval($data['village_send_id']) : 0;

            //语音通知
            $is_voice = isset($data['is_voice']) && $data['is_voice'] ? intval($data['is_voice']) : 0;

            //O2O多个号码以空格分开，取最后一个号码
            $mobileArr = array();
            $phone_array = explode(' ', $mobile);
            foreach ($phone_array as $phone) {
                if ($this->checkmobile($phone)) {
                    $mobileArr[] = $phone;
                }
            }
            if (count($mobileArr) > 1) {
                $mobile = array_pop($mobileArr);
            }

            $data = array(
                'o2o_type' => $type,
                'o2o_sendto' => $sendto,
                'o2o_mer_id' => $mer_id,
                'o2o_store_id' => $store_id,
                'o2o_village_id' => $village_id,
                'o2o_uid' => $uid,
                'village_send_id' => $village_send_id,
                'topdomain' => cfg('sms_server_topdomain'),
                'key' => trim(cfg('sms_key')),
                'token' => $mer_id . 'o2opigcms',
                'content' => $content,
                'mobile' => trim($mobile),
                'sign' =>trim($sms_sign),
                'is_voice' => $is_voice,
                'nationCode' => $nationCode,
            );

            if (empty($data['nationCode'])) {
                $userService = new UserService();
                $merchantService = new MerchantService();
                if (cfg('open_ali_national_sms')) {
                    if ($sendto == 'user' && $uid>0) {
                        $user = $userService->getUser($uid);
                        $data['nationCode'] = isset($user['phone_country_type']) && $user['phone_country_type'] ? $user['phone_country_type'] : cfg('ali_national_sms_default_country');
                    } else if ($sendto == 'merchant') {
                        $mer = $merchantService->getInfo($mer_id);
                        $data['nationCode'] = isset($mer['phone_country_type']) && $mer['phone_country_type'] ? $mer['phone_country_type'] : cfg('ali_national_sms_default_country');
                    }
                } else if (cfg('open_twilio_sms')) {
                    if ($sendto == 'user' && $uid>0) {
                        $user = $userService->getUser($uid);
                        $data['nationCode'] = isset($user['phone_country_type']) && $user['phone_country_type'] ? $user['phone_country_type'] : cfg('twilio_sms_default_country');
                    } else if ($sendto == 'merchant') {
                        $mer = $merchantService->getInfo($mer_id);
                        $data['nationCode'] = isset($mer['phone_country_type']) && $mer['phone_country_type'] ? $mer['phone_country_type'] : cfg('twilio_sms_default_country');
                    }
                } else if (cfg('open_qcloud_sms')) {
                    if ($sendto == 'user' && $uid>0) {
                        $user = $userService->getUser($uid);
                        $data['nationCode'] = isset($user['phone_country_type']) && $user['phone_country_type'] ? $user['phone_country_type'] : cfg('qcloud_sms_default_country');
                    } else if ($sendto == 'merchant') {
                        $mer = $merchantService->getInfo($mer_id);
                        $data['nationCode'] = isset($mer['phone_country_type']) && $mer['phone_country_type'] ? $mer['phone_country_type'] : cfg('qcloud_sms_default_country');
                    }
                }
            }
            fdump_api([$data], 'sendSms_0331', true);
            //if (cfg('sms_send_type')) {
                $param = array(
                    'type' => '1',
                    'content' => $data,
                );
                (new PlanMsgService())->addTask($param);
            //} else {
                //self::sendSmsData($data);
            //}

        }
    }


    /**
     * 安全过滤函数
     * @param $string
     * @return string
     */
    private function _safe_replace($string)
    {
        $string = str_replace('%20', '', $string);  //space
        $string = str_replace('%27', '', $string);  //'
        $string = str_replace('%2527', '', $string); //%'
        $string = str_replace('*', '', $string);
        $string = str_replace('"', '&quot;', $string);
        $string = str_replace("'", '', $string);
        $string = str_replace('"', '', $string);
        $string = str_replace(';', '', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);
        $string = str_replace("{", '', $string);
        $string = str_replace('}', '', $string);
        $string = str_replace('\\', '', $string);
        return $string;
    }

    /**
     * 手机号码验证
     * @param $string
     * @return string
     */
    public function checkmobile($mobilephone)
    {
        $mobilephone = trim($mobilephone);
// 		if (preg_match("/^13[0-9]{1}[0-9]{8}$|15[01236789]{1}[0-9]{8}$|18[01236789]{1}[0-9]{8}$/", $mobilephone)) {
        if (preg_match("/^1[0-9]{10}$/", $mobilephone)) {
            return $mobilephone;
        } else {
            return false;
        }
    }


    /**
     * 配送员3.0登录、重置密码短信验证码
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function sendDeliverCode($from, $phone, $phoneCountryType, $deviceId, $uid = 0, $param = [])
    {
        $code = mt_rand(1000, 9999);
        $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！', ['X1' => $code]);
        $typeName = 'app_deliver_' . $from;
        if ($from == 'login' || $from == 'reset_password') {
            //判断是否存在配送员
            $type = ($from == 'login') ? 31 : 32;
            $deliverUser = (new DeliverUserService())->getOneUser(['phone' => $phone, 'status' => DeliverUser::STATUS_NORMAL]);
            if (empty($deliverUser)) {
                throw new Exception('账号不存在');
            }
        } else if ($from == 'change_phone') {
            //更换手机
            $type = 33;
            $deliverUser = (new DeliverUserService())->getOneUser([['phone', '=', $phone], ['status', '<>', DeliverUser::STATUS_DEL], ['uid', '<>', $uid]]);
            if ($deliverUser) {
                throw new Exception('该手机号已经是配送员账号了，不能重复绑定');
            }
        } else if ($from == 'register') {
            //注册
            $type = 34;
            $deliverUser = (new DeliverUserService())->getOneUser([['phone', '=', $phone], ['status', '<>', DeliverUser::STATUS_DEL]]);
            if ($deliverUser) {
                throw new Exception('该手机号已经是配送员账号了,请更换');
            }
            $registerService = new \app\deliver\model\service\DeliverUserRegisterService();
            if ($registerService->checkPhoneInRegisterApply($phone)) {
                throw new Exception('该手机号已经申请注册,请更换');
            }
        }
        $phone = phone_format($phoneCountryType, $phone);
        $columns = [];
        $columns['phone'] = $phone;
        $columns['text'] = $text;
        $columns['extra'] = $code;
        $columns['device_id'] = $deviceId;
        $columns['type'] = $type;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['send_ip'] = \think\Facade\Request::ip();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = \think\facade\Db::name('app_sms_record')->insert($columns);
        if (!$result) {
            throw new Exception('短信数据写入失败');
        }

        $smsData = [
            'mer_id' => 0,
            'store_id' => 0,
            'content' => $text,
            'mobile' => $phone,
            'uid' => 0,
            'type' => $typeName ?? ''
        ];
        $phoneCountryType && $smsData['nationCode'] = $phoneCountryType;
        $this->sendSms($smsData);
    }

    public function sendLoginSms($param,$from) {
        if ($from == 'property_guide' || !$from) {
            //预约注册物业
            $type = 51;
            $from = 'property_guide';
        }elseif ($from == 'park_temp'){
            //临时车登记
            $type = 52;
            $from = 'park_temp';
        }

        $phone = $param['phone'];
        $code = $param['code'] ?? '';
        $phoneCountryType = $param['phone_country_type'] ?? '';

        if(empty($phone)){
            throw new \think\Exception(L_("请输入手机号!"), 1003);
        }
        if ($phoneCountryType == '' || $phoneCountryType == '86') {
            $regx = '/^1\d{10}$/';
        } else {
            $regx = '/^\d+$/';
        }
        if (!preg_match($regx, $phone)) {
            return api_output(1001, [], '手机号格式有误');
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
        $where = [
            'phone' => $phone,
            'type' => $type
        ];
        $result = \think\facade\Db::name('app_sms_record')->where($where)->find();
        if($result && time() - $result['send_time'] < 60){
            throw new \think\Exception(L_("一分钟内不能多次发送短信"), 1003);
        }

        $code = mt_rand(1000, 9999);
        $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！', ['X1' => $code]);

        $phone = phone_format($phoneCountryType, $phone);
        $columns = [];
        $columns['phone'] = $phone;
        $columns['text'] = $text;
        $columns['extra'] = $code;
        $columns['type'] = $type;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['send_ip'] = \think\Facade\Request::ip();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = \think\facade\Db::name('app_sms_record')->insert($columns);
        if (!$result) {
            throw new Exception('短信数据写入失败');
        }
        $typeName = 'app_deliver_' . $from;

        $smsData = [
            'mer_id' => 0,
            'store_id' => 0,
            'content' => $text,
            'mobile' => $phone,
            'uid' => 0,
            'type' => $typeName ?? ''
        ];
        $phoneCountryType && $smsData['nationCode'] = $phoneCountryType;
        $this->sendSms($smsData);
        return true;
    }


    public function sendTelSms($param,$from) {
        if ($from == 'property_guide' || !$from) {
            //预约注册物业
            $type = 51;
            $from = 'property_guide';
        }elseif ($from == 'park_temp'){
            //临时车登记
            $type = 52;
            $from = 'park_temp';
        }

        $phone = $param['phone'];
        $code = $param['code'] ?? '';
        $phoneCountryType = $param['phone_country_type'] ?? '';

        if(empty($phone)){
            throw new \think\Exception(L_("请输入手机号!"), 1003);
        }
        if ($phoneCountryType == '' || $phoneCountryType == '86') {
            $regx = '/^1\d{10}$/';
        } else {
            $regx = '/^\d+$/';
        }
        if (!preg_match($regx, $phone)) {
            return api_output(1001, [], '手机号格式有误');
        }
        $where = [
            'phone' => $phone,
            'type' => $type
        ];
        $result = \think\facade\Db::name('app_sms_record')->where($where)->find();
        if($result && time() - $result['send_time'] < 60){
            throw new \think\Exception(L_("一分钟内不能多次发送短信"), 1003);
        }

        $code = mt_rand(1000, 9999);
        $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！', ['X1' => $code]);

        $phone = phone_format($phoneCountryType, $phone);
        $columns = [];
        $columns['phone'] = $phone;
        $columns['text'] = $text;
        $columns['extra'] = $code;
        $columns['type'] = $type;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['send_ip'] = \think\Facade\Request::ip();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = \think\facade\Db::name('app_sms_record')->insert($columns);
        if (!$result) {
            throw new Exception('短信数据写入失败');
        }
        $typeName = 'app_deliver_' . $from;

        $smsData = [
            'mer_id' => 0,
            'store_id' => 0,
            'content' => $text,
            'mobile' => $phone,
            'uid' => 0,
            'type' => $typeName ?? ''
        ];
        $phoneCountryType && $smsData['nationCode'] = $phoneCountryType;
        $this->sendSms($smsData);
        return true;
    }


    /**
     * 发送验证码
     * @param $from
     * @param $phone
     * @param $phoneCountryType
     * @param $deviceId
     * @param array $param
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendCode($from, $phone, $phoneCountryType, $deviceId, $param = [])
    {
        $code = mt_rand(1000, 9999);
        $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！', ['X1' => $code]);
        $typeName = 'app_user_' . $from;
        if ($from == WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE_TYPE) {
            // 企业微信登录移动管理绑定手机号
            $type = WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE;
            $bindCode = isset($param['bindCode']) && $param['bindCode'] ? $param['bindCode'] : '';
            $checkInfo = (new WorkWeiXinSuiteService())->getAuthBindInfo($bindCode, $type, $phone, true);
            if (isset($checkInfo['checkExistUser']) && $checkInfo['checkExistUser']) {
                throw new Exception('该手机号已经绑定了其他企微账号,请更换');
            }
            if (isset($checkInfo['checkExist']) && !$checkInfo['checkExist']) {
                throw new Exception('绑定对象不存在');
            }
        }
        $where = [
            'phone' => $phone,
            'type'  => $type
        ];
        $result = \think\facade\Db::name('app_sms_record')->where($where)->find();
        if($result && time() - $result['send_time'] < 60){
            throw new \think\Exception(L_("一分钟内不能多次发送短信"), 1003);
        }
        $phone = phone_format($phoneCountryType, $phone);
        $columns = [];
        $columns['phone'] = $phone;
        $columns['text'] = $text;
        $columns['extra'] = $code;
        $columns['device_id'] = $deviceId;
        $columns['type'] = $type;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['send_ip'] = \think\Facade\Request::ip();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = \think\facade\Db::name('app_sms_record')->insert($columns);
        if (!$result) {
            throw new Exception('短信数据写入失败');
        }
        fdump_api($columns, '$columns');

        $smsData = [
            'mer_id' => 0,
            'store_id' => 0,
            'content' => $text,
            'mobile' => $phone,
            'uid' => 0,
            'type' => $typeName ?? ''
        ];
        $phoneCountryType && $smsData['nationCode'] = $phoneCountryType;
        $this->sendSms($smsData);
    }

    /**
     * 检查验证码
     * @param $phone
     * @param $code
     * @param string $type
     * @param string $from
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkCode($phone, $code, $type = '', $from = '') {
        if ($from == WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE_TYPE) {
            // 企业微信登录移动管理绑定手机号
            $type = WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE;
        }
        if (!$type) {
            throw new Exception(L_('验证码错误'));
        }
        $smsWhere = [
            ['phone',       '=', $phone],
            ['type',        '=', $type],
            ['status',      '=', 0],
            ['extra',       '=', $code],
            ['expire_time', '>', time()],
        ];
        $smsRecord = \think\facade\Db::name('app_sms_record')->where($smsWhere)->find();
        if (empty($smsRecord)) {
            throw new Exception(L_('验证码错误'));
        }
        \think\facade\Db::name('app_sms_record')->where('pigcms_id', $smsRecord['pigcms_id'])->update(['status' => 1]);
        return true;
    }
}