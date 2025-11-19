<?php

namespace app\complaint\controller\api;

use app\BaseController as BaseController;
use app\common\model\service\UserService as UserService;
use map\longLat;

class ApiBaseController extends BaseController{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $userInfo;

    /**
     * 临时用户信息
     * @var array
     */
    public $userTemp;

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

    public function initialize()
    {

        parent::initialize();

        // 验证登录
        $this->checkLogin();

        $userId = intval($this->request->log_uid);
        // 获得用户信息
        $userService = new UserService();
        $user = $userService->getUser($userId);

        // 用户id
        $this->_uid = $userId;

        // 用户信息
        $this->userInfo = $user;

        // 设置当前用户经纬度
        $lng = $this->request->param('lng');
        $lat = $this->request->param('lat');
        if($lng && $lat){
            $lbsType = 0;
            if ($this->request->param('lbs_type') == 'gcj02') {
                $lbsType = 3;
            } else if ($this->request->param('lbs_type')== 'gps') {
                $lbsType = 1;
            }
            if ($lbsType) {
                $longlat_class = new longLat();
                $location2 = $longlat_class->toBaidu($lat, $lng, $lbsType);
                $lat = $location2['lat'];
                $lng = $location2['lng'];
            }
            request()->lat = $lat;
            request()->lng = $lng;
        }
    }

    /**
     * 验证登录
     * @var int
     */
    private function checkLogin(){
        $log_uid = request()->log_uid ?? 0;
        if(empty($log_uid)){
            throw new \think\Exception("未登录", 1002);
        }
    }
}
