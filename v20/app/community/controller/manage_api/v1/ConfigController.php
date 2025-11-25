<?php
/**
 * 社区管理APP配置项信息
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/11 14:27
 */

namespace app\community\controller\manage_api\v1;


use app\community\controller\manage_api\BaseController;

class ConfigController extends BaseController
{
    /**
     * 获取配置项
     * @author: wanziyang
     * @date_time: 2020/6/11 14:41
     * @return \json
     */
    public function index()  {
        $arr = [];
        $arr['config'] = [];
        $arr['config']['mobile_head_color']   = preg_match("/^rgb/", cfg('mobile_head_color')) ? rgb_to_hex(cfg('mobile_head_color')) : cfg('mobile_head_color');
        $arr['config']['international_phone'] = cfg('international_phone') ? true : false;
        if(!$arr['config']['mobile_head_color']){
            $arr['config']['mobile_head_color'] = '#06c1ae';
        }
        return api_output(0,$arr);
    }
}