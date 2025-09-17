<?php

/**
 * 系统后台管理-控制器基础类
 * Author: hengtingmei
 * Date Time: 2022/01/06 13:50
 */

namespace app\banking\controller\platform;
use app\common\controller\platform\AuthBaseController as BaseController;
class AuthBaseController extends BaseController{
    
    public function initialize()
    {

        parent::initialize();

    }

    public function checkLogin(){
        if(!$this->systemUser){
            return false;
        }
        return true;
    }
}