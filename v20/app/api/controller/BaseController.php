<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\api\middleware\Org;
use app\BaseController as AppBaseController;

class BaseController extends AppBaseController
{
    //楼宇appkey鉴权中间件
    protected $middleware = [Org::class];


    public function apiOutput($code = 0, $data = [], $msg = '')
    {
        $output = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];
        return json($output)->code(200);
    }
}
