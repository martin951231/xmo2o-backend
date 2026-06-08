<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityRoleValidate extends Validate
{
    protected $rule = [
        'id' => 'integer|gt:0',
        'name' => 'require|length:2,50',
        'description' => 'length:0,255',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'name.require' => '角色名称不能为空',
        'name.length' => '角色名称长度为2-50个字符',
        'description.length' => '角色描述长度不能超过255个字符',
        'status.require' => '状态不能为空',
        'status.in' => '状态值不正确',
    ];

    protected $scene = [
        'add' => ['name', 'description', 'status'],
        'edit' => ['id', 'name', 'description', 'status'],
        'status' => ['status'],
        'delete' => ['id'],
        'detail' => ['id'],
    ];
}
