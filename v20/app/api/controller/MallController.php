<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\model\service\building\ParkService;
use app\building\model\ShoppingMall;

//商场
class MallController extends BaseController
{
    //商场列表
    public function getLists()
    {
        $result = ShoppingMall::field('id,name')->select();
        return $this->apiOutput(1000, ['result' => $result], '');
    }
}
