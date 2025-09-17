<?php
declare (strict_types=1);

namespace app\thirdRobot\validate;

use app\thirdRobot\model\db\ThirdRobotMapPoints;
use think\Validate;

class MerchantStoreRobot extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'store_id'       => 'require|number',
        'robot_code'     => 'require|max:255',
        'map_point_id'   => 'require|number|checkPointId',
        'store_robot_id' => 'require|number',
        'robot_name'     => 'require',
        'shop_order_id'  => 'require|number',
        'end_point_id'   => 'require|number|checkPointId',
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [];

    /**
     * @var array
     */
    protected $scene = [
        'merchant_save_store_robot' => [
            'robot_code',
            'store_id',
            'map_point_id',
        ], 
        'merchant_del_store_robot' => [
            'robot_code',
            'store_id',
            'store_robot_id',
        ],
        'merchant_del_merchant_store_robot' => [
            'robot_code',
            'store_id',
            'store_robot_id',
        ],
        'merchant_merchant_store_robot_list' => [
            'store_id',
        ],
        'staff_store_robot_list' => [
            'store_id',
        ],
        'staff_create_order'   => [
            'robot_code',
            'shop_order_id',
            'end_point_id',
        ],
    ];

    protected function checkPointId($value, $rule, $data = [])
    {
        $where = [
            'id'    => $value,
            'delete_time' => 0
        ];
        $point = ThirdRobotMapPoints::where($where)->field('point_id')->find();

        if (empty($point)) {
            return '地图位置错误！';
        }
        return true;
    }
}
