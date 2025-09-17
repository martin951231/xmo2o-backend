<?php

namespace app\classify\validate;

use think\Validate;

class ClassifyMenuOrder extends Validate
{
    protected $rule = [
        'menu_detail_id|套餐ID' => 'require|number',
        'order_id|订单ID'       => 'require|number',
        'classify_id|分类'      => 'require|number',
        'name|套餐名称'           => 'require|max:25',
        'price|套餐价格'          => 'require|float|>=:0.01',
        'uid|用户ID'            => 'require|number',
        'expire_time|过期时间'    => 'require',
        'effective_days|有效天数' => 'require|integer|>=:0',
    ];

    protected $message = [
        'menu_detail_id.require' => '套餐ID不能为空',
        'menu_detail_id.number'  => '套餐ID必须为数字',
        'name.require'           => '套餐名称不能为空',
        'name.max'               => '套餐名称最多不能超过25个字符',
        'price.require'          => '套餐价格不能为空',
        'price.float'            => '套餐价格必须为数字',
        'price.egt'              => '套餐价格必须大于等于0.01',
        'uid.require'            => '用户ID不能为空',
        'uid.number'             => '用户ID必须为数字',
        'effective_days.require' => '有效天数不能为空',
        'effective_days.integer' => '有效天数必须为整数',
        'effective_days.egt'     => '有效天数必须大于等于0'
    ];

    protected $scene = [
        'add_order'          => ['menu_detail_id'],
        'verify_user_money'  => ['menu_detail_id'],
        'classify_menu_list' => ['classify_id'],
        'change_expire'      => ['order_id', 'expire_time'],
    ];
}