<?php

namespace app\recruit\controller\api;

use app\BaseController as BaseController;
use app\common\model\service\AreaService as AreaService;
use app\common\model\service\UserService as UserService;
use app\recruit\model\service\JobIntentionService;


class ApiBaseController extends BaseController{
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

    /**
     * 全局配置项
     * @var array
     */
    public $config;

    public $userInfo;

    public function initialize()
    {

        parent::initialize();

        $userId= request()->log_uid;//1358679
        if ($userId) {
            // 获得用户信息
            $userService = new UserService();
            $user = $userService->getUser($userId);

            // 用户id
            $this->_uid = $userId;

            // 用户信息
            $this->userInfo = $user;
            //更新活跃时间
            (new JobIntentionService())->updateLivelyTime($userId);
        }
        
        // 读取配置缓存
        $cache = cache();
        $config = $cache->get('config');


        // 设置域名
        $config['site_url'] = $this->request->server('REQUEST_SCHEME').'://'.$this->request->server('SERVER_NAME');

        // 设置配置缓存
        $cache->set('config',$config);

    }

    public function checkLogin()
    {
        if ($this->request->log_uid < 1) {
            throw_exception(L_('用户未登录'), '\\think\\Exception', 1002);
        }
    }
}