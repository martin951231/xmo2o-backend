<?php

/**
 * 对外接口-控制器基础类
 * Created by subline.
 * Author: JJC
 * Date Time: 2020/5/27 10:10
 */

namespace app\external\controller\api;

use app\BaseController as BaseController;
use app\common\model\service\UserService as UserService;
use tools\Sign;


class ApiBaseController extends BaseController{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $systemUser;
    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;

    /**
     * 全局配置项
     * @var array
     */
    public $config;

    //对外接口默认秘钥
    const DEFAULT_SECRET = 'b897afbec6f584415ba515aa171ac72f';

    public function initialize()
    {

        parent::initialize();

        $userId= request()->log_uid;//1358679
        if ($userId) {
            // 获得用户信息
            $userService = new UserService();
            $user = $userService->getUser($userId);

            // 用户id
            $this->_uid = $userId;

            // 用户信息
            $this->userInfo = $user;
        }
    }

    public function checkSign($param)
    {
        foreach ($param as $k=>$v){
            if(empty($param[$k])){
                return ['msg'=>$k.'不能为空'];
            }
        }
        $sign = $this->request->post('sign', '', 'trim');
        $Sign = new Sign();
        $re = $Sign->check($param, $sign, self::DEFAULT_SECRET);
        if($re !== true){
            $info = $Sign->getInfo();
            fdump_sql($info, 'api_external_error',1);
            return false;
        }
        return true;
    }
}