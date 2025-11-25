<?php
declare (strict_types=1);

namespace app\merchant\validate;

use think\Validate;

class MerchantMenu extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'menu_ids' => 'require|isArray',
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [
        'menu_ids.require' => '菜单ID不可为空！',
    ];

    /**
     * @var array
     */
    protected $scene = [
        'custom_menu' => ['menu_ids'],
    ];

    protected function isArray($value)
    {
        return (is_array($value) && !empty($value)) ? true : '菜单参数值错误！';
    }
}