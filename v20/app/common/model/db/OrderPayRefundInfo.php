<?php

namespace app\common\model\db;

use think\Model;

class  OrderPayRefundInfo extends Model
{
    public $json = ['extra_info'];
    public $jsonAssoc = true;
    /*
     *  添加订单支付信息
     *  pay_type 类型如下：
     *  system_coupon_money
        merchant_coupon_money
        merchant_card_money
        ecard_pay_money
        platform_money
        weixin_pay_money
        ali_pay_money
        internal_card_pay_money
     */
    public static function saveInfo($params)
    {
        return self::create($params);
    }
}