<?php
/**
 * 后台用户登录
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */
namespace app\common\controller\platform\user;

use app\common\controller\CommonBaseController;
use app\common\model\service\weixin\RecognitionService as RecognitionService;
use app\common\model\service\weixin\AdminQrcodeService as AdminQrcodeService;
use app\common\model\service\admin_user\AdminUserService;
class LoginController extends CommonBaseController{
    /**
     * desc: 登录接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/5/7 09:23
     */
    public function index(){
 		if(!$this->request->isPost()) {
            return api_output_error(-1, "非法请求");
        }

        $account  = $this->request->param("username", "", "trim");
        $pwd  = $this->request->param("password", "", "trim");
        $verify = $this->request->param("verify",'','trim');
        // $code = input("param.code", 0, "intval");
       
        // 参数校验
        $data = [
            'account' => $account,
            'pwd' => $pwd,
            // 'code' => $code,
        ];

        if (empty($account)) {
            return api_output_error(-1, "请输入账号");
        }

        if (empty($pwd)) {
            return api_output_error(-1, "请输入密码");
        }

        if (empty($verify)) {
            return api_output_error(-1, "请输入验证码");
        }

        if (!captcha_check($verify)) {
            return api_output_error(-1, "验证码不正确，请重新");
        }

        // if (empty($code)) {
        //     return api_output_error(-1, "请输入验证码");
        // }
        $key = 'platform_'.$account.request()->ip();
        $loginFailMessage = function ($message = '')use($key){
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
        $cacheAccount = cache($key);
        if($cacheAccount && $cacheAccount['num'] >= 5){
            throw_exception($loginFailMessage());
        }
        // 登录类型1-账号密码2-扫码
        $data['ltype'] = 1;
        try {
            $result = (new AdminUserService())->login($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $loginFailMessage($e->getMessage()));
        }
        if($result) {
            return api_output(0, $result, "登录成功");
        }
        
        return api_output_error(-1, $loginFailMessage("登录失败"));
    }

    /**
     * desc: 扫码登录获取二维码接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/5/18 11:21
     */
    public function getAdminQrcode(){
        if(!$this->request->isPost()) {
            return api_output_error(-1, "非法请求");
        }
      
        $recognitionService = new RecognitionService();
        try {
            $qrcodeReturn = $recognitionService->getAdminQrcode(); 
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $qrcodeReturn);
    }

    /**
     * desc: 验证是否扫码登录成功
     * return :array
     * Author: henhtingmei
     * Date Time: 2020/5/18 11:21
     */
    public function scanLogin()
	{
        $id = $this->request->param("qrcode_id", "", "intval");

        $adminQrcodeService = new AdminQrcodeService();
        $nowQrcode = $adminQrcodeService->getAdminQrcodeById($id);

		if (!$nowQrcode || empty($nowQrcode['aid'])) {
            return api_output(0, []);
        }

        // 删除记录
        $conditionLoginQrcode['id'] = $id;
        $adminQrcodeService->del($conditionLoginQrcode);

        // 账号id
        $data['id'] = $nowQrcode['aid'];

        // 登录类型1-账号密码2-扫码
        $data['ltype'] = 2;

        try {
            $result = (new AdminUserService())->login($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if($result) {
            return api_output(0, $result, "登录成功");
        }
    }


}