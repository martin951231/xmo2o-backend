<?php
/**
 * 店员后台用户登录
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/09 09:01
 */
namespace app\storestaff\controller\storestaff\user;

use app\common\controller\CommonBaseController;
use app\merchant\model\service\storestaff\LoginService;
use app\merchant\model\service\weixin\MerchantQrcodeService;
use app\storestaff\model\service\sms\StaffSmsService;
use app\storestaff\model\service\StoreStaffService;
use think\captcha\facade\Captcha;

class LoginController extends CommonBaseController{
    /**
     * desc: 登录接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/5/7 09:23
     */
    public function index(){
 		if(!$this->request->isPost()) {
            return api_output_error(1003, "非法请求");
        }

        $param['username']  = $this->request->param("account", "", "trim");
        $param['password']  = $this->request->param("pwd", "", "trim");
        $param['verify']  = $this->request->param("verify", "", "trim");
        // 登录类型1-账号密码2-扫码
        $param['ltype']  = $this->request->param("ltype", "1", "trim");
        $param['smscode']  = $this->request->param("smscode", "", "trim,string");

        $loginFailMessage = function ($message)use($param){
            $key = 'staff_'.$param['username'];
            $value = [
                'num' => 1,
                'time' => time()
            ];
            $expireTime = 60*30;
            $cacheAccount = cache($key);
            if(empty($cacheAccount)){
                cache($key, $value, $expireTime);
            } else if($cacheAccount['num'] >= 5) {
                $cacheAccount['num'] = 5;
                cache($key, $cacheAccount, $expireTime);
                $message = L_('请使用其他方式登录，或者联系商家管理员！');
            }else if(time() - $cacheAccount['time'] < $expireTime){
                $cacheAccount['num']++;
                cache($key, $cacheAccount, $expireTime);
            }else{
                cache($key, $value, $expireTime);
            }

            return $message;
        };

        try {
            $result = (new LoginService())->login($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $loginFailMessage($e->getMessage()));
        }

        return api_output(0, $result, "登录成功");
    }

    /**
     * desc: 店员app登录接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 14:24
     */
    public function appLogin(){
 		if(!$this->request->isPost()) {
            return api_output_error(1003, "非法请求");
        }

        $param['username']  = $this->request->param("username", "", "trim");
        $param['password']  = $this->request->param("password", "", "trim");
        $param['phone']  = $this->request->param("phone", "", "trim");
        $param['sms_code']  = $this->request->param("sms_code", "", "trim");
        // 登录类型1-账号密码2-手机号
        $param['ltype']  = $this->request->param("ltype", "1", "trim");
        $param['registrationId']  = $this->request->param("registrationId", "", "trim");
        $param['Device-Id']  = $this->request->param("Device-Id", "", "trim");
        $param['client']  = $this->request->param("client", "", "trim");
        $param['app_version']  = $this->request->param("app_version", "", "trim");
        $param['app_version_name']  = $this->request->param("app_version_name", "", "trim");

        try {
            $result = (new LoginService())->appLogin($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result, "登录成功");
    }


    /**
     * desc: 发送短信
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 14:34
     */
    public function sendSms(){
 		if(!$this->request->isPost()) {
            return api_output_error(1003, "非法请求");
        }

        $param  = $this->request->param();
        $result = (new StaffSmsService())->sendLoginSms($param);
        return api_output(0, $result, "发送成功");
    }


    /**
     * desc: 获得下线通知与版本升级
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 17:37
     */
    public function checkLogin(){
        $result = (new StoreStaffService())->checkLogin();
        return api_output(0, $result, "");
    }

    /**
     * desc: 获得下线通知与版本升级
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 17:37
     */
    public function getPrivacyPolicy(){
        $result = (new StoreStaffService())->getPrivacyPolicy();
        return api_output(0, $result, "");
    }

    /**
     * desc: 扫码登录获取二维码接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/07/06 10:58
     */
    public function seeQrcode(){
        $merchantQrcodeService = new MerchantQrcodeService();
        $qrcodeReturn = $merchantQrcodeService->seeLoginQrcode();

        return api_output(0, $qrcodeReturn);
    }

    /**
     * desc: 验证是否扫码登录成功
     * return :array
     * Author: henhtingmei
     * Date Time: 2020/07/06 10:05
     */
    public function scanLogin()
	{
        $param['qrcode_id'] = $this->request->param("qrcode_id", "", "intval");

        try {
            $result = (new LoginService())->weixinLogin($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        if($result) {
            return api_output(0, $result);
        }
    }

    /**
     * desc: 获得图片验证码
     * return :string
     * Author: henhtingmei
     * Date Time: 2020/07/10 11:41
     */
    public function verify()
    {
        return Captcha::create();
    }

}