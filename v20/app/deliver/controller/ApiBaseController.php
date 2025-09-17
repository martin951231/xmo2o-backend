<?php

namespace app\deliver\controller;


use app\BaseController;
use app\common\controller\CommonBaseController;
use app\deliver\model\service\DeliverUserService;
use app\common\model\service\AreaService as AreaService;
use token\Token;

class ApiBaseController extends CommonBaseController
{
    public $deviceId;

    public function initialize()
    {
        parent::initialize();
        $this->deviceId = $this->request->param('Device-Id', '', 'trim');

        // 如果配送员登录且使用谷歌地图，判断设置时区
        if($this->request->log_uid && cfg('google_map_ak')){
            $deliverUser = (new DeliverUserService())->getOneUser(['uid' => $this->request->log_uid]);
            if($deliverUser){
                $areaInfo = [];
                $cityInfo = [];
                if (!empty($deliverUser['area_id']))
                {
                    $areaInfo = (new AreaService())->getAreaByAreaId($deliverUser['area_id']);
                }
                
                //如果区域有，以区域为准，不查城市级别
                if (empty($areaInfo['timezone']) && !empty($deliverUser['city_id']))
                {
                    $cityInfo = (new AreaService())->getAreaByAreaId($deliverUser['city_id']);
                }
    
                //area的时区优先于城市的时区
                if (!empty($areaInfo['timezone']) || !empty($cityInfo['timezone']))
                {
                    date_default_timezone_set($areaInfo['timezone'] ? $areaInfo['timezone'] : $cityInfo['timezone']);
                }
            }
        }
    }

    public function checkLogin()
    {
        if ($this->request->log_uid < 1) {
            throw_exception(L_('配送员未登录'), '\\think\\Exception', 1002);
        }
    }
}
