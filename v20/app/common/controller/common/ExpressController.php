<?php

namespace app\common\controller\common;

use app\common\controller\CommonBaseController;
use app\mall\model\service\ExpressService;

/**
 * 快递信息
 * @author: 张涛
 * @date: 2020/11/12
 * @package app\common\controller\common
 */
class ExpressController extends CommonBaseController
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 快递列表
     * @author: 张涛
     * @date: 2020/11/12
     */
    public function lists()
    {
        $rs = (new ExpressService())->getExpress();
        return api_output(0, $rs, '成功');
    }

}