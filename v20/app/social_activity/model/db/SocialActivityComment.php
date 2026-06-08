<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivityComment extends Model
{
    const STATUS_0 = 0;
    const STATUS_10 = 10;
    const STATUS_20 = 20;

    const STATUS_MAP = [
        self::STATUS_0 => '待审核',
        self::STATUS_10 => '已通过',
        self::STATUS_20 => '已拒绝',
    ];

    public function getCommentById($id)
    {
        return $this->where('id', $id)->find();
    }

    public function getAuditStatusTextAttr($value, $data)
    {
        return self::STATUS_MAP[$data['audit_status']] ?? '未知';
    }
}