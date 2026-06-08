<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityOrderValidate extends Validate
{
    protected $rule = [
        'page' => 'integer|gt:0',
        'pageSize' => 'integer|gt:0',
        'cat_id' => 'integer',
        'keywords' => 'max:255',
        'session_name' => 'max:255',
        'nickname' => 'max:255',
        'start_time' => 'dateFormat:Y-m-d',
        'end_time' => 'dateFormat:Y-m-d',
        'amount' => 'require|float|gt:0',
        'realname' => 'require|max:255',
        'remark' => 'require|max:255',
        'audit_status' => 'integer|egt:-1',
        'is_export' => 'integer|in:0,1'
    ];

    protected $message = [
        'page.integer' => '页码必须为整数',
        'page.gt' => '页码必须大于0',
        'pageSize.integer' => '每页数量必须为整数',
        'pageSize.gt' => '每页数量必须大于0',
        'cat_id.integer' => '分类ID必须为整数',
        'keywords.max' => '关键词长度不能超过255',
        'session_name.max' => '场次名称长度不能超过255',
        'nickname.max' => '用户昵称长度不能超过255',
        'start_time.dateFormat' => '开始时间格式不正确',
        'end_time.dateFormat' => '结束时间格式不正确',
        'amount.require' => '提现金额不能为空',
        'amount.float' => '提现金额必须为数字',
        'amount.gt' => '提现金额必须大于0',
        'realname.require' => '提现人姓名不能为空',
        'realname.max' => '提现人姓名长度不能超过255',
        'remark.require' => '提现备注不能为空',
        'remark.max' => '提现备注长度不能超过255',
        'audit_status.integer' => '审核状态必须为整数',
        'audit_status.egt' => '审核状态值不正确',
        'is_export.in' => '导出参数不正确'
    ];

    protected $scene = [
        'list' => ['page', 'pageSize', 'cat_id', 'keywords', 'session_name', 'nickname', 'start_time', 'end_time', 'is_export'],
        'withdraw' => ['amount', 'realname', 'remark'],
        'record' => ['page', 'pageSize', 'audit_status', 'start_time', 'end_time']
    ];
}
