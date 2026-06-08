<?php


namespace app\social_activity\model\db;


use think\Model;

class SocialActivityCommentLike extends Model
{
    const STATUS_1 = 1;
    const STATUS_2 = 2;

    const STATUS_MAP = [
        self::STATUS_1 => '正常',
        self::STATUS_2 => '取消',
    ];
}