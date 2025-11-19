<?php

/**
 * 控制器基础类
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/9/2
 * Time: 10:25
 */

namespace app\new_marketing\controller\api;

use app\common\controller\api\ApiBaseController as BaseController;
use app\common\model\service\AreaService;
use app\common\model\service\UserService;

class ApiBaseController extends BaseController{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $userInfo;

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
//        $userId = 1358679;
        if ($userId) {
            // 获得用户信息
            $userService = new UserService();
            $user = $userService->getUser($userId);

            // 用户id
            $this->_uid = $userId;

            // 用户信息
            $this->userInfo = $user;

            request()->user = $user;
        }


        // 读取配置缓存
        $cache = cache();
        $config = $cache->get('config');
        if(empty($config)){
            $configService = new \app\common\model\service\ConfigService;
            $all_config = $configService->getConfigData();
            $cache->set('config',$all_config);
            $config = $all_config;
        }

        // 设置域名
        $config['site_url'] = $this->request->server('REQUEST_SCHEME').'://'.$this->request->server('SERVER_NAME');

        // 设置配置缓存
        $cache->set('config',$config);

    }
}