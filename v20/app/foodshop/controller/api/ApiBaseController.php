<?php

/**
 * 餐饮平台接口-控制器基础类
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 10:10
 */

namespace app\foodshop\controller\api;

use app\BaseController as BaseController;
use app\common\model\service\AreaService as AreaService;
use app\common\model\service\UserService as UserService;

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
        }else{
            // 临时用户信息
            $this->userTemp = [
                'uid' => 0 ,
                'nickname' => $this->request->param('nickname'),
                'avatar' => $this->request->param('avatar'),
                'openid'=>$this->request->param('openid'),
                'wxapp_openid' => $this->request->param('wxapp_openid'),
                'alipay_uid' => $this->request->param('alipay_uid'),
            ];
        }



    }
}