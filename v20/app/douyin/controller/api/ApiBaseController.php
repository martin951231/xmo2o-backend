<?php
namespace app\douyin\controller\api;

/**
 * 抖音探店
 */ 

use app\BaseController;
use app\common\model\service\UserService;

class ApiBaseController extends BaseController{

     /**
     * 控制器登录用户信息
     * @var array
     */
    public $userInfo;

    /**
     * 临时用户信息
     * @var array
     */
    public $userTemp;

    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;

    /**
     * 全局配置项
     * @var array
     */
    public $config;

    public function initialize()
    {

        parent::initialize();

        $userId = intval($this->request->log_uid);
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

    public function checkLogin()
    {
        if ($this->_uid < 1) {
            throw_exception(L_('未登录'), '\\think\\Exception', 1002);
        }
    }

}