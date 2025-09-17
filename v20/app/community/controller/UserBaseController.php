<?php


namespace app\community\controller;


use app\BaseController;
use app\common\model\service\UserService as UserService;

class UserBaseController extends BaseController
{
    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;
    public $userInfo;

    public function initialize()
    {
        parent::initialize();
        $userId= request()->log_uid;
        if ($userId) {
            // 获得用户信息
            $userService = new UserService();
            $user = $userService->getUser($userId);

            // 用户id
            $this->_uid = $userId;
            
            // 用户信息
            $this->userInfo = $user;
        }
    }
}