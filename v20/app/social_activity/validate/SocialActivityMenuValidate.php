<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityMenuValidate extends Validate
{
    protected $rule = [
        'id' => 'integer|gt:0',
        'pid' => 'require|integer|egt:0',
        'name' => 'require|length:2,50|regex:/^[a-zA-Z0-9_:]+$/',
        'title' => 'require|length:2,100',
        'type' => 'require|in:1,2,3',
        'perms' => 'require|length:2,100',
        'sort' => 'integer|egt:0',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'pid.require' => '父级菜单不能为空',
        'pid.integer' => '父级菜单ID必须为整数',
        'pid.egt' => '父级菜单ID不能小于0',
        'name.require' => '菜单标识不能为空',
        'name.length' => '菜单标识长度为2-50个字符',
        'name.regex' => '菜单标识只能包含字母、数字、下划线和冒号',
        'title.require' => '菜单标题不能为空',
        'title.length' => '菜单标题长度为2-100个字符',
        'type.require' => '菜单类型不能为空',
        'type.in' => '菜单类型不正确',
        'perms.require' => '权限标识不能为空',
        'perms.length' => '权限标识长度为2-100个字符',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序不能小于0',
        'status.require' => '状态不能为空',
        'status.in' => '状态值不正确',
    ];

    protected $scene = [
        'add' => ['pid', 'name', 'title', 'type', 'perms', 'sort', 'status'],
        'edit' => ['id', 'pid', 'name', 'title', 'type', 'perms', 'sort', 'status'],
        'status' => ['status'],
        'delete' => ['id'],
        'detail' => ['id'],
    ];
}
