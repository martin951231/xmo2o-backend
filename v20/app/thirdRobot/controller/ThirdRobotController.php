<?php

declare (strict_types=1);

namespace app\thirdRobot\controller;

use app\thirdRobot\model\service\ThirdRobotDeliveryOrderService;

class ThirdRobotController
{
    public function robotDeliveryPrice()
    {
        $robotCode = input('robot_code');
        empty($robotCode) && throw_exception('机器人编码缺失');
        
        $deliveryPrice = app(ThirdRobotDeliveryOrderService::class)->getRobotDeliveryPrice($robotCode);
        
        return json([
            'robot_code' => $robotCode,
            'delivery_price' => $deliveryPrice
        ]);
    }

}

