<?php

declare(strict_types=1);

namespace app\storestaff\controller\appapi;

use app\common\controller\CommonBaseController;
use think\Request;

class ApiBaseController extends CommonBaseController
{

    public function initialize()
    {
        parent::initialize();
    }

    public function checkLogin()
    {
        if ($this->request->log_uid < 1) {
            throw_exception('店员未登录', '\\think\\Exception', 1002);
        }
    }
}
