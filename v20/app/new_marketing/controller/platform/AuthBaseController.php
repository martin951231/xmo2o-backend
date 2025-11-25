<?php

/**
 * 系统后台管理-控制器基础类
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/6/19 14:15
 */

namespace app\new_marketing\controller\platform;
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