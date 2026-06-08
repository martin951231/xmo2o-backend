<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityAdminValidate extends Validate
{
    protected $rule = [
        'id' => 'integer|gt:0',
        'account' => 'require|length:2,50|regex:/^[a-zA-Z0-9_]+$/',
        'password' => 'length:6,32',
        'realname' => 'require|length:2,50',
        'phone' => 'require|regex:/^1[3-9]\d{9}$/',
        'role_id' => 'require|integer|gt:0',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'account.require' => '账号不能为空',
        'account.length' => '账号长度为2-50个字符',
        'account.regex' => '账号只能包含字母、数字和下划线',
        'password.length' => '密码长度为6-32个字符',
        'realname.require' => '真实姓名不能为空',
        'realname.length' => '真实姓名长度为2-50个字符',
        'phone.require' => '联系电话不能为空',
        'phone.regex' => '请输入正确的手机号码',
        'role_id.require' => '角色不能为空',
        'role_id.integer' => '角色ID必须为整数',
        'role_id.gt' => '请选择有效的角色',
        'status.require' => '状态不能为空',
        'status.in' => '状态值不正确',
    ];

    protected $scene = [
        'add' => ['account', 'password', 'realname', 'phone', 'role_id', 'status'],
        'edit' => ['id', 'account', 'password', 'realname', 'phone', 'role_id', 'status'],
        'status' => ['status'],
        'login' => ['account', 'password'],
        'delete' => ['id'],
        'detail' => ['id'],
    ];
}
