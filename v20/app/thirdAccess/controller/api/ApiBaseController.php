<?php

namespace app\thirdAccess\controller\api;

use app\BaseController;
use app\common\model\service\UserService;

/**
 * 平台用户基类
 * @package app
 */
class ApiBaseController extends BaseController
{
    /**
     * 登录用户信息
     * @var
     */
    public $userInfo = [];

    /**
     * 登录用户ID
     * @var
     */
    public $uid;

    public function initialize()
    {
        parent::initialize();
        $this->uid = intval($this->request->log_uid);
        if ($this->uid > 0) {
            $this->userInfo = (new UserService())->getUser($this->uid);
        }
    }

    /**
     * 检查用户是否登录
     * @author: 张涛
     * @date: 2020/12/19
     */
    public function checkLogin()
    {
        if ($this->uid < 1) {
            throw_exception('用户未登录', '\\think\\Exception', 1002);
        }
    }
}