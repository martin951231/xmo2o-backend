<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityValidate extends Validate
{
    protected $rule = [
        'id' => 'integer|gt:0',
        'name' => 'require|length:1,100',
        'category_id' => 'require|integer|gt:0',
        'start_time' => 'require|date',
        'end_time' => 'require|date',
        'form_id' => 'integer|gt:0',
        'rule_id' => 'integer|gt:0',
        'content' => 'require',
        'notice' => 'require',
        'status' => 'require|integer|in:0,10,20,30',
        'sessions' => 'require|array',
        'poster' => 'require',
        'recommend' => 'require|integer|in:0,1',
    ];

    protected $message = [
        'id.integer' => '活动ID必须为整数',
        'id.gt' => '活动ID必须大于0',
        'name.require' => '活动名称不能为空',
        'name.length' => '活动名称长度为1-100个字符',
        'category_id.require' => '活动分类不能为空',
        'category_id.integer' => '活动分类ID必须为整数',
        'category_id.gt' => '活动分类ID必须大于0',
        'start_time.require' => '活动开始时间不能为空',
        'start_time.date' => '活动开始时间格式不正确',
        'end_time.require' => '活动结束时间不能为空',
        'end_time.date' => '活动结束时间格式不正确',
        'form_id.integer' => '报名表单ID必须为整数',
        'form_id.gt' => '报名表单ID必须大于0',
        'rule_id.integer' => '积分规则ID必须为整数',
        'rule_id.gt' => '积分规则ID必须大于0',
        'poster.array' => '海报必须为数组',
        'position_poster.array' => '位置海报必须为数组',
        'notice.require' => '活动须知不能为空',
        'content.require' => '活动内容不能为空',
        'status.integer' => '状态必须为整数',
        'status.in' => '状态值不正确',
        'sessions.require' => '场次不能为空',
        'sessions.array' => '场次必须为数组',
        'poster.require' => '活动海报不能为空',
        'recommend.require' => '推荐状态不能为空',
        'recommend.integer' => '推荐状态必须为整数',
        'recommend.in' => '推荐状态值不正确',
    ];



    protected $scene = [
        'add' => ['name', 'category_id', 'start_time', 'end_time', 'form_id', 'rule_id', 'content', 'notice', 'sessions', 'poster'],
        'edit' => ['id', 'name', 'category_id', 'start_time', 'end_time', 'form_id', 'rule_id', 'content', 'notice', 'sessions', 'poster'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'status'],
        'audit' => ['id', 'status'],
        'recommend' => ['id', 'recommend'],
    ];
}
