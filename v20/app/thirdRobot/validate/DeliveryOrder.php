<?php
declare (strict_types=1);

namespace app\thirdRobot\validate;

use app\thirdRobot\model\db\ThirdRobotDeliveryOrder;
use app\thirdRobot\model\db\ThirdRobotMapPoints;
use think\Validate;

class DeliveryOrder extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'village_id'              => 'require|number',
        'robot_id'                => 'require|number',
        'order_id'                => 'require|number|checkOrder',
        'uid'                     => 'require|number',
        'start_point_id'          => 'require|number|checkStartPointId',
        'end_point_id'            => 'requireFilled:end_point_id|number|checkEndPointId',
        'robot_code'              => 'require',
        'start_address_name'      => 'require',
        'start_address_user_name' => 'require',
        'start_address_phone'     => 'require',
        'end_address_name'        => 'requireFilled:end_address_name',
        'end_address_user_name'   => 'requireFilled:end_address_user_name',
        'end_address_phone'       => 'requireFilled:end_address_phone',
        'delivery_price'          => 'requireFilled:delivery_price|number',
        'call_price'              => 'requireFilled:call_price',
        'type'                    => 'requireFilled:type',
        'shop_order_id'           => 'require|number',
        'status'                  => 'require|number',

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
        'user_create_order'   => [
            'village_id',
            'robot_id',
            'start_point_id',
            'start_address_name',
            'start_address_user_name',
            'start_address_phone',
            'end_point_id',
            'end_address_name',
            'end_address_user_name',
            'end_address_phone',
        ],
        'user_order_detail' => [
            'order_id',
        ],
        'user_cancel_order' => [
            'order_id',
        ],
        'user_robot_info' => [
            'robot_code',
        ],
        'user_change_robot_status' => [
            'order_id',
            'status'
        ],
        
        'platform_cancel_order'             => [
            'order_id',
            'uid',
        ],
        'platform_set_robot_delivery_price' => [
            'robot_id',
            'delivery_price',
        ]
    ];

    protected function checkOrder($value, $rule, $data = [])
    {
        $order = ThirdRobotDeliveryOrder::where('id', $value)->field('id')->find();

        if (empty($order)) {
            return '当前订单不存在！';
        }
        return true;
    }

    protected function checkStartPointId($value, $rule, $data = [])
    {
        $where = [
            'id'    => $value,
            'delete_time' => 0
        ];
        $point = ThirdRobotMapPoints::where($where)->field('point_id')->find();
        
        if (empty($point)) {
            return '当前开始位置错误！';
        }
        return true;
    }

    protected function checkEndPointId($value, $rule, $data = [])
    {
        $where = [
            'id'    => $value,
            'delete_time' => 0
        ];
        $point = ThirdRobotMapPoints::where($where)->field('point_id')->find();

        if (empty($point)) {
            return '当前目的地位置错误！';
        }
        return true;
    }
    
    protected function requireFilled($value, $rule, $data = [])
    {
        if(array_key_exists($rule, $data)){
            if(!empty($data[$rule]) || $value === 0 || $value === '0'){
                return true;
            }
            return $rule.'不能为空';
        }
        return true;
    }

}
