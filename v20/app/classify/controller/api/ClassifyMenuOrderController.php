<?php

namespace app\classify\controller\api;

use app\classify\model\service\ClassifyMenu as ClassifyMenuService;
use app\classify\validate\ClassifyMenuOrder as ClassifyMenuOrderValidate;
use app\classify\model\service\ClassifyMenuOrder as ClassifyMenuOrderService;
use app\community\model\service\HouseVillageUserBindService;

class ClassifyMenuOrderController extends ClassifyBaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->classifyValidate = ClassifyMenuOrderValidate::class;
    }

    public function verifyUserMoney()
    {
        $params = $this->validateParameter();
        $params['uid'] = $this->request->log_uid;

        $data = app(ClassifyMenuOrderService::class)->verifyUserMoney($params);

        return api_output(self::STATUS_OK, $data);
    }
    
    public function addOrder()
    {
        $params = $this->validateParameter();
        $params['uid'] = $this->request->log_uid;
            
        $data = app(ClassifyMenuOrderService::class)->addOrder($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function orderList()
    {
        $params = input('post.');
        $params['uid'] = $this->request->log_uid;
        
        $data = app(ClassifyMenuOrderService::class)->orderList($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function classifyMenuList()
    {
        $params = $this->validateParameter();
        $data = app(ClassifyMenuService::class)->classifyMenuList($params);
        return api_output(self::STATUS_OK, $data);
    }
    /**
     * 
     * getUserRoomInfoByUid($uid,$xtype,$bind_user_id);
     * $uid 客户身份的uid ，
     * $xtype值 1 业主自己的房子 ，2 自己是家人亲属身份入住的房子 ，3 自己是租客租住的房子 ，0所有的这个人的房子，
     * $bind_user_id是客户房子身份id （用户获取单个房子信息）
     * @return \think\response\Json
     */
    public function userRoomInfo()
    {
        $uid = $this->request->log_uid;
        $userHouseBindId = input('bind_user_id',0);
        $data = app(HouseVillageUserBindService::class)->getUserRoomInfoByUid($uid,1,$userHouseBindId);

        return api_output(self::STATUS_OK, $data);
    }
    
}