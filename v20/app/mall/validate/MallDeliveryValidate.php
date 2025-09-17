<?php
declare (strict_types=1);

namespace app\mall\validate;

use think\Validate;

class MallDeliveryValidate extends Validate
{
    protected $rule = [
        'mer_id'                 => 'require|number',//商家ID
        'store_id'               => 'require|number',//店铺ID
        'page_size'              => 'require|number',
        'delivery_range_type'    => 'require|number|checkPolygon|in:1,2',//配送范围的类型（1:半径距离，2：自定义）
        'custom_id'              => 'require|number',//系统自定义配送范围ID
        'delivery_radius'        => 'require|number',//服务距离，公里
        'delivery_range_polygon' => 'require',//配送自定义范围
    ];

    protected $message = [
        'mer_id.require'                 => '商家ID不可为空！',
        'store_id.require'               => '店铺ID不可为空！',
        'delivery_range_type.require'    => '配送范围的类型不可为空！',
        'delivery_range_type.in'         => '配送范围的类型值错误！',
        'delivery_radius.require'        => '服务距离不可为空！'
    ];

    protected $scene = [
        'platform_merchant_store_list' => ['page_size'],
        'platform_delivery_setting'    => [
            'store_id', 'delivery_range_type', 'custom_id'
        ],
    ];

    protected function checkPolygon($value, $rule, $data=[])
    {
        if($value == 1){
            if(empty($data['delivery_radius'])){
                return '服务距离不可为空！';
            }
        }else if($value == 2){
            if(empty($data['delivery_range_polygon']) && empty($data['custom_id'])){
                return '请绘制您的服务范围！';
            }
        }
       
        return true;
    }
}