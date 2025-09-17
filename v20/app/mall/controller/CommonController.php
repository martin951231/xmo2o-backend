<?php

namespace app\mall\controller;

use app\BaseController;
use app\mall\model\service\MallOrderService;

class CommonController extends BaseController
{
    /**
     * 商城订单导出自定义列配置
     *
     * @return void
     * @author: zt
     * @date: 2024/03/04
     */
    public function exportColumns()
    {
        $fields = (new MallOrderService)->exportColumns();
        return api_output(0, $fields, 'success');
    }
}
