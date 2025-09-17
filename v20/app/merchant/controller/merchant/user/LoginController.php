<?php
/**
 * 后台用户登录
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */
namespace app\merchant\controller\merchant\user;

use app\common\controller\CommonBaseController;
use app\common\model\service\weixin\RecognitionService as RecognitionService;
use app\common\model\service\weixin\AdminQrcodeService as AdminQrcodeService;
use app\merchant\model\service\LoginService;
use app\merchant\model\service\weixin\MerchantQrcodeService;
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

        $param['account']  = $this->request->param("account", "", "trim");
        $param['pwd']  = $this->request->param("pwd", "", "trim");
        $param['verify']  = $this->request->param("verify", "", "trim");
        $param['smscode']  = $this->request->param("smscode", "", "trim");
        // 登录类型1-账号密码2-扫码,3-手机号验证码
        $param['ltype']  = $this->request->param("ltype", "1", "trim");

        $loginFailMessage = function ($message)use($param){
            $key = 'merchant_'.$param['account'];
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
                $message = L_('请使用其他方式登录，或者联系平台管理员！');
            }else if(time() - $cacheAccount['time'] < $expireTime){
                $cacheAccount['num']++;
                cache($key, $cacheAccount, $expireTime);
            }else{
                cache($key, $value, $expireTime);
            }

            return $message;
        };
        
        try {
            if (strpos($param['account'], "@") !== false) {
                //子账号
                $result = (new LoginService())->subAccountLogin($param);
            }else{
                $result = (new LoginService())->login($param);
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $loginFailMessage($e->getMessage()));
        }

        fdump($result,'result',111);
        return api_output(0, $result, "登录成功");
    }

    /**
     * desc: 扫码登录获取二维码接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/07/06 10:58
     */
    public function seeQrcode(){

        $merchantQrcodeService = new MerchantQrcodeService();

        $param['qrcode_type'] = 'merchant';
        $qrcodeReturn = $merchantQrcodeService->seeLoginQrcode($param);
       

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
        $param['longhttp'] = $this->request->param("longhttp", "", "trim");

        try {
            $result = (new LoginService())->weixinLogin($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        if($result) {
            return api_output(0, $result);
        }
    }


}