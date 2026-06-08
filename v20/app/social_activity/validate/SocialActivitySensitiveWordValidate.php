<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivitySensitiveWordValidate extends Validate
{
    protected $rule = [
        'id' => 'integer|gt:0',
        'word' => 'require|length:1,100',
        'status' => 'require|in:1,2',
        'keyword' => 'length:0,100',
        'status_search' => 'in:0,1,2',
    ];

    protected $message = [
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'word.require' => '敏感词不能为空',
        'word.length' => '敏感词长度不能超过100个字符',
        'status.require' => '状态不能为空',
        'status.in' => '状态值不正确',
        'keyword.length' => '关键词长度不能超过100个字符',
        'status_search.in' => '状态值不正确',
    ];

    protected $scene = [
        'add' => ['word', 'status'],
        'edit' => ['id', 'word', 'status'],
        'delete' => ['id'],
        'list' => ['keyword', 'status_search'],
    ];
}
