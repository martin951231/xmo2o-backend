<?php
/**
 * 判断用户登录
 */
namespace app\scan\controller\platform;

use app\common\controller\CommonBaseController;
use app\common\model\service\admin_user\AdminUserService as AdminUserService;
class AuthBaseController extends CommonBaseController{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $systemUser;

    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;

    public function initialize()
    {
        parent::initialize();

        // 验证登录
        $this->checkLogin();

        $userId = intval($this->request->log_uid);
        // 获得用户信息
        $userService = new AdminUserService();
        $user = $userService->getUserRole($userId);

        // 用户id
        $this->_uid = $userId;

        // 用户信息
        $this->systemUser = $user;
    }

    /**
     * 验证登录
     * @var int
     */
    private function checkLogin(){
    	$log_uid = request()->log_uid ?? 0;
    	if(empty($log_uid)){
    		throw new \think\Exception("未登录", 1002);    		
    	}
    }
}