<?php

namespace app\social_activity\validate;

use think\Validate;

class SocialActivityCommentValidate extends Validate
{
    protected $rule = [
        'id' => 'integer|gt:0',
        'comment_alias' => 'require|length:1,50',
        'share_audit' => 'require|in:0,1',
        'page' => 'integer|gt:0',
        'pageSize' => 'integer|gt:0',
        'activity_name' => 'length:0,100',
        'session' => 'length:0,100',
        'status' => 'in:0,10,20',
        'audit_status' => 'require|in:1,2',
        'ids' => 'require|array',
    ];

    protected $message = [
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'comment_alias.require' => '评论别名不能为空',
        'comment_alias.length' => '评论别名长度不能超过50个字符',
        'share_audit.require' => '分享审核状态不能为空',
        'share_audit.in' => '分享审核状态值不正确',
        'page.integer' => '页码必须为整数',
        'page.gt' => '页码必须大于0',
        'pageSize.integer' => '每页数量必须为整数',
        'pageSize.gt' => '每页数量必须大于0',
        'activity_name.length' => '活动名称长度不能超过100个字符',
        'session.length' => '活动场次长度不能超过100个字符',
        'status.in' => '状态值不正确',
        'audit_status.require' => '审核状态不能为空',
        'audit_status.in' => '审核状态值不正确',
        'ids.require' => '请选择要操作的评论',
        'ids.array' => '评论ID格式不正确',
    ];

    protected $scene = [
        'saveSettings' => ['comment_alias', 'share_audit'],
        'list' => ['page', 'pageSize', 'activity_name', 'session', 'status'],
        'audit' => ['id', 'audit_status'],
        'batchAudit' => ['ids', 'audit_status'],
        'select' => ['id'],
        'cancelSelect' => ['id'],
    ];
}
