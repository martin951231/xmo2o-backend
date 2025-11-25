<?php
declare (strict_types=1);

namespace app\merchant\validate;

use think\Validate;

class MerchantUserAccount extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'id'       => 'gt:0',
        'account'  => 'require',
        'password' => 'alphaNum|length:8,15',
        'mobile'=>'require|mobile'
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [
        'account.require'  => '账号名不可为空！',
        'password.require' => '密码不可为空！',
        'password.alphaNum' => '密码仅能包含字母、数字！',
        'password.length' => '密码长度8-15位！',
        'mobile.require' => '手机号不可为空！',
        'mobile.mobile' => '手机号格式不正确！',
    ];

    /**
     * @var array
     */
    protected $scene = [
        'user_account_add_or_edit' => ['account'],
        'user_account_delete' => ['id'],
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|gt:0');
    }

    public function sceneAdd()
    {
        return $this->append('password', 'require');
    }

}