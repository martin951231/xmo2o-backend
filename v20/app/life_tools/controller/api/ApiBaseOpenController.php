<?php

/**
 * 对外接口-控制器基础类
 */

namespace app\life_tools\controller\api;

use app\BaseController as BaseController;
use app\common\model\service\AreaService as AreaService;
use app\common\model\service\UserService as UserService;


class ApiBaseOpenController extends BaseController{

    /**
     * 全局配置项
     * @var array
     */
    public $config;

    public function initialize()
    {
        parent::initialize();
        
        // 读取配置缓存
        $cache = cache();
        $config = $cache->get('config');
        if(empty($config)){
            $configService = new \app\common\model\service\ConfigService;
            $all_config = $configService->getConfigData();
            $cache->set('config',$all_config);
            $config = $all_config;
        }
        // 设置域名
        $config['site_url'] = $this->request->server('REQUEST_SCHEME').'://'.$this->request->server('SERVER_NAME');
        // 设置配置缓存
        $cache->set('config',$config);
    }


    protected function success($data = '', $code = 200, $msg = 'success')
    {
        $output = [
            'code'  =>  $code,
            'msg'   =>  $msg,
            'data'  =>  $data
        ];
        return json($output);
    }


    protected function error($msg = 'error', $code = 1000, $data = '')
    {
        $output = [
            'code'  =>  $code,
            'msg'   =>  $msg,
            'data'  =>  $data
        ];
        return json($output);
    }

}