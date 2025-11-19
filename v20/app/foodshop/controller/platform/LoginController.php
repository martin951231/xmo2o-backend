<?php
/**
 * 平台自动登录商家后台
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:46
 */

namespace app\foodshop\controller\platform;
use app\foodshop\controller\platform\AuthBaseController;
use app\merchant\model\service\LoginService;
use app\merchant\model\service\storestaff\LoginService as StaffLoginService;
class LoginController extends AuthBaseController
{
    
    /**
     * 登陆商家后台
     */
    public function merchantAutoLogin()
    {

        $loginService = new LoginService();

        // 商家id
        $param['mer_id'] = $this->request->param("mer_id", "0", "intval");

        try {
            $result = $loginService->autoLogin($param, $this->systemUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        
        return api_output(0, $result);
    }

    /**
     * 登陆店员后台
     */
    public function staffAutoLogin()
    {

        $loginService = new StaffLoginService();

        // 店铺id
        $param['store_id'] = $this->request->param("store_id", "0", "intval");

        try {
            $result = $loginService->autoLogin($param, $this->systemUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }

}
