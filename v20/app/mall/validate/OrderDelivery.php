<?php
declare (strict_types=1);

namespace app\mall\validate;

use think\Validate;

class OrderDelivery extends Validate
{

    protected $rule = [
        'express_id'       => 'require',
        'express_no'       => 'require',
        'order_id'         => 'require',
        'fh_type'          => 'require|in:1,2',//1=发货 2=修改快递
        'express_type'     => 'require|in:1,2',
        'extra_delivery'   => 'require',
    ];

    protected $message = [

    ];

    protected $scene = [
        'check_order_delivery' => [
            'order_id', 'fh_type', 'extra_delivery', 'express_type'
        ],
        'check_extra_delivery' => ['express_id', 'express_no'],
        
        'order_delivery_list' => ['order_id'],
    ];
}