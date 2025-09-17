<?php
/**
 * 商家后台用户管理
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */
namespace app\merchant\controller\merchant\user;
use app\merchant\model\service\MerchantService as MerchantService;
use app\merchant\controller\merchant\AuthBaseController;
class UserController extends AuthBaseController{
    public $merchantService = null;
    public function initialize()
    {
        parent::initialize();
        $this->merchantService = new MerchantService();
    }

   
    /**
     * desc: 返回用户信息
     * return :array
     */
    public function userInfo(){
    	$returnArr = $this->merchantService->formatMerchantUserData($this->merchantUser);
    	if (!$returnArr) {
           	return api_output_error(1002, "商家不存在或未登录");
    	}
        if ($this->subAccountUser) {
            $returnArr['name'] .= '(子账号：' . $this->subAccountUser['account'] . ')';
        }
        return api_output(0, $returnArr);
    }
}