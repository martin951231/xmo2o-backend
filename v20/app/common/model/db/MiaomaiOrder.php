<?php

namespace app\common\model\db;

use think\Model;

class MiaomaiOrder extends Model
{
    public static function saveOrder($params)
    {
        $order = self::where([
            'store_id' => $params['store_id'],
            'order_no' => $params['order_no'],
        ])->find();
        
        if($order){
            $params['order_price'] = $order['order_price'] + $params['pay_money'];
            $order->setAttrs($params);
            $order->save();
        }else{
            $params['order_price'] = $params['pay_money'];
            $order = self::create($params);
        }
        return $order;
    }
}