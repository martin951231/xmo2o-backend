<?php

/**
 * 控制器基础类
 * Author: hengtingmei
 * Date Time: 2022/01/06
 */

namespace app\banking\controller\api;

use app\BaseController as BaseController;
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
        // 获得用户信息
        $userService = new UserService();
        $user = $userService->getUser($userId);

        // 用户id
        $this->_uid = $userId;

        // 用户信息
        $this->userInfo = $user;
    }
}