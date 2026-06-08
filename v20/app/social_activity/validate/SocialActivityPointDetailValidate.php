<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityPointDetailValidate extends Validate
{
    protected $rule = [
        'page' => 'integer|gt:0',
        'pageSize' => 'integer|gt:0',
        'name' => 'length:0,50',
        'phone' => 'length:0,20',
        'activity_name' => 'length:0,100',
        'start_time' => 'dateFormat:Y-m-d H:i:s',
        'end_time' => 'dateFormat:Y-m-d H:i:s|egt:start_time',
    ];

    protected $message = [
        'page.integer' => '页码必须为整数',
        'page.gt' => '页码必须大于0',
        'pageSize.integer' => '每页数量必须为整数',
        'pageSize.gt' => '每页数量必须大于0',
        'name.length' => '姓名长度不能超过50个字符',
        'phone.length' => '手机号长度不能超过20个字符',
        'activity_name.length' => '活动名称长度不能超过100个字符',
        'start_time.dateFormat' => '开始时间格式不正确',
        'end_time.dateFormat' => '结束时间格式不正确',
        'end_time.egt' => '结束时间不能小于开始时间',
    ];

    protected $scene = [
        'list' => ['page', 'pageSize', 'name', 'phone', 'activity_name', 'start_time', 'end_time'],
    ];
}
