<?php

/**
 * 控制器基础类
 * Author: wangchen
 * Date Time: 2021/6/22
 */

namespace app\recruit\controller\merchant;

use app\common\controller\CommonBaseController;
use app\merchant\model\service\MerchantService as MerchantService;

class ApiBaseController extends CommonBaseController
{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $merchantUser;

    /**
     * 控制器登录用户uid
     * @var int
     */
    public $merId;

    public function initialize()
    {
        parent::initialize();

        // 验证登录
        $this->checkLogin();

        $merId = intval($this->request->log_uid);

//        $merId = 1;
        // 获得用户信息
        $merchantService = new MerchantService();
        $merchant = $merchantService->getMerchantByMerId($merId);

        // 用户id
        $this->merId = $merId;

        // 用户信息
        $this->merchantUser = $merchant;
    }

    /**
     * 验证登录
     * @var int
     */
    private function checkLogin(){
        $log_uid = request()->log_uid ?? 0;
        if(empty($log_uid)){
            throw new \think\Exception("未登录", 1002);
        }
    }
}