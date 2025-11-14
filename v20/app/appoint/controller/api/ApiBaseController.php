<?php

namespace app\appoint\controller\api;

use app\BaseController as BaseController;
use app\common\model\service\ConfigService;
use app\common\model\service\UserService as UserService;
use app\http\exceptions\ParametersException;
use think\helper\Str;


class ApiBaseController extends BaseController{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $systemUser;
    protected $validateClass;
    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;
    public $userInfo;

    /**
     * 全局配置项
     * @var array
     */
    public $config;

    public function initialize()
    {
        parent::initialize();
        $userId = request()->log_uid;
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
            $configService = new ConfigService;
            $all_config = $configService->getConfigData();
            $cache->set('config',$all_config);
            $config = $all_config;
        }
        // 设置域名
        $config['site_url'] = $this->request->server('REQUEST_SCHEME').'://'.$this->request->server('SERVER_NAME');
        // 设置配置缓存
        $cache->set('config',$config);
    }

    public function checkLogin()
    {
        if ($this->_uid < 1) {
            throw_exception(L_('用户未登录'), '\\think\\Exception', 1002);
        }
    }

    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    protected function validateParameter(array $params = [], string $scenario = '', string $method = 'post'): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate($this->validateClass);
        if (!$validate->scene($scenario ?: 'api_'.Str::snake(request()->action()))->check($params)) {
            throw_exception(L_($validate->getError()), ParametersException::class);
        }

        return $params;
    }
}