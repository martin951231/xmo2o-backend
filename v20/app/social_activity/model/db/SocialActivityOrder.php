<?php


namespace app\social_activity\model\db;


use think\Model;

class SocialActivityOrder extends Model
{
    const STATUS_0 = 0;
    const STATUS_10 = 10;
    const STATUS_20 = 20;

    const STATUS_MAP = [
        self::STATUS_0 => '未支付',
        self::STATUS_10 => '已支付',
        self::STATUS_20 => '已取消',
    ];
}