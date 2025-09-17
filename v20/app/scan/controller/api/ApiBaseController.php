<?php

/**
 * 商城模块接口-控制器基础类
 * Created by subline.
 * Author: JJC
 * Date Time: 2020/5/27 10:10
 */

namespace app\scan\controller\api;

use app\BaseController as BaseController;
use app\common\model\service\UserService as UserService;
use think\facade\Cache;


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
        }else{
            // 临时用户信息
            $this->userTemp = [
                'uid' => 1358679 ,
                'nickname' => '黑',
                'avatar' => 'https://xxxxxx',
                'openid'=>'opwwWxLvcPZvoEMadt1MA7SCSYj8',
                'wxapp_openid' => '',
                'alipay_uid' => '',
            ];
        }
        
        // 读取配置缓存
        $config = Cache::get('config');


        // 设置域名
        $config['site_url'] = $this->request->server('REQUEST_SCHEME').'://'.$this->request->server('SERVER_NAME');

        // 设置配置缓存
        Cache::set('config', $config);

    }
}