<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityPointRuleValidate extends Validate
{
    protected $rule = [
        'id' => 'integer|gt:0',
        'name' => 'require|length:2,100',
        'sign_point' => 'integer|egt:0',
        'participate_point' => 'integer|egt:0',
        'share_point' => 'integer|egt:0',
        'pay_ratio' => 'float|egt:0',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'name.require' => '积分规则名称不能为空',
        'name.length' => '积分规则名称长度为2-100个字符',
        'sign_point.integer' => '报名积分必须为整数',
        'sign_point.egt' => '报名积分不能小于0',
        'participate_point.integer' => '参与积分必须为整数',
        'participate_point.egt' => '参与积分不能小于0',
        'share_point.integer' => '分享积分必须为整数',
        'share_point.egt' => '分享积分不能小于0',
        'pay_ratio.float' => '支付积分比例必须为数字',
        'pay_ratio.egt' => '支付积分比例不能小于0',
        'status.require' => '状态不能为空',
        'status.in' => '状态值不正确',
    ];

    protected $scene = [
        'add' => ['name', 'sign_point', 'participate_point', 'share_point', 'pay_ratio', 'status'],
        'edit' => ['id', 'name', 'sign_point', 'participate_point', 'share_point', 'pay_ratio', 'status'],
        'status' => ['id', 'status'],
        'delete' => ['id'],
        'detail' => ['id'],
    ];
}
