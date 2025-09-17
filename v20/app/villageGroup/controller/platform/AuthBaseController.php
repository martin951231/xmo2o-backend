<?php

declare(strict_types=1);
/**
 * 系统后台管理-控制器基础类
 */

namespace app\villageGroup\controller\platform;

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