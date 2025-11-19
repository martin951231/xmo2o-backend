<?php

/**
 * 控制器基础类
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/04 09:40
 */

namespace app\merchant\controller\api;

use app\BaseController as BaseController;
use app\common\model\service\AreaService;
use app\common\model\service\UserService;
use think\Exception;

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

        // 读取配置缓存
        $cache = cache();
        $config = $cache->get('config');
        if(empty($config)){
            $configService = new \app\common\model\service\ConfigService;
            $all_config = $configService->getConfigData();
            $cache->set('config',$all_config);
            $config = $all_config;
        }

        // 设置当前城市
        $config = (new AreaService())->setNowCity($config);

        // 设置域名
        $config['site_url'] = $this->request->server('REQUEST_SCHEME').'://'.$this->request->server('SERVER_NAME');

        // 设置配置缓存
        $cache->set('config',$config);

    }

    /**
     * 验证登录
     * @var int
     */
    protected function checkLogin()
    {
        $log_uid = request()->log_uid ?? 0;
        if (empty($log_uid)) {
            throw_exception(L_('未登录', Exception::class, 1002));
        }
        
        return true;
    }
}