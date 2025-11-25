<?php

/**
 * 系统后台管理-控制器基础类
 */
namespace app\wechatLive\controller\platform;

use app\common\controller\platform\AuthBaseController as BaseController;
class AuthBaseController extends BaseController{

    
    public function checkLogin(){
        if(!$this->systemUser){
            throw_exception('未登录！');
        }
        return true;
    }
}