<?php

/**
 * 公共项目管理-控制器基础类
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/01 10:06
 */

namespace app\common\controller;

use app\BaseController;
use app\common\model\service\ConfigService;

class CommonBaseController extends BaseController{
    /**
     * 全局配置项
     * @var array
     */
    public $config;

    public function initialize()
    {

        parent::initialize();
        $this->config = (new ConfigService())->getConfigData();
    }

    //检查http referer
    public static function checkReferer()
    {
        $referer = request()->server('HTTP_REFERER');
        if (!$referer) {
            return false;
        }
        $refererHost = parse_url($referer, PHP_URL_HOST);
        $currentHost = request()->host();
        return $refererHost == $currentHost;
    }
}