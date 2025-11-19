<?php

namespace app\classify\validate;

use think\Validate;

class ClassifyMenu extends Validate
{
    protected $rule = [
        'menu_id'                   => 'require|number',
        'name'                      => 'require|max:25',
        'classify_ids'              => 'require|max:255',
        'classify_detail_list|套餐明细' => 'require',
        'effective_time|过期时间'       => 'require',
        'menu_detail_id'            => 'require|number',
        'price'                     => 'require|float|>=:0.01',
        'effective_days'            => 'require|integer|>=:0',
    ];

    protected $message = [
        'menu_id.require'      => '套餐项目ID不能为空',
        'name.require'         => '套餐项目名称不能为空',
        'name.max'             => '套餐项目最多不能超过25个字符',
        'classify_ids.require' => '分类ID不能为空',
        'classify_ids.max'     => '分类ID最多不能超过255个字符',

        'menu_detail_id.require' => '套餐ID不能为空',
        'price.require'          => '套餐金额不能为空',
        'price.float'            => '套餐金额必须为数字',
        'price.>=:0.01'          => '套餐金额必须大于等于0.01',
        'effective_days.require' => '有效天数不能为空',
        'effective_days.integer' => '有效天数必须为整数',
        'effective_days.>=:0'    => '有效天数必须大于等于0',
    ];

    protected $scene = [
        'save_classify_menu'   => ['name', 'classify_ids', 'classify_detail_list'],
        'del_classify_menu'    => ['menu_id'],
        'classify_detail_list' => ['name', 'price', 'effective_days'],
        'change_expire'        => ['effective_time'],
    ];

}