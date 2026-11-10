<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityPayConfigValidate extends Validate
{
    protected $rule = [
        'village_id' => 'require|integer|gt:0',
        'status' => 'require|in:0,1',
        'app_id' => 'require|length:1,100',
        'merchant_no' => 'require|length:1,100',
        'private_key' => 'require|max:10000',
    ];

    protected $message = [
        'village_id.require' => '小区ID不能为空',
        'village_id.integer' => '小区ID必须为整数',
        'village_id.gt' => '小区ID必须大于0',
        'status.require' => '开启状态不能为空',
        'status.in' => '开启状态值不正确',
        'app_id.require' => 'AppID不能为空',
        'app_id.length' => 'AppID长度不能超过100个字符',
        'merchant_no.require' => '商户号不能为空',
        'merchant_no.length' => '商户号长度不能超过100个字符',
        'private_key.require' => '通联私钥不能为空',
        'private_key.max' => '通联私钥长度不能超过10000个字符',
    ];

    protected $scene = [
        'getConfig' => ['village_id'],
        'saveConfig' => ['village_id', 'status', 'app_id', 'merchant_no', 'private_key'],
    ];
}
