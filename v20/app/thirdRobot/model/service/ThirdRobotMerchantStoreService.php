<?php

declare(strict_types=1);

namespace app\thirdRobot\model\service;

use app\thirdRobot\model\db\ThirdRobotDevice;
use app\thirdRobot\model\db\ThirdRobotMerchantStoreRobot;

class ThirdRobotMerchantStoreService
{
    public static $pageSize = 15;

    public function __construct()
    {
        if (!empty(input('page_size'))) {
            self::$pageSize = min(input('page_size'), 100);
        }
    }

    public function saveStoreRobot(array $params)
    {
        $storeRobot = ThirdRobotMerchantStoreRobot::where([
            'store_id'     => $params['store_id'],
            'robot_code'   => $params['robot_code'],
        ])->find();
        if (empty($storeRobot)) {
            $storeRobot = app(ThirdRobotMerchantStoreRobot::class, [], true);
        }
        $robot = ThirdRobotDevice::where('robot_code', $params['robot_code'])
            ->field('name,id')
            ->find();
        empty($robot) && throw_exception('当前机器人不存在');
        
        $params['robot_name'] = $robot['name'];
        $params['robot_id']   = $robot['id'];

        $storeRobot->setAttrs($params);
        $storeRobot->is_del      = $params['is_del'] ?? ThirdRobotMerchantStoreRobot::IS_DEL_0;
        $storeRobot->delete_time = $params['delete_time'] ?? NULL;

        return $storeRobot->save();
    }
    
    public function storeRobotList($storeId)
    {
        return ThirdRobotMerchantStoreRobot::where([
                'store_id' => $storeId,
                'is_del'   => ThirdRobotMerchantStoreRobot::IS_DEL_0
            ])
            ->with(['map_point' => function($query){
                $query->field('id, point_id, name, map_name, point_name');
            }])
            ->paginate();
    }

}