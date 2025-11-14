<?php

namespace app\building\model;

use think\Model;

//商场停车缴费订单
class ShoppingMallParkOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    const STATUS_WAIT = 0;

    const STATUS_PAID = 10;

    const STATUS_REFUND = 20;

    //停车费费用类型
    const COST_TYPE = 'cost_20';


    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_WAIT => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_REFUND => '已退款',
        ];
        return $status[$data['status']] ?? '未知';
    }
}
