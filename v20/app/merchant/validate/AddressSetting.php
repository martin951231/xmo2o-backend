<?php
declare (strict_types=1);

namespace app\merchant\validate;

use think\Validate;

class AddressSetting extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'merchant_id'           => 'require',
        'order_id'              => 'require',
        'type'                  => 'require',
        'change_address_id'     => 'require',
        'merchant_allow'        => 'in:1,2',
        'platform_allow'        => 'in:1,2',
        'has_check'             => 'in:1,2',
        'order_status'          => 'in:1,2,3',
        'distribution_distance' => 'egt:0|isNumber',
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [
        'merchant_id.require' => '商家ID不可为空！',
    ];

    /**
     * @var array
     */
    protected $scene = [
        'address_setting_edit'      => ['merchant_allow', 'platform_allow', 'has_check', 'order_status', 'distribution_distance'],
        'address_change_add_record' => ['order_id', 'type', 'change_address_id'],
    ];
    
    protected function isNumber($value)
    {
        return is_numeric($value) ? true : '公里数必须为数字！';
    }    
}